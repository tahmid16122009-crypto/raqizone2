<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

// ── Rate limiting: একই IP থেকে ৫ মিনিটে সর্বোচ্চ ১০ বার login attempt ──
$ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateKey   = 'login_rate_' . md5($ip);
$rateCount = $_SESSION[$rateKey . '_count'] ?? 0;
$rateUntil = $_SESSION[$rateKey . '_until'] ?? 0;
$now       = time();

if ($rateUntil > $now) {
    http_response_code(429);
    echo '<p style="font-family:sans-serif;text-align:center;margin-top:40px;color:#F44336">Too many attempts. Please wait a few minutes and try again.</p>';
    exit;
}

// ── CSRF token verify ──
$token     = $_POST['csrf_token'] ?? '';
$sessToken = $_SESSION['csrf_login_token'] ?? '';

if (!$sessToken || !hash_equals($sessToken, $token)) {
    http_response_code(403);
    echo '<p style="font-family:sans-serif;text-align:center;margin-top:40px;color:#F44336">Invalid request. Please refresh the page and try again.</p>';
    exit;
}
unset($_SESSION['csrf_login_token']); // one-time token

// ── Input ──
$name   = trim($_POST['name']   ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$next   = trim($_POST['next']   ?? '/home');

// Validate next URL (open redirect prevent)
if (!preg_match('/^\/[a-zA-Z0-9\/?=&_\-]*$/', $next)) {
    $next = '/home';
}

if ($name && $mobile) {
    $rateCount++;
    $_SESSION[$rateKey . '_count'] = $rateCount;
    if ($rateCount >= 10) {
        $_SESSION[$rateKey . '_until'] = $now + (5 * 60);
        $_SESSION[$rateKey . '_count'] = 0;
    }
    rz_login_user($name, $mobile);
}

header('Location: ' . $next);
exit;