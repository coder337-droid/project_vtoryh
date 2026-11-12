<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

$user = require_admin();
$pdo  = db();
$msg  = '';
$err  = '';

if (isset($_GET['export']) && $_GET['export'] === 'json') {
    export_users_json($pdo);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        [$msg, $err] = handle_user_delete($pdo, $user);
    }
    if ($action === 'update') {
        [$msg, $err] = handle_user_update($pdo, $user);
    }
    if ($action === 'create') {
        [$msg, $err] = handle_user_create($pdo);
    }
    if ($action === 'import') {
        [$msg, $err] = handle_users_import($pdo);
    }
}

$users = $pdo->query(
    "SELECT u.user_id, u.login, u.last_name, u.first_name, u.middle_name,
            u.email, r.code AS role, u.is_active, u.last_login
     FROM users u
     JOIN roles r ON r.role_id = u.role_id
     ORDER BY u.user_id"
)->fetchAll();

require __DIR__ . '/../templates/admin_users.view.php';