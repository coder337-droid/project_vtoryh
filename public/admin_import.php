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
    [$msg, $err] = handle_full_import($pdo, $user);
}

require __DIR__ . '/../templates/admin_import.view.php';