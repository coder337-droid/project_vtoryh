<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

$user = require_admin();

if (!isset($_GET['download'])) {
    require __DIR__ . '/../templates/admin_export.view.php';
    exit;
}

export_full_backup(db());
exit;