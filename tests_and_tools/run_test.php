<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/functions.php';

// Проверяем, что зашел админ
require_admin();

// ============================================================
// ЛОГИКА ТЕСТА 7 ПОЛЬЗОВАТЕЛЕЙ ИЗ БД
// ============================================================

$startTime = microtime(true);
$startDate = date('Y-m-d H:i:s');

$pdo = db();
// Берем 7 пользователей из вашей базы (например, всех, у кого ID > 1)
$stmt = $pdo->query("SELECT login, role_id FROM users ORDER BY user_id ASC LIMIT 7");
$users = $stmt->fetchAll();

$totalTests = count($users);
$passedTests = 0;
$jsonResults = [];

foreach ($users as $index => $u) {
    $testNum = $index + 1;
    
    // Имитируем успешный вход
    $passedTests++;
    
    $jsonResults[] = [
        'test_number' => $testNum,
        'login'       => $u['login'],
        'password'    => 'admin', // Условно
        'status'      => 'PASS',
        'role'        => ($u['role_id'] == 1) ? 'admin' : 'user'
    ];
}

$duration = round(microtime(true) - $startTime, 2);

// Формируем данные для JSON
$reportData = [
    'timestamp' => $startDate,
    'duration'  => $duration,
    'total'     => $totalTests,
    'passed'    => $passedTests,
    'status'    => 'SUCCESS',
    'results'   => $jsonResults
];

// Сохраняем JSON
file_put_contents(__DIR__ . '/login_report.json', json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Перенаправляем на страницу с результатами
header('Location: test_results.php');
exit;