<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

if (rz_is_admin()) { header('Location: /admin'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = trim($_POST['password'] ?? '');
    if (rz_admin_login($pw)) {
        header('Location: /admin'); exit;
    }
    $error = 'Wrong password';
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
      <div style="font-size:2.5rem;margin-bottom:10px">🔐</div>
      <h1 style="font-size:1.2rem;font-weight:700;color:var(--g)">Admin Login</h1>
    </div>
    <?php if ($error): ?>
    <div style="background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:.86rem"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="/admin/login" method="POST" class="fs">
      <div class="fd">
        <label style="font-size:.82rem;font-weight:600;color:var(--w)">Password</label>
        <input type="password" name="password" class="inp" placeholder="Admin password" autofocus required>
      </div>
      <button type="submit" class="bg" style="margin-top:4px">🔐 Login</button>
    </form>
    <a href="/home" style="display:block;text-align:center;margin-top:16px;color:var(--agray);font-size:.8rem;text-decoration:none">← Back to store</a>
  </div>
</div>
</body>
</html>