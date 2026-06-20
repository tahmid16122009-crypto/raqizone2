 <?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (!$q) { echo json_encode([]); exit; }

try {
    $results = DB::rows(
        "SELECT id, name, base_price, regular_price, discount_percent, delivery_charge, is_free_delivery,
         (SELECT image_path FROM product_images WHERE product_id=products.id ORDER BY sort_order ASC LIMIT 1) AS thumb
         FROM products WHERE is_active=1 AND name LIKE ?
         ORDER BY created_at DESC LIMIT 8",
        ['%' . $q . '%']
    );
    $out = [];
    foreach ($results as $r) {
        $price = (float)$r['base_price'];
        if (($r['discount_percent'] ?? 0) > 0 && ($r['regular_price'] ?? 0) > 0) {
            $price = round($r['regular_price'] * (1 - $r['discount_percent']/100));
        }
        $out[] = [
            'id'            => $r['id'],
            'name'          => $r['name'],
            'price'         => $price,
            'thumb'         => $r['thumb'] ?? '',
            'free_delivery' => (bool)($r['is_free_delivery'] ?? 0),
            'delivery'      => (float)$r['delivery_charge'],
        ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([]);
}