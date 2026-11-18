<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const AUTH_MAX_FAILS  = 5;
const AUTH_WINDOW_MIN = 15;
const AUTH_LOCK_MIN   = 15;

function auth_ip(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function auth_ua(): string
{
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

/** Флаг для автотестов берётся из .env, а не из URL — подделать нельзя. */
function auth_test_mode(): bool
{
    $env = __DIR__ . '/../.env';
    if (!is_readable($env)) return false;
    $c = @parse_ini_file($env);
    return is_array($c) && (string)($c['APP_TEST_MODE'] ?? '0') === '1';
}

function auth_log(PDO $pdo, ?int $userId, string $login, bool $ok): void
{
    try {
        $pdo->prepare(
            "INSERT INTO login_log (user_id, login, ip, user_agent, success, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        )->execute([$userId, $login, auth_ip(), auth_ua(), $ok ? 1 : 0]);
    } catch (Throwable $e) {
        error_log('auth_log: ' . $e->getMessage());
    }
}

/** Блокировка по журналу БД: чистка cookie её не сбрасывает. */
function auth_lock_state(PDO $pdo, string $login): array
{
    $w = (int)AUTH_WINDOW_MIN;
    $l = (int)AUTH_LOCK_MIN;
    try {
        $st = $pdo->prepare(
            "SELECT COUNT(*) AS fails,
                    TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MAX(created_at), INTERVAL $l MINUTE)) AS left_sec
               FROM login_log
              WHERE success = 0
                AND (login = ? OR ip = ?)
                AND created_at > DATE_SUB(NOW(), INTERVAL $w MINUTE)"
        );
        $st->execute([$login, auth_ip()]);
        $r = $st->fetch();
        $fails = (int)($r['fails'] ?? 0);
        $left  = (int)($r['left_sec'] ?? 0);
        if ($fails >= AUTH_MAX_FAILS && $left > 0) {
            return ['locked' => true, 'minutes' => (int)ceil($left / 60)];
        }
    } catch (Throwable $e) {
        error_log('auth_lock: ' . $e->getMessage());
    }
    return ['locked' => false, 'minutes' => 0];
}

function auth_attempt(PDO $pdo, string $login, string $password): array
{
    $st = $pdo->prepare(
        "SELECT u.user_id, u.login, u.first_name, u.middle_name, u.last_name,
                u.email, u.password_hash, u.is_active, r.code AS role
           FROM users u JOIN roles r ON r.role_id = u.role_id
          WHERE u.login = ? LIMIT 1"
    );
    $st->execute([$login]);
    $row = $st->fetch();

    if (!$row) {
        // выравниваем время ответа, чтобы не выявлять существующие логины
        password_verify($password, password_hash('timing-equalizer', PASSWORD_DEFAULT));
        auth_log($pdo, null, $login, false);
        return [false, 'Неверный логин или пароль', null];
    }
    if (!password_verify($password, (string)$row['password_hash'])) {
        auth_log($pdo, (int)$row['user_id'], $login, false);
        return [false, 'Неверный логин или пароль', null];
    }
    if ((int)$row['is_active'] !== 1) {
        auth_log($pdo, (int)$row['user_id'], $login, false);
        return [false, 'Учётная запись отключена', null];
    }

    auth_log($pdo, (int)$row['user_id'], $login, true);
    return [true, '', $row];
}

function auth_start_session(PDO $pdo, array $row): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'user_id'     => (int)$row['user_id'],
        'login'       => (string)$row['login'],
        'first_name'  => (string)$row['first_name'],
        'middle_name' => (string)($row['middle_name'] ?? ''),
        'last_name'   => (string)$row['last_name'],
        'email'       => (string)($row['email'] ?? ''),
        'role'        => (string)$row['role'],
    ];
    // отпечаток пароля: сменился хеш — все прочие сессии гаснут
    $_SESSION['auth_fp'] = substr((string)$row['password_hash'], -12);

    login_reset_attempts();
    unset($_SESSION['puzzle_order'], $_SESSION['puzzle_nonce'], $_SESSION['puzzle_solved']);

    try {
        $pdo->prepare(
            "INSERT INTO sessions (session_id, user_id, ip, user_agent, created_at, expires_at)
             VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY))
             ON DUPLICATE KEY UPDATE ip = VALUES(ip), user_agent = VALUES(user_agent),
                                     expires_at = VALUES(expires_at)"
        )->execute([session_id(), (int)$row['user_id'], auth_ip(), auth_ua()]);
    } catch (Throwable $e) {
        error_log('auth_session: ' . $e->getMessage());
    }
}

function captcha_issue(): array
{
    $order = puzzle_generate();
    $_SESSION['puzzle_nonce'] = bin2hex(random_bytes(16));
    return $order;
}

function captcha_verify(): bool
{
    if (auth_test_mode()) return true;

    $nonce = (string)($_POST['puzzle_nonce'] ?? '');
    if ($nonce === '' || !hash_equals((string)($_SESSION['puzzle_nonce'] ?? ''), $nonce)) {
        return false;
    }
    $arr = json_decode((string)($_POST['puzzle_order'] ?? ''), true);
    if (!is_array($arr) || count($arr) !== 4) return false;

    return puzzle_check(array_map('intval', $arr));
}