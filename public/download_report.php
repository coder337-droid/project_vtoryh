<?php
declare(strict_types=1);

$file = __DIR__ . '/login_report.json';

if (!file_exists($file)) {
    die("Ошибка: Файл отчета еще не создан.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="login_report.json"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));

readfile($file);
exit;