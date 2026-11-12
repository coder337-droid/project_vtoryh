<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

$user = require_admin();
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

$stats = [
    'total'  => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
    'admins' => (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.code = 'admin'")->fetchColumn(),
    'users'  => (int)$pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.code = 'user'")->fetchColumn(),
];

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

require __DIR__ . '/../templates/admin_settings.view.php';