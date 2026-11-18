<?php
declare(strict_types=1);

$root = dirname(__DIR__);

/* Режим отладки: APP_DEBUG=1 в .env. В продакшене убрать. */
$envFile = $root . '/.env';
$env     = is_readable($envFile) ? (parse_ini_file($envFile) ?: []) : [];
$debug   = (string)($env['APP_DEBUG'] ?? '0') === '1';

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

$logDir = $root . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0700, true);
}
if (is_dir($logDir)) {
    ini_set('error_log', $logDir . '/php_errors.log');
}

/* Сессии: каталог 0700, а не 0777 */
$sessionPath = $root . '/storage/sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0700, true);
}
if (is_dir($sessionPath)) {
    @chmod($sessionPath, 0700);
    session_save_path($sessionPath);
}

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $https ? '1' : '0');
ini_set('session.gc_maxlifetime', '86400');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Database.php';