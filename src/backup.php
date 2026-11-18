<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

function backup_dir(): string
{
    $dir = __DIR__ . '/../storage/backups';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Нет доступа к storage/backups');
    }
    return realpath($dir);
}

function backup_valid_name(string $n): bool
{
    return (bool)preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $n);
}

function backup_file(string $n): ?string
{
    if (!backup_valid_name($n)) return null;
    $p = backup_dir() . '/' . $n;
    return is_file($p) ? $p : null;
}

/** SQL-литерал для одного значения. */
function backup_val(PDO $pdo, $v): string
{
    if ($v === null)  return 'NULL';
    if (is_int($v))   return (string)$v;
    if (is_float($v)) return is_finite($v) ? var_export($v, true) : 'NULL';

    $s = (string)$v;
    if ($s === '') return "''";
    // BLOB/бинарные данные quote() портит — пишем hex-литералом
    if (strpos($s, "\0") !== false || preg_match('//u', $s) !== 1) {
        return '0x' . bin2hex($s);
    }
    return $pdo->quote($s);
}

/** Пишет дамп в поток. Память не растёт: unbuffered + батчи по 200 строк. */
function backup_dump(PDO $pdo, $fh): array
{
    $st = ['tables' => 0, 'rows' => 0, 'bytes' => 0];
    $w = function (string $s) use ($fh, &$st) {
        fwrite($fh, $s);
        $st['bytes'] += strlen($s);
    };

    $w('-- БД: ' . $pdo->query('SELECT DATABASE()')->fetchColumn() . "\n");
    $w('-- Дата: ' . date('Y-m-d H:i:s') . "\n\n");
    $w("SET NAMES utf8mb4;\n");
    $w("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
    $w("SET time_zone='+00:00';\n");
    $w("SET FOREIGN_KEY_CHECKS=0;\n");
    $w("SET UNIQUE_CHECKS=0;\n");
    $w("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;\n");
    $w("START TRANSACTION WITH CONSISTENT SNAPSHOT;\n\n");

    // Только BASE TABLE — VIEW не дампим как таблицы
    $tables = array_column(
        $pdo->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")
            ->fetchAll(PDO::FETCH_NUM), 0
    );

    foreach ($tables as $t) {
        $w("-- ===== {$t} =====\n");
        $w("DROP TABLE IF EXISTS `$t`;\n");
        $w($pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1] . ";\n\n");
        $st['tables']++;

        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $q = $pdo->query("SELECT * FROM `$t`");

        $cols = null; $buf = []; $len = 0;
        while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
            $cols ??= '`' . implode('`,`', array_keys($r)) . '`';
            $vals = [];
            foreach ($r as $v) $vals[] = backup_val($pdo, $v);
            $row = '(' . implode(',', $vals) . ')';
            $buf[] = $row;
            $len  += strlen($row) + 2;
            $st['rows']++;

            if (count($buf) >= 200 || $len > 524288) {
                $w("INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $buf) . ";\n");
                $buf = []; $len = 0;
            }
        }
        if ($buf) {
            $w("INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $buf) . ";\n");
        }

        $q->closeCursor();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $w("\n");
    }

    $w("COMMIT;\n");
    $w("SET FOREIGN_KEY_CHECKS=1;\n");
    $w("SET UNIQUE_CHECKS=1;\n");
    return $st;
}

function backup_save(PDO $pdo): array
{
    $name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $path = backup_dir() . '/' . $name;
    $fh = fopen($path, 'wb');
    if (!$fh) throw new RuntimeException('Не удалось создать файл');
    try { $st = backup_dump($pdo, $fh); } finally { fclose($fh); }
    chmod($path, 0600);
    return [$name, $st];
}

function backup_list(): array
{
    $out = [];
    foreach (scandir(backup_dir()) ?: [] as $f) {
        if (backup_valid_name($f)) {
            $p = backup_dir() . '/' . $f;
            $out[] = ['name' => $f, 'size' => filesize($p), 'time' => filemtime($p)];
        }
    }
    usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
    return $out;
}

function backup_delete(string $n): bool
{
    $p = backup_file($n);
    return $p !== null && unlink($p);
}

function backup_prune(int $keep = 14): int
{
    $n = 0;
    foreach (array_slice(backup_list(), $keep) as $f) {
        if (backup_delete($f['name'])) $n++;
    }
    return $n;
}

function backup_headers(string $name): void
{
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
}

function backup_download_new(PDO $pdo): void
{
    backup_headers('backup_' . date('Y-m-d_H-i-s') . '.sql');
    $fh = fopen('php://output', 'wb');
    backup_dump($pdo, $fh);
    fclose($fh);
    exit;
}

function backup_download_saved(string $name): void
{
    $p = backup_file($name);
    if (!$p) { http_response_code(404); exit('Файл не найден'); }
    backup_headers($name);
    header('Content-Length: ' . filesize($p));
    readfile($p);
    exit;
}