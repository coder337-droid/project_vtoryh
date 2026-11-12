<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Засекаем время старта для расчета Ping сайта
$startTime = microtime(true);

// ==========================================
// НАСТРОЙКИ ПОДКЛЮЧЕНИЯ К БД
// ==========================================
$dbHost = '127.0.0.1';
$dbName = 'test_projectvtor';
$dbUser = 'phpuser'; 
$dbPass = 'php123';  

$pdo = null;
$dbPingMs = 0;
$dbVersion = 'Ошибка подключения';
$dbTables = 0;
$dbSizeMb = 0;
$usersCount = 0;

try {
    $dbStartTime = microtime(true);
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $dbVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
    $dbTables = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$dbName'")->fetchColumn();
    $dbSizeMb = round((float)$pdo->query("SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = '$dbName'")->fetchColumn() / 1048576, 2);
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    $dbEndTime = microtime(true);
    $dbPingMs = round(($dbEndTime - $dbStartTime) * 1000, 2);
} catch (Exception $e) { /* Ошибка БД */ }

// ==========================================
// 1. СЕТЬ
// ==========================================
function getNetworkSpeed(): array {
    $readFile = function() {
        $data = [];
        if (!is_readable('/proc/net/dev')) return [];
        $lines = file('/proc/net/dev');
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false && strpos($line, 'lo:') === false) {
                $parts = preg_split('/\s+/', trim($line));
                if (isset($parts[1]) && isset($parts[9])) {
                    $data[] = ['rx' => (int)$parts[1], 'tx' => (int)$parts[9]];
                }
            }
        }
        return $data;
    };

    $start = $readFile();
    $t1 = microtime(true);
    usleep(100000); 
    $end = $readFile();
    $t2 = microtime(true);

    $diff = $t2 - $t1;
    $rx = 0; $tx = 0;
    foreach ($end as $i => $d) {
        if (isset($start[$i])) {
            $rx += $d['rx'] - $start[$i]['rx'];
            $tx += $d['tx'] - $start[$i]['tx'];
        }
    }
    return ['rx' => round(($rx / $diff) / 1024, 1), 'tx' => round(($tx / $diff) / 1024, 1)];
}
$netSpeed = getNetworkSpeed();

// ==========================================
// 2. ПАМЯТЬ И ДИСК
// ==========================================
$memTotal = 0; $memFree = 0;
if (is_readable('/proc/meminfo')) {
    $memInfo = file_get_contents('/proc/meminfo');
    if (preg_match('/MemTotal:\s+(\d+)/', $memInfo, $m)) $memTotal = $m[1] * 1024;
    if (preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $m)) $memFree = $m[1] * 1024;
}
$memUsed = $memTotal - $memFree;
$memPercent = $memTotal > 0 ? round(($memUsed / $memTotal) * 100) : 0;

$diskTotal = disk_total_space("/");
$diskFree  = disk_free_space("/");
$diskUsed  = $diskTotal - $diskFree;
$diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;

// ==========================================
// 3. ПРОЦЕССОР, UPTIME И ТОП ПРОЦЕССОВ
// ==========================================
$cpuLoad = sys_getloadavg();
$cpuCores = 1;
if (is_readable('/proc/cpuinfo')) {
    $cpuCores = substr_count(file_get_contents('/proc/cpuinfo'), 'processor');
}
if ($cpuCores < 1) $cpuCores = 1;
$cpuPercent = min(100, round(($cpuLoad[0] / $cpuCores) * 100));

$uptimeSeconds = (int)explode(' ', file_get_contents('/proc/uptime'))[0];
$days = floor($uptimeSeconds / 86400);
$hours = floor(($uptimeSeconds % 86400) / 3600);
$minutes = floor(($uptimeSeconds % 3600) / 60);
$uptimeStr = ($days > 0 ? "$days д. " : "") . "$hours ч. $minutes мин.";

// Получаем ТОП-5 процессов по памяти (используем ps)
$topProcesses = [];
$psOutput = shell_exec("ps -eo comm,%cpu,%mem --sort=-%cpu | head -n 6");
if ($psOutput) {
    $lines = explode("\n", trim($psOutput));
    array_shift($lines); // Удаляем заголовок
    foreach ($lines as $line) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 3) {
            $topProcesses[] = [
                'name' => $parts[0],
                'cpu'  => (float)$parts[1],
                'mem'  => (float)$parts[2]
            ];
        }
    }
}

// ==========================================
// 4. ПИНГ САЙТА
// ==========================================
$sitePingMs = round((microtime(true) - $startTime) * 1000, 2);

// ==========================================
// ВЫВОД JSON
// ==========================================
echo json_encode([
    'cpu' => $cpuPercent,
    'cpu_cores' => $cpuCores,
    'mem_percent' => $memPercent,
    'mem_used_gb' => round($memUsed / 1073741824, 1),
    'mem_total_gb' => round($memTotal / 1073741824, 1),
    'disk_percent' => $diskPercent,
    'disk_used_gb' => round($diskUsed / 1073741824, 1),
    'disk_total_gb' => round($diskTotal / 1073741824, 1),
    'uptime' => $uptimeStr,
    'net_rx_kb' => $netSpeed['rx'],
    'net_tx_kb' => $netSpeed['tx'],
    'db_size_mb' => $dbSizeMb,
    'db_users' => $usersCount,
    'db_tables' => $dbTables,
    'db_version' => $dbVersion,
    'db_ping_ms' => $dbPingMs,
    'site_ping_ms' => $sitePingMs,
    'top_processes' => $topProcesses, // Новое поле
    'timestamp' => date('H:i:s')
]);