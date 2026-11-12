<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Личный кабинет</title>
    <style>
        body  { font-family: system-ui, sans-serif; background:#f3f4f6; margin:0; padding:3rem; }
        .card { max-width:640px; margin:0 auto; background:#fff; padding:2rem;
                border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.06); }
        h1    { margin:0 0 1rem; }
        .row  { margin:.4rem 0; }
        .btn  { display:inline-block; margin-top:1rem; padding:.6rem 1rem;
                background:#6b7280; color:#fff; text-decoration:none; border-radius:8px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Здравствуйте, <?= htmlspecialchars(($user['last_name'] ?? '') . ' ' . ($user['first_name'] ?? '')) ?></h1>
    <div class="row">Логин: <?= htmlspecialchars($user['login'] ?? '—') ?></div>
    <div class="row">Email: <?= htmlspecialchars($user['email'] ?? '—') ?></div>
    <div class="row">Роль: <?= htmlspecialchars($role) ?></div>
    <a class="btn" href="logout.php">Выйти</a>
</div>
</body>
</html>