<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

$user = require_admin();

$serverInfo = [
    'os'           => PHP_OS_FAMILY . ' (' . php_uname('r') . ')',
    'php_version'  => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Built-in PHP Server',
    'server_ip'    => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
];

require __DIR__ . '/../templates/admin_monitoring.view.php';