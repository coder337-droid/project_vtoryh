<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Вы авторизовались</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #E8B4F0;
            margin: 0; padding: 2rem;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .card {
            width: 100%; max-width: 360px;
            background: #fff; border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,.15);
            padding: 2rem; text-align: center;
        }
        h1 { font-size: 1.05rem; margin: 0 0 1.2rem;
             color: #1f2937; font-weight: 600; }
        .photo {
            width: 100%; border-radius: 14px; overflow: hidden;
            margin-bottom: 1rem; background: #f3f4f6;
            aspect-ratio: 1 / 1;
            display: flex; align-items: center; justify-content: center;
            color: #9ca3af; font-size: 3rem;
        }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .name {
            font-size: 1rem; font-weight: 600; color: #1f2937;
            margin-bottom: .3rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .name .heart { color: #9ca3af; font-size: 1rem; }
        .desc { font-size: .8rem; color: #6b7280;
                text-align: left; margin-bottom: 1.2rem; }
        .btn {
            display: inline-block; padding: .65rem 1.5rem;
            background: #B8E0E8; color: #1f2937; border-radius: 10px;
            font-size: .9rem; font-weight: 600; text-decoration: none;
        }
        .btn:hover { background: #9fd0db; }
    </style>
</head>
<body>
<div class="card">
    <h1>Вы авторизовались</h1>
    <div class="photo">
        <?php if (file_exists(__DIR__ . '/../captcha/cat.jpg')): ?>
            <img src="captcha/cat.jpg" alt="Кот">
        <?php else: ?>🐱<?php endif; ?>
    </div>
    <div class="name">
        <span>Полнейший балбес</span>
        <span class="heart">♡</span>
    </div>
    <div class="desc">
        Добро пожаловать, <?= htmlspecialchars($user['login']) ?>!
    </div>
    <?php if ($user['role'] === 'admin'): ?>
        <a class="btn" href="admin_users.php">→ К пользователям</a>
    <?php else: ?>
        <a class="btn" href="settings.php">→ К настройкам</a>
    <?php endif; ?>
</div>
</body>
</html>