<?php
require_once __DIR__ . '/base.php';

$pid     = (int)($_GET['id'] ?? 0);
$product = null;
$images  = [];
$options = [];
$error   = '';

$categories = json_decode($cfg['product_categories'] ?? '[]', true) ?: [];

if ($pid) {
    $product = DB::row("SELECT * FROM products WHERE id = ?", [$pid]);
    if (!$product) { header('Location: /admin/products'); exit; }
    $images   = DB::rows("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC", [$pid]);
    $opts_raw = DB::rows("SELECT * FROM product_options WHERE product_id=?", [$pid]);
    foreach ($opts_raw as $opt) {
        $vals_raw = DB::rows("SELECT * FROM product_option_values WHERE option_id=? ORDER BY sort_order ASC", [$opt['id']]);
        foreach ($vals_raw as &$val) {
            $subs_raw = DB::rows("SELECT * FROM product_suboptions WHERE option_value_id=? ORDER BY sort_order ASC", [$val['id']]);
            foreach ($subs_raw as &$sub) {
                $sub['values'] = DB::rows("SELECT * FROM product_suboption_values WHERE suboption_id=? ORDER BY sort_order ASC", [$sub['id']]);
            }
            unset($sub);
            $val['suboptions'] = $subs_raw;
        }
        unset($val);
        $opt['values'] = $vals_raw;
        $options[] = $opt;
    }
}

$cd_slots = json_decode($product['cd_image_slots'] ?? 'null', true);
if (!$cd_slots) {
    $cd_slots = [
        ['title' => 'সামনের ছবি', 'required' => false],
        ['title' => 'পিছনের ছবি', 'required' => false],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name                   = trim($_POST['name']        ?? '');
    $description            = trim($_POST['description'] ?? '');
    $video_url              = trim($_POST['video_url']   ?? '');
    $gender                 = trim($_POST['gender']      ?? 'all');
    $category               = trim($_POST['category']   ?? '');
    $has_custom_design      = isset($_POST['has_custom_design']) ? 1 : 0;
    $base_price             = (float)($_POST['base_price']          ?? 0);
    $regular_price          = (float)($_POST['regular_price']       ?? 0);
    $discount_percent       = (float)($_POST['discount_percent']    ?? 0);
    $delivery_charge        = (float)($_POST['delivery_charge']     ?? 60);
    $is_free_delivery       = isset($_POST['is_free_delivery']) ? 1 : 0;
    $product_payment_method = trim($_POST['product_payment_method'] ?? 'default');
    $max_quantity           = (int)($_POST['max_quantity']    ?? 0);
    $advance_percent        = (float)($_POST['advance_percent'] ?? 0);
    $options_data           = $_POST['options_data']      ?? '[]';
    $deleted_ids_raw        = $_POST['deleted_image_ids'] ?? '[]';
    $fixed_price_enabled    = isset($_POST['fixed_price_enabled']) ? 1 : 0;
    $fixed_display_price    = $fixed_price_enabled ? (float)($_POST['fixed_display_price'] ?? 0) : null;

    // CD slots
    $slot_titles   = $_POST['cd_slot_title']    ?? [];
    $slot_required = $_POST['cd_slot_required'] ?? [];
    $new_cd_slots  = [];
    foreach ($slot_titles as $si => $st) {
        if (trim($st) === '') continue;
        $new_cd_slots[] = [
            'title'    => trim($st),
            'required' => in_array((string)$si, array_keys($slot_required)),
        ];
    }

    if ($regular_price > 0 && $discount_percent > 0) {
        $base_price = round($regular_price * (1 - $discount_percent / 100));
    } elseif ($regular_price > 0 && $discount_percent == 0) {
        $base_price = $regular_price;
    }
    if ($is_free_delivery) $delivery_charge = 0;

    $img_prices     = json_decode($_POST['image_prices']     ?? '[]', true) ?: [];
    $new_img_prices = json_decode($_POST['new_image_prices'] ?? '[]', true) ?: [];
    $deleted_ids    = array_filter(json_decode($deleted_ids_raw, true) ?: []);

    if (!$name) {
        $error = 'Product name is required';
    } else {
        $fields = [
            'name'                   => $name,
            'description'            => $description,
            'base_price'             => $base_price,
            'regular_price'          => $regular_price,
            'discount_percent'       => $discount_percent,
            'delivery_charge'        => $delivery_charge,
            'is_free_delivery'       => $is_free_delivery,
            'product_payment_method' => $product_payment_method,
            'max_quantity'           => $max_quantity,
            'advance_percent'        => $advance_percent,
            'cd_image_slots'         => json_encode($new_cd_slots, JSON_UNESCAPED_UNICODE),
            'video_url'              => $video_url ?: null,
            'gender'                 => $gender,
            'category'               => $category,
            'has_custom_design'      => $has_custom_design,
            'fixed_display_price'    => $fixed_display_price,
        ];

        if ($pid) {
            $sets   = implode(',', array_map(fn($k) => "`$k`=?", array_keys($fields)));
            $vals   = array_values($fields);
            $vals[] = $pid;
            DB::run("UPDATE products SET {$sets} WHERE id=?", $vals);

            foreach ($deleted_ids as $did) {
                DB::run("DELETE FROM product_images WHERE id=? AND product_id=?", [(int)$did, $pid]);
            }
            $mo = DB::row("SELECT MAX(sort_order) AS mo FROM product_images WHERE product_id=?", [$pid]);
            $sort_idx = ((int)($mo['mo'] ?? -1)) + 1;

            if (!empty($_FILES['new_images']['name'][0])) {
                $files = $_FILES['new_images'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file = ['name'=>$files['name'][$i],'type'=>$files['type'][$i],'tmp_name'=>$files['tmp_name'][$i],'error'=>$files['error'][$i],'size'=>$files['size'][$i]];
                    $url = upload_image($file, 'prod', 'products');
                    if ($url) {
                        $price = (float)($new_img_prices[$i] ?? $base_price) ?: $base_price;
                        DB::run("INSERT INTO product_images (product_id,image_path,price,sort_order,created_at) VALUES (?,?,?,?,NOW())", [$pid,$url,$price,$sort_idx++]);
                    }
                }
            }
        } else {
            $cols    = '`' . implode('`,`', array_keys($fields)) . '`,`is_active`,`created_at`';
            $phs     = implode(',', array_fill(0, count($fields), '?')) . ',1,NOW()';
            $new_pid = DB::exec("INSERT INTO products ($cols) VALUES ($phs)", array_values($fields));

            if (!empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file = ['name'=>$files['name'][$i],'type'=>$files['type'][$i],'tmp_name'=>$files['tmp_name'][$i],'error'=>$files['error'][$i],'size'=>$files['size'][$i]];
                    $url = upload_image($file, 'prod', 'products');
                    if ($url) {
                        $price = (float)($img_prices[$i] ?? $base_price) ?: $base_price;
                        DB::run("INSERT INTO product_images (product_id,image_path,price,sort_order,created_at) VALUES (?,?,?,?,NOW())", [$new_pid,$url,$price,$i]);
                    }
                }
            }
            $pid = $new_pid;
        }

        // Options — name, is_required, values with extra_price + nested suboptions
        $opts = json_decode($options_data, true) ?: [];
        $eo   = DB::rows("SELECT id FROM product_options WHERE product_id=?", [$pid]);
        foreach ($eo as $o) DB::run("DELETE FROM product_option_values WHERE option_id=?", [$o['id']]);
        DB::run("DELETE FROM product_options WHERE product_id=?", [$pid]);
        foreach ($opts as $opt) {
            if (empty($opt['name'])) continue;
            $is_req = isset($opt['is_required']) ? (int)$opt['is_required'] : 1;
            $oid = DB::exec(
                "INSERT INTO product_options (product_id,option_name,is_required) VALUES (?,?,?)",
                [$pid, $opt['name'], $is_req]
            );
            foreach (($opt['values'] ?? []) as $j => $v) {
                if (trim($v['val'] ?? '') === '') continue;
                $vid = DB::exec(
                    "INSERT INTO product_option_values (option_id,value,extra_price,sort_order) VALUES (?,?,?,?)",
                    [$oid, trim($v['val']), (float)($v['price'] ?? 0), $j]
                );
                // Nested sub-options for this value
                foreach (($v['suboptions'] ?? []) as $sk => $sub) {
                    $subName = trim($sub['name'] ?? '');
                    if ($subName === '') continue;
                    $subId = DB::exec(
                        "INSERT INTO product_suboptions (option_value_id, suboption_name, sort_order) VALUES (?,?,?)",
                        [$vid, $subName, $sk]
                    );
                    foreach (($sub['values'] ?? []) as $sj => $sv) {
                        if (trim($sv['val'] ?? '') === '') continue;
                        DB::run(
                            "INSERT INTO product_suboption_values (suboption_id, value, extra_price, sort_order) VALUES (?,?,?,?)",
                            [$subId, trim($sv['val']), (float)($sv['price'] ?? 0), $sj]
                        );
                    }
                }
            }
        }
        header('Location: /admin/products'); exit;
    }
}

admin_head($product ? 'Edit Product' : 'New Product');
admin_nav('products');

// SVG icons used in this form
$ic_pencil = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75z"/></svg>';
$ic_box    = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
$ic_doc    = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>';
$ic_cash   = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>';
$ic_card   = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>';
$ic_bolt   = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>';
$ic_truck  = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zM18 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
$ic_free   = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
$ic_people = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>';
$ic_male   = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9.5 2C7.01 2 5 4.01 5 6.5S7.01 11 9.5 11c1.36 0 2.57-.61 3.41-1.56l4.65 4.65 1.41-1.41-4.65-4.65C15.39 7.07 16 5.86 16 4.5 16 2.01 13.99 0 11.5 0M9.5 4C10.88 4 12 5.12 12 6.5S10.88 9 9.5 9 7 7.88 7 6.5 8.12 4 9.5 4z"/></svg>';
$ic_female = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M12 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3m0 14.2c1.71 0 3.2.5 3.2 1.3 0 .47-1.21 1.5-3.2 1.5s-3.2-1.03-3.2-1.5c0-.8 1.49-1.3 3.2-1.3M12 2C9.24 2 7 4.24 7 7c0 2.24 1.49 4.13 3.5 4.78V14H8v2h2.5v2H8v2h2.5v2h3v-2H16v-2h-2.5v-2H16v-2h-2.5v-2.22C15.51 11.13 17 9.24 17 7c0-2.76-2.24-5-5-5z"/></svg>';
$ic_star   = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
$ic_pal    = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
$ic_camera = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9.4 10.5l4.77-8.26C13.47 2.09 12.75 2 12 2c-2.4 0-4.6.85-6.32 2.25l3.66 6.35.06-.1zM21.54 9c-.92-2.92-3.15-5.26-6-6.34L11.88 9h9.66zm.26 1h-7.49l.29.5 4.76 8.25C21.07 16.17 22 14.21 22 12c0-.69-.07-1.36-.2-2zM8.54 12l-3.9-6.75C3.01 7.03 2 9.39 2 12c0 .69.07 1.36.2 2h7.49l-1.15-2zm-6.08 3c.92 2.92 3.15 5.26 6 6.34L12.12 15H2.46zm11.27 0-3.9 6.76c.7.15 1.42.24 2.17.24 2.4 0 4.6-.85 6.32-2.25l-2.44-4.75H13.73z"/></svg>';
$ic_gear   = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65A.488.488 0 0 0 14 2h-4c-.24 0-.43.17-.47.41l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.23.41.47.41h4c.24 0 .44-.17.47-.41l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>';
$ic_check  = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
$ic_layer  = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M11.99 18.54l-7.37-5.73L3 14.07l9 7 9-7-1.63-1.27-7.38 5.74zM12 16l7.36-5.73L21 9l-9-7-9 7 1.63 1.27L12 16z"/></svg>';
?>

<style>
.opt-val-row{display:flex;align-items:center;gap:7px;margin-bottom:7px;background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px;padding:8px 10px;flex-wrap:wrap}
.opt-val-row .val-inp{flex:2;min-width:0}
.opt-val-row .price-inp{width:90px;flex-shrink:0}
.opt-val-row .brv{background:none;border:none;color:#F44336;cursor:pointer;font-size:.9rem;flex-shrink:0;padding:0 4px}
.cd-slot-row{display:flex;align-items:center;gap:9px;background:var(--ak3);border:1px solid var(--abdr2);border-radius:9px;padding:9px 12px;margin-bottom:8px}
.opt-header-row{display:grid;grid-template-columns:1fr 90px auto;gap:7px;margin-bottom:4px;padding:0 10px}
.opt-header-row span{font-size:.7rem;color:var(--agray);font-weight:600;text-transform:uppercase}
h3 svg{vertical-align:-2px;margin-right:5px}
.gender-btn{display:inline-flex;align-items:center;gap:5px}
.opt-val-block{background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px;padding:8px 10px;margin-bottom:7px}
.opt-val-main-row{display:flex;align-items:center;gap:7px}
.sub-zone{margin-top:8px;margin-left:14px;padding-left:10px;border-left:2px solid var(--g)}
.sub-card{background:var(--ak2);border:1px solid var(--abdr);border-radius:8px;padding:8px 10px;margin-bottom:7px}
.sub-card-head{display:flex;align-items:center;gap:7px;margin-bottom:6px}
.sub-val-row{display:flex;align-items:center;gap:6px;margin-bottom:5px}
.sub-val-row .val-inp{flex:2;min-width:0}
.sub-val-row .price-inp{width:80px;flex-shrink:0}
.btn-link-sm{background:none;border:1px solid var(--abdr2);color:var(--g);font-size:.72rem;padding:4px 9px;border-radius:6px;cursor:pointer;font-family:inherit;font-weight:600}
</style>

<div class="aph">
  <a href="/admin/products" class="abl">← Products</a>
  <h1 style="display:flex;align-items:center;gap:7px"><?= $product ? $ic_pencil : $ic_box ?><?= $product ? 'Edit Product' : 'New Product' ?></h1>
</div>

<?php if ($error): ?>
<div style="background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);padding:10px 14px;border-radius:8px;margin-bottom:16px"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form action="<?= $product ? '/admin/products/'.$product['id'].'/edit' : '/admin/products/add' ?>"
      method="POST" enctype="multipart/form-data" class="aform" id="pf">

  <!-- Basic Info -->
  <div class="afs">
    <h3><?= $ic_doc ?>Product Info</h3>
    <div class="frow"><label>Product Name *</label><input type="text" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required class="ai" placeholder="Product name"></div>
    <div class="frow"><label>Description</label><textarea name="description" class="ata" placeholder="Product description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea></div>
    <div class="frow"><label>YouTube Video URL</label><input type="url" name="video_url" value="<?= htmlspecialchars($product['video_url'] ?? '') ?>" class="ai" placeholder="https://www.youtube.com/watch?v=..."></div>
  </div>

  <!-- Pricing -->
  <div class="afs">
    <h3><?= $ic_cash ?>Pricing</h3>
    <p class="afn">Regular price দিন। Discount % দিলে Offer price auto calculate হবে।</p>
    <div class="fr2">
      <div class="frow">
        <label>Regular Price (৳)</label>
        <input type="number" name="regular_price" id="regularPrice" value="<?= $product['regular_price'] ?? '0' ?>" class="ai" min="0" step="0.01" oninput="calcOfferPrice()">
      </div>
      <div class="frow">
        <label>Discount (%)</label>
        <input type="number" name="discount_percent" id="discountPct" value="<?= $product['discount_percent'] ?? '0' ?>" class="ai" min="0" max="99" step="0.01" oninput="calcOfferPrice()">
      </div>
    </div>
    <div id="offerPreview" style="display:none;background:var(--ak3);border:1px solid var(--g);border-radius:var(--r);padding:12px;margin-bottom:13px">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <div><p style="font-size:.72rem;color:var(--agray)">Regular:</p><p id="prevRegular" style="font-size:.9rem;text-decoration:line-through;color:var(--agray)"></p></div>
        <div style="font-size:1.2rem;color:var(--g)">→</div>
        <div><p style="font-size:.72rem;color:var(--agray)">Offer:</p><p id="prevOffer" style="font-size:1.1rem;font-weight:700;color:var(--g)"></p></div>
        <div style="background:linear-gradient(135deg,#F44336,#C62828);color:#fff;border-radius:50px;padding:4px 11px;font-size:.78rem;font-weight:700" id="prevBadge"></div>
      </div>
    </div>
    <input type="hidden" name="base_price" id="basePriceInput" value="<?= $product['base_price'] ?? '0' ?>">

    <!-- Fixed Display Price for customizable products -->
    <div style="display:flex;align-items:center;gap:14px;padding:12px;background:var(--ak3);border-radius:var(--r);border:1px solid var(--abdr2);margin-top:6px">
      <div style="flex:1"><p style="font-weight:700;font-size:.88rem;margin-bottom:3px;display:flex;align-items:center;gap:6px"><?= $ic_layer ?>Fixed Display Price</p><p style="font-size:.76rem;color:var(--agray)">Home page এ এই fixed দাম দেখাবে, size/option select করলেও বদলাবে না (custom design product এর জন্য উপযোগী)</p></div>
      <label style="position:relative;width:50px;height:27px;cursor:pointer;flex-shrink:0">
        <input type="checkbox" id="fixedPriceToggle" name="fixed_price_enabled" style="opacity:0;width:0;height:0;position:absolute" <?= isset($product['fixed_display_price']) && $product['fixed_display_price']!==null ? 'checked' : '' ?> onchange="toggleFixedPrice(this)">
        <span id="fpTrack" style="position:absolute;inset:0;border-radius:50px;background:<?= isset($product['fixed_display_price']) && $product['fixed_display_price']!==null ? 'var(--g)' : 'var(--abdr2)' ?>;transition:background .3s"><span id="fpKnob" style="position:absolute;top:3px;left:<?= isset($product['fixed_display_price']) && $product['fixed_display_price']!==null ? '26px' : '3px' ?>;width:21px;height:21px;border-radius:50%;background:white;transition:left .3s;box-shadow:0 1px 4px rgba(0,0,0,.3)"></span></span>
      </label>
    </div>
    <div class="frow" id="fixedPriceRow" style="margin-top:10px;<?= (isset($product['fixed_display_price']) && $product['fixed_display_price']!==null) ? '' : 'display:none' ?>">
      <label>Fixed Display Price (৳)</label>
      <input type="number" name="fixed_display_price" id="fixedDisplayPriceInput" value="<?= $product['fixed_display_price'] ?? '' ?>" class="ai" min="0" step="0.01" placeholder="e.g. 1000">
    </div>
  </div>

  <!-- Advance Payment -->
  <div class="afs">
    <h3><?= $ic_card ?>Advance Payment</h3>
    <p class="afn">0 দিলে advance লাগবে না। যত % দেবেন user order এর মোট দামের তত % advance pay করবে। নিচের Payment Method এ "% Advance Payment" সিলেক্ট করলে user শুধু এই advance amount টাই পেমেন্ট করে অর্ডার করতে পারবে (বাকিটা ডেলিভারির সময়)।</p>
    <div class="frow">
      <label>Advance Payment (%) — 0 = disabled</label>
      <input type="number" name="advance_percent" value="<?= $product['advance_percent'] ?? '0' ?>" class="ai" min="0" max="100" step="0.01" placeholder="0">
    </div>
  </div>

  <!-- Max Quantity -->
  <div class="afs">
    <h3><?= $ic_bolt ?>Quantity Limit</h3>
    <div class="frow">
      <label>Max Quantity Per Order (0 = unlimited)</label>
      <input type="number" name="max_quantity" value="<?= $product['max_quantity'] ?? '0' ?>" class="ai" min="0" placeholder="0">
    </div>
  </div>

  <!-- Delivery -->
  <div class="afs">
    <h3><?= $ic_truck ?>Delivery</h3>
    <div style="display:flex;align-items:center;gap:14px;padding:12px;background:var(--ak3);border-radius:var(--r);border:1px solid var(--abdr2);margin-bottom:12px">
      <div style="flex:1"><p style="font-weight:700;font-size:.88rem;margin-bottom:3px;display:flex;align-items:center;gap:6px"><?= $ic_free ?>Free Delivery</p><p style="font-size:.76rem;color:var(--agray)">চালু করলে ডেলিভারি ফ্রি</p></div>
      <label style="position:relative;width:50px;height:27px;cursor:pointer;flex-shrink:0">
        <input type="checkbox" name="is_free_delivery" id="freeDelivToggle" style="opacity:0;width:0;height:0;position:absolute" <?= ($product['is_free_delivery']??0)?'checked':'' ?> onchange="toggleFreeDelivery(this)">
        <span id="fdTrack" style="position:absolute;inset:0;border-radius:50px;background:<?= ($product['is_free_delivery']??0)?'#4CAF50':'var(--abdr2)' ?>;transition:background .3s"><span id="fdKnob" style="position:absolute;top:3px;left:<?= ($product['is_free_delivery']??0)?'26px':'3px' ?>;width:21px;height:21px;border-radius:50%;background:white;transition:left .3s;box-shadow:0 1px 4px rgba(0,0,0,.3)"></span></span>
      </label>
    </div>
    <div class="frow" id="delivChargeRow" style="<?= ($product['is_free_delivery']??0)?'display:none':'' ?>">
      <label>Delivery Charge (৳)</label>
      <input type="number" name="delivery_charge" id="delivCharge" value="<?= $product['delivery_charge'] ?? '60' ?>" class="ai" min="0" step="0.01">
    </div>
  </div>

  <!-- Payment Method -->
  <div class="afs">
    <h3><?= $ic_card ?>Payment Method</h3>
    <div class="frow">
      <label>এই পণ্যের জন্য payment method</label>
      <select name="product_payment_method" class="ai">
        <?php foreach (['default'=>'Default','cod'=>'COD Only','delivery_only'=>'Delivery Charge Online','advance_only'=>'% Advance Payment','full'=>'Full Payment','all'=>'All Options'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= ($product['product_payment_method']??'default')===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <p style="font-size:.72rem;color:var(--agray);margin-top:5px">"% Advance Payment" বাছলে উপরের Advance Payment % অনুযায়ী user শুধু advance amount পেমেন্ট করবে।</p>
    </div>
  </div>

  <!-- Gender & Category -->
  <div class="afs">
    <h3><?= $ic_people ?>Gender & Category</h3>
    <div class="fr2">
      <div class="frow">
        <label>Gender</label>
        <?php $cg = $product['gender'] ?? 'all'; ?>
        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-top:4px">
          <button type="button" class="gender-btn<?= $cg==='all'?' gender-on':'' ?>" onclick="setGender('all',this)"><?= $ic_star ?>All</button>
          <button type="button" class="gender-btn<?= $cg==='male'?' gender-on':'' ?>" onclick="setGender('male',this)"><?= $ic_male ?>Male</button>
          <button type="button" class="gender-btn<?= $cg==='female'?' gender-on':'' ?>" onclick="setGender('female',this)"><?= $ic_female ?>Female</button>
        </div>
        <input type="hidden" name="gender" id="genderInput" value="<?= htmlspecialchars($cg) ?>">
      </div>
      <div class="frow">
        <label>Category</label>
        <select name="category" class="ai">
          <option value="">-- Select --</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= ($product['category']??'')===$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Custom Design -->
  <div class="afs">
    <h3><?= $ic_pal ?>Custom Design</h3>
    <div style="display:flex;align-items:center;gap:14px;padding:12px;background:var(--ak3);border-radius:var(--r);border:1px solid var(--abdr2);margin-bottom:14px">
      <div style="flex:1"><p style="font-weight:700;font-size:.88rem;margin-bottom:3px;display:flex;align-items:center;gap:6px"><?= $ic_pal ?>Custom Design Enable</p><p style="font-size:.76rem;color:var(--agray)">User ছবি ও লেখা দিতে পারবে</p></div>
      <label style="position:relative;width:50px;height:27px;cursor:pointer;flex-shrink:0">
        <input type="checkbox" name="has_custom_design" id="cdToggle" style="opacity:0;width:0;height:0;position:absolute" <?= ($product['has_custom_design']??0)?'checked':'' ?> onchange="toggleCD(this);toggleCDSlots(this.checked)">
        <span id="cdTrack" style="position:absolute;inset:0;border-radius:50px;background:<?= ($product['has_custom_design']??0)?'var(--g)':'var(--abdr2)' ?>;transition:background .3s"><span id="cdKnob" style="position:absolute;top:3px;left:<?= ($product['has_custom_design']??0)?'26px':'3px' ?>;width:21px;height:21px;border-radius:50%;background:white;transition:left .3s;box-shadow:0 1px 4px rgba(0,0,0,.3)"></span></span>
      </label>
    </div>

    <div id="cdSlotsSection" style="display:<?= ($product['has_custom_design']??0)?'block':'none' ?>">
      <p style="font-size:.82rem;font-weight:700;color:var(--g);margin-bottom:10px;display:flex;align-items:center;gap:6px"><?= $ic_camera ?>Image Slots — user কতটা ছবি দিতে পারবে</p>
      <div style="display:grid;grid-template-columns:1fr auto auto;gap:8px;margin-bottom:6px;padding:0 2px">
        <span style="font-size:.7rem;color:var(--agray);font-weight:600">TITLE</span>
        <span style="font-size:.7rem;color:var(--agray);font-weight:600">REQUIRED</span>
        <span></span>
      </div>
      <div id="cdSlotList">
        <?php foreach ($cd_slots as $si => $slot): ?>
        <div class="cd-slot-row">
          <input type="text" name="cd_slot_title[]" value="<?= htmlspecialchars($slot['title']) ?>" class="ai" placeholder="e.g. সামনের ছবি" style="flex:1">
          <label style="display:flex;align-items:center;gap:5px;font-size:.78rem;color:var(--w);cursor:pointer;flex-shrink:0;white-space:nowrap">
            <input type="checkbox" name="cd_slot_required[<?= $si ?>]" value="1" <?= ($slot['required']??false)?'checked':'' ?> style="accent-color:var(--g);width:16px;height:16px">
            Required
          </label>
          <button type="button" onclick="this.closest('.cd-slot-row').remove()" style="background:none;border:none;color:#F44336;cursor:pointer;font-size:.9rem;flex-shrink:0;padding:0 4px">✕</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="abs" onclick="addCDSlot()" style="margin-top:6px">+ Add Image Slot</button>
    </div>
  </div>

  <!-- Product Options — with per-value price + is_required per option + nested sub-options -->
  <div class="afs">
    <h3><?= $ic_gear ?>Product Options</h3>
    <p class="afn">প্রতিটা option এর জন্য "Required" সেট করুন। প্রতিটা value এর extra price দিন (0 = base price)।<br>
    যেমন: "দৈর্ঘ্য" option → 1ফুট: +0৳, 2ফুট: +100৳ | "Extra Color" → Yes: +200৳<br>
    প্রতিটা value-এর নিচে "+ Sub-option" দিয়ে নিজের মতো নাম দিয়ে আরেকটা ভেতরের option (যেমন Color) যুক্ত করতে পারবেন, যার value-গুলোর দামও আলাদা।</p>
    <div id="optContainer">
      <?php foreach ($options as $i => $opt): ?>
      <div class="og" id="og<?= $i ?>">
        <div class="ohd" style="flex-wrap:wrap;gap:8px">
          <input type="text" class="ai on-i" placeholder="Option name (e.g. দৈর্ঘ্য)" value="<?= htmlspecialchars($opt['option_name']) ?>" style="flex:1;min-width:120px">
          <label style="display:flex;align-items:center;gap:6px;font-size:.78rem;cursor:pointer;flex-shrink:0;background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px;padding:5px 10px">
            <input type="checkbox" class="req-i" value="1" <?= ($opt['is_required']??1)?'checked':'' ?> style="accent-color:var(--g);width:15px;height:15px">
            <span style="font-size:.76rem;font-weight:600">Required</span>
          </label>
          <button type="button" class="bro" onclick="removeOpt(this)">✕ Remove</button>
        </div>
        <div class="opt-header-row" style="margin-top:8px">
          <span>Value</span>
          <span>Extra Price (৳)</span>
          <span></span>
        </div>
        <div class="ovs" id="ov<?= $i ?>">
          <?php foreach ($opt['values'] as $vi => $val): ?>
          <div class="opt-val-block" id="ovb<?= $i ?>_<?= $vi ?>">
            <div class="opt-val-main-row">
              <input type="text" class="ai val-inp" placeholder="e.g. 1 ফুট" value="<?= htmlspecialchars($val['value']) ?>">
              <input type="number" class="ai price-inp" placeholder="0" min="0" step="0.01" value="<?= (float)($val['extra_price'] ?? 0) ?>">
              <button type="button" class="brv" onclick="this.closest('.opt-val-block').remove()">✕</button>
            </div>
            <div class="sub-zone" id="subzone<?= $i ?>_<?= $vi ?>">
              <?php foreach (($val['suboptions'] ?? []) as $si2 => $sub): ?>
              <div class="sub-card">
                <div class="sub-card-head">
                  <input type="text" class="ai sub-name-inp" placeholder="Sub-option name (e.g. কালার)" value="<?= htmlspecialchars($sub['suboption_name']) ?>" style="flex:1">
                  <button type="button" class="brv" onclick="this.closest('.sub-card').remove()">✕</button>
                </div>
                <div class="sub-vals">
                  <?php foreach (($sub['values'] ?? []) as $sv): ?>
                  <div class="sub-val-row">
                    <input type="text" class="ai val-inp" placeholder="e.g. লাল" value="<?= htmlspecialchars($sv['value']) ?>">
                    <input type="number" class="ai price-inp" placeholder="0" min="0" step="0.01" value="<?= (float)($sv['extra_price'] ?? 0) ?>">
                    <button type="button" class="brv" onclick="this.parentElement.remove()">✕</button>
                  </div>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn-link-sm" onclick="addSubVal(this)">+ Add value</button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn-link-sm" style="margin-top:6px" onclick="addSubOption(this)">+ Sub-option (e.g. Color)</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="bav" onclick="addVal(<?= $i ?>)">+ Add value</button>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="abs" style="margin-top:10px" onclick="addOpt()">+ Add Option</button>
    <input type="hidden" name="options_data" id="optData" value="[]">
  </div>

  <!-- Images -->
  <div class="afs">
    <h3><?= $ic_camera ?>Product Images</h3>
    <p class="afn">Multiple images। First image = thumbnail।</p>
    <?php if ($images): ?>
    <div class="ig" id="eig">
      <?php foreach ($images as $img): ?>
      <div class="ipc" id="eiw<?= $img['id'] ?>">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="">
        <div class="iov"><span style="font-size:.7rem;color:#fff">৳<?= $img['price'] ?></span></div>
        <button type="button" class="img-del-btn" onclick="markDel(<?= $img['id'] ?>)">✕</button>
      </div>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="deleted_image_ids" id="deletedIds" value="[]">
    <?php endif; ?>
    <div class="aup" onclick="document.getElementById('imgInput').click()" style="margin-top:10px">
      <div class="ui"><?= $ic_camera ?></div><p><?= $product ? 'Add new images' : 'Select images' ?></p><p class="us">Multiple images at once</p>
    </div>
    <input type="file" id="imgInput" accept="image/*" multiple style="display:none" onchange="handleImgs(this)">
    <div class="ig" id="newImgGrid" style="margin-top:10px"></div>
    <div id="imgFilesContainer"></div>
    <input type="hidden" name="<?= $product ? 'new_image_prices' : 'image_prices' ?>" id="imgPrices" value="[]">
  </div>

  <div class="afact">
    <button type="submit" class="abg" onclick="prepSub(event)" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic_check ?><?= $product ? 'Save Changes' : 'Add Product' ?></button>
    <a href="/admin/products" class="abs">Cancel</a>
  </div>
</form>

<script>
var cdSlotCount = <?= count($cd_slots) ?>;

function setGender(v,btn){document.getElementById('genderInput').value=v;document.querySelectorAll('.gender-btn').forEach(b=>b.classList.remove('gender-on'));btn.classList.add('gender-on');}
function toggleCD(cb){const t=document.getElementById('cdTrack'),k=document.getElementById('cdKnob');if(cb.checked){t.style.background='var(--g)';k.style.left='26px';}else{t.style.background='var(--abdr2)';k.style.left='3px';}}
function toggleCDSlots(show){document.getElementById('cdSlotsSection').style.display=show?'block':'none';}
function toggleFreeDelivery(cb){const t=document.getElementById('fdTrack'),k=document.getElementById('fdKnob'),row=document.getElementById('delivChargeRow');if(cb.checked){t.style.background='#4CAF50';k.style.left='26px';row.style.display='none';document.getElementById('delivCharge').value='0';}else{t.style.background='var(--abdr2)';k.style.left='3px';row.style.display='block';}}
function toggleFixedPrice(cb){const t=document.getElementById('fpTrack'),k=document.getElementById('fpKnob'),row=document.getElementById('fixedPriceRow');if(cb.checked){t.style.background='var(--g)';k.style.left='26px';row.style.display='block';}else{t.style.background='var(--abdr2)';k.style.left='3px';row.style.display='none';}}

function calcOfferPrice(){
  const reg=parseFloat(document.getElementById('regularPrice').value)||0;
  const pct=parseFloat(document.getElementById('discountPct').value)||0;
  const preview=document.getElementById('offerPreview');
  if(reg>0&&pct>0){const offer=Math.round(reg*(1-pct/100));document.getElementById('prevRegular').textContent='৳'+reg.toFixed(0);document.getElementById('prevOffer').textContent='৳'+offer.toFixed(0);document.getElementById('prevBadge').textContent='-'+pct+'%';document.getElementById('basePriceInput').value=offer;preview.style.display='block';}
  else if(reg>0){document.getElementById('basePriceInput').value=reg;preview.style.display='none';}
  else{document.getElementById('basePriceInput').value=0;preview.style.display='none';}
}
calcOfferPrice();

function addCDSlot(){
  const i=cdSlotCount++;
  document.getElementById('cdSlotList').insertAdjacentHTML('beforeend',`
    <div class="cd-slot-row">
      <input type="text" name="cd_slot_title[]" class="ai" placeholder="e.g. তৃতীয় ছবি" style="flex:1">
      <label style="display:flex;align-items:center;gap:5px;font-size:.78rem;color:var(--w);cursor:pointer;flex-shrink:0;white-space:nowrap">
        <input type="checkbox" name="cd_slot_required[${i}]" value="1" style="accent-color:var(--g);width:16px;height:16px"> Required
      </label>
      <button type="button" onclick="this.closest('.cd-slot-row').remove()" style="background:none;border:none;color:#F44336;cursor:pointer;font-size:.9rem;flex-shrink:0;padding:0 4px">✕</button>
    </div>`);
}

const delIds=[];
function markDel(id){if(!delIds.includes(id))delIds.push(id);document.getElementById('deletedIds').value=JSON.stringify(delIds);const w=document.getElementById('eiw'+id);if(w){w.style.opacity='.2';w.style.pointerEvents='none';}}
let newFiles=[];
function handleImgs(input){Array.from(input.files).forEach(f=>newFiles.push({file:f,price:'',url:URL.createObjectURL(f),removed:false}));input.value='';renderNewImgs();}
function renderNewImgs(){const g=document.getElementById('newImgGrid');g.innerHTML='';newFiles.forEach((item,i)=>{if(item.removed)return;const d=document.createElement('div');d.className='ipc';d.style.position='relative';d.innerHTML=`<img src="${item.url}" style="width:100%;height:100%;object-fit:cover"><div class="iov" style="opacity:1;background:rgba(0,0,0,.5)"><input type="number" class="ip" placeholder="৳ Price" value="${item.price}" style="opacity:1;width:70px" onchange="newFiles[${i}].price=this.value"></div><button type="button" class="img-del-btn" onclick="removeNewImg(${i})">✕</button>`;g.appendChild(d);});}
function removeNewImg(i){newFiles[i].removed=true;renderNewImgs();}

let optCount=<?= count($options) ?>;

function addOpt(){
  const i=optCount++;
  const d=document.createElement('div');d.className='og';d.id='og'+i;
  d.innerHTML=`
    <div class="ohd" style="flex-wrap:wrap;gap:8px">
      <input type="text" class="ai on-i" placeholder="Option name (e.g. দৈর্ঘ্য)" style="flex:1;min-width:120px">
      <label style="display:flex;align-items:center;gap:6px;font-size:.78rem;cursor:pointer;flex-shrink:0;background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px;padding:5px 10px">
        <input type="checkbox" class="req-i" value="1" checked style="accent-color:var(--g);width:15px;height:15px">
        <span style="font-size:.76rem;font-weight:600">Required</span>
      </label>
      <button type="button" class="bro" onclick="removeOpt(this)">✕ Remove</button>
    </div>
    <div class="opt-header-row" style="margin-top:8px">
      <span>Value</span><span>Extra Price (৳)</span><span></span>
    </div>
    <div class="ovs" id="ov${i}"></div>
    <button type="button" class="bav" onclick="addVal(${i})">+ Add value</button>`;
  document.getElementById('optContainer').appendChild(d);
  addVal(i);
}

function removeOpt(btn){btn.closest('.og').remove();}

var subBlockCounter = 0;

function addVal(i){
  const c=document.getElementById('ov'+i);
  const vi='n'+(subBlockCounter++);
  const block=document.createElement('div');
  block.className='opt-val-block';
  block.innerHTML=`
    <div class="opt-val-main-row">
      <input type="text" class="ai val-inp" placeholder="Enter value">
      <input type="number" class="ai price-inp" placeholder="0" min="0" step="0.01">
      <button type="button" class="brv" onclick="this.closest('.opt-val-block').remove()">✕</button>
    </div>
    <div class="sub-zone"></div>
    <button type="button" class="btn-link-sm" style="margin-top:6px" onclick="addSubOption(this)">+ Sub-option (e.g. Color)</button>`;
  c.appendChild(block);
}

function addSubOption(btn){
  const valBlock = btn.closest('.opt-val-block');
  const zone = valBlock.querySelector('.sub-zone');
  const card = document.createElement('div');
  card.className = 'sub-card';
  card.innerHTML = `
    <div class="sub-card-head">
      <input type="text" class="ai sub-name-inp" placeholder="Sub-option name (e.g. কালার)" style="flex:1">
      <button type="button" class="brv" onclick="this.closest('.sub-card').remove()">✕</button>
    </div>
    <div class="sub-vals"></div>
    <button type="button" class="btn-link-sm" onclick="addSubVal(this)">+ Add value</button>`;
  zone.appendChild(card);
  addSubVal(card.querySelector('.btn-link-sm'));
}

function addSubVal(btn){
  const card = btn.closest('.sub-card');
  const valsZone = card.querySelector('.sub-vals');
  const row = document.createElement('div');
  row.className = 'sub-val-row';
  row.innerHTML = `<input type="text" class="ai val-inp" placeholder="e.g. লাল"><input type="number" class="ai price-inp" placeholder="0" min="0" step="0.01"><button type="button" class="brv" onclick="this.parentElement.remove()">✕</button>`;
  valsZone.appendChild(row);
}

function prepSub(e){
  e.preventDefault();
  calcOfferPrice();
  const opts=[];
  document.querySelectorAll('.og').forEach(g=>{
    const n=g.querySelector('.on-i')?.value?.trim();if(!n)return;
    const reqCb=g.querySelector('.req-i');
    const is_required=reqCb&&reqCb.checked?1:0;
    const valBlocks = Array.from(g.querySelectorAll('.ovs > .opt-val-block'));
    const vals = valBlocks.map(block=>{
      const val=block.querySelector('.opt-val-main-row .val-inp')?.value?.trim()||'';
      const price=parseFloat(block.querySelector('.opt-val-main-row .price-inp')?.value||'0')||0;
      if(!val) return null;
      const subCards = Array.from(block.querySelectorAll('.sub-zone > .sub-card'));
      const suboptions = subCards.map(card=>{
        const subName = card.querySelector('.sub-name-inp')?.value?.trim()||'';
        if(!subName) return null;
        const subRows = Array.from(card.querySelectorAll('.sub-val-row'));
        const subVals = subRows.map(row=>{
          const sv = row.querySelector('.val-inp')?.value?.trim()||'';
          const sp = parseFloat(row.querySelector('.price-inp')?.value||'0')||0;
          return sv ? {val:sv, price:sp} : null;
        }).filter(Boolean);
        return {name:subName, values:subVals};
      }).filter(Boolean);
      return {val, price, suboptions};
    }).filter(Boolean);
    opts.push({name:n,is_required,values:vals});
  });
  document.getElementById('optData').value=JSON.stringify(opts);
  const prices=newFiles.filter(i=>!i.removed).map(i=>i.price||'');
  document.getElementById('imgPrices').value=JSON.stringify(prices);
  const container=document.getElementById('imgFilesContainer');container.innerHTML='';
  const validFiles=newFiles.filter(i=>!i.removed);
  if(validFiles.length>0){
    try{const dt=new DataTransfer();validFiles.forEach(item=>dt.items.add(item.file));const fi=document.createElement('input');fi.type='file';fi.name='<?= $product?'new_images[]':'images[]' ?>';fi.multiple=true;fi.style.display='none';fi.files=dt.files;container.appendChild(fi);}
    catch(ex){console.warn(ex);}
  }
  document.getElementById('pf').submit();
}
</script>

<?php admin_foot(); ?>