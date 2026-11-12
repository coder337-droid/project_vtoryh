<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Управление пользователями | Admin Panel</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            --surface: rgba(255, 255, 255, 0.95);
            --border: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg-gradient); margin: 0; min-height: 100vh; color: var(--text-main); display: flex; flex-direction: column; }
        
        .top-nav { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; padding: 0 2rem; height: 70px; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .brand { font-size: 1.25rem; font-weight: 800; margin-right: 3rem; background: linear-gradient(90deg, #4f46e5, #9333ea); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .nav-links { display: flex; gap: 0.5rem; align-items: center; height: 100%; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600; padding: 0.6rem 1rem; border-radius: 8px; transition: all 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); background: #eef2ff; }
        .nav-links a.test-link { color: #d97706; background: #fef3c7; margin-left: 1rem; }
        .nav-links a.test-link:hover { background: #fde68a; color: #b45309; }
        
        .user-menu { margin-left: auto; display: flex; align-items: center; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); }
        .user-menu strong { color: var(--text-main); }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; padding: 0.5rem 1rem; border: 1px solid #fecaca; border-radius: 8px; background: #fef2f2; transition: 0.2s; }
        .btn-logout:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

        .main-container { flex: 1; padding: 2.5rem 2rem; max-width: 1600px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { margin: 0; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }

        .panel { background: var(--surface); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); margin-bottom: 2rem; overflow: hidden; }
        .panel-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); font-weight: 700; background: rgba(255,255,255,0.5); }
        .panel-body { padding: 1.5rem; }

        .form-row { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; flex: 1; min-width: 140px; }
        .form-group label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; }
        
        input, select { width: 100%; padding: 0.6rem 0.75rem; font-size: 0.9rem; color: var(--text-main); border: 1px solid var(--border); border-radius: 8px; background: var(--surface); outline: none; transition: all 0.2s; }
        input:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
        
        .btn { padding: 0.6rem 1.2rem; font-size: 0.9rem; font-weight: 600; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2); }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-success { background: var(--success); color: #fff; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); }
        .btn-success:hover { background: #059669; transform: translateY(-1px); }
        .btn-outline { background: transparent; border-color: var(--border); color: var(--text-main); }
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        
        .btn-icon { padding: 0.4rem 0.6rem; font-size: 0.85rem; border-radius: 6px; border: 1px solid var(--border); background: #fff; cursor: pointer; transition: 0.2s; font-weight: 600;}
        .btn-icon:hover { background: var(--bg-gradient); }
        .btn-del { color: var(--danger); border-color: #fecaca; }
        .btn-del:hover { background: #fef2f2; border-color: #fca5a5; }

        .table-toolbar { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; gap: 1rem; align-items: center; background: rgba(255,255,255,0.5); }
        .search-box { width: 350px; }
        .search-box input { padding: 0.6rem 1rem 0.6rem 2.5rem; border-radius: 30px; background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%2364748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>') no-repeat 0.8rem center; border: 1px solid var(--border); }
        .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
        
        .table-wrapper { overflow-x: auto; background: #fff; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 1000px; }
        th { background: #f8fafc; font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.85rem 1rem; border-bottom: 2px solid var(--border); }
        td { padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0; vertical-align: middle; font-size: 0.875rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f8fafc; }

        td input, td select { padding: 0.4rem; border-color: transparent; background: transparent; font-size: 0.875rem; }
        td input:hover, td select:hover { border-color: var(--border); background: #fff; }
        td input:focus, td select:focus { border-color: var(--primary); background: #fff; }

        .alert { padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 2rem; font-size: 0.9rem; font-weight: 600; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .tools-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .tool-card { background: var(--surface); border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 1.25rem 1.5rem; border-radius: 12px; display: flex; align-items: center; gap: 1rem; }
        .tool-card strong { font-size: 0.9rem; color: var(--text-muted); }
        .hidden-row { display: none; }
    </style>
</head>
<body>

    <header class="top-nav">
        <a href="admin_users.php" class="brand">ADMIN SYSTEM</a>
        <nav class="nav-links">
            <a href="admin_users.php" class="active">Пользователи</a>
            <a href="admin_monitoring.php">Мониторинг</a>
            <a href="admin_settings.php">Настройки</a>
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
        <div class="page-header">
            <h1 class="page-title">Управление пользователями</h1>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

        <div class="panel">
            <div class="panel-header">Добавить нового пользователя</div>
            <div class="panel-body">
                <form class="form-row" method="post">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group"><label>Логин *</label><input type="text" name="login" placeholder="user123" required></div>
                    <div class="form-group"><label>Фамилия *</label><input type="text" name="last_name" placeholder="Иванов" required></div>
                    <div class="form-group"><label>Имя</label><input type="text" name="first_name" placeholder="Иван"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="mail@site.com"></div>
                    <div class="form-group" style="max-width: 120px;"><label>Роль</label><select name="role"><option value="user">user</option><option value="admin">admin</option></select></div>
                    <div class="form-group" style="max-width: 150px;"><label>Пароль *</label><input type="text" name="password" placeholder="••••••" required></div>
                    <button class="btn btn-success" type="submit">Создать</button>
                </form>
            </div>
        </div>

        <div class="tools-grid">
            <div class="tool-card">
                <strong>Импорт:</strong>
                <form method="post" enctype="multipart/form-data" style="display:flex; gap:0.5rem; align-items:center; flex:1;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="import">
                    <input type="file" name="json_file" accept=".json" required style="padding: 0.3rem; border: 1px solid var(--border); background: #fff;">
                    <button type="submit" class="btn btn-outline" style="background: #fff;">Загрузить</button>
                </form>
            </div>
            <div class="tool-card">
                <strong>Экспорт:</strong>
                <a href="?export=json" class="btn btn-outline" style="background: #fff;">Скачать JSON</a>
            </div>
        </div>

        <div class="panel">
            <div class="table-toolbar">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Поиск по логину, имени или email...">
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-left: auto;">
                    Всего записей: <strong id="totalCount"><?= count($users) ?></strong>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th><th>Логин</th><th>Фамилия</th><th>Имя</th><th>Email</th><th style="width: 100px;">Роль</th><th style="width: 80px; text-align:center;">Активен</th><th style="width: 150px;">Новый пароль</th><th style="width: 180px; text-align:right;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php $uid = (int)$u['user_id']; ?>
                        <tr>
                            <td style="color: var(--text-muted); font-family: monospace;"><?= $uid ?></td>
                            <td><input form="update-<?= $uid ?>" type="text" name="login" value="<?= htmlspecialchars($u['login']) ?>" required></td>
                            <td><input form="update-<?= $uid ?>" type="text" name="last_name" value="<?= htmlspecialchars($u['last_name']) ?>" required></td>
                            <td><input form="update-<?= $uid ?>" type="text" name="first_name" value="<?= htmlspecialchars($u['first_name']) ?>" required></td>
                            <td><input form="update-<?= $uid ?>" type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>"></td>
                            <td><select form="update-<?= $uid ?>" name="role"><option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option></select></td>
                            <td style="text-align:center"><input form="update-<?= $uid ?>" type="checkbox" name="is_active" value="1" style="width:16px; height:16px; cursor:pointer; accent-color: var(--primary);" <?= $u['is_active'] ? 'checked' : '' ?>></td>
                            <td><input form="update-<?= $uid ?>" type="text" name="password" placeholder="Не менять"></td>
                            <td style="text-align:right; white-space:nowrap;">
                                <button form="update-<?= $uid ?>" class="btn-icon" type="submit" title="Сохранить">Сохранить</button>
                                <button form="delete-<?= $uid ?>" class="btn-icon btn-del" type="submit" title="Удалить">Удалить</button>
                            </td>
                        </tr>
                        <form id="update-<?= $uid ?>" method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="user_id" value="<?= $uid ?>"></form>
                        <form id="delete-<?= $uid ?>" method="post" onsubmit="return confirm(<?= htmlspecialchars(json_encode('Удалить пользователя ' . $u['login'] . '?'), ENT_QUOTES) ?>);"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= $uid ?>"></form>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('usersTable');
            const rows = table.querySelectorAll('tbody tr');
            const totalCount = document.getElementById('totalCount');
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                let visibleCount = 0;
                rows.forEach(row => {
                    const inputs = row.querySelectorAll('input[type="text"], input[type="email"]');
                    let rowText = '';
                    inputs.forEach(input => { rowText += input.value.toLowerCase() + ' '; });
                    if (rowText.includes(query)) { row.classList.remove('hidden-row'); visibleCount++; } else { row.classList.add('hidden-row'); }
                });
                totalCount.textContent = visibleCount;
            });
        });
    </script>
</body>
</html>