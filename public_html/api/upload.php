<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    echo json_encode(['error' => 'no_image']); exit;
}

$url = upload_image($_FILES['image'], 'design', 'designs');
if (!$url) { echo json_encode(['error' => 'upload_failed']); exit; }

echo json_encode(['ok' => true, 'url' => $url]);