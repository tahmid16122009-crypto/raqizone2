<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

$u      = rz_get_user();
$action = $_GET['action'] ?? '';

if (!$u) { echo json_encode(['error' => 'not_logged_in']); exit; }

if ($action === 'add') {
    $product_id       = (int)($_POST['product_id']       ?? 0);
    $product_image_id = (int)($_POST['product_image_id'] ?? 0);
    $product_name     = trim($_POST['product_name']      ?? '');
    $image_path       = trim($_POST['image_path']        ?? '');
    $quantity         = (int)($_POST['quantity']         ?? 1);
    $price            = (float)($_POST['price']          ?? 0);
    if (!$product_id || !$product_name) { echo json_encode(['error' => 'missing_fields']); exit; }
    $uc = DB::row("SELECT id FROM users WHERE id = ?", [$u['user_id']]);
    if (!$uc) { echo json_encode(['error' => 'session_expired']); exit; }
    $ex = DB::row("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_image_id = ?", [$u['user_id'], $product_image_id]);
    if ($ex) {
        DB::run("UPDATE cart_items SET quantity = ? WHERE id = ?", [$ex['quantity'] + $quantity, $ex['id']]);
    } else {
        DB::run("INSERT INTO cart_items (user_id, product_id, product_image_id, product_name, image_path, quantity, price, selected_options, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, '{}', NOW())",
            [$u['user_id'], $product_id, $product_image_id, $product_name, $image_path, $quantity, $price]);
    }
    echo json_encode(['ok' => true]);

} elseif ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    DB::run("DELETE FROM cart_items WHERE id = ? AND user_id = ?", [$id, $u['user_id']]);
    echo json_encode(['ok' => true]);

} elseif ($action === 'update') {
    $id  = (int)($_GET['id']        ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    if ($qty <= 0) {
        DB::run("DELETE FROM cart_items WHERE id = ? AND user_id = ?", [$id, $u['user_id']]);
    } else {
        DB::run("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?", [$qty, $id, $u['user_id']]);
    }
    echo json_encode(['ok' => true]);

} else {
    echo json_encode(['error' => 'unknown_action']);
}