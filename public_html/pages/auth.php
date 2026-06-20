<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

$name   = trim($_POST['name']   ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$next   = trim($_POST['next']   ?? '/home');

// Validate next URL
if (!preg_match('/^\/[a-zA-Z0-9\/?=&_-]*$/', $next)) {
    $next = '/home';
}

if ($name && $mobile) {
    rz_login_user($name, $mobile);
}

header('Location: ' . $next);
exit;