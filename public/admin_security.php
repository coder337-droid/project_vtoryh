<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/auth.php';

$user = require_admin();
$pdo  = db();

/* ---------- экспорт CSV по текущим фильтрам ---------- */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $ranges = ['1h' => 1, '6h' => 6, '24h' => 24, '7d' => 168, '30d' => 720];
    $rk     = isset($_GET['range'], $ranges[$_GET['range']]) ? (string)$_GET['range'] : '24h';
    $hours  = (int)$ranges[$rk];
    $type   = in_array($_GET['type'] ?? 'all', ['all', 'ok', 'fail'], true) ? (string)$_GET['type'] : 'all';
    $q      = trim((string)($_GET['q'] ?? ''));

    $where  = ["created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)"];
    $params = [];
    if ($type === 'ok')   $where[] = 'success = 1';
    if ($type === 'fail') $where[] = 'success = 0';
    if ($q !== '') { $where[] = '(login LIKE :q OR ip LIKE :q)'; $params[':q'] = '%' . $q . '%'; }

    $st = $pdo->prepare(
        'SELECT log_id, created_at, login, ip, success, user_agent FROM login_log WHERE '
        . implode(' AND ', $where) . ' ORDER BY log_id DESC LIMIT 20000'
    );
    $st->execute($params);

    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="security_log_' . date('Y-m-d_H-i') . '.csv"');
    header('Cache-Control: no-store, private');
    $fh = fopen('php://output', 'wb');
    fwrite($fh, "\xEF\xBB\xBF");
    fputcsv($fh, ['ID', 'Время', 'Логин', 'IP', 'Результат', 'User-Agent'], ';');
    while ($r = $st->fetch()) {
        fputcsv($fh, [
            $r['log_id'], $r['created_at'], $r['login'], $r['ip'],
            ((int)$r['success'] === 1) ? 'УСПЕХ' : 'ОТКАЗ', $r['user_agent'],
        ], ';');
    }
    fclose($fh);
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'revoke') {
            $sid = (string)($_POST['session_id'] ?? '');
            if (!preg_match('/^[a-zA-Z0-9,\-]{16,128}$/', $sid)) {
                $err = 'Некорректный идентификатор сессии';
            } elseif ($sid === session_id()) {
                $err = 'Нельзя отозвать собственную сессию — используйте «Выйти»';
            } else {
                $pdo->prepare('DELETE FROM sessions WHERE session_id = ?')->execute([$sid]);
                $file = rtrim(session_save_path() ?: sys_get_temp_dir(), '/') . '/sess_' . $sid;
                if (is_file($file)) @unlink($file);
                $msg = 'Сессия отозвана, устройство разлогинено';
            }
        } elseif ($action === 'unblock') {
            $ip = (string)($_POST['ip'] ?? '');
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $err = 'Некорректный IP-адрес';
            } else {
                $w = (int)AUTH_WINDOW_MIN;
                $st = $pdo->prepare(
                    "DELETE FROM login_log WHERE ip = ? AND success = 0
                       AND created_at >= DATE_SUB(NOW(), INTERVAL $w MINUTE)"
                );
                $st->execute([$ip]);
                $msg = 'IP ' . $ip . ' разблокирован (снято записей: ' . $st->rowCount() . ')';
            }
        } elseif ($action === 'cleanup') {
            $days = (int)($_POST['days'] ?? 90);
            if ($days < 7) $days = 90;
            $st = $pdo->prepare('CALL sp_cleanup_sessions(?, @s, @l)');
            $st->execute([$days]);
            $st->closeCursor();
            $r = $pdo->query('SELECT @s AS s, @l AS l')->fetch();
            $msg = 'Регламент выполнен: удалено сессий — ' . (int)($r['s'] ?? 0)
                 . ', записей журнала старше ' . $days . ' дн. — ' . (int)($r['l'] ?? 0);
        }
    } catch (Throwable $e) {
        error_log('admin_security: ' . $e->getMessage());
        $err = 'Не удалось выполнить операцию. Подробности в журнале PHP.';
    }

    $_SESSION['sec_flash'] = ['msg' => $msg, 'err' => $err];
    header('Location: admin_security.php');
    exit;
}

if (!empty($_SESSION['sec_flash'])) {
    $msg = (string)($_SESSION['sec_flash']['msg'] ?? '');
    $err = (string)($_SESSION['sec_flash']['err'] ?? '');
    unset($_SESSION['sec_flash']);
}

require __DIR__ . '/../templates/admin_security.view.php';