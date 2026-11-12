<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Вход в систему | Admin Panel</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            --surface: #ffffff;
            --border: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-gradient);
            margin: 0; padding: 2rem;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-main);
        }

        .card {
            width: 100%; max-width: 440px;
            background: var(--surface);
            border-radius: 16px;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.05);
            padding: 2.5rem 2rem;
        }

        h1 {
            font-size: 1.35rem;
            text-align: center;
            margin: 0 0 1.8rem;
            color: var(--text-main);
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 1rem 0 .4rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%;
            padding: .75rem .9rem .75rem 2.4rem;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: .95rem;
            color: var(--text-main);
            outline: none;
            transition: 0.2s;
        }
        .input-wrap input:focus { background:#fff; border-color:var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
        
        .input-wrap .icon {
            position: absolute;
            left: .8rem; top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #94a3b8;
        }

        .btn {
            display: block;
            width: 100%;
            margin-top: 1.8rem;
            padding: .8rem;
            background: var(--primary);
            color: #fff;
            border: 0;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn:hover:not(:disabled) { background: var(--primary-hover); }
        .btn:disabled { background: #cbd5e1; color: #94a3b8; cursor: not-allowed; }

        .msg {
            background: #fff; border-radius: 8px; padding: 1rem;
            margin-bottom: 1.2rem; font-size: .9rem; color: var(--text-main);
            box-shadow: 0 2px 5px rgba(0,0,0,.05);
            border-left: 4px solid var(--danger);
        }
        .msg.warn { border-left-color: var(--warning); }
        .msg a { color: var(--primary); font-weight: bold; text-decoration: none; }

        .captcha-title { font-size:.9rem; font-weight:600; color:var(--text-main); margin:1.5rem 0 .8rem; text-align: center;}
        .puzzle-row { display:flex; gap:1rem; justify-content:center;
                      align-items:flex-start; margin-bottom:1rem; }
        .puzzle-col { display:flex; flex-direction:column; align-items:center; }
        .puzzle-col h4 { font-size:.75rem; color:var(--text-muted); text-transform: uppercase;
                         margin:0 0 .6rem; font-weight:700; }
        .puzzle {
            display:grid; grid-template-columns:repeat(2, 70px);
            grid-template-rows:repeat(2, 70px);
            gap:3px; padding:3px; background:#f1f5f9; border-radius:8px;
            border: 1px solid var(--border);
        }
        .tile {
            width:70px; height:70px; border-radius:4px;
            background-size:70px 70px; background-repeat:no-repeat;
            background-position:center; cursor:pointer;
            transition:transform .12s, box-shadow .12s; user-select:none;
        }
        .tile:hover { transform:scale(1.05); box-shadow:0 0 0 2px var(--primary); }
        .tile.selected { box-shadow:0 0 0 3px var(--primary); transform:scale(1.05); }
        .tile.target { cursor:default; }
        .tile.target:hover { transform:none; box-shadow:none; }
        
        .puzzle-status { text-align:center; font-size:.85rem; font-weight: 600; margin:.8rem 0 .2rem; }
        .puzzle-status.ok  { color: #166534; }
        .puzzle-status.bad { color: var(--danger); }
    </style>
</head>
<body>
<div class="card">
    <h1>Вход в систему</h1>

    <?php if (!empty($testReport)): ?>
        <!-- Блок результатов (если передан ?test_results=1) -->
        <div class="msg" style="border-left-color: var(--primary); max-height: 300px; overflow-y: auto;">
            <h3 style="margin: 0 0 10px 0; font-size: 0.95rem; color: var(--primary);">Результаты тестирования</h3>
            <table style="width: 100%; font-size: 0.75rem; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 4px;">Логин</th>
                    <th style="padding: 4px;">Статус</th>
                </tr>
                <?php foreach ($testReport['results'] as $row): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 4px;"><?= htmlspecialchars($row['login']) ?></td>
                        <td style="padding: 4px; color: green; font-weight: bold;"><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <div class="msg"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if (!empty($isLocked)): ?>
        <div class="msg warn">
            Аккаунт заблокирован на <?= login_lock_remaining() ?> мин.<br>
            <a href="?reset_lock=1">Сбросить блокировку</a>
        </div>
    <?php endif; ?>

    <form method="post" novalidate id="loginForm" action="<?= !empty($isTest) ? '?test_mode=1' : '' ?>">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="puzzle_solved" id="puzzle_solved" value="0">

        <label for="login">Логин</label>
        <div class="input-wrap">
            <span class="icon">👤</span>
            <input id="login" type="text" name="login" required
                   placeholder="admin"
                   value="<?= htmlspecialchars($login) ?>"
                   <?= !empty($isLocked) ? 'disabled' : '' ?>>
        </div>

        <label for="password">Пароль</label>
        <div class="input-wrap">
            <span class="icon">🔒</span>
            <input id="password" type="password" name="password" required
                   placeholder="••••••"
                   <?= !empty($isLocked) ? 'disabled' : '' ?>>
        </div>

        <div class="captcha-title">Соберите пазл</div>

        <div class="puzzle-row">
            <div class="puzzle-col">
                <h4>Ваш пазл</h4>
                <div class="puzzle" id="puzzle">
                    <?php foreach ($order as $pos => $pieceId): ?>
                        <?php $img = 'captcha/' . ($pieceId + 1) . '.png'; ?>
                        <div class="tile"
                             data-pos="<?= (int)$pos ?>"
                             data-piece="<?= (int)$pieceId ?>"
                             style="background-image:url('<?= htmlspecialchars($img) ?>');">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="puzzle-col">
                <h4>Образец</h4>
                <div class="puzzle">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="tile target"
                             style="background-image:url('captcha/<?= $i + 1 ?>.png');"></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div class="puzzle-status bad" id="puzzleStatus">Пазл не собран</div>

        <button type="submit" id="submitBtn" class="btn" 
                <?= (!empty($isLocked) || (empty($isTest) && !$puzzleSolved)) ? 'disabled' : '' ?>>
            Войти
        </button>
    </form>
</div>

<script>
(function () {
    const puzzle      = document.getElementById('puzzle');
    const status      = document.getElementById('puzzleStatus');
    const submitBtn   = document.getElementById('submitBtn');
    const solvedInput = document.getElementById('puzzle_solved');
    
    const locked      = <?= !empty($isLocked) ? 'true' : 'false' ?>;
    const isTestMode  = <?= !empty($isTest) ? 'true' : 'false' ?>;

    if (locked) return;

    if (isTestMode) {
        solvedInput.value = '1';
        submitBtn.disabled = false;
        status.textContent = 'Тестовый режим';
        status.classList.remove('bad'); status.classList.add('ok');
        return;
    }

    let selected = null;
    puzzle.addEventListener('click', (e) => {
        const tile = e.target.closest('.tile');
        if (!tile) return;
        if (!selected) { selected = tile; tile.classList.add('selected'); return; }
        if (selected === tile) { tile.classList.remove('selected'); selected = null; return; }
        swapTiles(selected, tile);
        selected.classList.remove('selected');
        selected = null;
        checkSolved();
    });

    function swapTiles(a, b) {
        const aBg = a.style.backgroundImage, bBg = b.style.backgroundImage;
        a.style.backgroundImage = bBg; b.style.backgroundImage = aBg;
        const aP = a.dataset.piece, bP = b.dataset.piece;
        a.dataset.piece = bP; b.dataset.piece = aP;
    }

    function checkSolved() {
        const tiles = puzzle.querySelectorAll('.tile');
        let ok = true;
        tiles.forEach((t, i) => { if (t.dataset.piece !== String(i)) ok = false; });
        if (ok) {
            status.textContent = 'Пазл собран!';
            status.classList.remove('bad'); status.classList.add('ok');
            solvedInput.value = '1'; submitBtn.disabled = false;
        } else {
            status.textContent = 'Пазл не собран';
            status.classList.remove('ok'); status.classList.add('bad');
            solvedInput.value = '0'; submitBtn.disabled = true;
        }
    }
})();
</script>
</body>
</html>