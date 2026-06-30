<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('DB_HOST'))             require_once __DIR__ . '/../config.php';
if (!class_exists('DB'))             require_once __DIR__ . '/../database.php';
if (!function_exists('rz_get_user')) require_once __DIR__ . '/../auth.php';

if (!function_exists('get_all_settings')) {
    function get_all_settings(): array {
        try {
            if (!class_exists('DB')) return [];
            $rows = DB::rows("SELECT `key`, value FROM site_settings");
            $out  = [];
            foreach ($rows as $r) $out[$r['key']] = $r['value'];
            return $out;
        } catch (Throwable $e) { return []; }
    }
}

if (!function_exists('save_setting')) {
    function save_setting(string $key, string $value): void {
        DB::run("INSERT INTO site_settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?", [$key, $value, $value]);
    }
}

if (!function_exists('upload_image')) {
    function upload_image(array $file, string $prefix = 'img', string $folder = 'products'): ?string {
        $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'] ?? '', $allowed)) return null;
        if (($file['size'] ?? 0) > 10 * 1024 * 1024) return null;
        $ext  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dir  = __DIR__ . '/../uploads/' . $folder . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'] ?? '', $dir . $name)) return null;
        return '/uploads/' . $folder . '/' . $name;
    }
}

$cfg = get_all_settings();

if (!rz_is_admin()) {
    header('Location: /admin/login');
    exit;
}

function admin_head(string $title = 'Admin'): void {
    $cfg   = get_all_settings();
    $theme = 'theme-' . ($cfg['site_theme'] ?? 'golden');
    $sname = htmlspecialchars($cfg['site_name'] ?? 'Raqizone');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>{$title} — {$sname} Admin</title>
<link rel="stylesheet" href="/static/css/style.css">
<link rel="stylesheet" href="/static/css/admin.css">
<style>
/* ── Hamburger Admin Nav ── */
.a-topbar {
  position: sticky;
  top: 0;
  z-index: 300;
  background: var(--ak2);
  border-bottom: 1px solid var(--abdr);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 14px;
  height: 52px;
}
.a-topbar-title {
  font-size: .96rem;
  font-weight: 700;
  color: var(--g);
  display: flex;
  align-items: center;
  gap: 6px;
}
.a-hamburger {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--ak3);
  border: 1px solid var(--abdr2);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  cursor: pointer;
  flex-shrink: 0;
}
.a-hamburger span {
  display: block;
  width: 20px;
  height: 2px;
  background: var(--g);
  border-radius: 2px;
  transition: all .3s;
}
/* Drawer overlay */
.a-drawer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.7);
  z-index: 400;
  opacity: 0;
  pointer-events: none;
  transition: opacity .3s;
  backdrop-filter: blur(4px);
}
.a-drawer-overlay.open {
  opacity: 1;
  pointer-events: all;
}
/* Drawer */
.a-drawer {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: 280px;
  max-width: 85vw;
  background: var(--ak2);
  border-right: 2px solid var(--g);
  z-index: 401;
  transform: translateX(-100%);
  transition: transform .35s cubic-bezier(.16,1,.3,1);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}
.a-drawer.open {
  transform: translateX(0);
}
.a-drawer-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 18px;
  border-bottom: 1px solid var(--abdr);
  flex-shrink: 0;
}
.a-drawer-logo {
  font-size: 1rem;
  font-weight: 700;
  color: var(--g);
  display: flex;
  align-items: center;
  gap: 6px;
}
.a-drawer-close {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--ak3);
  border: 1px solid var(--abdr2);
  color: var(--agray);
  cursor: pointer;
  font-size: .9rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.a-drawer-nav {
  display: flex;
  flex-direction: column;
  padding: 12px 0;
  flex: 1;
}
.a-drawer-nav a {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 13px 20px;
  text-decoration: none;
  color: var(--w);
  font-size: .92rem;
  font-weight: 500;
  border-left: 3px solid transparent;
  transition: all .15s;
}
.a-drawer-nav a:hover,
.a-drawer-nav a:active {
  background: var(--ak3);
  color: var(--g);
}
.a-drawer-nav a.active {
  border-left-color: var(--g);
  background: var(--gl);
  color: var(--g);
  font-weight: 700;
}
.a-drawer-nav .nav-ico {
  width: 28px;
  text-align: center;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.a-drawer-nav .nav-ico svg {
  width: 19px;
  height: 19px;
  fill: currentColor;
}
.a-drawer-nav .a-sep {
  height: 1px;
  background: var(--abdr);
  margin: 8px 16px;
}
.a-drawer-nav a.logout-link {
  color: #F44336;
  margin-top: auto;
}
.a-drawer-nav a.logout-link:hover {
  background: rgba(244,67,54,.08);
  color: #F44336;
}
</style>
</head>
<body class="{$theme}">
HTML;
}


function admin_nav(string $page = ''): void {
    $icoHome     = '<svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>';
    $icoBox      = '<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
    $icoClip     = '<svg viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
    $icoBell     = '<svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>';
    $icoGear     = '<svg viewBox="0 0 24 24"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65A.488.488 0 0 0 14 2h-4c-.24 0-.43.17-.47.41l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.23.41.47.41h4c.24 0 .44-.17.47-.41l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>';
    $icoGlobe    = '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>';
    $icoLogout   = '<svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L17.17 10H9v2h8.17l-1.58 1.59L17 15l4-4zM5 5h7V3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h7v-2H5V5z"/></svg>';
    $icoBolt     = '<svg viewBox="0 0 24 24"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>';

    $nav = [
        'dashboard' => ['/admin',          $icoHome, 'Dashboard'],
        'products'  => ['/admin/products', $icoBox,  'Products'],
        'orders'    => ['/admin/orders',   $icoClip, 'Orders'],
        'posts'     => ['/admin/posts',    $icoBell, 'Updates'],
        'edit-ui'   => ['/admin/edit-ui',  $icoGear, 'Settings'],
    ];

    $cfg   = get_all_settings();
    $sname = htmlspecialchars($cfg['site_name'] ?? 'Raqizone');

    // Top bar with hamburger
    echo <<<HTML
<div class="a-topbar">
  <button class="a-hamburger" id="aHamBtn" onclick="openDrawer()" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
  <span class="a-topbar-title">{$icoBolt} {$sname} Admin</span>
  <a href="/home" style="font-size:.72rem;color:var(--agray);text-decoration:none;padding:6px 10px;background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px;display:inline-flex;align-items:center;gap:5px">{$icoGlobe} Site</a>
</div>

<!-- Drawer Overlay -->
<div class="a-drawer-overlay" id="aDrawerOverlay" onclick="closeDrawer()"></div>

<!-- Drawer -->
<div class="a-drawer" id="aDrawer">
  <div class="a-drawer-head">
    <span class="a-drawer-logo">{$icoBolt} Admin Panel</span>
    <button class="a-drawer-close" onclick="closeDrawer()">✕</button>
  </div>
  <nav class="a-drawer-nav">
HTML;

    foreach ($nav as $key => [$url, $ico, $label]) {
        $cls = $key === $page ? ' class="active"' : '';
        echo "<a href=\"{$url}\"{$cls} onclick=\"closeDrawer()\"><span class=\"nav-ico\">{$ico}</span>{$label}</a>\n";
    }

    echo <<<HTML
    <div class="a-sep"></div>
    <a href="/home" onclick="closeDrawer()"><span class="nav-ico">{$icoGlobe}</span>View Store</a>
    <a href="/admin/logout" class="logout-link"><span class="nav-ico">{$icoLogout}</span>Logout</a>
  </nav>
</div>

<script>
function openDrawer(){
  document.getElementById('aDrawer').classList.add('open');
  document.getElementById('aDrawerOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeDrawer(){
  document.getElementById('aDrawer').classList.remove('open');
  document.getElementById('aDrawerOverlay').classList.remove('open');
  document.body.style.overflow='';
}
// Swipe to close
(function(){
  var d=document.getElementById('aDrawer'),sx=0;
  d.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;},{passive:true});
  d.addEventListener('touchend',function(e){if(sx-e.changedTouches[0].clientX>60)closeDrawer();},{passive:true});
})();
</script>
HTML;
}


function admin_foot(): void {
    echo '</body></html>';
}