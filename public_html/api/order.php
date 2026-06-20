<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'place') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { echo json_encode(['error' => 'bad_body']); exit; }

    $name   = trim($body['name']   ?? '');
    $mobile = trim($body['mobile'] ?? '');
    $items  = $body['items']  ?? [];

    if (!$items || !$name || !$mobile) {
        echo json_encode(['error' => 'missing_fields']); exit;
    }

    $u = rz_get_user();
    if (!$u) {
        try {
            rz_login_user($name, $mobile);
            $u = rz_get_user();
        } catch (Throwable $e) {
            echo json_encode(['error' => 'auto_login_failed: ' . $e->getMessage()]); exit;
        }
    }
    if (!$u) { echo json_encode(['error' => 'session_error']); exit; }

    try {
        $advance_percent = (float)($body['advance_percent'] ?? 0);
        $advance_amount  = (float)($body['advance_amount']  ?? 0);

        $oid = DB::exec(
            "INSERT INTO orders
             (user_id,name,mobile,district,upazila,union_name,village,road_name,holding_number,
              total_amount,delivery_charge,advance_percent,advance_amount,
              payment_method,payment_status,sender_last4,status,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',NOW())",
            [
                $u['user_id'], $name, $mobile,
                $body['district']        ?? '',
                $body['upazila']         ?? '',
                $body['union_name']      ?? '',
                $body['village']         ?? '',
                $body['road_name']       ?? '',
                $body['holding_number']  ?? '',
                (float)($body['total_amount']    ?? 0),
                (float)($body['delivery_charge'] ?? 0),
                $advance_percent,
                $advance_amount,
                $body['payment_method']  ?? 'cod',
                $body['payment_status']  ?? 'cod',
                $body['sender_last4']    ?? '',
            ]
        );

        foreach ($items as $item) {
            $cd_slots = $item['custom_design_slots'] ?? [];
            DB::run(
                "INSERT INTO order_items
                 (order_id,product_id,product_image_id,product_name,image_path,
                  quantity,price,extra_price,selected_options,
                  custom_design_text,custom_design_slots,
                  custom_design_image1,custom_design_image2,created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
                [
                    $oid,
                    (int)($item['product_id']       ?? 0),
                    (int)($item['product_image_id'] ?? 0) ?: null,
                    $item['product_name']            ?? '',
                    $item['image_path']              ?? '',
                    (int)($item['quantity']          ?? 1),
                    (float)($item['price']           ?? 0),
                    (float)($item['extra_price']     ?? 0),
                    json_encode($item['selected_options'] ?? []),
                    $item['custom_design_text']      ?? '',
                    json_encode($cd_slots),
                    $item['custom_design_image1']    ?? '',
                    $item['custom_design_image2']    ?? '',
                ]
            );
        }
        echo json_encode(['ok' => true, 'order_id' => $oid]);

    } catch (Throwable $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'cancel') {
    $u = rz_get_user();
    if (!$u) { echo json_encode(['error' => 'not_logged_in']); exit; }
    $id    = (int)($_GET['id'] ?? 0);
    $order = DB::row("SELECT * FROM orders WHERE id=? AND user_id=?", [$id, $u['user_id']]);
    if (!$order) { echo json_encode(['error' => 'not_found']); exit; }
    if ((time() - strtotime($order['created_at'])) >= 86400) {
        echo json_encode(['error' => 'time_expired']); exit;
    }
    DB::run("UPDATE orders SET status='cancelled' WHERE id=?", [$id]);
    echo json_encode(['ok' => true]);

} elseif ($action === 'edit') {
    $u = rz_get_user();
    if (!$u) { header('Location: /'); exit; }
    $id    = (int)($_GET['id'] ?? 0);
    $order = DB::row("SELECT * FROM orders WHERE id=? AND user_id=?", [$id, $u['user_id']]);
    if (!$order || (time() - strtotime($order['created_at'])) >= 86400) {
        header('Location: /orders/' . $id); exit;
    }
    DB::run(
        "UPDATE orders SET name=?,mobile=?,district=?,upazila=?,union_name=?,village=?,road_name=?,holding_number=? WHERE id=?",
        [
            trim($_POST['name']           ?? ''),
            trim($_POST['mobile']         ?? ''),
            trim($_POST['district']       ?? ''),
            trim($_POST['upazila']        ?? ''),
            trim($_POST['union_name']     ?? ''),
            trim($_POST['village']        ?? ''),
            trim($_POST['road_name']      ?? ''),
            trim($_POST['holding_number'] ?? ''),
            $id
        ]
    );
    header('Location: /orders/' . $id);

} elseif ($action === 'status') {
    if (!rz_is_admin()) { echo json_encode(['error' => 'unauth']); exit; }
    $id     = (int)($_GET['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    DB::run("UPDATE orders SET status=? WHERE id=?", [$status, $id]);
    echo json_encode(['ok' => true]);

} else {
    echo json_encode(['error' => 'unknown_action']);
}