<?php
declare(strict_types=1);

$jsonFile = __DIR__ . '/login_report.json';

if (!file_exists($jsonFile)) {
    die("
    <div style='font-family: system-ui; text-align: center; margin-top: 50px;'>
        <h2>Отчетов пока нет</h2>
        <p>Сначала запустите тестирование в терминале: <code>php selenium_test.php</code></p>
        <a href='admin_users.php' style='color: #4f46e5; font-weight: bold;'>← Назад в админку</a>
    </div>");
}

$data = json_decode(file_get_contents($jsonFile), true);
$isSuccess = ($data['status'] === 'SUCCESS');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Результаты тестирования | Admin Panel</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            --surface: #ffffff;
            --border: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-gradient); margin: 0; padding: 2rem;
            min-height: 100vh; color: var(--text-main);
        }
        .wrap { max-width: 900px; margin: 0 auto; }
        
        .card { 
            background: var(--surface); border-radius: 16px; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 0 0 1px rgba(0,0,0,0.05);
            padding: 2rem; margin-bottom: 1.5rem; 
        }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        h1 { font-size: 1.3rem; margin: 0; color: var(--text-main); font-weight: 800; }
        .back-link { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .back-link:hover { color: var(--primary); }

        .meta { font-size: .9rem; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.6; }
        .meta b { color: var(--text-main); }
        
        .badge { display: inline-block; padding: .3rem .8rem; border-radius: 6px; font-size: .75rem; font-weight: 700; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-fail { background: #fee2e2; color: #991b1b; }
        
        .btn { 
            display: inline-block; padding: .6rem 1.2rem; border-radius: 8px; 
            font-weight: 600; text-decoration: none; font-size: .9rem; 
            background: var(--primary); color: #fff; transition: 0.2s;
        }
        .btn:hover { background: #4338ca; }

        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: .75rem 1rem; border-bottom: 1px solid #e2e8f0; font-size: .9rem; text-align: left; }
        th { background: #f8fafc; color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 2px solid var(--border); }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f8fafc; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="top">
            <h1>Результаты тестирования (Selenium)</h1>
            <a href="admin_users.php" class="back-link">← Назад в админку</a>
        </div>
        
        <div class="meta">
            <b>Дата запуска:</b> <?= htmlspecialchars($data['timestamp']) ?><br>
            <b>Длительность:</b> <?= htmlspecialchars((string)$data['duration']) ?> сек.<br>
            <b>Пройдено тестов:</b> <?= $data['passed'] ?> из <?= $data['total'] ?><br>
            <b>Статус сборки:</b> 
            <span class="badge <?= $isSuccess ? 'badge-success' : 'badge-fail' ?>">
                <?= $isSuccess ? 'УСПЕШНО' : 'ОШИБКА' ?>
            </span>
        </div>

        <a href="download_report.php" class="btn">Скачать JSON-отчет</a>
    </div>

    <div class="card">
        <h2 style="font-size: 1.1rem; margin: 0 0 1rem; color: var(--text-main); font-weight: 700;">Детальный лог</h2>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Логин</th>
                    <th>Пароль</th>
                    <th>Статус</th>
                    <th>Роль</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['results'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['test_number']) ?></td>
                        <td><b><?= htmlspecialchars($row['login']) ?></b></td>
                        <td><?= htmlspecialchars($row['password']) ?></td>
                        <td>
                            <span class="badge <?= $row['status'] === 'PASS' ? 'badge-success' : 'badge-fail' ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['role']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>