<?php $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="ru" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Журнал безопасности | Admin Panel</title>
<style>
:root{
  --indigo:#6366f1; --indigo-d:#4f46e5; --violet:#a855f7; --blue:#38bdf8;
  --acid:#4ade80; --green:#22c55e; --amber:#fbbf24; --red:#f43f5e; --magenta:#ec4899;
  --bg:#080b16; --bg2:#0d1220; --surface:#121a2c; --surface2:#18223a; --overlay:#1e2942;
  --line:#26314d; --line2:#33405f;
  --ink:#e9eefb; --ink2:#c3cde6; --muted:#8b98b8; --faint:#66739a;
  --scrim:rgba(6,9,18,.72);
  --ok:var(--acid); --bad:var(--red); --warn:var(--amber); --info:var(--blue);
  --r:14px; --rail:64px;
  --shadow:0 18px 44px -22px rgba(0,0,0,.9);
}
html[data-theme="light"]{
  --bg:#eef1f8; --bg2:#e6eaf4; --surface:#ffffff; --surface2:#f5f7fc; --overlay:#ffffff;
  --line:#dbe2ef; --line2:#c6d0e4;
  --ink:#0d1424; --ink2:#28324a; --muted:#5c6884; --faint:#8592ad;
  --scrim:rgba(20,26,44,.42);
  --indigo:#4f46e5; --violet:#7c3aed; --blue:#0284c7; --acid:#16a34a; --green:#15803d;
  --amber:#b45309; --red:#e11d48; --magenta:#be185d;
  --shadow:0 16px 36px -22px rgba(20,30,60,.5);
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;background:var(--bg);color:var(--ink);
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
  -webkit-font-smoothing:antialiased;overflow-x:hidden;
}
/* ---------- фон ---------- */
.bg{position:fixed;inset:0;z-index:0;pointer-events:none;overflow:hidden}
.bg .mesh{position:absolute;inset:-20%;
  background:
    radial-gradient(42vmax 42vmax at 12% 8%, color-mix(in srgb,var(--indigo) 42%,transparent), transparent 62%),
    radial-gradient(38vmax 38vmax at 88% 4%, color-mix(in srgb,var(--violet) 34%,transparent), transparent 60%),
    radial-gradient(46vmax 46vmax at 62% 96%, color-mix(in srgb,var(--blue) 26%,transparent), transparent 64%),
    radial-gradient(30vmax 30vmax at 30% 70%, color-mix(in srgb,var(--magenta) 20%,transparent), transparent 66%);
  filter:blur(30px);opacity:.55;animation:mesh 34s ease-in-out infinite}
html[data-theme="light"] .bg .mesh{opacity:.3}
@keyframes mesh{0%,100%{transform:translate3d(0,0,0) scale(1)}50%{transform:translate3d(-3%,2%,0) scale(1.08)}}
.bg .grid{position:absolute;inset:0;opacity:.5;
  background-image:linear-gradient(color-mix(in srgb,var(--line) 60%,transparent) 1px,transparent 1px),
                   linear-gradient(90deg,color-mix(in srgb,var(--line) 60%,transparent) 1px,transparent 1px);
  background-size:46px 46px;
  mask-image:radial-gradient(circle at 50% 22%,#000 20%,transparent 78%)}
.bg .grain{position:absolute;inset:0;opacity:.05;
  background-image:radial-gradient(var(--ink) .6px,transparent .6px);background-size:3px 3px}
.scan{position:absolute;left:0;right:0;height:180px;
  background:linear-gradient(180deg,transparent,color-mix(in srgb,var(--indigo) 14%,transparent),transparent);
  animation:scan 11s linear infinite}
@keyframes scan{0%{top:-180px}100%{top:100%}}

/* ---------- шапка ---------- */
header{position:fixed;top:0;left:0;right:0;height:var(--rail);z-index:40;
  display:flex;align-items:center;gap:14px;padding:0 clamp(12px,2.4vw,24px);
  background:color-mix(in srgb,var(--bg2) 88%,transparent);
  backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}
.brand{display:flex;align-items:center;gap:10px;min-width:0}
.sig{width:32px;height:32px;border-radius:10px;flex:none;display:grid;place-items:center;
  background:linear-gradient(140deg,var(--indigo),var(--violet) 60%,var(--magenta));
  box-shadow:0 0 0 1px color-mix(in srgb,var(--violet) 50%,transparent),0 8px 22px -8px var(--indigo)}
.sig svg{width:17px;height:17px;fill:none;stroke:#fff;stroke-width:2.1;stroke-linecap:round;stroke-linejoin:round}
.brand b{font-size:13px;font-weight:900;letter-spacing:.15em;white-space:nowrap}
.brand small{display:block;font-size:9.5px;letter-spacing:.16em;color:var(--faint);font-weight:700}
.pulse{display:inline-flex;align-items:center;gap:7px;padding:5px 11px;border-radius:999px;
  font-size:11px;font-weight:800;letter-spacing:.06em;
  background:color-mix(in srgb,var(--acid) 15%,transparent);color:var(--ok);
  border:1px solid color-mix(in srgb,var(--acid) 38%,transparent)}
.pulse.off{background:color-mix(in srgb,var(--muted) 14%,transparent);color:var(--muted);
  border-color:var(--line2)}
.dot{width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 0 0 currentColor;animation:beat 1.9s infinite}
.pulse.off .dot{animation:none}
@keyframes beat{0%{box-shadow:0 0 0 0 color-mix(in srgb,currentColor 70%,transparent)}70%{box-shadow:0 0 0 7px transparent}100%{box-shadow:0 0 0 0 transparent}}
.spacer{flex:1}
.hbtn{display:inline-flex;align-items:center;gap:7px;height:34px;padding:0 12px;border-radius:9px;
  border:1px solid var(--line2);background:var(--surface);color:var(--ink2);
  font:inherit;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;
  transition:border-color .16s,color .16s,background .16s,transform .12s}
.hbtn:hover{border-color:var(--indigo);color:var(--ink);transform:translateY(-1px)}
.hbtn:active{transform:translateY(0)}
.hbtn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.hbtn.on{border-color:color-mix(in srgb,var(--acid) 50%,transparent);color:var(--ok);
  background:color-mix(in srgb,var(--acid) 12%,transparent)}
.hbtn.danger{border-color:color-mix(in srgb,var(--red) 45%,transparent);color:var(--bad);
  background:color-mix(in srgb,var(--red) 10%,transparent)}
.who{font-size:12px;color:var(--muted);white-space:nowrap}
.who b{color:var(--ink)}

/* ---------- пульт управления ---------- */
.rail{position:sticky;top:var(--rail);z-index:30;margin:0 0 18px;
  background:color-mix(in srgb,var(--surface) 92%,transparent);backdrop-filter:blur(12px);
  border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);
  padding:14px clamp(12px,2vw,18px);display:grid;gap:12px;
  grid-template-columns:repeat(auto-fit,minmax(158px,1fr))}
.ctl{display:flex;flex-direction:column;gap:6px;min-width:0}
.ctl>span{font-size:9.5px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--faint)}
select,input[type=text],input[type=search]{height:36px;width:100%;padding:0 11px;border-radius:9px;
  border:1px solid var(--line2);background:var(--bg2);color:var(--ink);font:inherit;font-size:12.5px;
  transition:border-color .16s,box-shadow .16s}
html[data-theme="light"] select,html[data-theme="light"] input[type=text],html[data-theme="light"] input[type=search]{background:var(--surface2)}
select:focus,input:focus{outline:none;border-color:var(--indigo);box-shadow:0 0 0 3px color-mix(in srgb,var(--indigo) 26%,transparent)}
.seg{display:flex;border:1px solid var(--line2);border-radius:9px;overflow:hidden;height:36px;background:var(--bg2)}
.seg button{flex:1;border:0;background:transparent;color:var(--muted);font:inherit;font-size:11.5px;font-weight:800;cursor:pointer;transition:.15s}
.seg button+button{border-left:1px solid var(--line2)}
.seg button[aria-pressed="true"]{background:linear-gradient(135deg,var(--indigo),var(--violet));color:#fff}
.seg button:hover:not([aria-pressed="true"]){color:var(--ink);background:var(--overlay)}
.rng{display:flex;align-items:center;gap:9px;height:36px}
input[type=range]{-webkit-appearance:none;appearance:none;flex:1;height:5px;border-radius:99px;background:var(--line2);outline:none}
input[type=range]::-webkit-slider-thumb{-webkit-appearance:none;width:16px;height:16px;border-radius:50%;
  background:linear-gradient(140deg,var(--indigo),var(--violet));cursor:pointer;
  box-shadow:0 0 0 3px color-mix(in srgb,var(--indigo) 24%,transparent)}
input[type=range]::-moz-range-thumb{width:16px;height:16px;border:0;border-radius:50%;background:var(--indigo);cursor:pointer}
.rng b{font-size:12px;font-variant-numeric:tabular-nums;color:var(--ink);min-width:34px;text-align:right}
.step{display:flex;align-items:center;height:36px;border:1px solid var(--line2);border-radius:9px;overflow:hidden;background:var(--bg2)}
.step button{width:34px;height:100%;border:0;background:transparent;color:var(--ink2);font:inherit;font-size:15px;font-weight:800;cursor:pointer}
.step button:hover{background:var(--overlay);color:var(--indigo)}
.step b{flex:1;text-align:center;font-size:12.5px;font-variant-numeric:tabular-nums}
.sw{display:flex;align-items:center;gap:9px;height:36px;font-size:12px;color:var(--ink2);cursor:pointer;user-select:none}
.sw input{position:absolute;opacity:0;width:0;height:0}
.track{width:40px;height:22px;border-radius:99px;background:var(--line2);position:relative;transition:background .2s;flex:none}
.track::after{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:var(--muted);transition:.2s}
.sw input:checked+.track{background:linear-gradient(135deg,var(--indigo),var(--violet))}
.sw input:checked+.track::after{left:21px;background:#fff}
.sw input:focus-visible+.track{box-shadow:0 0 0 3px color-mix(in srgb,var(--indigo) 30%,transparent)}
.act{display:flex;gap:8px;align-items:flex-end}
.btn{height:36px;padding:0 13px;border-radius:9px;border:1px solid var(--line2);background:var(--surface2);
  color:var(--ink2);font:inherit;font-size:12px;font-weight:800;cursor:pointer;white-space:nowrap;
  display:inline-flex;align-items:center;gap:7px;text-decoration:none;transition:.15s}
.btn:hover{border-color:var(--indigo);color:var(--ink);transform:translateY(-1px)}
.btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.btn.pri{background:linear-gradient(135deg,var(--indigo),var(--violet));border-color:transparent;color:#fff;
  box-shadow:0 8px 20px -10px var(--indigo)}
.btn.warn{border-color:color-mix(in srgb,var(--amber) 45%,transparent);color:var(--warn);
  background:color-mix(in srgb,var(--amber) 10%,transparent)}

/* ---------- раскладка ---------- */
main{position:relative;z-index:10;padding:calc(var(--rail) + 20px) clamp(12px,2.4vw,24px) 60px;max-width:1560px;margin:0 auto}
.title{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin:0 0 16px}
h1{margin:0;font-size:clamp(21px,3.2vw,30px);font-weight:900;letter-spacing:-.025em}
.title p{margin:5px 0 0;font-size:13px;color:var(--muted);max-width:62ch;line-height:1.55}
.card{background:color-mix(in srgb,var(--surface) 94%,transparent);border:1px solid var(--line);
  border-radius:var(--r);box-shadow:var(--shadow);padding:16px clamp(12px,1.6vw,18px);min-width:0}
.card>h2{margin:0 0 12px;font-size:12px;font-weight:900;letter-spacing:.11em;text-transform:uppercase;color:var(--muted);
  display:flex;align-items:center;justify-content:space-between;gap:10px}
.card>h2 em{font-style:normal;font-size:11px;letter-spacing:0;text-transform:none;color:var(--faint);font-weight:700}

.kpis{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(178px,1fr));margin-bottom:14px}
.kpi{position:relative;overflow:hidden;background:color-mix(in srgb,var(--surface) 94%,transparent);
  border:1px solid var(--line);border-radius:var(--r);padding:13px 14px;box-shadow:var(--shadow);
  animation:up .5s cubic-bezier(.22,1,.36,1) both}
.kpi::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--c)}
.kpi::after{content:"";position:absolute;right:-30px;top:-30px;width:110px;height:110px;border-radius:50%;
  background:radial-gradient(circle,color-mix(in srgb,var(--c) 26%,transparent),transparent 70%)}
@keyframes up{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
.kpi .lab{font-size:9.5px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--faint)}
.kpi .val{font-size:clamp(22px,3vw,30px);font-weight:900;letter-spacing:-.03em;margin-top:3px;
  font-variant-numeric:tabular-nums;color:var(--c)}
.kpi .sub{font-size:11px;color:var(--muted);margin-top:2px}
.kpi svg{display:block;width:100%;height:26px;margin-top:6px;overflow:visible}

.grid2{display:grid;gap:14px;grid-template-columns:minmax(0,2.05fr) minmax(0,1fr);margin-bottom:14px}
.grid3{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));margin-bottom:14px}
@media(max-width:1040px){.grid2{grid-template-columns:1fr}}

.chartwrap{position:relative}
.chartwrap svg{display:block;width:100%;height:266px}
.legend{display:flex;gap:14px;flex-wrap:wrap;margin-top:10px;font-size:11.5px;color:var(--muted)}
.legend i{display:inline-block;width:11px;height:11px;border-radius:3px;margin-right:6px;vertical-align:-1px}
.tip{position:absolute;pointer-events:none;opacity:0;transform:translate(-50%,-118%);
  background:var(--overlay);border:1px solid var(--line2);border-radius:10px;padding:8px 10px;
  font-size:11.5px;line-height:1.6;white-space:nowrap;box-shadow:var(--shadow);transition:opacity .12s;z-index:5}
.tip b{color:var(--ink)}
.tip.show{opacity:1}

table{width:100%;border-collapse:collapse}
th,td{padding:9px 10px;text-align:left;font-size:12.5px;border-bottom:1px solid var(--line)}
th{font-size:9.5px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;color:var(--faint);
  position:sticky;top:0;background:var(--surface);z-index:2}
tbody tr{transition:background .14s}
tbody tr:hover{background:var(--surface2)}
tbody tr:last-child td{border-bottom:0}
td.num{font-variant-numeric:tabular-nums;color:var(--muted)}
.scroll{max-height:340px;overflow:auto;border-radius:10px}
.tag{display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;font-size:10.5px;font-weight:800;letter-spacing:.04em}
.tag.ok{background:color-mix(in srgb,var(--acid) 16%,transparent);color:var(--ok)}
.tag.bad{background:color-mix(in srgb,var(--red) 16%,transparent);color:var(--bad)}
.tag.adm{background:color-mix(in srgb,var(--violet) 18%,transparent);color:var(--violet)}
.tag.usr{background:color-mix(in srgb,var(--blue) 16%,transparent);color:var(--info)}
.tag.lock{background:color-mix(in srgb,var(--amber) 18%,transparent);color:var(--warn)}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11.5px}
.ua{max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--faint);font-size:11px}
tr.fresh{animation:fresh 1.5s ease-out}
@keyframes fresh{0%{background:color-mix(in srgb,var(--red) 26%,transparent)}100%{background:transparent}}
.empty{padding:26px 10px;text-align:center;color:var(--faint);font-size:12.5px}

.bar{height:6px;border-radius:99px;background:var(--line2);overflow:hidden;margin-top:5px}
.bar i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,var(--magenta),var(--violet))}
.pager{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:12px;font-size:12px;color:var(--muted)}
.pager .nav{display:flex;gap:6px}
.pg{min-width:32px;height:32px;padding:0 9px;border-radius:8px;border:1px solid var(--line2);
  background:var(--surface2);color:var(--ink2);font:inherit;font-size:12px;font-weight:700;cursor:pointer}
.pg:hover:not(:disabled){border-color:var(--indigo);color:var(--ink)}
.pg[aria-current="true"]{background:linear-gradient(135deg,var(--indigo),var(--violet));border-color:transparent;color:#fff}
.pg:disabled{opacity:.4;cursor:not-allowed}

.banner{display:flex;gap:10px;align-items:flex-start;padding:11px 13px;border-radius:11px;font-size:12.5px;margin-bottom:14px;line-height:1.55}
.banner.ok{background:color-mix(in srgb,var(--acid) 13%,transparent);border:1px solid color-mix(in srgb,var(--acid) 34%,transparent);color:var(--ok)}
.banner.bad{background:color-mix(in srgb,var(--red) 13%,transparent);border:1px solid color-mix(in srgb,var(--red) 34%,transparent);color:var(--bad)}
.banner svg{width:16px;height:16px;flex:none;margin-top:1px;fill:none;stroke:currentColor;stroke-width:2.1;stroke-linecap:round;stroke-linejoin:round}

.toasts{position:fixed;right:16px;bottom:16px;z-index:80;display:flex;flex-direction:column;gap:9px;max-width:340px}
.toast{display:flex;gap:9px;align-items:flex-start;padding:11px 13px;border-radius:11px;font-size:12.5px;
  background:var(--overlay);border:1px solid var(--line2);box-shadow:var(--shadow);color:var(--ink2);
  animation:tin .3s cubic-bezier(.22,1,.36,1) both}
.toast.bad{border-color:color-mix(in srgb,var(--red) 50%,transparent)}
.toast.ok{border-color:color-mix(in srgb,var(--acid) 50%,transparent)}
.toast i{width:8px;height:8px;border-radius:50%;flex:none;margin-top:5px;background:var(--info)}
.toast.bad i{background:var(--bad)} .toast.ok i{background:var(--ok)}
@keyframes tin{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:none}}

::-webkit-scrollbar{width:11px;height:11px}
::-webkit-scrollbar-track{background:var(--bg2)}
::-webkit-scrollbar-thumb{background:linear-gradient(180deg,var(--indigo),var(--violet));border-radius:99px;border:3px solid var(--bg2)}
::-webkit-scrollbar-thumb:hover{background:linear-gradient(180deg,var(--violet),var(--magenta))}
::-webkit-scrollbar-corner{background:var(--bg2)}
*{scrollbar-width:thin;scrollbar-color:var(--indigo) var(--bg2)}
:focus-visible{outline:2px solid var(--indigo);outline-offset:2px}
@media(max-width:640px){
  :root{--rail:58px}
  .brand small,.who{display:none}
  .hbtn span{display:none}
  .hbtn{padding:0 9px}
  .ua{display:none}
}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}}
</style>
</head>
<body>

<div class="bg" aria-hidden="true">
  <div class="mesh"></div><div class="grid"></div><div class="scan"></div><div class="grain"></div>
</div>

<header>
  <div class="brand">
    <span class="sig"><svg viewBox="0 0 24 24"><path d="M12 3l7 3v6c0 4.2-2.9 7.6-7 9-4.1-1.4-7-4.8-7-9V6l7-3z"/><path d="M9.3 12.2l1.9 1.9 3.6-3.9"/></svg></span>
    <span><b>SECURITY OPS</b><small>ЖУРНАЛ ДОСТУПА</small></span>
  </div>
  <span class="pulse" id="livePill"><i class="dot"></i><span id="liveTxt">LIVE</span></span>
  <div class="spacer"></div>
  <button class="hbtn on" id="soundBtn" aria-pressed="true" title="Звук оповещений">
    <svg viewBox="0 0 24 24" id="sndIcon"><path d="M11 5L6.5 9H3v6h3.5L11 19V5z"/><path d="M15.5 9.5a3.5 3.5 0 010 5M18 7a7 7 0 010 10"/></svg>
    <span>Звук</span>
  </button>
  <button class="hbtn" id="themeBtn" title="Тема оформления">
    <svg viewBox="0 0 24 24"><path d="M21 12.8A8.5 8.5 0 1111.2 3a6.6 6.6 0 009.8 9.8z"/></svg>
    <span id="themeTxt">Тёмная</span>
  </button>
  <a class="hbtn" href="admin_users.php"><svg viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></svg><span>Админка</span></a>
  <span class="who">Вы вошли как <b><?= $e($user['login']) ?></b></span>
  <a class="hbtn danger" href="logout.php"><svg viewBox="0 0 24 24"><path d="M15 12H4M9 7l-5 5 5 5M14 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4"/></svg><span>Выйти</span></a>
</header>

<main>
  <div class="title">
    <div>
      <h1>Журнал безопасности</h1>
      <p>Каждая попытка входа пишется в <span class="mono">login_log</span>. Здесь видно, кто и откуда стучался, какие адреса заблокированы и какие устройства держат активную сессию.</p>
    </div>
  </div>

  <?php if ($msg): ?><div class="banner ok"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg><div><?= $e($msg) ?></div></div><?php endif; ?>
  <?php if ($err): ?><div class="banner bad"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/></svg><div><?= $e($err) ?></div></div><?php endif; ?>

  <!-- ПУЛЬТ -->
  <section class="rail" aria-label="Пульт управления">
    <label class="ctl"><span>Период</span>
      <select id="cRange">
        <option value="1h">Последний час</option>
        <option value="6h">6 часов</option>
        <option value="24h" selected>24 часа</option>
        <option value="7d">7 дней</option>
        <option value="30d">30 дней</option>
      </select>
    </label>

    <div class="ctl"><span>Тип события</span>
      <div class="seg" id="cType" role="group">
        <button type="button" data-v="all" aria-pressed="true">Все</button>
        <button type="button" data-v="ok" aria-pressed="false">Успех</button>
        <button type="button" data-v="fail" aria-pressed="false">Отказ</button>
      </div>
    </div>

    <label class="ctl"><span>Поиск по логину / IP</span>
      <input type="search" id="cQ" placeholder="например admin или 127.0.0.1" autocomplete="off">
    </label>

    <label class="ctl"><span>Сортировка</span>
      <select id="cSort">
        <option value="new">Сначала новые</option>
        <option value="old">Сначала старые</option>
        <option value="login">По логину</option>
        <option value="ip">По IP</option>
      </select>
    </label>

    <div class="ctl"><span>Строк на странице</span>
      <div class="step">
        <button type="button" id="perMinus" aria-label="Меньше">−</button>
        <b id="perVal">25</b>
        <button type="button" id="perPlus" aria-label="Больше">+</button>
      </div>
    </div>

    <div class="ctl"><span>Интервал обновления</span>
      <div class="rng"><input type="range" id="cRate" min="2" max="30" step="1" value="5"><b id="rateVal">5с</b></div>
    </div>

    <div class="ctl"><span>Вид графика</span>
      <div class="seg" id="cChart" role="group">
        <button type="button" data-v="bar" aria-pressed="true">Столбцы</button>
        <button type="button" data-v="area" aria-pressed="false">Область</button>
      </div>
    </div>

    <div class="ctl"><span>Поток</span>
      <label class="sw"><input type="checkbox" id="cLive" checked><span class="track"></span>Автообновление</label>
    </div>

    <div class="ctl"><span>Действия</span>
      <div class="act">
        <button class="btn" id="cReset" title="Сбросить фильтры"><svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 109-9 9 9 0 00-6.4 2.7L3 8"/><path d="M3 3v5h5"/></svg>Сброс</button>
        <a class="btn pri" id="cExport" href="#"><svg viewBox="0 0 24 24"><path d="M12 3v12M7 11l5 5 5-5M4 20h16"/></svg>CSV</a>
      </div>
    </div>
  </section>

  <!-- KPI -->
  <section class="kpis" id="kpis"></section>

  <!-- ГРАФИКИ -->
  <section class="grid2">
    <div class="card">
      <h2>Попытки входа во времени <em id="chartMeta">—</em></h2>
      <div class="chartwrap">
        <svg id="timeline" viewBox="0 0 900 266" preserveAspectRatio="none" role="img" aria-label="График попыток входа"></svg>
        <div class="tip" id="tip"></div>
      </div>
      <div class="legend">
        <span><i style="background:var(--acid)"></i>Успешные</span>
        <span><i style="background:var(--red)"></i>Отказы</span>
        <span><i style="background:var(--blue)"></i>Уникальные логины</span>
        <span><i style="background:var(--amber)"></i>Порог блокировки</span>
      </div>
    </div>

    <div class="card">
      <h2>Соотношение <em id="donutMeta">—</em></h2>
      <div class="chartwrap"><svg id="donut" viewBox="0 0 300 266" role="img" aria-label="Соотношение успешных и неудачных входов"></svg></div>
    </div>
  </section>

  <!-- IP / СЕССИИ -->
  <section class="grid3">
    <div class="card">
      <h2>Адреса доступа <em>топ-8 за период</em></h2>
      <div class="scroll"><table><thead><tr><th>IP</th><th>Всего</th><th>Отказов</th><th>Статус</th><th></th></tr></thead><tbody id="ipsBody"></tbody></table></div>
    </div>

    <div class="card">
      <h2>Активные сессии <em id="sessMeta">—</em></h2>
      <div class="scroll"><table><thead><tr><th>Пользователь</th><th>IP</th><th>Создана</th><th></th></tr></thead><tbody id="sessBody"></tbody></table></div>
    </div>

    <div class="card">
      <h2>Сводка и регламент <em>хранимые процедуры</em></h2>
      <div id="statsBox" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px"></div>
      <form method="post" id="cleanupForm" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
        <input type="hidden" name="csrf" value="<?= $e(csrf_token()) ?>">
        <input type="hidden" name="action" value="cleanup">
        <label class="ctl" style="flex:1;min-width:130px"><span>Хранить журнал, дней</span>
          <select name="days" id="cDays"><option value="30">30</option><option value="90" selected>90</option><option value="180">180</option><option value="365">365</option></select>
        </label>
        <button class="btn warn" type="submit" id="cleanupBtn"><svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2M6 7l1 13h10l1-13"/></svg>Очистить</button>
      </form>
      <p style="margin:10px 0 0;font-size:11px;color:var(--faint);line-height:1.6">
        Вызывает <span class="mono">sp_cleanup_sessions()</span>: удаляет истёкшие сессии и записи журнала старше указанного срока.
      </p>
    </div>
  </section>

  <!-- ЛЕНТА -->
  <section class="card">
    <h2>Лента событий <em id="feedMeta">—</em></h2>
    <div class="scroll" style="max-height:520px">
      <table>
        <thead><tr><th style="width:64px">ID</th><th style="width:150px">Время</th><th>Логин</th><th>IP</th><th style="width:96px">Результат</th><th style="width:86px">Роль</th><th>Устройство</th></tr></thead>
        <tbody id="feedBody"></tbody>
      </table>
    </div>
    <div class="pager">
      <span id="pageInfo">—</span>
      <div class="nav" id="pageNav"></div>
    </div>
  </section>
</main>

<div class="toasts" id="toasts" aria-live="polite"></div>

<form method="post" id="actForm" hidden>
  <input type="hidden" name="csrf" value="<?= $e(csrf_token()) ?>">
  <input type="hidden" name="action" id="actName">
  <input type="hidden" name="session_id" id="actSid">
  <input type="hidden" name="ip" id="actIp">
</form>

<script>
(function () {
  'use strict';
  var CSRF = <?= json_encode(csrf_token()) ?>;
  var PER_STEPS = [10, 25, 50, 100];
  var LOCK_THRESHOLD = <?= (int)AUTH_MAX_FAILS ?>;

  var S = {
    range: '24h', type: 'all', q: '', sort: 'new',
    per: 25, page: 1, rate: 5, live: true, chart: 'bar',
    sound: true, theme: 'dark'
  };
  try {
    var saved = JSON.parse(localStorage.getItem('secops') || '{}');
    ['theme', 'sound', 'rate', 'per', 'chart', 'range'].forEach(function (k) {
      if (saved[k] !== undefined) S[k] = saved[k];
    });
  } catch (e) {}
  function persist() {
    try {
      localStorage.setItem('secops', JSON.stringify({
        theme: S.theme, sound: S.sound, rate: S.rate, per: S.per, chart: S.chart, range: S.range
      }));
    } catch (e) {}
  }

  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var css = function (n) { return getComputedStyle(document.documentElement).getPropertyValue(n).trim(); };
  var nf = function (n) { return (Number(n) || 0).toLocaleString('ru-RU'); };

  /* ---------- звук ---------- */
  var actx = null;
  function blip(kind) {
    if (!S.sound) return;
    try {
      if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)();
      if (actx.state === 'suspended') actx.resume();
      var t = actx.currentTime, o = actx.createOscillator(), g = actx.createGain();
      o.type = kind === 'bad' ? 'square' : 'sine';
      o.frequency.setValueAtTime(kind === 'bad' ? 320 : 660, t);
      o.frequency.exponentialRampToValueAtTime(kind === 'bad' ? 150 : 880, t + 0.13);
      g.gain.setValueAtTime(0.0001, t);
      g.gain.exponentialRampToValueAtTime(kind === 'bad' ? 0.075 : 0.05, t + 0.012);
      g.gain.exponentialRampToValueAtTime(0.0001, t + 0.2);
      o.connect(g); g.connect(actx.destination); o.start(t); o.stop(t + 0.22);
    } catch (e) {}
  }
  document.addEventListener('pointerdown', function once() {
    if (S.sound && !actx) { try { actx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {} }
    document.removeEventListener('pointerdown', once);
  }, { once: true });

  /* ---------- тосты ---------- */
  function toast(text, kind) {
    var box = $('toasts'), el = document.createElement('div');
    el.className = 'toast ' + (kind || '');
    el.innerHTML = '<i></i><div>' + esc(text) + '</div>';
    box.appendChild(el);
    setTimeout(function () {
      el.style.transition = 'opacity .3s, transform .3s';
      el.style.opacity = '0'; el.style.transform = 'translateX(20px)';
      setTimeout(function () { el.remove(); }, 320);
    }, 4200);
    while (box.children.length > 4) box.firstChild.remove();
  }

  /* ---------- тема / звук ---------- */
  function applyTheme() {
    document.documentElement.setAttribute('data-theme', S.theme);
    $('themeTxt').textContent = S.theme === 'dark' ? 'Тёмная' : 'Светлая';
    $('themeBtn').classList.toggle('on', S.theme === 'light');
  }
  function applySound() {
    $('soundBtn').classList.toggle('on', S.sound);
    $('soundBtn').setAttribute('aria-pressed', S.sound ? 'true' : 'false');
    $('sndIcon').innerHTML = S.sound
      ? '<path d="M11 5L6.5 9H3v6h3.5L11 19V5z"/><path d="M15.5 9.5a3.5 3.5 0 010 5M18 7a7 7 0 010 10"/>'
      : '<path d="M11 5L6.5 9H3v6h3.5L11 19V5z"/><path d="M16 9.5l5 5M21 9.5l-5 5"/>';
  }
  $('themeBtn').addEventListener('click', function () {
    S.theme = S.theme === 'dark' ? 'light' : 'dark'; applyTheme(); persist(); renderAll(last);
  });
  $('soundBtn').addEventListener('click', function () {
    S.sound = !S.sound; applySound(); persist();
    if (S.sound) blip('ok');
    toast(S.sound ? 'Оповещения включены' : 'Оповещения выключены');
  });

  /* ---------- пульт ---------- */
  $('cRange').value = S.range;
  $('cRange').addEventListener('change', function () { S.range = this.value; S.page = 1; persist(); load(); });
  $('cSort').addEventListener('change', function () { S.sort = this.value; S.page = 1; load(); });
  var qT;
  $('cQ').addEventListener('input', function () {
    var v = this.value; clearTimeout(qT);
    qT = setTimeout(function () { S.q = v.trim(); S.page = 1; load(); }, 320);
  });
  function segInit(id, key, cb) {
    var box = $(id);
    Array.prototype.forEach.call(box.querySelectorAll('button'), function (b) {
      b.setAttribute('aria-pressed', b.dataset.v === S[key] ? 'true' : 'false');
      b.addEventListener('click', function () {
        S[key] = b.dataset.v;
        Array.prototype.forEach.call(box.querySelectorAll('button'), function (x) {
          x.setAttribute('aria-pressed', x === b ? 'true' : 'false');
        });
        persist(); cb();
      });
    });
  }
  segInit('cType', 'type', function () { S.page = 1; load(); });
  segInit('cChart', 'chart', function () { renderTimeline(last); });

  function perIdx() { var i = PER_STEPS.indexOf(S.per); return i < 0 ? 1 : i; }
  function showPer() { $('perVal').textContent = S.per; }
  $('perMinus').addEventListener('click', function () {
    var i = perIdx(); if (i > 0) { S.per = PER_STEPS[i - 1]; S.page = 1; showPer(); persist(); load(); }
  });
  $('perPlus').addEventListener('click', function () {
    var i = perIdx(); if (i < PER_STEPS.length - 1) { S.per = PER_STEPS[i + 1]; S.page = 1; showPer(); persist(); load(); }
  });
  showPer();

  $('cRate').value = S.rate; $('rateVal').textContent = S.rate + 'с';
  $('cRate').addEventListener('input', function () {
    S.rate = parseInt(this.value, 10); $('rateVal').textContent = S.rate + 'с'; persist(); restart();
  });
  $('cLive').checked = S.live;
  $('cLive').addEventListener('change', function () {
    S.live = this.checked; setPill(); restart();
    toast(S.live ? 'Автообновление включено' : 'Поток остановлен');
  });
  $('cReset').addEventListener('click', function () {
    S.range = '24h'; S.type = 'all'; S.q = ''; S.sort = 'new'; S.per = 25; S.page = 1;
    $('cRange').value = '24h'; $('cQ').value = ''; $('cSort').value = 'new'; showPer();
    Array.prototype.forEach.call($('cType').querySelectorAll('button'), function (b) {
      b.setAttribute('aria-pressed', b.dataset.v === 'all' ? 'true' : 'false');
    });
    persist(); load(); toast('Фильтры сброшены');
  });
  function syncExport() {
    $('cExport').href = 'admin_security.php?export=csv&range=' + encodeURIComponent(S.range)
      + '&type=' + encodeURIComponent(S.type) + '&q=' + encodeURIComponent(S.q);
  }
  function setPill() {
    $('livePill').classList.toggle('off', !S.live);
    $('liveTxt').textContent = S.live ? 'LIVE' : 'ПАУЗА';
  }

  /* ---------- действия ---------- */
  function post(action, fields, confirmText) {
    if (confirmText && !window.confirm(confirmText)) return;
    $('actName').value = action;
    $('actSid').value = fields.session_id || '';
    $('actIp').value = fields.ip || '';
    $('actForm').submit();
  }
  $('cleanupForm').addEventListener('submit', function (ev) {
    if (!window.confirm('Удалить истёкшие сессии и записи журнала старше ' + $('cDays').value + ' дней? Действие необратимо.')) ev.preventDefault();
  });

  /* ---------- отрисовка KPI ---------- */
  function spark(vals, color) {
    if (!vals.length) return '';
    var max = Math.max.apply(null, vals) || 1, w = 100, h = 26;
    var pts = vals.map(function (v, i) {
      return [(i / Math.max(1, vals.length - 1)) * w, h - (v / max) * (h - 3) - 1.5];
    });
    var d = pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' ');
    var area = d + ' L' + w + ' ' + h + ' L0 ' + h + ' Z';
    var gid = 'sp' + Math.random().toString(36).slice(2, 8);
    return '<svg viewBox="0 0 100 26" preserveAspectRatio="none">'
      + '<defs><linearGradient id="' + gid + '" x1="0" y1="0" x2="0" y2="1">'
      + '<stop offset="0" stop-color="' + color + '" stop-opacity=".45"/>'
      + '<stop offset="1" stop-color="' + color + '" stop-opacity="0"/></linearGradient></defs>'
      + '<path d="' + area + '" fill="url(#' + gid + ')"/>'
      + '<path d="' + d + '" fill="none" stroke="' + color + '" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/>'
      + '<circle cx="' + pts[pts.length - 1][0].toFixed(1) + '" cy="' + pts[pts.length - 1][1].toFixed(1) + '" r="2.2" fill="' + color + '"/></svg>';
  }
  function renderKpis(d) {
    var k = d.kpi || {}, ser = d.series || [];
    var okS = ser.map(function (s) { return s.ok; });
    var badS = ser.map(function (s) { return s.fail; });
    var allS = ser.map(function (s) { return s.ok + s.fail; });
    var uqS = ser.map(function (s) { return s.uniq; });
    var lockedN = Object.keys(d.locked || {}).length;
    var rate = k.attempts ? Math.round((k.ok / k.attempts) * 100) : 0;
    var cards = [
      { lab: 'Всего попыток', val: nf(k.attempts), sub: 'за выбранный период', c: 'var(--indigo)', s: allS, col: css('--indigo') },
      { lab: 'Успешных входов', val: nf(k.ok), sub: rate + '% от всех попыток', c: 'var(--acid)', s: okS, col: css('--acid') },
      { lab: 'Отказов доступа', val: nf(k.fail), sub: (100 - rate) + '% от всех попыток', c: 'var(--red)', s: badS, col: css('--red') },
      { lab: 'Заблокировано IP', val: nf(lockedN), sub: 'порог ' + LOCK_THRESHOLD + ' отказов', c: 'var(--amber)', s: badS, col: css('--amber') },
      { lab: 'Уникальных логинов', val: nf(k.logins), sub: nf(k.ips) + ' адресов', c: 'var(--blue)', s: uqS, col: css('--blue') },
      { lab: 'Активных сессий', val: nf(d.session_count), sub: 'устройства онлайн', c: 'var(--magenta)', s: okS, col: css('--magenta') }
    ];
    $('kpis').innerHTML = cards.map(function (c, i) {
      return '<article class="kpi" style="--c:' + c.c + ';animation-delay:' + (i * 45) + 'ms">'
        + '<div class="lab">' + c.lab + '</div><div class="val">' + c.val + '</div>'
        + '<div class="sub">' + c.sub + '</div>' + spark(c.s, c.col) + '</article>';
    }).join('');
  }

  /* ---------- график времени ---------- */
  function fmtBucket(t, step) {
    var d = new Date(t * 1000);
    var p = function (n) { return n < 10 ? '0' + n : '' + n; };
    if (step >= 86400) return p(d.getDate()) + '.' + p(d.getMonth() + 1);
    if (step >= 21600) return p(d.getDate()) + '.' + p(d.getMonth() + 1) + ' ' + p(d.getHours()) + 'ч';
    return p(d.getHours()) + ':' + p(d.getMinutes());
  }
  var hitBoxes = [];
  function renderTimeline(d) {
    if (!d) return;
    var svg = $('timeline'), ser = d.series || [], step = d.step || 3600;
    var W = 900, H = 266, L = 44, R = 12, T = 16, B = 34;
    var iw = W - L - R, ih = H - T - B;
    if (!ser.length) { svg.innerHTML = '<text x="450" y="140" text-anchor="middle" fill="' + css('--faint') + '" font-size="13">Нет данных за период</text>'; return; }
    var max = 1;
    ser.forEach(function (s) { max = Math.max(max, s.ok + s.fail, s.uniq); });
    max = Math.ceil(max * 1.18) || 1;
    var n = ser.length, bw = iw / n;
    var y = function (v) { return T + ih - (v / max) * ih; };
    var cOk = css('--acid'), cBad = css('--red'), cU = css('--blue'), cWarn = css('--amber'),
        cLine = css('--line'), cFaint = css('--faint');
    var g = '';
    for (var i = 0; i <= 4; i++) {
      var v = Math.round((max / 4) * i), yy = y(v);
      g += '<line x1="' + L + '" y1="' + yy.toFixed(1) + '" x2="' + (W - R) + '" y2="' + yy.toFixed(1) + '" stroke="' + cLine + '" stroke-width="1"/>'
        + '<text x="' + (L - 8) + '" y="' + (yy + 4).toFixed(1) + '" text-anchor="end" font-size="10" fill="' + cFaint + '">' + v + '</text>';
    }
    var thrY = y(LOCK_THRESHOLD);
    if (LOCK_THRESHOLD <= max) {
      g += '<line x1="' + L + '" y1="' + thrY.toFixed(1) + '" x2="' + (W - R) + '" y2="' + thrY.toFixed(1)
        + '" stroke="' + cWarn + '" stroke-width="1.3" stroke-dasharray="6 5" opacity=".85"/>'
        + '<text x="' + (W - R) + '" y="' + (thrY - 6).toFixed(1) + '" text-anchor="end" font-size="9.5" fill="' + cWarn + '" font-weight="700">порог ' + LOCK_THRESHOLD + '</text>';
    }
    var bars = '', areaOk = '', areaBad = '', lineU = '';
    var pOk = [], pBad = [], pU = [];
    ser.forEach(function (s, i) {
      var x = L + i * bw, cx = x + bw / 2;
      pOk.push([cx, y(s.ok)]); pBad.push([cx, y(s.ok + s.fail)]); pU.push([cx, y(s.uniq)]);
      if (S.chart === 'bar') {
        var w = Math.max(2, bw * 0.62), bx = cx - w / 2;
        var hOk = (s.ok / max) * ih, hBad = (s.fail / max) * ih;
        if (hOk > 0) bars += '<rect x="' + bx.toFixed(1) + '" y="' + y(s.ok).toFixed(1) + '" width="' + w.toFixed(1) + '" height="' + hOk.toFixed(1) + '" rx="2" fill="' + cOk + '" opacity=".92"/>';
        if (hBad > 0) bars += '<rect x="' + bx.toFixed(1) + '" y="' + y(s.ok + s.fail).toFixed(1) + '" width="' + w.toFixed(1) + '" height="' + hBad.toFixed(1) + '" rx="2" fill="' + cBad + '" opacity=".92"/>';
      }
      var every = Math.ceil(n / 9);
      if (i % every === 0 || i === n - 1) {
        bars += '<text x="' + cx.toFixed(1) + '" y="' + (H - 12) + '" text-anchor="middle" font-size="9.5" fill="' + cFaint + '">' + esc(fmtBucket(s.t, step)) + '</text>';
      }
    });
    function path(pts) { return pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' '); }
    if (S.chart === 'area') {
      var base = ' L' + (L + iw).toFixed(1) + ' ' + (T + ih) + ' L' + L + ' ' + (T + ih) + ' Z';
      areaBad = '<path d="' + path(pBad) + base + '" fill="' + cBad + '" opacity=".26"/>'
              + '<path d="' + path(pBad) + '" fill="none" stroke="' + cBad + '" stroke-width="1.8"/>';
      areaOk = '<path d="' + path(pOk) + base + '" fill="' + cOk + '" opacity=".3"/>'
             + '<path d="' + path(pOk) + '" fill="none" stroke="' + cOk + '" stroke-width="1.8"/>';
    }
    lineU = '<path d="' + path(pU) + '" fill="none" stroke="' + cU + '" stroke-width="1.8" stroke-dasharray="4 4" opacity=".95"/>';
    var hits = '';
    hitBoxes = [];
    ser.forEach(function (s, i) {
      var x = L + i * bw;
      hitBoxes.push({ x: x, w: bw, s: s, cx: x + bw / 2, top: y(s.ok + s.fail) });
      hits += '<rect x="' + x.toFixed(1) + '" y="' + T + '" width="' + bw.toFixed(1) + '" height="' + ih + '" fill="transparent" data-i="' + i + '" style="cursor:crosshair"/>';
    });
    svg.innerHTML = '<g>' + g + '</g>' + areaBad + areaOk + bars + lineU + '<g>' + hits + '</g>';
    $('chartMeta').textContent = n + ' интервалов · шаг ' + (step >= 86400 ? (step / 86400) + ' дн' : step >= 3600 ? (step / 3600) + ' ч' : (step / 60) + ' мин');

    var tip = $('tip');
    svg.onmousemove = function (ev) {
      var t = ev.target;
      if (!t || t.dataset.i === undefined) { tip.classList.remove('show'); return; }
      var b = hitBoxes[+t.dataset.i]; if (!b) return;
      var rect = svg.getBoundingClientRect();
      tip.innerHTML = '<b>' + esc(fmtBucket(b.s.t, step)) + '</b><br>'
        + '<span style="color:' + cOk + '">Успех: ' + b.s.ok + '</span><br>'
        + '<span style="color:' + cBad + '">Отказ: ' + b.s.fail + '</span><br>'
        + '<span style="color:' + cU + '">Уникальных: ' + b.s.uniq + '</span>';
      tip.style.left = (rect.left + (b.cx / 900) * rect.width - rect.left) + 'px';
      tip.style.left = ((b.cx / 900) * rect.width) + 'px';
      tip.style.top = ((b.top / 266) * rect.height) + 'px';
      tip.classList.add('show');
    };
    svg.onmouseleave = function () { tip.classList.remove('show'); };
  }

  /* ---------- пончик ---------- */
  function renderDonut(d) {
    if (!d) return;
    var svg = $('donut'), k = d.kpi || {};
    var ok = k.ok || 0, bad = k.fail || 0, tot = ok + bad;
    var cx = 150, cy = 118, r = 74, sw = 26;
    if (!tot) {
      svg.innerHTML = '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + css('--line') + '" stroke-width="' + sw + '"/>'
        + '<text x="' + cx + '" y="' + (cy + 5) + '" text-anchor="middle" font-size="13" fill="' + css('--faint') + '">Нет событий</text>';
      $('donutMeta').textContent = '—'; return;
    }
    var C = 2 * Math.PI * r, okLen = (ok / tot) * C;
    var pct = Math.round((ok / tot) * 100);
    var cOk = css('--acid'), cBad = css('--red');
    svg.innerHTML =
      '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + css('--line') + '" stroke-width="' + sw + '"/>'
      + '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + cBad + '" stroke-width="' + sw + '" stroke-dasharray="' + C.toFixed(1) + '" transform="rotate(-90 ' + cx + ' ' + cy + ')"/>'
      + '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + cOk + '" stroke-width="' + sw + '"'
      + ' stroke-dasharray="' + okLen.toFixed(1) + ' ' + (C - okLen).toFixed(1) + '" transform="rotate(-90 ' + cx + ' ' + cy + ')" stroke-linecap="butt"/>'
      + '<text x="' + cx + '" y="' + (cy - 2) + '" text-anchor="middle" font-size="34" font-weight="900" fill="' + css('--ink') + '">' + pct + '%</text>'
      + '<text x="' + cx + '" y="' + (cy + 18) + '" text-anchor="middle" font-size="10.5" letter-spacing="1.4" fill="' + css('--faint') + '">УСПЕШНЫХ</text>'
      + '<g font-size="12">'
      + '<rect x="42" y="222" width="11" height="11" rx="3" fill="' + cOk + '"/><text x="60" y="232" fill="' + css('--ink2') + '">Успех — ' + nf(ok) + '</text>'
      + '<rect x="164" y="222" width="11" height="11" rx="3" fill="' + cBad + '"/><text x="182" y="232" fill="' + css('--ink2') + '">Отказ — ' + nf(bad) + '</text>'
      + '</g>';
    $('donutMeta').textContent = nf(tot) + ' событий';
  }

  /* ---------- таблицы ---------- */
  function renderIps(d) {
    var rows = d.top_ips || [], locked = d.locked || {}, body = $('ipsBody');
    if (!rows.length) { body.innerHTML = '<tr><td colspan="5" class="empty">Записей за период нет</td></tr>'; return; }
    var max = Math.max.apply(null, rows.map(function (r) { return +r.total; })) || 1;
    body.innerHTML = rows.map(function (r) {
      var lk = locked[r.ip];
      return '<tr><td class="mono">' + esc(r.ip) + '<div class="bar"><i style="width:' + ((+r.total / max) * 100).toFixed(1) + '%"></i></div></td>'
        + '<td class="num">' + nf(r.total) + '</td>'
        + '<td class="num" style="color:' + (+r.fail > 0 ? css('--red') : css('--muted')) + '">' + nf(r.fail) + '</td>'
        + '<td>' + (lk
            ? '<span class="tag lock">БЛОК ' + Math.ceil(lk.left / 60) + 'м</span>'
            : '<span class="tag ok">НОРМА</span>') + '</td>'
        + '<td>' + (lk ? '<button class="btn" data-unblock="' + esc(r.ip) + '" style="height:28px;font-size:11px">Снять</button>' : '') + '</td></tr>';
    }).join('');
    Array.prototype.forEach.call(body.querySelectorAll('[data-unblock]'), function (b) {
      b.addEventListener('click', function () {
        post('unblock', { ip: b.dataset.unblock }, 'Разблокировать адрес ' + b.dataset.unblock + '? Записи об отказах за окно блокировки будут удалены.');
      });
    });
  }
  function shortDate(s) {
    if (!s) return '—';
    var d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d)) return String(s);
    var p = function (n) { return n < 10 ? '0' + n : '' + n; };
    return p(d.getDate()) + '.' + p(d.getMonth() + 1) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
  }
  function renderSessions(d) {
    var rows = d.sessions || [], body = $('sessBody');
    $('sessMeta').textContent = nf(d.session_count) + ' устройств';
    if (!rows.length) { body.innerHTML = '<tr><td colspan="4" class="empty">Активных сессий нет</td></tr>'; return; }
    body.innerHTML = rows.map(function (s) {
      var mine = s.session_id === d.my_session;
      var role = s.role === 'admin' ? 'adm' : 'usr';
      return '<tr><td><b>' + esc(s.login || '—') + '</b> <span class="tag ' + role + '">' + esc(s.role || '?') + '</span>'
        + (mine ? ' <span class="tag ok">ВЫ</span>' : '') + '</td>'
        + '<td class="mono">' + esc(s.ip) + '</td>'
        + '<td class="num">' + shortDate(s.created_at) + '</td>'
        + '<td>' + (mine ? '' : '<button class="btn" data-revoke="' + esc(s.session_id) + '" style="height:28px;font-size:11px">Отозвать</button>') + '</td></tr>';
    }).join('');
    Array.prototype.forEach.call(body.querySelectorAll('[data-revoke]'), function (b) {
      b.addEventListener('click', function () {
        post('revoke', { session_id: b.dataset.revoke }, 'Отозвать сессию? Устройство будет разлогинено немедленно.');
      });
    });
  }
  function renderStats(d) {
    var s = d.stats, box = $('statsBox');
    var items = s ? [
      ['Пользователей', s.total_users, 'var(--indigo)'],
      ['Активных', s.active_users, 'var(--acid)'],
      ['Админов', s.admins, 'var(--violet)'],
      ['Заметок', s.total_notes, 'var(--blue)'],
      ['Отказов за 24ч', s.failed_24h, 'var(--red)'],
      ['Сессий в БД', s.active_sessions, 'var(--magenta)']
    ] : [
      ['Попыток', d.kpi.attempts, 'var(--indigo)'],
      ['Успешных', d.kpi.ok, 'var(--acid)'],
      ['Отказов', d.kpi.fail, 'var(--red)'],
      ['Сессий', d.session_count, 'var(--magenta)']
    ];
    box.innerHTML = items.map(function (i) {
      return '<div style="border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--surface2)">'
        + '<div style="font-size:9px;letter-spacing:.12em;text-transform:uppercase;color:var(--faint);font-weight:800">' + esc(i[0]) + '</div>'
        + '<div style="font-size:19px;font-weight:900;color:' + i[2] + ';font-variant-numeric:tabular-nums">' + nf(i[1]) + '</div></div>';
    }).join('');
  }
  var lastLatest = 0;
  function renderFeed(d) {
    var rows = d.events || [], body = $('feedBody');
    $('feedMeta').textContent = nf(d.total) + ' записей';
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="7" class="empty">Ничего не найдено. Измените период или фильтр.</td></tr>';
    } else {
      body.innerHTML = rows.map(function (r) {
        var ok = +r.success === 1;
        var fresh = !ok && r.log_id > lastLatest && lastLatest > 0;
        var role = r.role === 'admin' ? 'adm' : (r.role ? 'usr' : '');
        return '<tr' + (fresh ? ' class="fresh"' : '') + '>'
          + '<td class="num">' + esc(r.log_id) + '</td>'
          + '<td class="num">' + shortDate(r.created_at) + '</td>'
          + '<td><b>' + esc(r.login) + '</b></td>'
          + '<td class="mono">' + esc(r.ip) + '</td>'
          + '<td><span class="tag ' + (ok ? 'ok' : 'bad') + '">' + (ok ? 'УСПЕХ' : 'ОТКАЗ') + '</span></td>'
          + '<td>' + (role ? '<span class="tag ' + role + '">' + esc(r.role) + '</span>' : '<span style="color:var(--faint)">—</span>') + '</td>'
          + '<td class="ua" title="' + esc(r.user_agent) + '">' + esc(r.user_agent || '—') + '</td></tr>';
      }).join('');
    }
    var newFail = rows.filter(function (r) { return +r.success === 0 && r.log_id > lastLatest; }).length;
    if (lastLatest > 0 && newFail > 0) {
      blip('bad');
      toast('Новых отказов доступа: ' + newFail, 'bad');
    }
    if (d.kpi && d.kpi.latest) lastLatest = d.kpi.latest;

    var pages = Math.max(1, Math.ceil((d.total || 0) / S.per));
    var from = (d.total === 0) ? 0 : (S.page - 1) * S.per + 1;
    var to = Math.min(S.page * S.per, d.total || 0);
    $('pageInfo').textContent = 'Показано ' + from + '–' + to + ' из ' + nf(d.total) + ' · стр. ' + S.page + ' из ' + pages;
    var nav = $('pageNav'); nav.innerHTML = '';
    function mk(label, pg, dis, cur) {
      var b = document.createElement('button');
      b.className = 'pg'; b.textContent = label; b.disabled = !!dis;
      if (cur) b.setAttribute('aria-current', 'true');
      b.addEventListener('click', function () { S.page = pg; load(); });
      nav.appendChild(b);
    }
    mk('‹', S.page - 1, S.page <= 1);
    var start = Math.max(1, S.page - 2), end = Math.min(pages, start + 4);
    start = Math.max(1, end - 4);
    for (var p = start; p <= end; p++) mk(String(p), p, false, p === S.page);
    mk('›', S.page + 1, S.page >= pages);
  }

  /* ---------- загрузка ---------- */
  var last = null, inflight = null, timer = null, errStreak = 0;
  function renderAll(d) {
    if (!d) return;
    renderKpis(d); renderTimeline(d); renderDonut(d);
    renderIps(d); renderSessions(d); renderStats(d); renderFeed(d);
  }
  function url() {
    return 'api_security.php?range=' + encodeURIComponent(S.range) + '&type=' + encodeURIComponent(S.type)
      + '&q=' + encodeURIComponent(S.q) + '&sort=' + encodeURIComponent(S.sort)
      + '&per=' + S.per + '&page=' + S.page + '&_=' + Date.now();
  }
  function load() {
    syncExport();
    if (inflight) { try { inflight.abort(); } catch (e) {} }
    var ctl = ('AbortController' in window) ? new AbortController() : null;
    inflight = ctl;
    fetch(url(), { cache: 'no-store', credentials: 'same-origin', signal: ctl ? ctl.signal : undefined })
      .then(function (r) {
        if (r.status === 401) throw new Error('Сессия истекла — войдите заново');
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (d) {
        if (d.error) throw new Error(d.error);
        errStreak = 0; last = d; renderAll(d);
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        errStreak++;
        if (errStreak === 1 || errStreak % 6 === 0) toast('Журнал недоступен: ' + err.message, 'bad');
      });
  }
  function restart() {
    if (timer) { clearInterval(timer); timer = null; }
    if (S.live) timer = setInterval(load, Math.max(2, S.rate) * 1000);
  }
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) { if (timer) { clearInterval(timer); timer = null; } }
    else { load(); restart(); }
  });
  window.addEventListener('resize', function () { if (last) renderTimeline(last); });

  applyTheme(); applySound(); setPill(); syncExport();
  load(); restart();
})();
</script>
</body>
</html>