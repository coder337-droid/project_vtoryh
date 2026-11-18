<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется вход администратора'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();

$ranges = ['1h' => 1, '6h' => 6, '24h' => 24, '7d' => 168, '30d' => 720];
$steps  = ['1h' => 300, '6h' => 1800, '24h' => 3600, '7d' => 21600, '30d' => 86400];
$rk     = isset($_GET['range'], $ranges[$_GET['range']]) ? (string)$_GET['range'] : '24h';
$hours  = (int)$ranges[$rk];
$step   = (int)$steps[$rk];

$type = in_array($_GET['type'] ?? 'all', ['all', 'ok', 'fail'], true) ? (string)$_GET['type'] : 'all';
$sort = in_array($_GET['sort'] ?? 'new', ['new', 'old', 'login', 'ip'], true) ? (string)$_GET['sort'] : 'new';
$q    = trim((string)($_GET['q'] ?? ''));
$per  = (int)($_GET['per'] ?? 25);
$per  = in_array($per, [10, 25, 50, 100], true) ? $per : 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$off  = ($page - 1) * $per;

$where  = ["created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)"];
$params = [];
if ($type === 'ok')   $where[] = 'success = 1';
if ($type === 'fail') $where[] = 'success = 0';
if ($q !== '') {
    $where[] = '(login LIKE :q OR ip LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
$W = implode(' AND ', $where);

$order = ['new' => 'log_id DESC', 'old' => 'log_id ASC', 'login' => 'login ASC, log_id DESC', 'ip' => 'ip ASC, log_id DESC'][$sort];

$out = [
    'range' => $rk, 'step' => $step, 'page' => $page, 'per' => $per,
    'generated' => date('H:i:s'),
];

try {
    /* ---- счётчик и лента ---- */
    $st = $pdo->prepare("SELECT COUNT(*) FROM login_log WHERE $W");
    $st->execute($params);
    $out['total'] = (int)$st->fetchColumn();

    $st = $pdo->prepare(
        "SELECT l.log_id, l.login, l.ip, l.success, l.user_agent, l.created_at,
                u.user_id, r.code AS role
           FROM login_log l
           LEFT JOIN users u ON u.user_id = l.user_id
           LEFT JOIN roles r ON r.role_id = u.role_id
          WHERE $W
          ORDER BY $order
          LIMIT $per OFFSET $off"
    );
    $st->execute($params);
    $out['events'] = $st->fetchAll();

    /* ---- временной ряд ---- */
    $st = $pdo->prepare(
        "SELECT FLOOR(UNIX_TIMESTAMP(created_at) / :s1) * :s2 AS b,
                SUM(success = 1) AS ok,
                SUM(success = 0) AS fail,
                COUNT(DISTINCT login) AS uniq
           FROM login_log
          WHERE $W
          GROUP BY b ORDER BY b"
    );
    $st->execute($params + [':s1' => $step, ':s2' => $step]);
    $raw = [];
    foreach ($st->fetchAll() as $r) {
        $raw[(int)$r['b']] = ['ok' => (int)$r['ok'], 'fail' => (int)$r['fail'], 'uniq' => (int)$r['uniq']];
    }

    $now    = time();
    $start  = (int)(floor(($now - $hours * 3600) / $step) * $step);
    $end    = (int)(floor($now / $step) * $step);
    $series = [];
    for ($t = $start; $t <= $end; $t += $step) {
        $series[] = [
            't'    => $t,
            'ok'   => $raw[$t]['ok']   ?? 0,
            'fail' => $raw[$t]['fail'] ?? 0,
            'uniq' => $raw[$t]['uniq'] ?? 0,
        ];
    }
    $out['series'] = $series;

    /* ---- KPI ---- */
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS attempts,
                SUM(success = 1) AS ok,
                SUM(success = 0) AS fail,
                COUNT(DISTINCT login) AS logins,
                COUNT(DISTINCT ip) AS ips,
                MAX(log_id) AS latest
           FROM login_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)"
    );
    $st->execute();
    $k = $st->fetch() ?: [];
    $out['kpi'] = [
        'attempts' => (int)($k['attempts'] ?? 0),
        'ok'       => (int)($k['ok'] ?? 0),
        'fail'     => (int)($k['fail'] ?? 0),
        'logins'   => (int)($k['logins'] ?? 0),
        'ips'      => (int)($k['ips'] ?? 0),
        'latest'   => (int)($k['latest'] ?? 0),
    ];

    /* ---- топ IP ---- */
    $st = $pdo->prepare(
        "SELECT ip, COUNT(*) AS total, SUM(success = 0) AS fail, MAX(created_at) AS last
           FROM login_log
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)
          GROUP BY ip ORDER BY fail DESC, total DESC LIMIT 8"
    );
    $st->execute();
    $out['top_ips'] = $st->fetchAll();

    /* ---- заблокированные IP ---- */
    $w = (int)AUTH_WINDOW_MIN;
    $m = (int)AUTH_MAX_FAILS;
    $st = $pdo->query(
        "SELECT ip, COUNT(*) AS fails,
                TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MAX(created_at), INTERVAL " . (int)AUTH_LOCK_MIN . " MINUTE)) AS left_sec
           FROM login_log
          WHERE success = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL $w MINUTE)
          GROUP BY ip HAVING fails >= $m"
    );
    $locked = [];
    foreach ($st->fetchAll() as $r) {
        if ((int)$r['left_sec'] > 0) {
            $locked[$r['ip']] = ['fails' => (int)$r['fails'], 'left' => (int)$r['left_sec']];
        }
    }
    $out['locked'] = $locked;

    /* ---- активные сессии ---- */
    $out['sessions'] = $pdo->query(
        "SELECT s.session_id, s.ip, s.user_agent, s.created_at, s.expires_at,
                u.user_id, u.login, r.code AS role
           FROM sessions s
           LEFT JOIN users u ON u.user_id = s.user_id
           LEFT JOIN roles r ON r.role_id = u.role_id
          WHERE s.expires_at IS NULL OR s.expires_at > NOW()
          ORDER BY s.created_at DESC LIMIT 50"
    )->fetchAll();
    $out['session_count'] = count($out['sessions']);
    $out['my_session']    = session_id();

    /* ---- сводка из хранимой процедуры ---- */
    try {
        $out['stats'] = $pdo->query('CALL sp_user_stats()')->fetch() ?: null;
    } catch (Throwable $e) {
        $out['stats'] = null;
    }
} catch (Throwable $e) {
    error_log('api_security: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка чтения журнала'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);