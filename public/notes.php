<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён. Используйте GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_GET['user_id'] ?? null;

if ($userId !== null) {
    if (!ctype_digit((string)$userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Параметр user_id должен быть целым положительным числом'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $userId = (int)$userId;
}

try {
    $pdo = db();

    $sql = "SELECT n.id, n.title, n.content, n.created_at, u.login
            FROM notes n
            LEFT JOIN users u ON u.user_id = n.id_user";

    $params = [];

    if ($userId !== null) {
        $sql .= " WHERE n.id_user = :uid";
        $params[':uid'] = $userId;
    }

    $sql .= " ORDER BY n.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $notes = [];

    foreach ($rows as $row) {
        $formatted = '';
        if (!empty($row['created_at'])) {
            $ts = strtotime((string)$row['created_at']);
            if ($ts !== false) {
                $formatted = date('d.m.Y', $ts);
            }
        }

        $login = $row['login'] ?? 'unknown';

        $notes[] = [
            'id'             => (int)$row['id'],
            'title_user'     => $row['title'] . ' - ' . $login,
            'content'        => $row['content'],
            'formatted_date' => $formatted,
        ];
    }

    http_response_code(200);
    echo json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log('API notes DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к базе данных'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('API notes error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера'], JSON_UNESCAPED_UNICODE);
}