<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

if (rz_is_admin()) { header('Location: /admin'); exit; }

// CSRF token generate করো (session এ না থাকলে)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if (rz_admin_login_locked()) {
    $secsLeft = rz_admin_login_lock_seconds_left();
    $minsLeft = ceil($secsLeft / 60);
    $error = "Too many failed attempts. Try again in {$minsLeft} minute" . ($minsLeft > 1 ? 's' : '') . '.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = 'Session expired, please try again.';
    } else {
        $pw = trim($_POST['password'] ?? '');
        if (rz_admin_login($pw)) {
            header('Location: /admin'); exit;
        }
        if (rz_admin_login_locked()) {
            $secsLeft = rz_admin_login_lock_seconds_left();
            $minsLeft = ceil($secsLeft / 60);
            $error = "Too many failed attempts. Try again in {$minsLeft} minute" . ($minsLeft > 1 ? 's' : '') . '.';
        } else {
            $error = 'Wrong password';
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$cfg   = get_all_settings();
$theme = 'theme-' . ($cfg['site_theme'] ?? 'golden');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Admin Login</title>
<link rel="stylesheet" href="/static/css/style.css">
<link rel="stylesheet" href="/static/css/admin.css">
</head>
<body class="<?= $theme ?>">
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:16px;padding:32px 24px;width:100%;max-width:360px">
    <div style="text-align:center;margin-bottom:24px">
      <div style="margin-bottom:10px;display:flex;justify-content:center;color:var(--g)"><svg viewBox="0 0 24 24" style="width:42px;height:42px;fill:currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg></div>
      <h1 style="font-size:1.2rem;font-weight:700;color:var(--g)">Admin Login</h1>
    </div>
    <?php if ($error): ?>
    <div style="background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:.86rem"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!rz_admin_login_locked()): ?>
    <form action="/admin/login" method="POST" class="fs">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="fd">
        <label style="font-size:.82rem;font-weight:600;color:var(--w)">Password</label>
        <input type="password" name="password" class="inp" placeholder="Admin password" autofocus required>
      </div>
      <button type="submit" class="bg" style="margin-top:4px;display:flex;align-items:center;justify-content:center;gap:7px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>Login</button>
    </form>
    <?php endif; ?>
    <a href="/home" style="display:block;text-align:center;margin-top:16px;color:var(--agray);font-size:.8rem;text-decoration:none">← Back to store</a>
  </div>
</div>
</body>
</html>