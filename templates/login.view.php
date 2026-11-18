<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$errors       = $errors       ?? [];
$login        = $login        ?? '';
$order        = $order        ?? range(0, 3);
$isLocked     = $isLocked     ?? false;
$lockMinutes  = $lockMinutes  ?? 0;
$isTest       = $isTest       ?? false;

$captchaDir = __DIR__ . '/../public/captcha';
$pieces = [];
$sample = null;
if (is_dir($captchaDir)) {
    $files = [];
    foreach (scandir($captchaDir) ?: [] as $f) {
        if ($f[0] === '.') continue;
        if (!is_file($captchaDir . '/' . $f)) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) continue;
        $files[] = $f;
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($files as $f) {
        $base = strtolower(pathinfo($f, PATHINFO_FILENAME));
        if (preg_match('/sample|obraz|origin|full|preview|reference|whole|itog/', $base)) {
            if ($sample === null) $sample = 'captcha/' . rawurlencode($f);
            continue;
        }
        $pieces[] = 'captcha/' . rawurlencode($f);
    }
    $pieces = array_slice($pieces, 0, 4);
}
$useImages = count($pieces) === 4;
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Вход в систему | Admin Panel</title>
<style>
:root{
  --indigo:#4f46e5; --indigo-d:#4338ca; --violet:#7c3aed; --blue:#2563eb;
  --emerald:#10b981; --amber:#f59e0b; --red:#ef4444;
  --ink:#0f172a; --ink-2:#334155; --muted:#64748b; --faint:#94a3b8;
  --line:#cbd5e1; --line-2:#e2e8f0; --surface:#ffffff; --surface-2:#f8fafc;
  --ring:rgba(79,70,229,.16);
  --tile:clamp(56px,17vw,64px);
  --r:14px;
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0; padding:clamp(16px,4vw,40px);
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
  color:var(--ink); background:#eef2f7;
  display:flex; align-items:center; justify-content:center;
  -webkit-font-smoothing:antialiased;
}

/* ---------- фон: mesh + дрейфующий свет + зерно ---------- */
.bg{position:fixed; inset:0; z-index:0; overflow:hidden; pointer-events:none}
.bg::after{
  content:""; position:absolute; inset:0;
  background-image:radial-gradient(rgba(15,23,42,.05) 1px, transparent 1px);
  background-size:22px 22px; opacity:.6;
}
.blob{position:absolute; border-radius:50%; filter:blur(70px); opacity:.5}
.b1{width:46vmax;height:46vmax;left:-14vmax;top:-16vmax;
    background:radial-gradient(circle at 30% 30%,#c7d2fe,#818cf8 60%,transparent 72%);
    animation:drift1 26s ease-in-out infinite}
.b2{width:38vmax;height:38vmax;right:-12vmax;top:-8vmax;
    background:radial-gradient(circle at 60% 40%,#ddd6fe,#a78bfa 60%,transparent 72%);
    animation:drift2 32s ease-in-out infinite}
.b3{width:42vmax;height:42vmax;left:22vw;bottom:-22vmax;
    background:radial-gradient(circle at 50% 50%,#bae6fd,#60a5fa 55%,transparent 72%);
    opacity:.38; animation:drift3 38s ease-in-out infinite}
@keyframes drift1{0%,100%{transform:translate3d(0,0,0)}50%{transform:translate3d(5vw,4vh,0)}}
@keyframes drift2{0%,100%{transform:translate3d(0,0,0)}50%{transform:translate3d(-6vw,5vh,0)}}
@keyframes drift3{0%,100%{transform:translate3d(0,0,0)}50%{transform:translate3d(4vw,-5vh,0)}}

/* ---------- карточка ---------- */
.card{
  position:relative; z-index:1; width:100%; max-width:452px;
  background:var(--surface); border:1px solid rgba(203,213,225,.9);
  border-radius:20px; overflow:hidden;
  box-shadow:0 1px 2px rgba(15,23,42,.04),0 12px 28px -10px rgba(15,23,42,.16),0 40px 70px -40px rgba(79,70,229,.28);
  animation:rise .55s cubic-bezier(.22,1,.36,1) both;
}
@keyframes rise{from{opacity:0;transform:translateY(14px) scale(.985)}to{opacity:1;transform:none}}
.card::before{content:"";display:block;height:4px;
  background:linear-gradient(90deg,var(--indigo),var(--violet) 45%,var(--blue) 78%,var(--emerald))}
.inner{padding:clamp(22px,5vw,32px)}

.brand{display:flex;align-items:center;gap:9px;margin-bottom:16px}
.mark{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;flex:none;
  background:linear-gradient(140deg,var(--indigo),var(--violet));
  box-shadow:0 5px 14px -4px rgba(79,70,229,.6)}
.mark svg{width:16px;height:16px;stroke:#fff;fill:none;stroke-width:2.1;stroke-linecap:round;stroke-linejoin:round}
.brand span{font-size:11px;font-weight:800;letter-spacing:.14em;color:var(--muted);text-transform:uppercase}
h1{margin:0 0 6px;font-size:clamp(21px,5vw,25px);font-weight:800;letter-spacing:-.02em}
.sub{margin:0 0 22px;font-size:13.5px;color:var(--muted);line-height:1.5}

/* ---------- сообщения ---------- */
.msg{display:flex;gap:10px;align-items:flex-start;padding:11px 13px;border-radius:11px;
  font-size:13px;line-height:1.5;margin-bottom:12px;animation:rise .3s ease both}
.msg svg{width:16px;height:16px;flex:none;margin-top:1px;fill:none;stroke-width:2.1;stroke-linecap:round;stroke-linejoin:round}
.msg-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.msg-err svg{stroke:var(--red)}
.msg-lock{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.msg-lock svg{stroke:var(--amber)}
.msg ul{margin:0;padding-left:16px}
.msg li+li{margin-top:3px}
.count{font-variant-numeric:tabular-nums;font-weight:800}

/* ---------- поля ---------- */
.field{margin-bottom:16px;animation:rise .45s cubic-bezier(.22,1,.36,1) both}
.field:nth-of-type(1){animation-delay:.05s}
.field:nth-of-type(2){animation-delay:.1s}
.field label{display:block;font-size:12px;font-weight:700;color:var(--ink-2);
  letter-spacing:.01em;margin-bottom:7px}
.control{position:relative;display:flex;align-items:center}
.control input{
  width:100%;height:46px;padding:0 14px;font-size:15px;font-family:inherit;color:var(--ink);
  background:var(--surface);border:1px solid var(--line);border-radius:11px;
  transition:border-color .16s,box-shadow .16s,background .16s;
}
.control input::placeholder{color:var(--faint)}
.control input:hover{border-color:#b6c2d2}
.control input:focus{outline:none;border-color:var(--indigo);box-shadow:0 0 0 4px var(--ring)}
.control input:disabled{background:var(--surface-2);color:var(--faint);cursor:not-allowed}
.control.has-toggle input{padding-right:46px}
.toggle{position:absolute;right:5px;width:36px;height:36px;display:grid;place-items:center;
  border:0;background:transparent;border-radius:9px;cursor:pointer;color:var(--muted);
  transition:background .15s,color .15s}
.toggle:hover{background:var(--surface-2);color:var(--indigo)}
.toggle:focus-visible{outline:2px solid var(--indigo);outline-offset:1px}
.toggle svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
.toggle .off{display:none}
.toggle[aria-pressed="true"] .on{display:none}
.toggle[aria-pressed="true"] .off{display:block}
.caps{display:none;margin-top:7px;font-size:11.5px;font-weight:600;color:#92400e}
.caps.show{display:block}

/* ---------- пазл ---------- */
.puzzle{border:1px solid var(--line-2);background:var(--surface-2);border-radius:var(--r);
  padding:15px;margin:4px 0 18px;animation:rise .5s cubic-bezier(.22,1,.36,1) .15s both}
.p-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:13px}
.p-title{font-size:12.5px;font-weight:800;color:var(--ink)}
.chip{font-size:10.5px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;
  padding:4px 9px;border-radius:999px;background:#e2e8f0;color:var(--muted);
  transition:background .2s,color .2s}
.chip.ok{background:#d1fae5;color:#065f46}
.p-cols{display:flex;gap:22px;align-items:flex-start;flex-wrap:wrap}
.p-lab{font-size:9.5px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;
  color:var(--faint);margin-bottom:6px}
.sample{width:78px;height:78px;object-fit:cover;display:block;border-radius:9px;
  border:1px solid var(--line);background:#fff}
.grid4{display:grid;grid-template-columns:repeat(2,39px);grid-template-rows:repeat(2,39px);
  width:78px;height:78px;border-radius:9px;overflow:hidden;border:1px solid var(--line);background:#fff}
.grid4 img{width:39px;height:39px;object-fit:cover;display:block}
.grid4.nums span{display:grid;place-items:center;background:#eef2ff;color:var(--indigo);
  font-weight:800;font-size:13px}
.slot{background:#fff;border:1px solid var(--line-2);display:grid;place-items:center;overflow:hidden;
  transition:background .18s,border-color .18s}
.slot img{width:39px;height:39px;object-fit:cover;display:block;animation:pop .22s cubic-bezier(.34,1.56,.64,1) both}
.slot b{color:#fff;font-size:13px;font-weight:800;animation:pop .22s cubic-bezier(.34,1.56,.64,1) both}
.slot.filled{background:var(--indigo);border-color:var(--indigo)}
@keyframes pop{from{opacity:0;transform:scale(.7)}to{opacity:1;transform:none}}
.p-frag{margin-top:15px}
.tiles{display:flex;gap:8px;flex-wrap:wrap}
.tile{width:var(--tile);height:var(--tile);padding:0;border-radius:10px;overflow:hidden;
  border:1px solid var(--line);background:#eef2ff;color:var(--indigo);
  font-size:19px;font-weight:800;font-family:inherit;cursor:pointer;
  transition:transform .14s,border-color .14s,box-shadow .14s,opacity .18s}
.tile img{width:100%;height:100%;object-fit:cover;display:block;pointer-events:none}
.tile:hover:not(:disabled){transform:translateY(-2px);border-color:var(--indigo);
  box-shadow:0 6px 14px -6px rgba(79,70,229,.6)}
.tile:active:not(:disabled){transform:translateY(0) scale(.96)}
.tile:focus-visible{outline:2px solid var(--indigo);outline-offset:2px}
.tile:disabled{opacity:.24;cursor:default}
.p-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:11px}
.hint{font-size:11.5px;color:var(--faint);font-variant-numeric:tabular-nums}
.hint.ok{color:#065f46;font-weight:700}
.reset{border:0;background:transparent;padding:2px 0;cursor:pointer;font-family:inherit;
  font-size:11.5px;font-weight:700;color:var(--indigo);border-bottom:1px solid transparent}
.reset:hover{border-bottom-color:var(--indigo)}
.reset:focus-visible{outline:2px solid var(--indigo);outline-offset:3px;border-radius:3px}

/* ---------- кнопка ---------- */
.submit{position:relative;width:100%;height:48px;border:0;border-radius:12px;cursor:pointer;
  font-family:inherit;font-size:14.5px;font-weight:800;letter-spacing:.01em;color:#fff;
  background:linear-gradient(135deg,var(--indigo),var(--violet));
  box-shadow:0 8px 20px -8px rgba(79,70,229,.85);
  transition:transform .14s,box-shadow .18s,filter .18s;
  animation:rise .5s cubic-bezier(.22,1,.36,1) .2s both}
.submit:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 12px 26px -8px rgba(79,70,229,.95)}
.submit:active:not(:disabled){transform:translateY(0) scale(.99)}
.submit:focus-visible{outline:2px solid var(--indigo);outline-offset:3px}
.submit:disabled{background:#c7d2fe;box-shadow:none;cursor:not-allowed}
.submit .lbl{display:inline-flex;align-items:center;gap:8px}
.submit.busy .lbl{visibility:hidden}
.submit.busy::after{content:"";position:absolute;inset:0;margin:auto;width:18px;height:18px;
  border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;
  animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.foot{margin:15px 0 0;text-align:center;font-size:11px;color:var(--faint);line-height:1.5}

::-webkit-scrollbar{width:10px;height:10px}
::-webkit-scrollbar-track{background:#e8edf4}
::-webkit-scrollbar-thumb{background:#c3cddd;border-radius:99px;border:2px solid #e8edf4}
::-webkit-scrollbar-thumb:hover{background:var(--indigo)}
*{scrollbar-width:thin;scrollbar-color:#c3cddd #e8edf4}

@media (max-width:380px){ .p-cols{gap:16px} }
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation:none!important;transition:none!important}
}
</style>
</head>
<body>

<div class="bg" aria-hidden="true">
  <div class="blob b1"></div><div class="blob b2"></div><div class="blob b3"></div>
</div>

<main class="card">
  <div class="inner">

    <div class="brand">
      <span class="mark">
        <svg viewBox="0 0 24 24"><path d="M12 3l7 3v6c0 4.2-2.9 7.6-7 9-4.1-1.4-7-4.8-7-9V6l7-3z"/></svg>
      </span>
      <span>Admin Panel</span>
    </div>

    <h1>Вход в систему</h1>
    <p class="sub">Введите учётные данные и соберите пазл, чтобы подтвердить, что вы не робот.</p>

    <?php if ($errors): ?>
      <div class="msg msg-err" role="alert">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/></svg>
        <div>
          <?php if (count($errors) === 1): ?>
            <?= $e($errors[0]) ?>
          <?php else: ?>
            <ul><?php foreach ($errors as $er): ?><li><?= $e($er) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($isLocked): ?>
      <div class="msg msg-lock" role="status">
        <svg viewBox="0 0 24 24"><rect x="4.5" y="10.5" width="15" height="9.5" rx="2"/><path d="M8 10.5V7.5a4 4 0 018 0v3"/></svg>
        <div>Вход временно заблокирован. Разблокировка через <span class="count" id="countdown">—</span></div>
      </div>
    <?php endif; ?>

    <form method="post" id="loginForm" novalidate>
      <input type="hidden" name="csrf" value="<?= $e(csrf_token()) ?>">
      <input type="hidden" name="puzzle_order" id="puzzle_order" value="">
      <input type="hidden" name="puzzle_solved" id="puzzle_solved" value="<?= $isTest ? '1' : '0' ?>">
      <?php if (!empty($_SESSION['puzzle_nonce'])): ?>
        <input type="hidden" name="puzzle_nonce" value="<?= $e($_SESSION['puzzle_nonce']) ?>">
      <?php endif; ?>

      <div class="field">
        <label for="login">Логин</label>
        <div class="control">
          <input type="text" id="login" name="login" value="<?= $e($login) ?>"
                 placeholder="Введите логин" autocomplete="username"
                 spellcheck="false" autocapitalize="none" required
                 <?= $isLocked ? 'disabled' : 'autofocus' ?>>
        </div>
      </div>

      <div class="field">
        <label for="password">Пароль</label>
        <div class="control has-toggle">
          <input type="password" id="password" name="password"
                 placeholder="Введите пароль" autocomplete="current-password" required
                 <?= $isLocked ? 'disabled' : '' ?>>
          <button type="button" class="toggle" id="togglePass" aria-pressed="false"
                  aria-label="Показать пароль" <?= $isLocked ? 'disabled' : '' ?>>
            <svg class="on" viewBox="0 0 24 24"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="off" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.7a3 3 0 004.2 4.2"/><path d="M9.4 5.9A9.6 9.6 0 0112 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 01-3.3 4.1M6.3 7.9A17 17 0 002.5 12S6 18.5 12 18.5c.9 0 1.7-.1 2.5-.4"/></svg>
          </button>
        </div>
        <div class="caps" id="caps">Включён Caps Lock</div>
      </div>

      <section class="puzzle" aria-labelledby="pTitle">
        <div class="p-head">
          <span class="p-title" id="pTitle">Проверка безопасности</span>
          <span class="chip" id="pChip" aria-live="polite">Не собрано</span>
        </div>

        <div class="p-cols">
          <div>
            <div class="p-lab">Образец</div>
            <?php if ($sample): ?>
              <img class="sample" src="<?= $e($sample) ?>" alt="Образец собранного пазла">
            <?php elseif ($useImages): ?>
              <div class="grid4" role="img" aria-label="Образец собранного пазла">
                <?php for ($i = 0; $i < 4; $i++): ?>
                  <img src="<?= $e($pieces[$i]) ?>" alt="">
                <?php endfor; ?>
              </div>
            <?php else: ?>
              <div class="grid4 nums" role="img" aria-label="Образец: 1, 2, 3, 4">
                <?php for ($i = 1; $i <= 4; $i++): ?><span><?= $i ?></span><?php endfor; ?>
              </div>
            <?php endif; ?>
          </div>

          <div>
            <div class="p-lab">Ваш пазл</div>
            <div class="grid4" id="target">
              <div class="slot"></div><div class="slot"></div>
              <div class="slot"></div><div class="slot"></div>
            </div>
          </div>
        </div>

        <div class="p-frag">
          <div class="p-lab">Фрагменты — нажимайте по порядку</div>
          <div class="tiles" id="pool">
            <?php foreach (array_values($order) as $i => $pid): ?>
              <button type="button" class="tile" data-id="<?= (int)$pid ?>"
                      aria-label="Фрагмент <?= $i + 1 ?>" <?= $isLocked ? 'disabled' : '' ?>>
                <?php if ($useImages): ?>
                  <img src="<?= $e($pieces[(int)$pid]) ?>" alt="">
                <?php else: ?>
                  <?= (int)$pid + 1 ?>
                <?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="p-foot">
          <span class="hint" id="pHint">Выбрано 0 из 4</span>
          <button type="button" class="reset" id="pReset">Сбросить</button>
        </div>
      </section>

      <button class="submit" type="submit" id="submitBtn" <?= $isLocked ? 'disabled' : '' ?>>
        <span class="lbl">Войти</span>
      </button>
    </form>

    <p class="foot">Все попытки входа записываются в журнал безопасности</p>
  </div>
</main>

<script>
(function () {
  var locked  = <?= $isLocked ? 'true' : 'false' ?>;
  var isTest  = <?= $isTest ? 'true' : 'false' ?>;
  var leftSec = <?= max(0, (int)$lockMinutes) * 60 ?>;

  var form   = document.getElementById('loginForm');
  var submit = document.getElementById('submitBtn');
  var pool   = document.getElementById('pool');
  var tiles  = Array.prototype.slice.call(pool.querySelectorAll('.tile'));
  var slots  = Array.prototype.slice.call(document.querySelectorAll('#target .slot'));
  var hOrder = document.getElementById('puzzle_order');
  var hSolv  = document.getElementById('puzzle_solved');
  var chip   = document.getElementById('pChip');
  var hint   = document.getElementById('pHint');
  var seq    = [];

  function sync() {
    var done = seq.length === 4;
    hOrder.value = JSON.stringify(seq);
    hSolv.value  = (done || isTest) ? '1' : '0';
    chip.textContent = done ? 'Готово' : 'Не собрано';
    chip.classList.toggle('ok', done);
    hint.textContent = done ? 'Пазл собран' : 'Выбрано ' + seq.length + ' из 4';
    hint.classList.toggle('ok', done);
    if (!locked) submit.disabled = !(done || isTest);
  }

  tiles.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled || seq.length >= 4 || locked) return;
      seq.push(parseInt(btn.dataset.id, 10));
      btn.disabled = true;
      var slot = slots[seq.length - 1];
      slot.innerHTML = btn.innerHTML;
      slot.classList.add('filled');
      sync();
    });
  });

  document.getElementById('pReset').addEventListener('click', function () {
    if (locked) return;
    seq = [];
    slots.forEach(function (s) { s.innerHTML = ''; s.classList.remove('filled'); });
    tiles.forEach(function (b) { b.disabled = false; });
    sync();
  });

  var pass = document.getElementById('password');
  var tgl  = document.getElementById('togglePass');
  tgl.addEventListener('click', function () {
    var show = pass.type === 'password';
    pass.type = show ? 'text' : 'password';
    tgl.setAttribute('aria-pressed', show ? 'true' : 'false');
    tgl.setAttribute('aria-label', show ? 'Скрыть пароль' : 'Показать пароль');
    pass.focus();
  });

  var caps = document.getElementById('caps');
  function capsCheck(e) {
    if (typeof e.getModifierState !== 'function') return;
    caps.classList.toggle('show', e.getModifierState('CapsLock'));
  }
  pass.addEventListener('keydown', capsCheck);
  pass.addEventListener('keyup', capsCheck);
  pass.addEventListener('blur', function () { caps.classList.remove('show'); });

  form.addEventListener('submit', function (e) {
    if (submit.disabled) { e.preventDefault(); return; }
    submit.classList.add('busy');
    submit.disabled = true;
    setTimeout(function () {
      submit.classList.remove('busy');
      sync();
    }, 8000);
  });

  if (locked && leftSec > 0) {
    var out = document.getElementById('countdown');
    (function tick() {
      var m = Math.floor(leftSec / 60), s = leftSec % 60;
      out.textContent = m + ':' + (s < 10 ? '0' : '') + s;
      if (leftSec <= 0) { window.location.reload(); return; }
      leftSec--;
      setTimeout(tick, 1000);
    })();
  }

  sync();
})();
</script>
</body>
</html>