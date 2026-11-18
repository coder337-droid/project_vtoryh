<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется вход администратора'], JSON_UNESCAPED_UNICODE);
    exit;
}

$t0        = microtime(true);
$storage   = __DIR__ . '/../storage';
$cacheFile = $storage . '/monitor_cache.json';
$netFile   = $storage . '/monitor_net.json';

if (is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < 2) {
    readfile($cacheFile);
    exit;
}

/* Сеть: разница с предыдущим замером — без блокирующего usleep */
function monitor_net_sample(): array
{
    $s = ['rx' => 0, 'tx' => 0];
    if (!is_readable('/proc/net/dev')) return $s;
    foreach ((array)file('/proc/net/dev') as $line) {
        if (strpos($line, ':') === false) continue;
        [$name, $rest] = explode(':', $line, 2);
        if (trim($name) === 'lo') continue;
        $p = preg_split('/\s+/', trim($rest));
        $s['rx'] += (int)($p[0] ?? 0);
        $s['tx'] += (int)($p[8] ?? 0);
    }
    return $s;
}

$now  = microtime(true);
$cur  = monitor_net_sample();
$prev = is_file($netFile) ? json_decode((string)file_get_contents($netFile), true) : null;
$rx = 0.0; $tx = 0.0;
if (is_array($prev) && isset($prev['t'], $prev['rx'], $prev['tx'])) {
    $dt = $now - (float)$prev['t'];
    if ($dt > 0.2) {
        $rx = max(0, ($cur['rx'] - (int)$prev['rx']) / $dt / 1024);
        $tx = max(0, ($cur['tx'] - (int)$prev['tx']) / $dt / 1024);
    }
}
@file_put_contents($netFile, json_encode(['t' => $now, 'rx' => $cur['rx'], 'tx' => $cur['tx']]));

/* Память */
$memTotal = 0; $memAvail = 0;
if (is_readable('/proc/meminfo')) {
    $mi = (string)file_get_contents('/proc/meminfo');
    if (preg_match('/MemTotal:\s+(\d+)/', $mi, $m))     $memTotal = (int)$m[1] * 1024;
    if (preg_match('/MemAvailable:\s+(\d+)/', $mi, $m)) $memAvail = (int)$m[1] * 1024;
}
$memUsed    = max(0, $memTotal - $memAvail);
$memPercent = $memTotal > 0 ? (int)round($memUsed / $memTotal * 100) : 0;

/* Диск */
$diskTotal   = (float)@disk_total_space('/');
$diskFree    = (float)@disk_free_space('/');
$diskUsed    = max(0, $diskTotal - $diskFree);
$diskPercent = $diskTotal > 0 ? (int)round($diskUsed / $diskTotal * 100) : 0;

/* CPU и uptime */
$cores = 1;
if (is_readable('/proc/cpuinfo')) {
    $cores = max(1, substr_count((string)file_get_contents('/proc/cpuinfo'), 'processor'));
}
$load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
$cpu  = is_array($load) ? (int)min(100, round(((float)$load[0] / $cores) * 100)) : 0;

$uptimeStr = '—';
if (is_readable('/proc/uptime')) {
    $sec = (int)explode(' ', (string)file_get_contents('/proc/uptime'))[0];
    $d = intdiv($sec, 86400); $h = intdiv($sec % 86400, 3600); $m = intdiv($sec % 3600, 60);
    $uptimeStr = ($d > 0 ? "{$d} д. " : '') . "{$h} ч. {$m} мин.";
}

/* Топ процессов */
$top = [];
$disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
if (function_exists('shell_exec') && !in_array('shell_exec', $disabled, true)) {
    $ps = @shell_exec('ps -eo comm,%cpu,%mem --sort=-%cpu 2>/dev/null | head -n 6');
    if (is_string($ps) && trim($ps) !== '') {
        $lines = explode("\n", trim($ps));
        array_shift($lines);
        foreach ($lines as $line) {
            $p = preg_split('/\s+/', trim($line));
            if (count($p) >= 3) {
                $top[] = ['name' => $p[0], 'cpu' => (float)$p[1], 'mem' => (float)$p[2]];
            }
        }
    }
}

/* БД — креды из .env, а не зашитые */
$dbVersion = null; $dbTables = 0; $dbSizeMb = 0.0; $dbUsers = 0;
$dbPing = 0.0; $dbError = null;
try {
    $pdo = db();
    $tdb = microtime(true);
    $dbVersion = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    $schema    = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();

    $q = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?');
    $q->execute([$schema]);
    $dbTables = (int)$q->fetchColumn();

    $q = $pdo->prepare('SELECT COALESCE(SUM(data_length + index_length),0)
                        FROM information_schema.tables WHERE table_schema = ?');
    $q->execute([$schema]);
    $dbSizeMb = round((float)$q->fetchColumn() / 1048576, 2);

    $dbUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $dbPing  = round((microtime(true) - $tdb) * 1000, 2);
} catch (Throwable $e) {
    error_log('monitor db: ' . $e->getMessage());
    $dbError = 'Нет данных БД — проверьте .env и права пользователя';
}

$json = json_encode([
    'cpu'            => $cpu,
    'cpu_cores'      => $cores,
    'mem_percent'    => $memPercent,
    'mem_used_gb'    => round($memUsed / 1073741824, 1),
    'mem_total_gb'   => round($memTotal / 1073741824, 1),
    'disk_percent'   => $diskPercent,
    'disk_used_gb'   => round($diskUsed / 1073741824, 1),
    'disk_total_gb'  => round($diskTotal / 1073741824, 1),
    'uptime'         => $uptimeStr,
    'net_rx_kb'      => round($rx, 1),
    'net_tx_kb'      => round($tx, 1),
    'db_size_mb'     => $dbSizeMb,
    'db_users'       => $dbUsers,
    'db_tables'      => $dbTables,
    'db_version'     => $dbVersion,
    'db_ping_ms'     => $dbPing,
    'db_error'       => $dbError,
    'site_ping_ms'   => round((microtime(true) - $t0) * 1000, 2),
    'top_processes'  => $top,
    'timestamp'      => date('H:i:s'),
], JSON_UNESCAPED_UNICODE);

@file_put_contents($cacheFile, $json);
echo $json;