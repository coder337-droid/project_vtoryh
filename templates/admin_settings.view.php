<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Настройки системы | Admin Panel</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            --surface: rgba(255, 255, 255, 0.95); --border: #cbd5e1;
            --text-main: #0f172a; --text-muted: #64748b;
            --primary: #4f46e5; --primary-hover: #4338ca; --danger: #ef4444;
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

        .main-container { flex: 1; padding: 2.5rem 2rem; max-width: 1000px; margin: 0 auto; width: 100%; }
        .page-title { margin: 0 0 2rem; font-size: 1.75rem; font-weight: 800; }
        
        .panel { background: var(--surface); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); margin-bottom: 2rem; overflow: hidden; }
        .panel-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); font-weight: 700; background: rgba(255,255,255,0.5); }
        .panel-body { padding: 1.5rem; }
        
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; }
        .form-row { display: flex; gap: 1rem; flex-wrap: wrap; }
        .form-row .form-group { flex: 1; min-width: 200px; }
        input { width: 100%; padding: 0.65rem 0.75rem; font-size: 0.9rem; color: var(--text-main); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); outline: none; transition: 0.2s; }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
        input:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
        .btn { padding: 0.7rem 1.5rem; font-size: 0.9rem; font-weight: 600; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: 0.2s; display: inline-block; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        
        .alert { padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 2rem; font-weight: 600; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; border: 1px solid var(--border); padding: 1.5rem; border-radius: 12px; text-align: center; }
        .stat-num { font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.25rem; }
        .stat-lbl { font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
    </style>
</head>
<body>

    <header class="top-nav">
        <a href="admin_users.php" class="brand">ADMIN SYSTEM</a>
        <nav class="nav-links">
            <a href="admin_users.php">Пользователи</a>
            <a href="admin_monitoring.php">Мониторинг</a>
            <a href="admin_settings.php" class="active">Настройки</a>
            <a href="admin_export.php">Экспорт</a>
            <a href="admin_import.php">Импорт</a>
            <a href="run_test.php" class="test-link">Запустить тест БД</a>
        </nav>
        <div class="user-menu">
            <span>Вы вошли как <strong><?= htmlspecialchars($user['login']) ?></strong></span>
            <a href="logout.php" class="btn-logout">Выйти</a>
        </div>
    </header>

    <main class="main-container">
        <h1 class="page-title">Настройки системы</h1>

        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-num"><?= $stats['total'] ?></div><div class="stat-lbl">Всего</div></div>
            <div class="stat-card"><div class="stat-num"><?= $stats['active'] ?></div><div class="stat-lbl">Активных</div></div>
            <div class="stat-card"><div class="stat-num"><?= $stats['admins'] ?></div><div class="stat-lbl">Админов</div></div>
            <div class="stat-card"><div class="stat-num"><?= $stats['users'] ?></div><div class="stat-lbl">Юзеров</div></div>
        </div>

        <div class="panel">
            <div class="panel-header">Редактировать профиль</div>
            <div class="panel-body">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="profile">
                    <div class="form-group"><label>Логин (нельзя изменить)</label><input type="text" value="<?= htmlspecialchars($me['login']) ?>" disabled></div>
                    <div class="form-row">
                        <div class="form-group"><label>Фамилия</label><input type="text" name="last_name" required value="<?= htmlspecialchars($me['last_name'] ?? '') ?>"></div>
                        <div class="form-group"><label>Имя</label><input type="text" name="first_name" required value="<?= htmlspecialchars($me['first_name'] ?? '') ?>"></div>
                        <div class="form-group"><label>Отчество</label><input type="text" name="middle_name" value="<?= htmlspecialchars($me['middle_name'] ?? '') ?>"></div>
                    </div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>"></div>
                    <button class="btn btn-primary" type="submit">Сохранить изменения</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">Сменить пароль</div>
            <div class="panel-body">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="password">
                    <div class="form-group"><label>Текущий пароль</label><input type="password" name="current_password" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>Новый пароль</label><input type="password" name="new_password" required></div>
                        <div class="form-group"><label>Подтвердите новый пароль</label><input type="password" name="confirm_password" required></div>
                    </div>
                    <button class="btn btn-primary" type="submit">Сменить пароль</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>