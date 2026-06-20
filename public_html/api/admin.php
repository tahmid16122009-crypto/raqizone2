<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

if (!rz_is_admin()) { echo json_encode(['error' => 'unauth']); exit; }

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'toggle') {
    $p = DB::row("SELECT is_active FROM products WHERE id = ?", [$id]);
    if (!$p) { echo json_encode(['error' => 'not_found']); exit; }
    $ns = $p['is_active'] ? 0 : 1;
    DB::run("UPDATE products SET is_active = ? WHERE id = ?", [$ns, $id]);
    echo json_encode(['ok' => true, 'active' => (bool)$ns]);

} elseif ($action === 'delete') {
    DB::run("DELETE FROM products WHERE id = ?", [$id]);
    echo json_encode(['ok' => true]);

} else {
    echo json_encode(['error' => 'unknown_action']);
}