<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/functions.php';

if (!file_exists(__DIR__ . '/templates/success.view.php')) {
    die('ОШИБКА: Файл шаблона templates/success.view.php не найден!');
}

if (empty($_SESSION['just_logged_in'])) {
    header('Location: login.php');
    exit;
}
unset($_SESSION['just_logged_in']);

$user = require_login();

require __DIR__ . '/templates/success.view.php';