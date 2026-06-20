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
  font-size: 1.2rem;
  width: 28px;
  text-align: center;
  flex-shrink: 0;
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
    $nav = [
        'dashboard' => ['/admin',          '🏠', 'Dashboard'],
        'products'  => ['/admin/products', '📦', 'Products'],
        'orders'    => ['/admin/orders',   '📋', 'Orders'],
        'posts'     => ['/admin/posts',    '📢', 'Updates'],
        'edit-ui'   => ['/admin/edit-ui',  '⚙️', 'Settings'],
    ];

    $cfg   = get_all_settings();
    $sname = htmlspecialchars($cfg['site_name'] ?? 'Raqizone');

    // Top bar with hamburger
    echo <<<HTML
<div class="a-topbar">
  <button class="a-hamburger" id="aHamBtn" onclick="openDrawer()" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
  <span class="a-topbar-title">⚡ {$sname} Admin</span>
  <a href="/home" style="font-size:.72rem;color:var(--agray);text-decoration:none;padding:6px 10px;background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px">🌐 Site</a>
</div>

<!-- Drawer Overlay -->
<div class="a-drawer-overlay" id="aDrawerOverlay" onclick="closeDrawer()"></div>

<!-- Drawer -->
<div class="a-drawer" id="aDrawer">
  <div class="a-drawer-head">
    <span class="a-drawer-logo">⚡ Admin Panel</span>
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
    <a href="/home" onclick="closeDrawer()"><span class="nav-ico">🌐</span>View Store</a>
    <a href="/admin/logout" class="logout-link"><span class="nav-ico">🚪</span>Logout</a>
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