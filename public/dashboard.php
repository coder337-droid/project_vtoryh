<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

if (!file_exists(__DIR__ . '/../templates/dashboard.view.php')) {
    die('ОШИБКА: Файл шаблона templates/dashboard.view.php не найден!');
}

$user = require_login();
$role = $user['role'] ?? '—';

require __DIR__ . '/../templates/dashboard.view.php';