<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

$uri  = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = rtrim($path, '/');
if ($path === '') $path = '/';

$segments = array_values(array_filter(explode('/', $path)));

// Root URL -> the welcome/landing index.php
if ($path === '/') {
    require __DIR__ . '/index.php';
    exit;
}

$first = $segments[0] ?? '';

// /auth/login, /auth/logout -> pages/auth.php, pages/logout.php (handles via first segment routing below)
if ($first === 'auth') {
    $action = $segments[1] ?? 'login';
    if ($action === 'logout') {
        $target = __DIR__ . '/pages/logout.php';
    } else {
        $target = __DIR__ . '/pages/auth.php';
    }
    if (is_file($target)) {
        require $target;
        exit;
    }
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

// Everything else: /home -> pages/home.php, /cart -> pages/cart.php, etc.
$target = __DIR__ . '/pages/' . $first . '.php';

if (is_file($target)) {
    require $target;
    exit;
}

http_response_code(404);
echo '404 Not Found';