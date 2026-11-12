<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Мои настройки</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #E8B4F0;
               margin: 0; padding: 2rem; min-height: 100vh; }
        .wrap { max-width: 720px; margin: 0 auto; }
        .card { background: #fff; border-radius: 20px;
                box-shadow: 0 20px 50px rgba(0,0,0,.12);
                padding: 2rem; margin-bottom: 1.2rem; }
        .top { display: flex; justify-content: space-between; align-items: center;
               margin-bottom: 1rem; }
        h1 { font-size: 1.2rem; margin: 0; color: #1f2937; }
        h2 { font-size: 1rem; margin: 0 0 1rem; color: #1f2937; }
        label { display: block; font-size: .8rem; color: #4b5563;
                margin: .8rem 0 .3rem; font-weight: 500; }
        input { width: 100%; padding: .65rem .8rem; background: #E5E7EB;
                border: 1px solid #E5E7EB; border-radius: 10px;
                font-size: .9rem; color: #1f2937; outline: none; }
        input:focus { background:#fff; border-color:#a78bfa; }
        input:disabled { background:#f3f4f6; color:#9ca3af; }
        .btn { display: inline-block; padding: .65rem 1.4rem;
               background: #B8E0E8; color: #1f2937; border: 0;
               border-radius: 10px; font-size: .9rem; font-weight: 600;
               cursor: pointer; margin-top: 1rem; }
        .btn:hover { background: #9fd0db; }
        .logout { color:#6b7280; text-decoration:none; font-size:.85rem; }
        .msg { background:#dcfce7; color:#166534; padding:.6rem .8rem;
               border-radius:8px; margin-bottom:.8rem; font-size:.85rem; }
        .err { background:#fee2e2; color:#991b1b; padding:.6rem .8rem;
               border-radius:8px; margin-bottom:.8rem; font-size:.85rem; }
        .info { font-size: .8rem; color: #6b7280; margin-top: .4rem; }
        .role-badge { display: inline-block; padding: .2rem .6rem;
                      border-radius: 6px; font-size: .75rem; font-weight: 600;
                      background:#dbeafe; color:#1e40af; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="top">
            <h1>Мои настройки</h1>
            <a class="logout" href="logout.php">Выйти</a>
        </div>
        <div style="font-size:.9rem; color:#1f2937;">
            <strong><?= htmlspecialchars($me['last_name'] . ' ' . $me['first_name']) ?></strong>
            <span class="role-badge"><?= htmlspecialchars($me['role']) ?></span>
        </div>
        <div class="info">
            Логин: <?= htmlspecialchars($me['login']) ?><br>
            Email: <?= htmlspecialchars($me['email']) ?><br>
            Последний вход: <?= htmlspecialchars($me['last_login'] ?? '—') ?><br>
            Создан: <?= htmlspecialchars($me['created_at']) ?>
        </div>
    </div>

    <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="card">
        <h2>Профиль</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="profile">
            <label>Логин</label>
            <input type="text" value="<?= htmlspecialchars($me['login']) ?>" disabled>
            <label>Фамилия</label>
            <input type="text" name="last_name" required value="<?= htmlspecialchars($me['last_name']) ?>">
            <label>Имя</label>
            <input type="text" name="first_name" required value="<?= htmlspecialchars($me['first_name']) ?>">
            <label>Отчество</label>
            <input type="text" name="middle_name" value="<?= htmlspecialchars($me['middle_name'] ?? '') ?>">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($me['email']) ?>">
            <button class="btn" type="submit">Сохранить профиль</button>
        </form>
    </div>

    <div class="card">
        <h2>Смена пароля</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="password">
            <label>Текущий пароль</label>
            <input type="password" name="current_password" required>
            <label>Новый пароль</label>
            <input type="password" name="new_password" required>
            <label>Подтвердите новый пароль</label>
            <input type="password" name="confirm_password" required>
            <button class="btn" type="submit">Сменить пароль</button>
        </form>
    </div>
</div>
</body>
</html>