<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/backup.php';

$user = require_admin();
$pdo  = db();
$msg  = '';
$err  = '';

if (isset($_GET['download'])) {
    set_time_limit(0);
    backup_download_new($pdo);
}
if (isset($_GET['file'])) {
    backup_download_saved((string)$_GET['file']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    try {
        if (($_POST['action'] ?? '') === 'save') {
            set_time_limit(0);
            [$name, $st] = backup_save($pdo);
            $del = backup_prune();
            $msg = sprintf(
                '%s — таблиц: %d, строк: %d, %s МБ. Удалено старых: %d.',
                $name, $st['tables'], $st['rows'],
                round($st['bytes'] / 1048576, 2), $del
            );
        } elseif (($_POST['action'] ?? '') === 'delete') {
            $n = (string)($_POST['name'] ?? '');
            if (backup_delete($n)) $msg = 'Удалён: ' . $n;
            else $err = 'Файл не найден или недопустимое имя';
        }
    } catch (Throwable $e) {
        error_log('backup: ' . $e->getMessage());
        $err = 'Ошибка бэкапа. Подробности в журнале PHP.';
    }
}

$backups = backup_list();
require __DIR__ . '/../templates/admin_backup.view.php';