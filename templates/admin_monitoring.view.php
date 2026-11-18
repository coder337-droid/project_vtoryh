<?php $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><title>Мониторинг сервера | Admin Panel</title>
<style>
body{font-family:-apple-system,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#f0f4f8,#e2e8f0);margin:0;color:#0f172a}
header{background:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.wrap{max-width:1200px;margin:2rem auto;padding:0 1rem}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
.card{background:#fff;border-radius:16px;box-shadow:0 10px 25px -5px rgba(0,0,0,.05);padding:1.75rem}
h1{font-size:1.6rem;font-weight:800;margin:0 0 1.5rem}
h2{font-size:1rem;font-weight:700;margin:0 0 1rem;padding-bottom:.75rem;border-bottom:1px solid #e2e8f0}
.btn{display:inline-block;padding:.55rem 1.1rem;border-radius:8px;font-weight:600;font-size:.875rem;background:#4f46e5;color:#fff;border:0;cursor:pointer;text-decoration:none}
.btn-grey{background:#eef2ff;color:#4f46e5}
.btn-red{background:#fee2e2;color:#991b1b}
.row{margin-bottom:1.1rem}
.row-top{display:flex;justify-content:space-between;font-size:.875rem;margin-bottom:.4rem}
.row-sub{text-align:right;color:#94a3b8;font-size:.75rem;margin-top:.25rem}
.track{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.fill{height:100%;width:0;background:#4f46e5;border-radius:3px;transition:width .4s}
.kv{display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #f1f5f9;font-size:.875rem}
.kv span:first-child{color:#64748b}
.kv span:last-child{font-weight:600}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;text-align:center}
.stat-l{color:#64748b;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em}
.stat-v{font-size:1.5rem;font-weight:800;color:#4f46e5;margin-top:.25rem}
table{width:100%;border-collapse:collapse}
th,td{padding:.6rem .5rem;border-bottom:1px solid #f1f5f9;font-size:.85rem;text-align:left}
th{color:#64748b;font-size:.7rem;text-transform:uppercase}
.err{display:none;background:#fee2e2;color:#991b1b;padding:.9rem 1.1rem;border-radius:10px;margin-bottom:1.5rem;font-size:.9rem}
.muted{color:#94a3b8}
</style></head><body>

<header>
  <div>
    <a class="btn btn-grey" href="admin_users.php">← На главный экран</a>
    <form method="post" action="admin_backup.php" style="display:inline-block;margin-left:.5rem">
      <input type="hidden" name="csrf" value="<?= $e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save">
      <button class="btn" type="submit">Сделать бэкап</button>
    </form>
  </div>
  <div style="font-size:.875rem;color:#64748b">
    Вы вошли как <b><?= $e($user['login']) ?></b>
    <a class="btn btn-red" href="logout.php" style="margin-left:.75rem">Выйти</a>
  </div>
</header>

<div class="wrap">
  <h1>Мониторинг сервера</h1>
  <div class="err" id="err"></div>

  <div class="grid">
    <div class="card">
      <h2>Нагрузка на сервер</h2>
      <div class="row">
        <div class="row-top"><span>Процессор (CPU)</span><b id="cpu">—</b></div>
        <div class="track"><div class="fill" id="cpu-bar"></div></div>
      </div>
      <div class="row">
        <div class="row-top"><span>Оперативная память (RAM)</span><b id="mem">—</b></div>
        <div class="track"><div class="fill" id="mem-bar"></div></div>
        <div class="row-sub" id="mem-sub">—</div>
      </div>
      <div class="row">
        <div class="row-top"><span>Дисковое пространство</span><b id="disk">—</b></div>
        <div class="track"><div class="fill" id="disk-bar"></div></div>
        <div class="row-sub" id="disk-sub">—</div>
      </div>
      <div class="row" style="margin-bottom:0">
        <div class="row-top"><span>Скорость сети (↓ RX / ↑ TX)</span><b id="net" style="color:#4f46e5">—</b></div>
        <div class="row-sub">Обновление каждые 2 секунды · <span id="ts">—</span></div>
      </div>
    </div>

    <div class="card">
      <h2>Окружение и скорость</h2>
      <div class="kv"><span>Операционная система</span><span><?= $e($serverInfo['os']) ?></span></div>
      <div class="kv"><span>Веб-сервер</span><span><?= $e($serverInfo['server_software']) ?></span></div>
      <div class="kv"><span>Версия PHP</span><span><?= $e($serverInfo['php_version']) ?></span></div>
      <div class="kv"><span>IP сервера</span><span><?= $e($serverInfo['server_ip']) ?></span></div>
      <div class="kv"><span>Время работы (Uptime)</span><span id="uptime">—</span></div>
      <div class="kv"><span>Время генерации ответа</span><span id="site-ms">—</span></div>
      <div class="kv" style="border:0"><span>Время запроса к БД</span><span id="db-ms">—</span></div>
    </div>

    <div class="card">
      <h2>Состояние базы данных</h2>
      <div class="stats">
        <div><div class="stat-l">Размер БД</div><div class="stat-v" id="db-size">—</div></div>
        <div><div class="stat-l">Версия БД</div><div class="stat-v" id="db-ver" style="font-size:1rem">—</div></div>
        <div><div class="stat-l">Таблиц</div><div class="stat-v" id="db-tables">—</div></div>
        <div><div class="stat-l">Пользователей</div><div class="stat-v" id="db-users">—</div></div>
      </div>
    </div>

    <div class="card">
      <h2>Топ-5 процессов (по CPU)</h2>
      <table>
        <thead><tr><th>Процесс</th><th>CPU %</th><th>RAM %</th></tr></thead>
        <tbody id="procs"><tr><td colspan="3" class="muted">Загрузка…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const $ = id => document.getElementById(id);
const setBar = (id, pct) => { $(id).style.width = Math.max(0, Math.min(100, pct || 0)) + '%'; };

async function tick() {
  try {
    const r = await fetch('server_stats.php', { cache: 'no-store', credentials: 'same-origin' });
    if (!r.ok) throw new Error('HTTP ' + r.status + (r.status === 401 ? ' — нет прав, войдите заново' : ''));
    const d = await r.json();
    if (d.error) throw new Error(d.error);

    $('cpu').textContent  = d.cpu + '% (' + d.cpu_cores + ' ядер)';
    setBar('cpu-bar', d.cpu);
    $('mem').textContent  = d.mem_percent + '%';
    setBar('mem-bar', d.mem_percent);
    $('mem-sub').textContent  = d.mem_used_gb + ' ГБ из ' + d.mem_total_gb + ' ГБ';
    $('disk').textContent = d.disk_percent + '%';
    setBar('disk-bar', d.disk_percent);
    $('disk-sub').textContent = d.disk_used_gb + ' ГБ из ' + d.disk_total_gb + ' ГБ';
    $('net').textContent  = d.net_rx_kb + ' / ' + d.net_tx_kb + ' КБ/с';
    $('ts').textContent   = d.timestamp;
    $('uptime').textContent  = d.uptime;
    $('site-ms').textContent = d.site_ping_ms + ' мс';
    $('db-ms').textContent   = d.db_ping_ms + ' мс';

    $('db-size').textContent   = d.db_error ? '—' : d.db_size_mb + ' МБ';
    $('db-ver').textContent    = d.db_version || '—';
    $('db-tables').textContent = d.db_error ? '—' : d.db_tables;
    $('db-users').textContent  = d.db_error ? '—' : d.db_users;

    const tb = $('procs');
    tb.innerHTML = '';
    if (!d.top_processes || !d.top_processes.length) {
      tb.innerHTML = '<tr><td colspan="3" class="muted">Нет данных (shell_exec недоступен)</td></tr>';
    } else {
      d.top_processes.forEach(p => {
        const tr = document.createElement('tr');
        [p.name, p.cpu.toFixed(1), p.mem.toFixed(1)].forEach(v => {
          const td = document.createElement('td');
          td.textContent = v;
          tr.appendChild(td);
        });
        tb.appendChild(tr);
      });
    }

    if (d.db_error) { $('err').textContent = d.db_error; $('err').style.display = 'block'; }
    else { $('err').style.display = 'none'; }
  } catch (e) {
    $('err').textContent = 'Не удалось получить данные: ' + e.message;
    $('err').style.display = 'block';
  }
}
tick();
setInterval(tick, 2000);
</script>
</body></html>