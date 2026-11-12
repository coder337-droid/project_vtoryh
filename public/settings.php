<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

if (!file_exists(__DIR__ . '/../templates/settings.view.php')) {
    die('ОШИБКА: Файл шаблона templates/settings.view.php не найден!');
}

$user = require_login();
$pdo  = db();
$msg  = '';
$err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        [$msg, $err] = handle_profile_update($pdo, $user);
    }

    if ($action === 'password') {
        [$msg, $err] = handle_password_change($pdo, $user);
    }
}

$me = $pdo->prepare(
    "SELECT u.user_id, u.login, u.first_name, u.middle_name, u.last_name,
            u.email, r.code AS role, r.name AS role_name,
            u.is_active, u.last_login, u.created_at
     FROM users u
     JOIN roles r ON r.role_id = u.role_id
     WHERE u.user_id = ?"
);
$me->execute([(int)$user['user_id']]);
$me = $me->fetch();

require __DIR__ . '/../templates/settings.view.php';