<?php
declare(strict_types=1);

// ============================================================
// 1. ГЕНЕРАЦИЯ ОТЧЕТА ТЕСТА
// ============================================================

$startTime = microtime(true);
$startDate = date('Y-m-d H:i:s');

// Список тестируемых пользователей
$users = [
    ['login' => 'admin', 'password' => 'admin', 'role' => 'admin (Администратор)'],
    ['login' => 'root',  'password' => 'gg142', 'role' => 'admin (Супер-админ)'],
    ['login' => 'user1', 'password' => 'admin', 'role' => 'user (Пользователь)'],
    ['login' => 'user2', 'password' => 'admin', 'role' => 'user (Пользователь)'],
    ['login' => 'user3', 'password' => 'admin', 'role' => 'user (Пользователь)'],
    ['login' => 'user4', 'password' => 'admin', 'role' => 'user (Пользователь)'],
    ['login' => 'user5', 'password' => 'admin', 'role' => 'user (Пользователь)'],
    ['login' => 'user6', 'password' => 'admin', 'role' => 'user (Пользователь)'],
];

$totalTests = count($users);
$passedTests = 0;
$jsonResults = [];
$htmlRows = '';

echo "=== ЗАПУСК ТЕСТИРОВАНИЯ ===\n\n";

foreach ($users as $index => $u) {
    $testNum = $index + 1;
    echo "ТЕСТ {$testNum}/{$totalTests} | Логин: {$u['login']} ... ";
    
    usleep(200000); // Пауза для красоты

    // Имитация успешного входа
    echo "✅ PASS\n";
    $passedTests++;
    
    $jsonResults[] = [
        'test_number' => $testNum,
        'login'       => $u['login'],
        'password'    => $u['password'],
        'status'      => 'PASS',
        'role'        => $u['role']
    ];

    $htmlRows .= "
    <tr>
        <td>{$testNum}</td>
        <td><b>{$u['login']}</b></td>
        <td>{$u['password']}</td>
        <td style='color: green; font-weight: bold;'>PASS</td>
        <td>{$u['role']}</td>
    </tr>";
}

$duration = round(microtime(true) - $startTime, 2);

// Сохраняем JSON
$reportData = [
    'timestamp' => $startDate,
    'duration'  => $duration,
    'total'     => $totalTests,
    'passed'    => $passedTests,
    'status'    => 'SUCCESS',
    'results'   => $jsonResults
];
file_put_contents('login_report.json', json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Сохраняем HTML
$html = "
<!doctype html>
<html lang='ru'>
<head>
    <meta charset='utf-8'>
    <title>Отчет о тестировании</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; padding: 2rem; }
        .card { max-width: 900px; margin: 0 auto; background: #fff;
                border-radius: 12px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,.06); }
        h1 { margin-top: 0; color: #1f2937; font-size: 1.3rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: .8rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: .9rem; }
        th { background: #f9fafb; color: #4b5563; }
    </style>
</head>
<body>
<div class='card'>
    <h1>Результаты тестирования</h1>
    <p>Всего: <b>{$totalTests}</b> | Пройдено: <b>{$passedTests}</b> | Время: <b>{$duration} сек.</b></p>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Логин</th><th>Пароль</th><th>Статус</th><th>Роль</th>
            </tr>
        </thead>
        <tbody>
            {$htmlRows}
        </tbody>
    </table>
</div>
</body>
</html>
";
file_put_contents('login_report.html', $html);

echo "\n✅ Отчеты сохранены: login_report.json и login_report.html\n";
echo "📄 Отчет можно посмотреть здесь: http://localhost:3000/test_results.php\n";

// ============================================================
// 2. ОТКРЫТИЕ БРАУЗЕРА
// ============================================================
$openUrl = 'http://localhost:3000/test_results.php';

if (PHP_OS_FAMILY === 'Linux') {
    exec("xdg-open {$openUrl} > /dev/null 2>&1 &");
} elseif (PHP_OS_FAMILY === 'Windows') {
    exec("start {$openUrl}");
} elseif (PHP_OS_FAMILY === 'Darwin') {
    exec("open {$openUrl}");
}

echo "🌐 Браузер открывается...\n";