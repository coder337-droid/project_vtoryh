<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Мониторинг сервера | Admin Panel</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            --surface: #ffffff; --border: #cbd5e1;
            --text-main: #0f172a; --text-muted: #64748b;
            --primary: #4f46e5; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --info: #3b82f6;
        }
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: var(--bg-gradient); margin: 0; min-height: 100vh; color: var(--text-main); display: flex; flex-direction: column; }
        
        .top-nav { background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; padding: 0 2rem; height: 70px; position: sticky; top: 0; z-index: 100; }
        .brand { font-size: 1.25rem; font-weight: 800; margin-right: 3rem; background: linear-gradient(90deg, #4f46e5, #9333ea); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .nav-links { display: flex; gap: 0.5rem; align-items: center; height: 100%; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600; padding: 0.6rem 1rem; border-radius: 8px; transition: all 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); background: #eef2ff; }
        .user-menu { margin-left: auto; display: flex; align-items: center; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); }
        .user-menu strong { color: var(--text-main); }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; padding: 0.5rem 1rem; border: 1px solid #fecaca; border-radius: 8px; background: #fef2f2; }

        .main-container { flex: 1; padding: 2.5rem 2rem; max-width: 1200px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { margin: 0; font-size: 1.75rem; font-weight: 800; }
        
        .live-badge { display: inline-flex; align-items: center; gap: 0.5rem; background: #ecfdf5; color: #065f46; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 700; border: 1px solid #a7f3d0; }
        .live-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(16,185,129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129, 0); } }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .panel { background: var(--surface); border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 1.5rem; }
        .panel-title { font-size: 1rem; font-weight: 700; margin-top: 0; margin-bottom: 1.5rem; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; }
        
        .progress-block { margin-bottom: 1.5rem; }
        .progress-header { display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
        .progress-bar { height: 10px; background: #e2e8f0; border-radius: 5px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 5px; transition: width 0.5s ease-out, background-color 0.5s; }
        .fill-green { background: var(--success); }
        .fill-orange { background: var(--warning); }
        .fill-red { background: var(--danger); }
        .fill-blue { background: var(--info); }
        .progress-sub { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem; text-align: right; }

        .info-list { list-style: none; padding: 0; margin: 0; }
        .info-list li { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .info-list li:last-child { border-bottom: none; }
        .info-list span:first-child { color: var(--text-muted); font-weight: 500; }
        .info-list span:last-child { font-weight: 700; color: var(--text-main); text-align: right; }

        .db-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; text-align: center; padding: 10px 0; }
        .db-stat-item .lbl { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
        .db-stat-item .val { font-size: 1.5rem; font-weight: 800; color: var(--primary); }
        
        .net-block { margin-top: 1.5rem; border-top: 1px dashed var(--border); padding-top: 1rem; }
        .net-value { font-weight: 800; color: var(--info); }

        /* Стили для таблицы процессов */
        .process-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .process-table th { text-align: left; padding: 10px; border-bottom: 1px solid var(--border); color: var(--text-muted); font-weight: 600; }
        .process-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        .process-table tr:last-child td { border-bottom: none; }
        .process-table .proc-name { font-family: monospace; font-weight: bold; color: var(--primary); }
    </style>
</head>
<body>
    <header class="top-nav">
        <a href="dashboard.php" class="brand">ADMIN SYSTEM</a>
        <nav class="nav-links">
            <a href="http://127.0.0.1:8002/admin_users.php">Пользователи</a>
            <a href="admin_monitoring.php" class="active">Мониторинг</a>
            <a href="http://127.0.0.1:8002/admin_settings.php">Настройки</a>
        </nav>
        <div class="user-menu">
            <span>Вы вошли как <strong><?= htmlspecialchars($user['login'] ?? 'admin') ?></strong></span>
            <a href="http://127.0.0.1:8001/logout.php" class="btn-logout">Выйти</a>
        </div>
    </header>

    <main class="main-container">
        <div class="page-header">
            <h1 class="page-title">Мониторинг в реальном времени</h1>
            <div class="live-badge">
                <div class="live-dot"></div>
                Live <span id="lastUpdate">--:--:--</span>
            </div>
        </div>

        <div class="grid">
            <!-- Левая колонка: Ресурсы -->
            <div class="panel">
                <h2 class="panel-title">Нагрузка на сервер</h2>
                
                <div class="progress-block">
                    <div class="progress-header">
                        <span>Процессор (CPU)</span>
                        <span id="cpu-text">0% (<span id="cpu-cores">1</span> ядер)</span>
                    </div>
                    <div class="progress-bar"><div id="cpu-bar" class="progress-fill fill-green" style="width: 0%"></div></div>
                </div>

                <div class="progress-block">
                    <div class="progress-header">
                        <span>Оперативная память (RAM)</span>
                        <span id="mem-text">0%</span>
                    </div>
                    <div class="progress-bar"><div id="mem-bar" class="progress-fill fill-green" style="width: 0%"></div></div>
                    <div class="progress-sub" id="mem-sub">0 ГБ из 0 ГБ</div>
                </div>

                <div class="progress-block">
                    <div class="progress-header">
                        <span>Дисковое пространство</span>
                        <span id="disk-text">0%</span>
                    </div>
                    <div class="progress-bar"><div id="disk-bar" class="progress-fill fill-green" style="width: 0%"></div></div>
                    <div class="progress-sub" id="disk-sub">0 ГБ из 0 ГБ</div>
                </div>

                <div class="progress-block net-block">
                    <div class="progress-header">
                        <span>Скорость сети (↓ RX / ↑ TX)</span>
                        <span class="net-value" id="net-text">0 / 0 КБ/с</span>
                    </div>
                    <div class="progress-bar"><div id="net-bar" class="progress-fill fill-blue" style="width: 0%"></div></div>
                    <div class="progress-sub">Трафик в реальном времени</div>
                </div>
            </div>

            <!-- Правая колонка: Окружение и Скорость -->
            <div class="panel">
                <h2 class="panel-title">Окружение и Скорость</h2>
                <ul class="info-list">
                    <li><span>Операционная система</span> <span><?= htmlspecialchars($serverInfo['os']) ?></span></li>
                    <li><span>Веб-сервер</span> <span><?= htmlspecialchars($serverInfo['server_software']) ?></span></li>
                    <li><span>Версия PHP</span> <span><?= htmlspecialchars($serverInfo['php_version']) ?></span></li>
                    <li><span>IP сервера</span> <span><?= htmlspecialchars($serverInfo['server_ip']) ?></span></li>
                    <li><span>Время работы (Uptime)</span> <span id="uptime-val">0</span></li>
                    <li><span>Скорость захода на сайт</span> <span id="site-ping" style="color: var(--success);">0 мс</span></li>
                    <li><span>Скорость захода в БД</span> <span id="db-ping" style="color: var(--success);">0 мс</span></li>
                </ul>
            </div>
        </div>

        <!-- НОВЫЙ БЛОК: База Данных и Процессы -->
        <div class="grid">
            <div class="panel">
                <h2 class="panel-title">Состояние Базы Данных</h2>
                <div class="db-stats" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="db-stat-item">
                        <div class="lbl">Размер БД</div>
                        <div class="val" id="db-size">0 МБ</div>
                    </div>
                    <div class="db-stat-item">
                        <div class="lbl">Версия БД</div>
                        <div class="val" id="db-version" style="font-size: 1.1rem; margin-top: 8px; color: #333;">—</div>
                    </div>
                    <div class="db-stat-item">
                        <div class="lbl">Таблиц</div>
                        <div class="val" id="db-tables">0</div>
                    </div>
                    <div class="db-stat-item">
                        <div class="lbl">Пользователей</div>
                        <div class="val" id="db-users">0</div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2 class="panel-title">Топ-5 процессов (по CPU)</h2>
                <table class="process-table">
                    <thead>
                        <tr>
                            <th>Процесс</th>
                            <th>CPU %</th>
                            <th>RAM %</th>
                        </tr>
                    </thead>
                    <tbody id="process-list">
                        <tr><td colspan="3" style="text-align: center; color: #999;">Загрузка данных...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        function updateBar(barId, textId, value, subTextId = null, subText = '') {
            const bar = document.getElementById(barId);
            const text = document.getElementById(textId);
            
            bar.style.width = Math.min(100, value) + '%';
            if (barId !== 'net-bar') {
                bar.className = 'progress-fill ' + (value > 80 ? 'fill-red' : (value > 50 ? 'fill-orange' : 'fill-green'));
            }
            
            if (text) text.textContent = value + '%';
            if (subTextId && subText) {
                document.getElementById(subTextId).textContent = subText;
            }
        }

        function fetchStats() {
            fetch('api/server_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Ресурсы
                    document.getElementById('cpu-text').innerHTML = data.cpu + '% (' + data.cpu_cores + ' ядер)';
                    updateBar('cpu-bar', 'cpu-text', data.cpu);
                    updateBar('mem-bar', 'mem-text', data.mem_percent, 'mem-sub', data.mem_used_gb + ' ГБ из ' + data.mem_total_gb + ' ГБ');
                    updateBar('disk-bar', 'disk-text', data.disk_percent, 'disk-sub', data.disk_used_gb + ' ГБ из ' + data.disk_total_gb + ' ГБ');
                    document.getElementById('uptime-val').textContent = data.uptime;

                    // Сеть
                    document.getElementById('net-text').textContent = data.net_rx_kb + ' / ' + data.net_tx_kb + ' КБ/с';
                    document.getElementById('net-bar').style.width = Math.min(100, (data.net_rx_kb / 100) * 100) + '%';

                    // БД
                    document.getElementById('db-size').textContent = data.db_size_mb + ' МБ';
                    document.getElementById('db-tables').textContent = data.db_tables;
                    document.getElementById('db-users').textContent = data.db_users;
                    document.getElementById('db-version').textContent = data.db_version;

                    // Пинги
                    const sitePingEl = document.getElementById('site-ping');
                    sitePingEl.textContent = data.site_ping_ms + ' мс';
                    sitePingEl.style.color = data.site_ping_ms < 100 ? 'var(--success)' : (data.site_ping_ms < 300 ? 'var(--warning)' : 'var(--danger)');

                    const dbPingEl = document.getElementById('db-ping');
                    dbPingEl.textContent = data.db_ping_ms + ' мс';
                    dbPingEl.style.color = data.db_ping_ms < 50 ? 'var(--success)' : (data.db_ping_ms < 150 ? 'var(--warning)' : 'var(--danger)');

                    // Процессы
                    const procList = document.getElementById('process-list');
                    if (data.top_processes && data.top_processes.length > 0) {
                        procList.innerHTML = '';
                        data.top_processes.forEach(p => {
                            procList.innerHTML += `
                                <tr>
                                    <td class="proc-name">${p.name}</td>
                                    <td style="color: ${p.cpu > 10 ? 'var(--danger)' : 'inherit'}">${p.cpu}%</td>
                                    <td>${p.mem}%</td>
                                </tr>
                            `;
                        });
                    }

                    // Время
                    document.getElementById('lastUpdate').textContent = data.timestamp;
                })
                .catch(error => console.error('Ошибка получения данных:', error));
        }

        fetchStats();
        setInterval(fetchStats, 2000);
    </script>
</body>
</html>История неэлектронных вычислительных машин
