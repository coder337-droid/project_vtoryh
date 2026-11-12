<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Настраиваем папку для сессий (она теперь в storage/sessions)
$sessionPath = __DIR__ . '/../storage/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Database.php';