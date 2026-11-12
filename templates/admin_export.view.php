<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Экспорт БД | Admin Panel</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            --surface: rgba(255, 255, 255, 0.95); --border: #cbd5e1;
            --text-main: #0f172a; --text-muted: #64748b;
            --primary: #4f46e5; --success: #10b981; --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg-gradient); margin: 0; min-height: 100vh; color: var(--text-main); display: flex; flex-direction: column; }
        .top-nav { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; padding: 0 2rem; height: 70px; position: sticky; top: 0; z-index: 100; }
        .brand { font-size: 1.25rem; font-weight: 800; margin-right: 3rem; background: linear-gradient(90deg, #4f46e5, #9333ea); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .nav-links { display: flex; gap: 0.5rem; align-items: center; height: 100%; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600; padding: 0.6rem 1rem; border-radius: 8px; transition: all 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); background: #eef2ff; }
        .nav-links a.test-link { color: #d97706; background: #fef3c7; margin-left: 1rem; }
        
        .user-menu { margin-left: auto; display: flex; align-items: center; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); }
        .user-menu strong { color: var(--text-main); }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; padding: 0.5rem 1rem; border: 1px solid #fecaca; border-radius: 8px; background: #fef2f2; }

        .main-container { flex: 1; padding: 2.5rem 2rem; max-width: 800px; margin: 0 auto; width: 100%; }
        .panel { background: var(--surface); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 2rem; }
        h1 { margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 800; }
        p.desc { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem; }
        ul { color: var(--text-main); font-size: 0.95rem; line-height: 1.8; padding-left: 1.5rem; margin-bottom: 2rem; }
        ul li b { color: var(--primary); }
        .btn { padding: 0.8rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; transition: 0.2s; display: inline-block; background: var(--success); color: #fff; text-decoration: none; }
        .btn:hover { background: #059669; }
        .back-link { display: block; margin-top: 1.5rem; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>

    <header class="top-nav">
        <a href="admin_users.php" class="brand">ADMIN SYSTEM</a>
        <nav class="nav-links">
            <a href="admin_users.php">Пользователи</a>
            <a href="admin_monitoring.php">Мониторинг</a>
            <a href="admin_settings.php">Настройки</a>
            <a href="admin_export.php" class="active">Экспорт</a>
            <a href="admin_import.php">Импорт</a>
            <a href="run_test.php" class="test-link">Запустить тест БД</a>
        </nav>
        <div class="user-menu">
            <span>Вы вошли как <strong><?= htmlspecialchars($user['login']) ?></strong></span>
            <a href="logout.php" class="btn-logout">Выйти</a>
        </div>
    </header>

    <main class="main-container">
        <div class="panel">
            <h1>Экспорт базы данных</h1>
            <p class="desc">Будет сформирован один JSON-файл, содержащий следующие таблицы:</p>
            <ul>
                <li><b>roles</b> — системные роли (admin, user)</li>
                <li><b>users</b> — учетные записи (без паролей)</li>
                <li><b>login_log</b> — журнал последних 1000 входов</li>
                <li><b>sessions</b> — активные сессии</li>
            </ul>
            <a href="?download=1" class="btn">Скачать резервную копию</a>
            <a href="admin_users.php" class="back-link">← Вернуться к пользователям</a>
        </div>
    </main>
</body>
</html>