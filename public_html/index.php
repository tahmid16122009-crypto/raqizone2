<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

$u = rz_get_user();
if ($u) {
    header('Location: /home');
    exit;
}

$cfg = get_all_settings();

$theme = 'theme-' . ($cfg['site_theme'] ?? 'golden');
$sname = htmlspecialchars($cfg['site_name'] ?? 'Raqizone');
$sub   = htmlspecialchars($cfg['welcome_subtitle'] ?? 'Best products, best prices');
$fav   = $cfg['site_favicon_ext'] ?? '';
$logo  = $cfg['site_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title><?= $sname ?></title>
<?php if ($fav): ?>
<link rel="icon" href="/favicon.<?= $fav ?>" type="image/<?= $fav==='ico'?'x-icon':$fav ?>">
<?php endif; ?>
<meta name="description" content="<?= htmlspecialchars($cfg['meta_description'] ?? $sub) ?>">
<link rel="stylesheet" href="/static/css/style.css">
<style>
/* ── Landing page glass / animation enhancement (scoped to this page only) ── */
.lp-bg-wrap{
  position:fixed; inset:0; z-index:0; overflow:hidden; background:var(--k);
}
.lp-bg-wrap::before,
.lp-bg-wrap::after{
  content:''; position:absolute; border-radius:50%;
  filter:blur(70px); opacity:.35;
  background:radial-gradient(circle, var(--g) 0%, transparent 70%);
  animation:lpFloat 14s ease-in-out infinite;
}
.lp-bg-wrap::before{ width:340px; height:340px; top:-80px; left:-80px; animation-delay:0s; }
.lp-bg-wrap::after{ width:300px; height:300px; bottom:-100px; right:-60px; animation-delay:3.5s; background:radial-gradient(circle, var(--gd) 0%, transparent 70%); }
@keyframes lpFloat{
  0%,100%{ transform:translate(0,0) scale(1); }
  50%{ transform:translate(30px,-25px) scale(1.12); }
}
body.has-bg-img .lp-bg-wrap{
  background-image:var(--bg-img); background-size:cover; background-position:center;
}
body.has-bg-img .lp-bg-wrap::after{
  content:''; position:absolute; inset:0; border-radius:0; filter:blur(18px) saturate(1.1);
  background:inherit; opacity:1; animation:none; background-image:var(--bg-img); background-size:cover; background-position:center;
}
body.has-bg-img .lp-bg-wrap::before{ display:none; }
.lp-bg-dim{
  position:fixed; inset:0; z-index:1;
  background:rgba(0,0,0,.45);
  backdrop-filter:blur(2px);
}

.lp-card{
  position:relative; z-index:2;
  background:rgba(255,255,255,.06);
  border:1px solid rgba(255,255,255,.14);
  border-radius:26px;
  padding:38px 26px 30px;
  width:100%; max-width:380px;
  backdrop-filter:blur(18px) saturate(140%);
  -webkit-backdrop-filter:blur(18px) saturate(140%);
  box-shadow:0 20px 60px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.04) inset;
  opacity:0; transform:translateY(28px) scale(.96);
  animation:lpCardIn .75s cubic-bezier(.2,.9,.2,1) .15s forwards;
}
@keyframes lpCardIn{ to{ opacity:1; transform:translateY(0) scale(1); } }

.lp-logo-wrap{
  opacity:0; transform:scale(.7);
  animation:lpPop .6s cubic-bezier(.34,1.56,.64,1) .35s forwards;
}
@keyframes lpPop{ to{ opacity:1; transform:scale(1); } }

.lp-title{
  opacity:0; transform:translateY(10px);
  animation:lpFadeUp .55s ease .5s forwards;
}
.lp-sub{
  opacity:0; transform:translateY(10px);
  animation:lpFadeUp .55s ease .6s forwards;
}
@keyframes lpFadeUp{ to{ opacity:1; transform:translateY(0); } }

.lp-lang{
  opacity:0; transform:translateY(10px);
  animation:lpFadeUp .55s ease .7s forwards;
}

.lp-btns > *{
  opacity:0; transform:translateY(14px);
  animation:lpFadeUp .55s ease forwards;
}
.lp-btns > *:nth-child(1){ animation-delay:.8s; }
.lp-btns > *:nth-child(2){ animation-delay:.92s; }
.lp-btns > *:nth-child(3){ animation-delay:1.04s; }

.lp-skip{
  display:flex; align-items:center; justify-content:center;
  padding:11px 20px; border-radius:50px; font-size:.86rem; font-weight:700;
  text-decoration:none; width:100%; gap:7px; font-family:inherit;
  background:linear-gradient(135deg,var(--g),var(--gd));
  color:var(--k);
  transition:transform .15s, box-shadow .2s;
  box-shadow:0 6px 18px rgba(0,0,0,.25);
}
.lp-skip:active{ transform:scale(.97); }
.lp-skip svg{ flex-shrink:0; }

.wl .ic svg{ filter:drop-shadow(0 4px 14px rgba(0,0,0,.35)); }
.wl img{ filter:drop-shadow(0 4px 14px rgba(0,0,0,.35)); }
</style>
</head>
<body class="<?= $theme ?><?php if (!empty($cfg['mobile_bg_image'])): ?> has-bg-img<?php endif; ?>" id="bodyRoot" <?php if (!empty($cfg['mobile_bg_image'])): ?>style="--bg-img:url('<?= htmlspecialchars($cfg['mobile_bg_image']) ?>')"<?php endif; ?>>

<div class="lp-bg-wrap"></div>
<div class="lp-bg-dim"></div>

<div class="wp" style="position:relative;z-index:2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">
  <div class="lp-card">
    <div class="wl" style="text-align:center;margin-bottom:18px">
      <div class="lp-logo-wrap" style="display:flex;justify-content:center;margin-bottom:12px">
        <?php if ($logo): ?>
        <img src="<?= htmlspecialchars($logo) ?>" alt="<?= $sname ?>" style="max-height:72px;max-width:220px;object-fit:contain;display:block">
        <?php else: ?>
        <span class="ic"><svg viewBox="0 0 24 24" style="width:64px;height:64px;fill:var(--g)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></span>
        <?php endif; ?>
      </div>
      <h1 class="lp-title"><?= $sname ?></h1>
      <p class="lp-sub" data-bn="<?= $sub ?>" data-en="<?= $sub ?>"><?= $sub ?></p>
    </div>

    <div class="lang-sel lp-lang">
      <button class="lang-btn lang-btn-item" data-lang="bn" onclick="setLang('bn')">বাংলা</button>
      <button class="lang-btn lang-btn-item" data-lang="en" onclick="setLang('en')">English</button>
    </div>

    <div class="wbtns lp-btns">
      <button class="bg" onclick="om('ml')"><svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;flex-shrink:0"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg><span data-bn="লগিন করুন" data-en="Login">Login</span></button>
      <button class="bo" onclick="om('mr')"><svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;flex-shrink:0"><path d="M15 14c-2.67 0-8 1.33-8 4v2h16v-2c0-2.67-5.33-4-8-4zm-8.94-6H4v2h2.06v2.06h2V10H10V8H8.06V5.94h-2V8zM15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/></svg><span data-bn="অ্যাকাউন্ট খুলুন" data-en="Create Account">Create Account</span></button>
      <a href="/home" class="lp-skip" data-bn="→ এখন না, পরে করব" data-en="→ Skip for now">
        <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M16.01 11H4v2h12.01v3L20 12l-3.99-4z"/></svg>
        <span data-bn="এখন না, পরে করব" data-en="Skip for now">Skip for now</span>
      </a>
    </div>
  </div>
</div>

<!-- Login Modal -->
<div class="overlay" id="ml">
  <div class="modal" style="position:relative">
    <button class="mc" onclick="cm('ml')">✕</button>
    <div class="mt"><span class="ic"><svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:var(--g)"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg></span><h3 data-bn="লগিন করুন" data-en="Login">Login</h3></div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/home">
      <div class="fd"><label data-bn="আপনার নাম" data-en="Your Name">Name</label><input type="text" name="name" class="inp" placeholder="Full name" required></div>
      <div class="fd"><label data-bn="মোবাইল নম্বর" data-en="Mobile Number">Mobile</label><input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required></div>
      <button type="submit" class="bg"><span data-bn="লগিন →" data-en="Login →">Login →</span></button>
    </form>
  </div>
</div>

<!-- Register Modal -->
<div class="overlay" id="mr">
  <div class="modal" style="position:relative">
    <button class="mc" onclick="cm('mr')">✕</button>
    <div class="mt"><span class="ic"><svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:var(--g)"><path d="M15 14c-2.67 0-8 1.33-8 4v2h16v-2c0-2.67-5.33-4-8-4zm-8.94-6H4v2h2.06v2.06h2V10H10V8H8.06V5.94h-2V8zM15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/></svg></span><h3 data-bn="অ্যাকাউন্ট খুলুন" data-en="Create Account">Create Account</h3></div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/home">
      <div class="fd"><label data-bn="আপনার নাম" data-en="Your Name">Name</label><input type="text" name="name" class="inp" placeholder="Full name" required></div>
      <div class="fd"><label data-bn="মোবাইল নম্বর" data-en="Mobile Number">Mobile</label><input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required></div>
      <button type="submit" class="bg"><span data-bn="অ্যাকাউন্ট খুলুন →" data-en="Create Account →">Create Account →</span></button>
    </form>
  </div>
</div>

<script>
function om(id){document.getElementById(id).classList.add('show');document.body.style.overflow='hidden';}
function cm(id){document.getElementById(id).classList.remove('show');document.body.style.overflow='';}
document.querySelectorAll('.overlay').forEach(function(el){el.addEventListener('click',function(e){if(e.target===this)cm(this.id);});});

// Language
(function(){
  var saved = localStorage.getItem('lang') || (document.cookie.match(/(?:^|;\s*)lang=([^;]+)/)||[])[1] || 'en';
  applyLang(saved);
  function applyLang(l){
    var html=document.getElementById('htmlRoot');
    if(html)html.lang=l==='bn'?'bn':'en';
    document.querySelectorAll('[data-bn],[data-en]').forEach(function(el){
      var bn=el.getAttribute('data-bn')||'';
      var en=el.getAttribute('data-en')||'';
      el.textContent=l==='bn'?(bn||en):(en||bn);
    });
    document.querySelectorAll('.lang-btn-item').forEach(function(b){
      var on=b.dataset.lang===l;
      b.classList.toggle('active',on);
      b.style.borderColor=on?'var(--g)':'';
      b.style.color=on?'var(--g)':'';
    });
  }
  window.setLang=function(l){
    localStorage.setItem('lang',l);
    document.cookie='lang='+l+';path=/;max-age='+(365*86400);
    applyLang(l);
  };
})();
</script>
</body>
</html>