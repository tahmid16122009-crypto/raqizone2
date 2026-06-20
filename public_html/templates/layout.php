<?php
if (!defined('DB_HOST')) require_once __DIR__ . '/../config.php';
if (!class_exists('DB')) require_once __DIR__ . '/../database.php';
if (!function_exists('rz_get_user')) require_once __DIR__ . '/../auth.php';

$cfg = get_all_settings();

function render_head(string $title, array $cfg): void {
    $theme     = 'theme-' . ($cfg['site_theme'] ?? 'golden');
    $mbg       = htmlspecialchars($cfg['mobile_bg_image']  ?? '', ENT_QUOTES);
    $dbg       = htmlspecialchars($cfg['desktop_bg_image'] ?? '', ENT_QUOTES);
    $sname     = htmlspecialchars($cfg['site_name']        ?? 'Raqizone', ENT_QUOTES);
    $meta_desc = htmlspecialchars($cfg['meta_description'] ?? ($cfg['welcome_subtitle'] ?? 'Best products, best prices'), ENT_QUOTES);
    $meta_kw   = htmlspecialchars($cfg['meta_keywords']    ?? '', ENT_QUOTES);
    $fav_ext   = $cfg['site_favicon_ext'] ?? '';
    $site_url  = defined('SITE_URL') ? SITE_URL : 'https://raqizone.com';
    $fav_tag   = $fav_ext ? "<link rel=\"icon\" href=\"{$site_url}/favicon.{$fav_ext}\" type=\"image/" . ($fav_ext==='ico'?'x-icon':$fav_ext) . "\">" : '';

    echo <<<HTML
<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>{$title}</title>
<meta name="description" content="{$meta_desc}">
<meta name="keywords" content="{$meta_kw}">
<meta property="og:title" content="{$title}">
<meta property="og:description" content="{$meta_desc}">
<meta property="og:site_name" content="{$sname}">
{$fav_tag}
<link rel="stylesheet" href="/static/css/style.css">
</head>
<body id="bodyRoot" class="{$theme}" data-mobile-bg="{$mbg}" data-desktop-bg="{$dbg}">
HTML;
}

function render_nav(string $page = ''): void {
    $pages = [
        'home'   => ['/home',   'হোম',   'Home',   'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z'],
        'cart'   => ['/cart',   'কার্ট', 'Cart',   'M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z'],
        'orders' => ['/orders', 'অর্ডার','Orders', 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z'],
        'me'     => ['/me',     'তথ্য',  'Info',   'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z'],
    ];

    // ── Mobile bottom nav
    echo '<nav class="bnav" id="mainBnav">';
    foreach ($pages as $key => [$url, $bn, $en, $path]) {
        $cls = $key === $page ? ' class="active"' : '';
        echo "<a href=\"{$url}\"{$cls}>"
           . "<svg viewBox=\"0 0 24 24\"><path d=\"{$path}\"/></svg>"
           . "<span data-bn=\"{$bn}\" data-en=\"{$en}\">{$en}</span>"
           . "</a>";
    }
    echo '</nav>';

    // ── Desktop nav bar (সব screen এ দেখাবে desktop এ)
    echo '<div class="desk-nav" id="deskNav">';
    foreach ($pages as $key => [$url, $bn, $en, $path]) {
        $cls = $key === $page ? ' class="active"' : '';
        echo "<a href=\"{$url}\"{$cls}>"
           . "<svg viewBox=\"0 0 24 24\" style=\"width:18px;height:18px;fill:currentColor\"><path d=\"{$path}\"/></svg>"
           . "<span data-bn=\"{$bn}\" data-en=\"{$en}\">{$en}</span>"
           . "</a>";
    }
    echo '</div>';
}

function render_foot(): void {
    echo <<<'HTML'
<script>
(function(){
  var body = document.getElementById('bodyRoot');
  if (!body) return;

  // Background image
  var isMobile = window.innerWidth <= 768;
  var mbg = body.dataset.mobileBg || '';
  var dbg = body.dataset.desktopBg || '';
  var bg  = isMobile ? mbg : (dbg || mbg);
  if (bg) {
    body.style.setProperty('--bg-img', 'url("' + bg + '")');
    body.classList.add('has-bg-img');
  }

  // Language
  var savedLang = localStorage.getItem('lang') ||
    (document.cookie.match(/(?:^|;\s*)lang=([^;]+)/) || [])[1] || 'en';
  applyLang(savedLang);

  function applyLang(l) {
    var html = document.getElementById('htmlRoot');
    if (html) html.lang = l === 'bn' ? 'bn' : 'en';
    body.classList.remove('lang-en','lang-bn');
    body.classList.add(l === 'en' ? 'lang-en' : 'lang-bn');

    document.querySelectorAll('[data-bn],[data-en]').forEach(function(el) {
      var bn = el.getAttribute('data-bn') || '';
      var en = el.getAttribute('data-en') || '';
      if (!bn && !en) return;
      el.textContent = l === 'bn' ? (bn || en) : (en || bn);
    });

    document.querySelectorAll('[data-bn-placeholder]').forEach(function(el) {
      var bn = el.getAttribute('data-bn-placeholder') || '';
      var en = el.getAttribute('data-en-placeholder') || '';
      el.placeholder = l === 'bn' ? (bn || en) : (en || bn);
    });

    document.querySelectorAll('.lang-btn-item').forEach(function(b) {
      var on = b.dataset.lang === l;
      b.classList.toggle('active', on);
      b.style.borderColor = on ? 'var(--g)' : '';
      b.style.color = on ? 'var(--g)' : '';
    });
  }

  window.setLang = function(l) {
    localStorage.setItem('lang', l);
    document.cookie = 'lang=' + l + ';path=/;max-age=' + (365 * 86400);
    applyLang(l);
  };

  window._getLang = function() {
    return localStorage.getItem('lang') ||
      (document.cookie.match(/(?:^|;\s*)lang=([^;]+)/) || [])[1] || 'en';
  };
})();
</script>
</body>
</html>
HTML;
}