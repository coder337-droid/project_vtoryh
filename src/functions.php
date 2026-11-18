<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function db(): PDO
{
    return Database::getConnection();
}

/* ---------------- CSRF и сессии ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check($token): void
{
    $token = is_string($token) ? $token : '';
    if ($token === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $token)) {
        http_response_code(403);
        exit('CSRF-токен недействителен. Обновите страницу и повторите.');
    }
}

function auth_kill_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Каждый запрос сверяется с БД.
 * Удалён / отключён / разжалован / сменил пароль — доступ теряется сразу.
 */
function require_login(): array
{
    if (empty($_SESSION['user']['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $row = false;
    try {
        $st = db()->prepare(
            "SELECT u.user_id, u.login, u.first_name, u.middle_name, u.last_name,
                    u.email, u.is_active, u.password_hash, r.code AS role
               FROM users u JOIN roles r ON r.role_id = u.role_id
              WHERE u.user_id = ?"
        );
        $st->execute([(int)$_SESSION['user']['user_id']]);
        $row = $st->fetch();
    } catch (Throwable $e) {
        error_log('require_login: ' . $e->getMessage());
    }

    $fp = substr((string)($row['password_hash'] ?? ''), -12);
    if (!$row || (int)$row['is_active'] !== 1
        || !hash_equals((string)($_SESSION['auth_fp'] ?? ''), $fp)) {
        auth_kill_session();
        header('Location: login.php');
        exit;
    }

    $_SESSION['user'] = [
        'user_id'     => (int)$row['user_id'],
        'login'       => (string)$row['login'],
        'first_name'  => (string)$row['first_name'],
        'middle_name' => (string)($row['middle_name'] ?? ''),
        'last_name'   => (string)$row['last_name'],
        'email'       => (string)($row['email'] ?? ''),
        'role'        => (string)$row['role'],
    ];
    return $_SESSION['user'];
}

function require_admin(): array
{
    $user = require_login();
    if (($user['role'] ?? '') !== 'admin') {
        header('Location: settings.php');
        exit;
    }
    return $user;
}

function is_admin(): bool
{
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

/* ---------------- Пазл ---------------- */

function puzzle_generate(): array
{
    $pieces = range(0, 3);
    do {
        shuffle($pieces);
    } while ($pieces === range(0, 3));
    $_SESSION['puzzle_order']  = $pieces;
    $_SESSION['puzzle_solved'] = false;
    return $pieces;
}

function puzzle_check(array $order): bool
{
    return $order === range(0, 3);
}

/* ---------------- Старая сессионная блокировка (оставлена для совместимости) ---------------- */

const MAX_ATTEMPTS = 3;
const LOCKOUT_MIN  = 30;

function login_is_locked(): bool
{
    return (int)($_SESSION['locked_until'] ?? 0) > time();
}

function login_lock_remaining(): int
{
    $left = (int)($_SESSION['locked_until'] ?? 0) - time();
    return $left > 0 ? (int)ceil($left / 60) : 0;
}

function login_register_failure(): void
{
    $_SESSION['failed_attempts'] = (int)($_SESSION['failed_attempts'] ?? 0) + 1;
    if ($_SESSION['failed_attempts'] >= MAX_ATTEMPTS) {
        $_SESSION['locked_until'] = time() + LOCKOUT_MIN * 60;
    }
}

function login_reset_attempts(): void
{
    unset($_SESSION['failed_attempts'], $_SESSION['locked_until']);
}

/* ---------------- Профиль и пароль ---------------- */

function handle_profile_update(PDO $pdo, array $user): array
{
    $first_name  = trim((string)($_POST['first_name'] ?? ''));
    $middle_name = trim((string)($_POST['middle_name'] ?? ''));
    $last_name   = trim((string)($_POST['last_name'] ?? ''));
    $email       = trim((string)($_POST['email'] ?? ''));

    if ($last_name === '' || $first_name === '') {
        return ['', 'Фамилия и Имя обязательны'];
    }

    try {
        $pdo->prepare(
            "UPDATE users
                SET first_name=:fn, middle_name=:mn, last_name=:ln, email=:em
              WHERE user_id=:id"
        )->execute([
            ':fn' => $first_name, ':mn' => $middle_name,
            ':ln' => $last_name, ':em' => $email,
            ':id' => (int)$user['user_id'],
        ]);
    } catch (Throwable $e) {
        error_log('profile_update: ' . $e->getMessage());
        return ['', 'Не удалось сохранить профиль: возможно, email уже занят'];
    }

    $_SESSION['user']['first_name']  = $first_name;
    $_SESSION['user']['middle_name'] = $middle_name;
    $_SESSION['user']['last_name']   = $last_name;
    $_SESSION['user']['email']       = $email;

    return ['Профиль сохранён', ''];
}

function handle_password_change(PDO $pdo, array $user): array
{
    $current = (string)($_POST['current_password'] ?? '');
    $new     = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    $st = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $st->execute([(int)$user['user_id']]);
    $hash = (string)$st->fetchColumn();

    // Бэекдор «хеш как пароль» удалён.
    if ($hash === '' || !password_verify($current, $hash)) {
        return ['', 'Текущий пароль неверен'];
    }
    if (strlen($new) < 8)             return ['', 'Новый пароль должен быть не короче 8 символов'];
    if ($new !== $confirm)            return ['', 'Новые пароли не совпадают'];
    if (password_verify($new, $hash)) return ['', 'Новый пароль совпадает с текущим'];

    $newHash = password_hash($new, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
            ->execute([$newHash, (int)$user['user_id']]);
        $pdo->prepare("DELETE FROM sessions WHERE user_id = ? AND session_id <> ?")
            ->execute([(int)$user['user_id'], session_id()]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('password_change: ' . $e->getMessage());
        return ['', 'Не удалось изменить пароль'];
    }

    $_SESSION['auth_fp'] = substr($newHash, -12);
    return ['Пароль изменён. Остальные устройства вышли из системы.', ''];
}

/* ---------------- Пользователи ---------------- */

function export_users_json(PDO $pdo): void
{
    $stmt = $pdo->query(
        "SELECT u.user_id, u.login, u.email, u.first_name, u.middle_name, u.last_name,
                r.code AS role, u.is_active, u.last_login, u.created_at
           FROM users u
           JOIN roles r ON r.role_id = u.role_id
          ORDER BY u.user_id"
    );
    $rows = $stmt->fetchAll();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_' . date('Y-m-d_H-i') . '.json"');
    header('Cache-Control: no-store, private');
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handle_user_delete(PDO $pdo, array $currentUser): array
{
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id <= 0) return ['', 'Не указан пользователь'];
    if ($id === (int)$currentUser['user_id']) return ['', 'Нельзя удалить самого себя'];

    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("UPDATE login_log SET user_id = NULL WHERE user_id = ?")->execute([$id]);
        $st = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $st->execute([$id]);
        if ($st->rowCount() === 0) {
            $pdo->rollBack();
            return ['', 'Пользователь не найден'];
        }
        $pdo->commit();
        return ['Пользователь удалён', ''];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('user_delete: ' . $e->getMessage());
        return ['', 'Нельзя удалить: у пользователя есть связанные записи (заметки). Сначала удалите их.'];
    }
}

function handle_user_update(PDO $pdo, array $currentUser): array
{
    $id         = (int)($_POST['user_id'] ?? 0);
    $login      = strtolower(trim((string)($_POST['login'] ?? '')));
    $first_name = trim((string)($_POST['first_name'] ?? ''));
    $middle     = trim((string)($_POST['middle_name'] ?? ''));
    $last_name  = trim((string)($_POST['last_name'] ?? ''));
    $email      = trim((string)($_POST['email'] ?? ''));
    $roleCode   = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $is_active  = isset($_POST['is_active']) ? 1 : 0;
    $new_pass   = (string)($_POST['password'] ?? '');

    if ($id <= 0) return ['', 'Не указан пользователь'];
    if ($login === '' || $last_name === '' || $first_name === '') {
        return ['', 'Логин, Фамилия и Имя обязательны'];
    }

    try {
        $rs = $pdo->prepare("SELECT role_id FROM roles WHERE code = ?");
        $rs->execute([$roleCode]);
        $rid = (int)$rs->fetchColumn();
        if (!$rid) return ['', 'Роль не найдена в таблице roles'];

        $sql = "UPDATE users
                   SET login=:login, first_name=:fn, middle_name=:mn,
                       last_name=:ln, email=:email, role_id=:rid, is_active=:act";
        $params = [
            ':login' => $login, ':fn' => $first_name, ':mn' => $middle,
            ':ln' => $last_name, ':email' => $email, ':rid' => $rid,
            ':act' => $is_active, ':id' => $id,
        ];
        if ($new_pass !== '') {
            if (strlen($new_pass) < 8) return ['', 'Пароль должен быть не короче 8 символов'];
            $sql .= ", password_hash=:pass";
            $params[':pass'] = password_hash($new_pass, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE user_id=:id";

        $pdo->beginTransaction();
        $pdo->prepare($sql)->execute($params);
        if ($new_pass !== '' || $is_active === 0) {
            $keep = ($id === (int)$currentUser['user_id']) ? session_id() : '';
            $pdo->prepare("DELETE FROM sessions WHERE user_id = ? AND session_id <> ?")
                ->execute([$id, $keep]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('user_update: ' . $e->getMessage());
        if ($e instanceof PDOException && $e->getCode() === '45000') {
            return ['', $e->getMessage()];
        }
        return ['', 'Не удалось сохранить: возможно, логин или email уже занят'];
    }

    if ($id === (int)$currentUser['user_id']) {
        $_SESSION['user']['login']      = $login;
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['last_name']  = $last_name;
        $_SESSION['user']['email']      = $email;
        $_SESSION['user']['role']       = $roleCode;

        if ($new_pass !== '') {
            $st = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $st->execute([$id]);
            $_SESSION['auth_fp'] = substr((string)$st->fetchColumn(), -12);
        }
    }

    return ['Данные сохранены', ''];
}

function handle_user_create(PDO $pdo): array
{
    $login      = strtolower(trim((string)($_POST['login'] ?? '')));
    $first_name = trim((string)($_POST['first_name'] ?? ''));
    $last_name  = trim((string)($_POST['last_name'] ?? ''));
    $email      = trim((string)($_POST['email'] ?? ''));
    $roleCode   = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $password   = (string)($_POST['password'] ?? '');

    if ($login === '' || $password === '' || $last_name === '') {
        return ['', 'Логин, Фамилия и Пароль обязательны'];
    }

    $ridStmt = $pdo->prepare("SELECT role_id FROM roles WHERE code = ?");
    $ridStmt->execute([$roleCode]);
    $rid = (int)$ridStmt->fetchColumn();
    if (!$rid) return ['', 'Роль не найдена в таблице roles'];

    try {
        $pdo->prepare(
            "INSERT INTO users (login, first_name, last_name, email, password_hash, role_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)"
        )->execute([
            $login, $first_name, $last_name, $email,
            password_hash($password, PASSWORD_DEFAULT), $rid,
        ]);
        return ['Пользователь добавлен', ''];
    } catch (PDOException $e) {
        error_log('user_create: ' . $e->getMessage());
        if ($e->getCode() === '45000') return ['', $e->getMessage()];
        return ['', 'Ошибка: такой логин или email уже существует'];
    }
}

function handle_users_import(PDO $pdo): array
{
    if (empty($_FILES['json_file']['tmp_name'])) {
        return ['', 'Файл не загружен'];
    }
    $content = file_get_contents($_FILES['json_file']['tmp_name']);
    $data    = json_decode((string)$content, true);
    if (!is_array($data)) {
        return ['', 'Неверный формат JSON'];
    }

    $imported = 0;
    $skipped  = 0;

    foreach ($data as $row) {
        $login       = strtolower(trim((string)($row['login'] ?? '')));
        $first_name  = trim((string)($row['first_name'] ?? ''));
        $middle_name = trim((string)($row['middle_name'] ?? ''));
        $last_name   = trim((string)($row['last_name'] ?? ''));
        $email       = trim((string)($row['email'] ?? ''));
        $roleCode    = ($row['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $password    = (string)($row['password'] ?? 'changeme');

        if ($login === '' || $last_name === '') {
            $skipped++;
            continue;
        }

        $ridStmt = $pdo->prepare("SELECT role_id FROM roles WHERE code = ?");
        $ridStmt->execute([$roleCode]);
        $rid = (int)$ridStmt->fetchColumn();
        if (!$rid) {
            $skipped++;
            continue;
        }

        try {
            $pdo->prepare(
                "INSERT INTO users (login, first_name, middle_name, last_name,
                                    email, password_hash, role_id, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
            )->execute([
                $login, $first_name, $middle_name, $last_name, $email,
                password_hash($password, PASSWORD_DEFAULT), $rid,
            ]);
            $imported++;
        } catch (PDOException $e) {
            $skipped++;
        }
    }

    return ["Импортировано: $imported. Пропущено (дубликаты/ошибки): $skipped.", ''];
}

/* ---------------- Экспорт / импорт JSON ---------------- */

function export_full_backup(PDO $pdo): void
{
    $data = [
        'exported_at' => date('Y-m-d H:i:s'),
        'app'         => 'project_vtoryh',
        'tables'      => [
            'roles'     => $pdo->query(
                "SELECT role_id, code, name, description FROM roles ORDER BY role_id"
            )->fetchAll(),
            'users'     => $pdo->query(
                "SELECT user_id, login, email, first_name, middle_name, last_name,
                        phone, avatar, role_id, is_active, is_verified,
                        last_login, created_at, updated_at
                   FROM users ORDER BY user_id"
            )->fetchAll(),
            'login_log' => $pdo->query(
                "SELECT log_id, user_id, login, ip, user_agent, success, created_at
                   FROM login_log ORDER BY log_id DESC LIMIT 1000"
            )->fetchAll(),
            'sessions'  => $pdo->query(
                "SELECT session_id, user_id, ip, user_agent, created_at, expires_at
                   FROM sessions ORDER BY created_at DESC"
            )->fetchAll(),
        ],
    ];

    $filename = 'full_backup_' . date('Y-m-d_H-i-s') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, private');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handle_full_import(PDO $pdo, array $currentUser): array
{
    if (empty($_FILES['json_file']['tmp_name'])) {
        return ['', 'Файл не загружен'];
    }
    $content = file_get_contents($_FILES['json_file']['tmp_name']);
    $data    = json_decode((string)$content, true);
    if (!is_array($data) || empty($data['tables'])) {
        return ['', 'Неверный формат JSON. Ожидается файл экспорта из этого проекта.'];
    }

    $mode    = ($_POST['mode'] ?? 'merge') === 'replace' ? 'replace' : 'merge';
    $stats   = ['roles' => 0, 'users' => 0, 'login_log' => 0, 'sessions' => 0];
    $skipped = ['roles' => 0, 'users' => 0, 'login_log' => 0, 'sessions' => 0];

    try {
        $pdo->beginTransaction();

        if ($mode === 'replace') {
            $myId = (int)$currentUser['user_id'];
            $pdo->exec("DELETE FROM sessions");
            $pdo->exec("DELETE FROM login_log");
            $pdo->prepare("DELETE FROM users WHERE user_id <> ?")->execute([$myId]);
            $pdo->exec("DELETE FROM roles WHERE role_id NOT IN (1, 2)");
        }

        if (!empty($data['tables']['roles'])) {
            foreach ($data['tables']['roles'] as $r) {
                $rid  = (int)($r['role_id'] ?? 0);
                $code = (string)($r['code'] ?? '');
                $name = (string)($r['name'] ?? '');
                $desc = (string)($r['description'] ?? '');
                if (!$rid || $code === '') {
                    $skipped['roles']++;
                    continue;
                }
                try {
                    $pdo->prepare(
                        "INSERT INTO roles (role_id, code, name, description)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE name = VALUES(name),
                                                 description = VALUES(description)"
                    )->execute([$rid, $code, $name, $desc]);
                    $stats['roles']++;
                } catch (PDOException $e) {
                    $skipped['roles']++;
                }
            }
        }

        if (!empty($data['tables']['users'])) {
            foreach ($data['tables']['users'] as $u) {
                $login       = strtolower(trim((string)($u['login'] ?? '')));
                $first_name  = trim((string)($u['first_name'] ?? ''));
                $middle_name = trim((string)($u['middle_name'] ?? ''));
                $last_name   = trim((string)($u['last_name'] ?? ''));
                $email       = trim((string)($u['email'] ?? ''));
                $phone       = trim((string)($u['phone'] ?? ''));
                $avatar      = trim((string)($u['avatar'] ?? ''));
                $role_id     = (int)($u['role_id'] ?? 2);
                $is_active   = (int)($u['is_active'] ?? 1);
                $is_verified = (int)($u['is_verified'] ?? 0);
                $last_login  = $u['last_login'] ?? null;
                $created_at  = $u['created_at'] ?? date('Y-m-d H:i:s');
                $updated_at  = $u['updated_at'] ?? date('Y-m-d H:i:s');

                if ($login === '' || $last_name === '') {
                    $skipped['users']++;
                    continue;
                }

                $chk = $pdo->prepare("SELECT user_id FROM users WHERE login = ?");
                $chk->execute([$login]);
                $exists = (int)$chk->fetchColumn();

                if ($exists) {
                    $pdo->prepare(
                        "UPDATE users SET
                            email=:em, first_name=:fn, middle_name=:mn,
                            last_name=:ln, phone=:ph, avatar=:av,
                            role_id=:rid, is_active=:act, is_verified=:ver,
                            last_login=:ll, created_at=:ca, updated_at=:ua
                          WHERE user_id=:id"
                    )->execute([
                        ':em' => $email, ':fn' => $first_name, ':mn' => $middle_name,
                        ':ln' => $last_name, ':ph' => $phone ?: null, ':av' => $avatar ?: null,
                        ':rid' => $role_id, ':act' => $is_active, ':ver' => $is_verified,
                        ':ll' => $last_login, ':ca' => $created_at, ':ua' => $updated_at,
                        ':id' => $exists,
                    ]);
                } else {
                    $pdo->prepare(
                        "INSERT INTO users
                            (login, email, first_name, middle_name, last_name,
                             phone, avatar, password_hash, role_id,
                             is_active, is_verified, last_login, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    )->execute([
                        $login, $email, $first_name, $middle_name, $last_name,
                        $phone ?: null, $avatar ?: null,
                        password_hash('changeme', PASSWORD_DEFAULT),
                        $role_id, $is_active, $is_verified,
                        $last_login, $created_at, $updated_at,
                    ]);
                }
                $stats['users']++;
            }
        }

        if (!empty($data['tables']['login_log'])) {
            foreach ($data['tables']['login_log'] as $l) {
                $uid  = ($l['user_id'] ?? null) !== null ? (int)$l['user_id'] : null;
                $lg   = (string)($l['login'] ?? '');
                $ip   = (string)($l['ip'] ?? '');
                $ua   = (string)($l['user_agent'] ?? '');
                $ok   = (int)($l['success'] ?? 0);
                $when = $l['created_at'] ?? date('Y-m-d H:i:s');
                if ($lg === '') {
                    $skipped['login_log']++;
                    continue;
                }
                try {
                    $pdo->prepare(
                        "INSERT INTO login_log (user_id, login, ip, user_agent, success, created_at)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    )->execute([$uid, $lg, $ip, $ua, $ok, $when]);
                    $stats['login_log']++;
                } catch (PDOException $e) {
                    $skipped['login_log']++;
                }
            }
        }

        if (!empty($data['tables']['sessions']) && $mode === 'replace') {
            foreach ($data['tables']['sessions'] as $s) {
                $sid = (string)($s['session_id'] ?? '');
                $uid = (int)($s['user_id'] ?? 0);
                $ip  = (string)($s['ip'] ?? '');
                $ua  = (string)($s['user_agent'] ?? '');
                $ca  = $s['created_at'] ?? date('Y-m-d H:i:s');
                $ea  = $s['expires_at'] ?? date('Y-m-d H:i:s');
                if ($sid === '' || !$uid) {
                    $skipped['sessions']++;
                    continue;
                }
                try {
                    $pdo->prepare(
                        "INSERT IGNORE INTO sessions
                            (session_id, user_id, ip, user_agent, created_at, expires_at)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    )->execute([$sid, $uid, $ip, $ua, $ca, $ea]);
                    $stats['sessions']++;
                } catch (PDOException $e) {
                    $skipped['sessions']++;
                }
            }
        }

        $pdo->commit();

        $msg = sprintf(
            'Импорт завершён. Режим: %s. roles: +%d (пропущено %d). users: +%d (пропущено %d). '
            . 'login_log: +%d (пропущено %d). sessions: +%d (пропущено %d). '
            . 'Внимание: у новых пользователей пароль changeme.',
            $mode,
            $stats['roles'], $skipped['roles'],
            $stats['users'], $skipped['users'],
            $stats['login_log'], $skipped['login_log'],
            $stats['sessions'], $skipped['sessions']
        );
        return [$msg, ''];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('full_import: ' . $e->getMessage());
        return ['', 'Ошибка импорта: ' . $e->getMessage()];
    }
}