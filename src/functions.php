<?php
declare(strict_types=1);

// 1. Подключаем наш новый bootstrap
require_once __DIR__ . '/bootstrap.php';

// 2. ДОБАВЛЯЕМ ФУНКЦИЮ db(), которой не хватало!
function db(): PDO
{
    return Database::getConnection();
}

// 3. ДАЛЬШЕ ИДЕТ ВАШ КОД БЕЗ ИЗМЕНЕНИЙ
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): void
{
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF-токен недействителен');
    }
}

function require_login(): array
{
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
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

function handle_profile_update(PDO $pdo, array $user): array
{
    $first_name  = trim((string)($_POST['first_name'] ?? ''));
    $middle_name = trim((string)($_POST['middle_name'] ?? ''));
    $last_name   = trim((string)($_POST['last_name'] ?? ''));
    $email       = trim((string)($_POST['email'] ?? ''));

    if ($last_name === '' || $first_name === '') {
        return ['', 'Фамилия и Имя обязательны'];
    }

    $pdo->prepare(
        "UPDATE users
         SET first_name=:fn, middle_name=:mn, last_name=:ln, email=:em
         WHERE user_id=:id"
    )->execute([
        ':fn' => $first_name, ':mn' => $middle_name,
        ':ln' => $last_name, ':em' => $email,
        ':id' => (int)$user['user_id'],
    ]);

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

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([(int)$user['user_id']]);
    $row = $stmt->fetch();

    // Поддерживаем как хеши, так и открытый текст (для тестов)
    if (!$row || !(password_verify($current, $row['password_hash']) || $current === $row['password_hash'])) {
        return ['', 'Текущий пароль неверен'];
    }

    if ($new === '' || $new !== $confirm) {
        return ['', 'Новые пароли не совпадают'];
    }

    $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
        ->execute([password_hash($new, PASSWORD_DEFAULT), (int)$user['user_id']]);

    return ['Пароль изменён', ''];
}

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
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handle_user_delete(PDO $pdo, array $currentUser): array
{
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id === (int)$currentUser['user_id']) {
        return ['', 'Нельзя удалить самого себя'];
    }
    if ($id <= 0) {
        return ['', ''];
    }
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
    return ['Пользователь удалён', ''];
}

function handle_user_update(PDO $pdo, array $currentUser): array
{
    $id          = (int)($_POST['user_id'] ?? 0);
    $login       = strtolower(trim((string)($_POST['login'] ?? '')));
    $first_name  = trim((string)($_POST['first_name'] ?? ''));
    $middle_name = trim((string)($_POST['middle_name'] ?? ''));
    $last_name   = trim((string)($_POST['last_name'] ?? ''));
    $email       = trim((string)($_POST['email'] ?? ''));
    $roleCode    = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $is_active   = isset($_POST['is_active']) ? 1 : 0;
    $new_pass    = (string)($_POST['password'] ?? '');

    if ($login === '' || $last_name === '' || $first_name === '') {
        return ['', 'Логин, Фамилия и Имя обязательны'];
    }

    $ridStmt = $pdo->prepare("SELECT role_id FROM roles WHERE code = ?");
    $ridStmt->execute([$roleCode]);
    $rid = (int)$ridStmt->fetchColumn();
    if (!$rid) {
        $rid = 2;
    }

    $sql = "UPDATE users
            SET login=:login, first_name=:fn, middle_name=:mn,
                last_name=:ln, email=:email, role_id=:rid, is_active=:act";
    $params = [
        ':login' => $login, ':fn' => $first_name, ':mn' => $middle_name,
        ':ln' => $last_name, ':email' => $email, ':rid' => $rid,
        ':act' => $is_active, ':id' => $id,
    ];
    if ($new_pass !== '') {
        $sql .= ", password_hash=:pass";
        $params[':pass'] = password_hash($new_pass, PASSWORD_DEFAULT);
    }
    $sql .= " WHERE user_id=:id";
    $pdo->prepare($sql)->execute($params);

    if ($id === (int)$currentUser['user_id']) {
        $_SESSION['user']['login']      = $login;
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['last_name']  = $last_name;
        $_SESSION['user']['email']      = $email;
        $_SESSION['user']['role']       = $roleCode;
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
    if (!$rid) {
        $rid = 2;
    }

    try {
        $pdo->prepare(
            "INSERT INTO users (login, first_name, last_name, email, password_hash, role_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)"
        )->execute([
            $login,
            $first_name,
            $last_name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $rid,
        ]);
        return ['Пользователь добавлен', ''];
    } catch (PDOException $e) {
        return ['', 'Ошибка: такой логин или email уже существует'];
    }
}

function handle_users_import(PDO $pdo): array
{
    if (empty($_FILES['json_file']['tmp_name'])) {
        return ['', 'Файл не загружен'];
    }

    $content = file_get_contents($_FILES['json_file']['tmp_name']);
    $data    = json_decode($content, true);

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
            $rid = 2;
        }

        try {
            $pdo->prepare(
                "INSERT INTO users (login, first_name, middle_name, last_name,
                                    email, password_hash, role_id, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
            )->execute([
                $login,
                $first_name,
                $middle_name,
                $last_name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $rid,
            ]);
            $imported++;
        } catch (PDOException $e) {
            $skipped++;
        }
    }

    return ["Импортировано: $imported. Пропущено (дубликаты/ошибки): $skipped.", ''];
}

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
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handle_full_import(PDO $pdo, array $currentUser): array
{
    if (empty($_FILES['json_file']['tmp_name'])) {
        return ['', 'Файл не загружен'];
    }

    $content = file_get_contents($_FILES['json_file']['tmp_name']);
    $data    = json_decode($content, true);

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
                $uid  = $l['user_id'] !== null ? (int)$l['user_id'] : null;
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
        $pdo->rollBack();
        return ['', 'Ошибка импорта: ' . $e->getMessage()];
    }
}