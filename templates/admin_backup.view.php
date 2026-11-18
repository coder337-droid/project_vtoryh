<?php $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><title>Бэкап БД | Admin Panel</title>
<style>
body{font-family:-apple-system,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#f0f4f8,#e2e8f0);margin:0;padding:2rem;color:#0f172a}
.wrap{max-width:900px;margin:0 auto}
.card{background:#fff;border-radius:16px;box-shadow:0 10px 25px -5px rgba(0,0,0,.05);padding:2rem;margin-bottom:1.5rem}
nav a{margin-right:1rem;color:#64748b;text-decoration:none;font-weight:600;font-size:.9rem}
h1{font-size:1.3rem;font-weight:800;margin:0 0 1rem}
.btn{display:inline-block;padding:.6rem 1.2rem;border-radius:8px;font-weight:600;font-size:.9rem;background:#4f46e5;color:#fff;border:0;cursor:pointer;text-decoration:none}
.del{background:#ef4444;padding:.35rem .8rem;font-size:.8rem}
table{width:100%;border-collapse:collapse;margin-top:1rem}
th,td{padding:.7rem 1rem;border-bottom:1px solid #e2e8f0;font-size:.9rem;text-align:left}
th{background:#f8fafc;color:#64748b;font-size:.75rem;text-transform:uppercase}
.ok{background:#dcfce7;color:#166534;padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem}
.bad{background:#fee2e2;color:#991b1b;padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem}
.warn{background:#fef3c7;color:#92400e;padding:.8rem 1rem;border-radius:8px;font-size:.9rem;margin-bottom:1rem}
code{background:#f1f5f9;padding:.15rem .4rem;border-radius:4px}
</style></head><body><div class="wrap">

<div class="card">
  <nav>
    <a href="admin_users.php">Пользователи</a>
    <a href="admin_monitoring.php">Мониторинг</a>
    <a href="admin_settings.php">Настройки</a>
    <a href="admin_backup.php"><b>Бэкап SQL</b></a>
    <a href="admin_export.php">Экспорт JSON</a>
    <a href="admin_import.php">Импорт</a>
    <a href="logout.php">Выйти</a>
  </nav>
  <p style="color:#64748b;font-size:.9rem;margin:1rem 0 0">Вы вошли как <b><?= $e($user['login']) ?></b></p>
</div>

<div class="card">
  <h1>Резервная копия базы данных</h1>
  <?php if ($msg): ?><div class="ok"><?= $e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="bad"><?= $e($err) ?></div><?php endif; ?>
  <div class="warn">Дамп содержит <b>password_hash</b> и все таблицы, включая <code>notes</code>. Файлы — только в <code>storage/backups/</code>, не в репозиторий.</div>
  <a class="btn" href="?download=1">Скачать .sql</a>
  <form method="post" style="display:inline-block;margin-left:.5rem">
    <input type="hidden" name="csrf" value="<?= $e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <button class="btn" type="submit">Сохранить на сервере</button>
  </form>
</div>

<div class="card">
  <h1>Сохранённые копии (<?= count($backups) ?>)</h1>
  <?php if (!$backups): ?>
    <p style="color:#64748b">Пока пусто.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Файл</th><th>Размер</th><th>Создан</th><th></th></tr></thead><tbody>
    <?php foreach ($backups as $b): ?>
      <tr>
        <td><?= $e($b['name']) ?></td>
        <td><?= round($b['size'] / 1048576, 2) ?> МБ</td>
        <td><?= date('d.m.Y H:i:s', $b['time']) ?></td>
        <td style="white-space:nowrap">
          <a class="btn" href="?file=<?= urlencode($b['name']) ?>">Скачать</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
            <input type="hidden" name="csrf" value="<?= $e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="name" value="<?= $e($b['name']) ?>">
            <button class="btn del" type="submit">Удалить</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php endif; ?>
</div>

<div class="card">
  <h1>Восстановление</h1>
  <p style="color:#64748b;font-size:.9rem">В дампе есть <code>DROP TABLE IF EXISTS</code> — текущие данные будут заменены. Сначала снимите свежую копию.</p>
  <pre style="background:#f1f5f9;padding:1rem;border-radius:8px;overflow:auto"><code>mysql --default-character-set=utf8mb4 -u app_user -p project_vtoryh \
  &lt; storage/backups/backup_ГГГГ-ММ-ДД_ЧЧ-ММ-СС.sql</code></pre>
</div>

</div></body></html>