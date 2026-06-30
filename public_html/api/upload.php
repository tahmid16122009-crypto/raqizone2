<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    echo json_encode(['error' => 'no_image']); exit;
}

$url = upload_image($_FILES['image'], 'design', 'designs');
if (!$url) {
    $reason = $GLOBALS['_upload_error'] ?? 'unknown_error';
    echo json_encode(['error' => 'upload_failed', 'reason' => $reason]); exit;
}

echo json_encode(['ok' => true, 'url' => $url]);