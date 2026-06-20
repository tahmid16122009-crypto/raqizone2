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
</head>
<body class="<?= $theme ?>" id="bodyRoot">

<div class="wp">
  <div class="wl">
    <?php if ($logo): ?>
    <img src="<?= htmlspecialchars($logo) ?>" alt="<?= $sname ?>" style="max-height:72px;max-width:220px;object-fit:contain;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto">
    <?php else: ?>
    <span class="ic">ЁЯЫНя╕П</span>
    <?php endif; ?>
    <h1><?= $sname ?></h1>
    <p data-bn="<?= $sub ?>" data-en="<?= $sub ?>"><?= $sub ?></p>
  </div>

  <div class="lang-sel">
    <button class="lang-btn lang-btn-item" data-lang="bn" onclick="setLang('bn')">ржмрж╛ржВрж▓рж╛</button>
    <button class="lang-btn lang-btn-item" data-lang="en" onclick="setLang('en')">English</button>
  </div>

  <div class="wbtns">
    <button class="bg" onclick="om('ml')" data-bn="ЁЯФР рж▓ржЧрж┐ржи ржХрж░рзБржи" data-en="ЁЯФР Login">ЁЯФР Login</button>
    <button class="bo" onclick="om('mr')" data-bn="тЬи ржЕрзНржпрж╛ржХрж╛ржЙржирзНржЯ ржЦрзБрж▓рзБржи" data-en="тЬи Create Account">тЬи Create Account</button>
    <a href="/home" style="display:flex;align-items:center;justify-content:center;padding:11px 20px;border-radius:50px;font-size:.86rem;font-weight:700;text-decoration:none;width:100%;background:linear-gradient(135deg,#2196F3,#1565C0);color:#fff;font-family:inherit" data-bn="тЖТ ржПржЦржи ржирж╛, ржкрж░рзЗ ржХрж░ржм" data-en="тЖТ Skip for now">тЖТ Skip for now</a>
  </div>
</div>

<!-- Login Modal -->
<div class="overlay" id="ml">
  <div class="modal" style="position:relative">
    <button class="mc" onclick="cm('ml')">тЬХ</button>
    <div class="mt"><span class="ic">ЁЯФР</span><h3 data-bn="рж▓ржЧрж┐ржи ржХрж░рзБржи" data-en="Login">Login</h3></div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/home">
      <div class="fd"><label data-bn="ржЖржкржирж╛рж░ ржирж╛ржо" data-en="Your Name">Name</label><input type="text" name="name" class="inp" placeholder="Full name" required></div>
      <div class="fd"><label data-bn="ржорзЛржмрж╛ржЗрж▓ ржиржорзНржмрж░" data-en="Mobile Number">Mobile</label><input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required></div>
      <button type="submit" class="bg" data-bn="рж▓ржЧрж┐ржи тЖТ" data-en="Login тЖТ">Login тЖТ</button>
    </form>
  </div>
</div>

<!-- Register Modal -->
<div class="overlay" id="mr">
  <div class="modal" style="position:relative">
    <button class="mc" onclick="cm('mr')">тЬХ</button>
    <div class="mt"><span class="ic">тЬи</span><h3 data-bn="ржЕрзНржпрж╛ржХрж╛ржЙржирзНржЯ ржЦрзБрж▓рзБржи" data-en="Create Account">Create Account</h3></div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/home">
      <div class="fd"><label data-bn="ржЖржкржирж╛рж░ ржирж╛ржо" data-en="Your Name">Name</label><input type="text" name="name" class="inp" placeholder="Full name" required></div>
      <div class="fd"><label data-bn="ржорзЛржмрж╛ржЗрж▓ ржиржорзНржмрж░" data-en="Mobile Number">Mobile</label><input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required></div>
      <button type="submit" class="bg" data-bn="ржЕрзНржпрж╛ржХрж╛ржЙржирзНржЯ ржЦрзБрж▓рзБржи тЬи" data-en="Create Account тЬи">Create Account тЬи</button>
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