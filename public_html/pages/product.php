<?php
require_once __DIR__ . '/../templates/layout.php';

$u  = rz_get_user();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /home'); exit; }

$p = DB::row("SELECT * FROM products WHERE id=? AND is_active=1", [$id]);
if (!$p) { header('Location: /home'); exit; }

$images   = DB::rows("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC", [$id]);
$opts_raw = DB::rows("SELECT * FROM product_options WHERE product_id=?", [$id]);
$options  = [];
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

$pay_opt        = $cfg['payment_options'] ?? 'cod';
$bkash_num      = $cfg['bkash_number']   ?? '';
$nagad_num      = $cfg['nagad_number']   ?? '';
$img_count      = count($images);
$regular_price  = (float)($p['regular_price']    ?? 0);
$discount_pct   = (float)($p['discount_percent'] ?? 0);
$base_price     = (float)$p['base_price'];
$is_free_del    = (int)($p['is_free_delivery']   ?? 0);
$delivery_charge= $is_free_del ? 0 : (float)$p['delivery_charge'];
$max_qty        = (int)($p['max_quantity']        ?? 0);
$advance_pct    = (float)($p['advance_percent']   ?? 0);
$prod_pay       = $p['product_payment_method']   ?? 'default';
if ($prod_pay !== 'default') $pay_opt = $prod_pay;
$has_discount   = $regular_price > 0 && $discount_pct > 0;
$offer_price    = $has_discount ? round($regular_price * (1 - $discount_pct / 100)) : $base_price;
$fixed_price    = isset($p['fixed_display_price']) && $p['fixed_display_price'] !== null ? (float)$p['fixed_display_price'] : null;
$display_price  = $fixed_price !== null ? $fixed_price : $offer_price;

$cd_slots = json_decode($p['cd_image_slots'] ?? 'null', true);
if (!$cd_slots) {
    $cd_slots = [
        ['title' => 'সামনের ছবি', 'required' => false],
        ['title' => 'পিছনের ছবি', 'required' => false],
    ];
}

// ── Related Products: same category হলে category থেকে, না হলে random সব পণ্য থেকে ──
if (!empty($p['category'])) {
    $related = DB::rows(
        "SELECT pr.*,
         (SELECT pi2.image_path FROM product_images pi2 WHERE pi2.product_id=pr.id ORDER BY pi2.sort_order ASC LIMIT 1) AS thumb
         FROM products pr
         WHERE pr.is_active=1 AND pr.id != ? AND pr.category = ?
         ORDER BY RAND() LIMIT 10",
        [$id, $p['category']]
    );
} else {
    $related = [];
}
if (!$related) {
    $related = DB::rows(
        "SELECT pr.*,
         (SELECT pi2.image_path FROM product_images pi2 WHERE pi2.product_id=pr.id ORDER BY pi2.sort_order ASC LIMIT 1) AS thumb
         FROM products pr
         WHERE pr.is_active=1 AND pr.id != ?
         ORDER BY RAND() LIMIT 10",
        [$id]
    );
}

$imgs_json = json_encode(array_map(fn($img) => [
    'id'    => (string)$img['id'],
    'path'  => $img['image_path'],
    'price' => (float)($img['price'] ?: $offer_price)
], $images), JSON_UNESCAPED_UNICODE);

$opts_json = json_encode(array_map(fn($o) => [
    'name'        => $o['option_name'],
    'is_required' => (bool)($o['is_required'] ?? true),
    'vals'        => array_map(fn($v) => [
        'val'        => $v['value'],
        'price'      => (float)($v['extra_price'] ?? 0),
        'suboptions' => array_map(fn($sub) => [
            'name' => $sub['suboption_name'],
            'vals' => array_map(fn($sv) => [
                'val'   => $sv['value'],
                'price' => (float)($sv['extra_price'] ?? 0)
            ], $sub['values'])
        ], $v['suboptions'] ?? [])
    ], $o['values'])
], $options), JSON_UNESCAPED_UNICODE);

$cd_slots_json = json_encode($cd_slots, JSON_UNESCAPED_UNICODE);

render_head(htmlspecialchars($p['name']), $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg></a>
    <span class="st"><?= htmlspecialchars($p['name']) ?></span>
    <a href="/cart" class="ib"><svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z"/></svg></a>
  </div>

  <!-- Carousel -->
  <div class="car" id="car">
    <?php if ($images): ?>
    <div class="ct" id="ct">
      <?php foreach ($images as $i => $img): ?>
      <div class="cs"><img src="<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" onclick="openViewer(<?= $i ?>)" style="cursor:zoom-in"></div>
      <?php endforeach; ?>
    </div>
    <div class="cd">
      <?php foreach ($images as $i => $img): ?><span class="dot<?= $i===0?' on':'' ?>" onclick="gs(<?= $i ?>)"></span><?php endforeach; ?>
    </div>
    <div style="position:absolute;bottom:36px;right:10px;background:rgba(0,0,0,.55);color:#fff;font-size:.6rem;padding:3px 7px;border-radius:50px;pointer-events:none;display:flex;align-items:center;gap:4px"><svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>Tap to zoom</div>
    <?php if ($has_discount && $fixed_price === null): ?>
    <div style="position:absolute;top:10px;left:10px;background:linear-gradient(135deg,#F44336,#C62828);color:#fff;font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:50px;z-index:2">-<?= (int)$discount_pct ?>% OFF</div>
    <?php endif; ?>
    <?php else: ?><div class="ce"><svg viewBox="0 0 24 24" style="width:56px;height:56px;fill:var(--gray)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></div><?php endif; ?>
    <?php if ($p['video_url']): ?><button class="vb2" onclick="ovFull('<?= htmlspecialchars($p['video_url']) ?>')" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M8 5v14l11-7z"/></svg>Video</button><?php endif; ?>
  </div>

  <!-- Product Info: Name → Price → Description -->
  <div class="pdi">
    <h1 class="pdn"><?= htmlspecialchars($p['name']) ?></h1>

    <!-- Price Block — dynamic (or fixed) -->
    <div id="priceBlock" style="background:var(--k3);border:1px solid var(--bdr2);border-radius:var(--r);padding:12px 14px;margin:8px 0 10px">
      <?php if ($has_discount && $fixed_price === null): ?>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px">
        <span style="font-size:.88rem;color:var(--gray);text-decoration:line-through">৳<?= number_format($regular_price, 0) ?></span>
        <span style="background:linear-gradient(135deg,#F44336,#C62828);color:#fff;border-radius:50px;padding:2px 9px;font-size:.7rem;font-weight:700">-<?= round($discount_pct) ?>%</span>
      </div>
      <?php endif; ?>

      <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:4px">
        <span style="font-size:1.55rem;font-weight:700;color:var(--g)" id="displayPrice">৳<?= number_format($display_price, 0) ?></span>
        <span style="font-size:.78rem;color:var(--gray)" id="priceNote"><?= ($p['has_custom_design'] && $fixed_price === null) ? '— size বা option select এ দাম পরিবর্তন হতে পারে' : '' ?></span>
      </div>

      <!-- Extra price badge (hidden when fixed price mode) -->
      <div id="extraPriceBadge" style="display:none;font-size:.78rem;color:var(--g);font-weight:600;margin-bottom:5px"></div>

      <!-- Advance payment notice -->
      <?php if ($advance_pct > 0): ?>
      <div id="advanceBox" style="background:rgba(201,168,76,.1);border:1px solid var(--g);border-radius:8px;padding:8px 12px;margin-bottom:6px">
        <p style="font-size:.78rem;font-weight:700;color:var(--g);margin-bottom:2px;display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>Advance Payment Required</p>
        <p style="font-size:.76rem;color:var(--gray)">মোট দামের <strong style="color:var(--g)"><?= $advance_pct ?>%</strong> advance pay করতে হবে।</p>
        <p style="font-size:.82rem;font-weight:700;color:var(--g)" id="advanceAmtDisplay">Advance: ৳<?= number_format($display_price * $advance_pct / 100, 0) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($is_free_del): ?>
      <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(76,175,80,.1);border:1px solid rgba(76,175,80,.3);border-radius:50px;padding:3px 11px;font-size:.76rem;color:#4CAF50;font-weight:700"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zM18 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>Free Delivery</div>
      <?php else: ?>
      <div style="display:inline-flex;align-items:center;gap:5px;background:var(--k2);border:1px solid var(--bdr2);border-radius:50px;padding:3px 11px;font-size:.76rem;color:var(--gray)"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zM18 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>Delivery: ৳<?= number_format($delivery_charge, 0) ?></div>
      <?php endif; ?>

      <?php if ($max_qty > 0): ?>
      <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,152,0,.1);border:1px solid #FF9800;border-radius:50px;padding:3px 11px;margin-left:5px;font-size:.74rem;color:#FF9800;font-weight:600"><svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>Max <?= $max_qty ?>/order</div>
      <?php endif; ?>
      <?php if ($p['has_custom_design']): ?>
      <div style="display:block;margin-top:6px;font-size:.76rem;color:var(--g);font-weight:600;display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>Custom Design Available</div>
      <?php endif; ?>
    </div>

    <!-- Description -->
    <?php if ($p['description']): ?>
    <p class="pdd"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if ($related): ?>
    <div style="margin-top:18px">
      <p style="font-size:.86rem;font-weight:700;color:var(--g);margin-bottom:10px;display:flex;align-items:center;gap:6px">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>
        Related Products
      </p>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">
        <?php foreach ($related as $rp):
          $rFixed   = isset($rp['fixed_display_price']) && $rp['fixed_display_price'] !== null ? (float)$rp['fixed_display_price'] : null;
          $rHasDisc = $rp['discount_percent'] > 0 && $rp['regular_price'] > 0;
          $rOffer   = $rHasDisc ? round($rp['regular_price'] * (1 - $rp['discount_percent']/100)) : (float)$rp['base_price'];
          $rDisplay = $rFixed !== null ? $rFixed : $rOffer;
        ?>
        <a href="/product/<?= $rp['id'] ?>" class="pc">
          <div class="pci">
            <?php if ($rp['thumb']): ?>
            <img src="<?= htmlspecialchars($rp['thumb']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>" loading="lazy">
            <?php else: ?>
            <div class="ni"><svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:var(--gray)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></div>
            <?php endif; ?>
            <?php if ($rHasDisc && $rFixed === null): ?><span class="disc-badge">-<?= (int)$rp['discount_percent'] ?>%</span><?php endif; ?>
          </div>
          <div class="pin">
            <p class="pn"><?= htmlspecialchars($rp['name']) ?></p>
            <div class="pr">
              <span class="pp">৳<?= number_format($rDisplay, 0) ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div style="height:calc(var(--nav) + 60px)"></div>
</div>

<!-- Action Buttons -->
<div class="pdacts">
  <button class="bca" onclick="qc()" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z"/></svg>Add to Cart</button>
  <button class="boa" onclick="openPanel()" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>Order Now</button>
</div>

<!-- Fullscreen Image Viewer -->
<div id="imgViewer" style="display:none;position:fixed;inset:0;background:#000;z-index:700">
  <div style="position:absolute;top:0;left:0;right:0;height:54px;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:space-between;padding:0 14px;z-index:3">
    <span id="viewerCount" style="color:rgba(255,255,255,.85);font-size:.86rem;font-weight:600"></span>
    <div style="display:flex;gap:8px;align-items:center">
      <a id="dlBtn" href="#" download style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;padding:6px 13px;border-radius:50px;font-size:.76rem;text-decoration:none;display:flex;align-items:center;gap:4px" onclick="event.stopPropagation()"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>Download</a>
      <button onclick="closeViewer()" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;width:34px;height:34px;border-radius:50%;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center">✕</button>
    </div>
  </div>
  <div style="position:absolute;top:54px;bottom:44px;left:0;right:0;overflow:hidden">
    <div id="viewerTrack" style="display:flex;height:100%;width:<?= max($img_count,1)*100 ?>%;transition:transform .38s cubic-bezier(.25,1,.5,1);will-change:transform">
      <?php foreach ($images as $img): ?>
      <div style="width:<?= $img_count>0?round(100/$img_count,4):100 ?>%;height:100%;flex-shrink:0;display:flex;align-items:center;justify-content:center;padding:8px" onclick="event.stopPropagation()">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:6px;display:block;-webkit-user-drag:none;user-select:none">
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if ($img_count > 1): ?>
  <button onclick="viewerGo(viewerCur-1)" style="position:absolute;left:6px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.2);color:#fff;width:38px;height:38px;border-radius:50%;font-size:1.2rem;cursor:pointer;z-index:3;display:flex;align-items:center;justify-content:center">‹</button>
  <button onclick="viewerGo(viewerCur+1)" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.2);color:#fff;width:38px;height:38px;border-radius:50%;font-size:1.2rem;cursor:pointer;z-index:3;display:flex;align-items:center;justify-content:center">›</button>
  <?php endif; ?>
  <div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:5px;z-index:3">
    <?php foreach ($images as $i => $img): ?>
    <span class="vdot" onclick="viewerGo(<?= $i ?>)" style="display:block;height:6px;border-radius:3px;cursor:pointer;transition:all .3s;background:<?= $i===0?'var(--g)':'rgba(255,255,255,.35)' ?>;width:<?= $i===0?'18px':'6px' ?>"></span>
    <?php endforeach; ?>
  </div>
</div>

<!-- Video -->
<div id="vov" style="display:none;position:fixed;inset:0;background:#000;z-index:800;flex-direction:column">
  <div style="position:absolute;top:0;left:0;right:0;height:50px;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:flex-end;padding:0 14px;z-index:2">
    <button onclick="closeVideo()" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;width:36px;height:36px;border-radius:50%;font-size:.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center">✕</button>
  </div>
  <div style="position:absolute;top:50px;bottom:0;left:0;right:0">
    <iframe id="vf" src="" frameborder="0" allowfullscreen allow="autoplay" style="width:100%;height:100%;display:block"></iframe>
  </div>
</div>

<!-- Order Panel -->
<div class="pov" id="pov" onclick="if(event.target===this)closePanel()"></div>
<div class="panel" id="panel">
  <div class="ph2"></div>
  <div class="phd"><h3>Place Order</h3><button class="pcl" onclick="closePanel()">✕</button></div>

  <!-- Step 1 -->
  <div id="s1" class="pst">
    <p class="slbl">STEP 1 — SELECT PRODUCT</p>
    <?php if ($images): ?>
    <div class="isg">
      <?php foreach ($images as $i => $img):
        $imgPrice = (float)($img['price'] ?: $offer_price);
      ?>
      <div class="isi" id="ic<?= $i ?>" onclick="selImg(<?= $i ?>,'<?= $img['id'] ?>','<?= addslashes($img['image_path']) ?>',<?= $imgPrice ?>)">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="">
        <div class="ick">✓</div>
        <div class="isp">৳<?= number_format($imgPrice, 0) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?><div style="padding:16px;text-align:center;color:var(--gray)">Order directly</div><?php endif; ?>
    <div id="selItems"></div>
    <div id="tMaxQty" style="display:none;background:rgba(255,152,0,.12);border:1px solid #FF9800;border-radius:var(--r2);padding:9px 13px;font-size:.82rem;color:#FF9800;margin:8px 0;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>Maximum quantity reached</div>
    <div id="priceSec" style="display:none" class="pbx">
      <div class="prow"><span>Product:</span><span id="subtotalTxt">৳0</span></div>
      <?php if ($advance_pct > 0): ?>
      <div class="prow" style="color:var(--g);font-weight:600"><span>Advance (<?= $advance_pct ?>%):</span><span id="advanceTxt">৳0</span></div>
      <div class="prow"><span>Remaining:</span><span id="remainingTxt">৳0</span></div>
      <?php endif; ?>
      <?php if ($is_free_del): ?>
      <div class="prow"><span>Delivery:</span><span style="color:#4CAF50;font-weight:700">Free</span></div>
      <?php else: ?>
      <div class="prow"><span>Delivery:</span><span>৳<?= number_format($delivery_charge, 0) ?></span></div>
      <?php endif; ?>
      <div class="ptot"><span>Total:</span><span id="totalTxt">৳0</span></div>
    </div>
    <button class="bn" id="nextBtn1" onclick="goStep2()" disabled>Next Step →</button>
  </div>

  <!-- Step 2: Custom Design -->
  <?php if ($p['has_custom_design']): ?>
  <div id="s2cd" class="pst" style="display:none">
    <button class="bbk" onclick="backTo1()">← Back</button>
    <p class="slbl">STEP 2 — CUSTOM DESIGN</p>
    <div id="cdContainer"></div>
    <button class="bn" onclick="goStep3()">Next →</button>
  </div>
  <?php endif; ?>

  <!-- Step 3: Delivery + Payment -->
  <div id="s3" class="pst" style="display:none">
    <button class="bbk" onclick="<?= $p['has_custom_design'] ? 'backTo2cd()' : 'backTo1()' ?>">← Back</button>
    <p class="slbl">STEP <?= $p['has_custom_design']?'3':'2' ?> — DELIVERY INFO</p>
    <div id="orderSummary"></div>
    <div class="fs" style="margin-top:12px">
      <div class="fd"><label>Name *</label><input id="oN" class="inp" placeholder="Full name" value="<?= htmlspecialchars($u['name'] ?? '') ?>"></div>
      <div class="fd"><label>Mobile *</label><input id="oM" class="inp" type="tel" placeholder="01XXXXXXXXX" value="<?= htmlspecialchars($u['mobile'] ?? '') ?>"></div>
      <div class="fd"><label>District *</label><input id="oDi" class="inp" placeholder="e.g. Dhaka"></div>
      <div class="fd"><label>Upazila *</label><input id="oUp" class="inp" placeholder="Upazila name"></div>
      <div class="fd"><label>Union *</label><input id="oUn" class="inp" placeholder="Union name"></div>
      <div class="fd"><label>Village *</label><input id="oVi" class="inp" placeholder="Village name"></div>
      <div class="fd"><label>Road</label><input id="oRo" class="inp" placeholder="Road (optional)"></div>
      <div class="fd"><label>Holding</label><input id="oHo" class="inp" placeholder="Holding (optional)"></div>
    </div>

    <!-- Payment -->
    <div style="margin-top:14px">
      <?php if ($pay_opt === 'cod' || $pay_opt === 'free_delivery'): ?>
      <div class="pay-cod-box"><span class="pico2"><svg viewBox="0 0 24 24" style="width:24px;height:24px;fill:#4CAF50"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></span><div class="ptxt2"><span class="ptitle2">Cash on Delivery</span><span class="psub2">Pay when you receive</span></div></div>
      <button class="bpl" onclick="placeOrder('cod',null,this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Confirm Order</button>

      <?php elseif ($pay_opt === 'delivery_only' || $pay_opt === 'advance_only'): ?>
      <p class="pay-title"><?= $pay_opt === 'advance_only' ? 'PAY ADVANCE AMOUNT (' . $advance_pct . '%)' : 'PAY DELIVERY CHARGE ONLY' ?></p>
      <div class="pay-methods">
        <?php if ($bkash_num): ?><button class="pmb" onclick="selPM('bkash',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#E2136E"><path d="M3 7h18v2H3zm2 4h14v2H5zm-2 4h18v2H3z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Bkash</span><span class="pmb-sub"><?= $pay_opt === 'advance_only' ? 'Advance payment' : 'Delivery only' ?></span></div><span class="pmb-amt" id="bkashAmt">৳—</span></button><?php endif; ?>
        <?php if ($nagad_num): ?><button class="pmb" onclick="selPM('nagad',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#F6921E"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Nagad</span><span class="pmb-sub"><?= $pay_opt === 'advance_only' ? 'Advance payment' : 'Delivery only' ?></span></div><span class="pmb-amt" id="nagadAmt">৳—</span></button><?php endif; ?>
      </div>
      <?php if ($bkash_num): ?><div id="bkashBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="bkashAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($bkash_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="bkashL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('bkash','bkashL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Bkash — Order Now</button></div><?php endif; ?>
      <?php if ($nagad_num): ?><div id="nagadBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="nagadAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($nagad_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="nagadL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('nagad','nagadL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Nagad — Order Now</button></div><?php endif; ?>

      <?php elseif ($pay_opt === 'full'): ?>
      <p class="pay-title">FULL PAYMENT</p>
      <div class="pay-methods">
        <?php if ($bkash_num): ?><button class="pmb" onclick="selPM('bkash',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#E2136E"><path d="M3 7h18v2H3zm2 4h14v2H5zm-2 4h18v2H3z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Bkash</span><span class="pmb-sub">Full payment</span></div><span class="pmb-amt" id="bkashAmt">৳—</span></button><?php endif; ?>
        <?php if ($nagad_num): ?><button class="pmb" onclick="selPM('nagad',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#F6921E"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Nagad</span><span class="pmb-sub">Full payment</span></div><span class="pmb-amt" id="nagadAmt">৳—</span></button><?php endif; ?>
      </div>
      <?php if ($bkash_num): ?><div id="bkashBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="bkashAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($bkash_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="bkashL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('bkash','bkashL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Bkash — Order Now</button></div><?php endif; ?>
      <?php if ($nagad_num): ?><div id="nagadBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="nagadAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($nagad_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="nagadL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('nagad','nagadL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Nagad — Order Now</button></div><?php endif; ?>

      <?php else: /* all */ ?>
      <p class="pay-title">SELECT PAYMENT METHOD</p>
      <div class="pay-methods">
        <button class="pmb" onclick="selPM('cod',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#4CAF50"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Cash on Delivery</span><span class="pmb-sub">Pay on delivery</span></div></button>
        <?php if ($bkash_num): ?><button class="pmb" onclick="selPM('bkash',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#E2136E"><path d="M3 7h18v2H3zm2 4h14v2H5zm-2 4h18v2H3z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Bkash</span><span class="pmb-sub">Full payment</span></div><span class="pmb-amt" id="bkashAmt">৳—</span></button><?php endif; ?>
        <?php if ($nagad_num): ?><button class="pmb" onclick="selPM('nagad',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#F6921E"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Nagad</span><span class="pmb-sub">Full payment</span></div><span class="pmb-amt" id="nagadAmt">৳—</span></button><?php endif; ?>
      </div>
      <?php if ($bkash_num): ?><div id="bkashBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="bkashAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($bkash_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="bkashL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('bkash','bkashL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Bkash — Order Now</button></div><?php endif; ?>
      <?php if ($nagad_num): ?><div id="nagadBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="nagadAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($nagad_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="nagadL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('nagad','nagadL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Nagad — Order Now</button></div><?php endif; ?>
      <div id="codBox" style="display:none;margin-top:8px"><div class="pay-cod-box" style="margin-bottom:10px"><span class="pico2"><svg viewBox="0 0 24 24" style="width:24px;height:24px;fill:#4CAF50"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></span><div class="ptxt2"><span class="ptitle2">Cash on Delivery</span><span class="psub2">Pay when you receive</span></div></div><button class="bpl" onclick="placeOrder('cod',null,this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Confirm Order</button></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Option Bottom Sheet (shared, reused for every option) -->
<div class="opt-sheet-ov" id="optSheetOv" onclick="if(event.target===this)closeOptSheet()"></div>
<div class="opt-sheet" id="optSheet">
  <div class="opt-sheet-handle"></div>
  <div class="opt-sheet-head">
    <h4 id="optSheetTitle">Select</h4>
    <button class="opt-sheet-close" onclick="closeOptSheet()">✕</button>
  </div>
  <div class="opt-sheet-list" id="optSheetList"></div>
</div>

<!-- Sub-option Bottom Sheet (popup within popup, e.g. Color inside a size) -->
<div class="opt-sheet-ov" id="subOptSheetOv" style="z-index:970" onclick="if(event.target===this)closeSubOptSheet()"></div>
<div class="opt-sheet" id="subOptSheet" style="z-index:971">
  <div class="opt-sheet-handle"></div>
  <div class="opt-sheet-head">
    <h4 id="subOptSheetTitle">Select</h4>
    <button class="opt-sheet-close" onclick="closeSubOptSheet()">✕</button>
  </div>
  <div class="opt-sheet-list" id="subOptSheetList"></div>
</div>

<!-- Toasts -->
<div class="toast" id="tOk" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Order placed!</div>
<div class="toast" id="tCart" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z"/></svg>Added to cart!</div>
<div class="toast" id="tNoAcc" style="border-color:#FF9800;color:#FF9800;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>No account. Please login.</div>
<div class="toast" id="tErr" style="border-color:#F44336;color:#F44336;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>Error occurred</div>

<script>
var PD = {
  id:         <?= (int)$p['id'] ?>,
  name:       <?= json_encode($p['name'], JSON_UNESCAPED_UNICODE) ?>,
  base:       <?= $offer_price ?>,
  fixedPrice: <?= $fixed_price !== null ? $fixed_price : 'null' ?>,
  del:        <?= $delivery_charge ?>,
  isFreeD:    <?= $is_free_del ? 'true' : 'false' ?>,
  hasCD:      <?= $p['has_custom_design'] ? 'true' : 'false' ?>,
  maxQty:     <?= $max_qty ?>,
  advancePct: <?= $advance_pct ?>,
  imgs:       <?= $imgs_json ?>,
  opts:       <?= $opts_json ?>,
  cdSlots:    <?= $cd_slots_json ?>,
  payOpt:     <?= json_encode($pay_opt) ?>,
  imgCount:   <?= max($img_count, 1) ?>
};
var carCur=0, sel={}, cdData={};
var optSheetCtx=null;    // {itemId, optName}
var subOptSheetCtx=null; // {itemId, optName, subName}

/* Carousel */
function gs(n){carCur=n;var t=document.getElementById('ct');if(t)t.style.transform='translateX(-'+(n*100)+'%)';document.querySelectorAll('.dot').forEach(function(d,i){d.classList.toggle('on',i===n);});}
(function(){var c=document.getElementById('car'),sx=0;if(!c)return;c.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;},{passive:true});c.addEventListener('touchend',function(e){var d=sx-e.changedTouches[0].clientX;if(Math.abs(d)>50)gs(d>0?Math.min(carCur+1,PD.imgs.length-1):Math.max(carCur-1,0));},{passive:true});})();

/* Viewer */
var viewerCur=0;
function openViewer(idx){viewerCur=idx;document.getElementById('imgViewer').style.display='block';document.body.style.overflow='hidden';_updateViewer();}
function closeViewer(){document.getElementById('imgViewer').style.display='none';document.body.style.overflow='';}
function viewerGo(n){viewerCur=((n%PD.imgCount)+PD.imgCount)%PD.imgCount;_updateViewer();}
function _updateViewer(){var t=document.getElementById('viewerTrack');if(t)t.style.transform='translateX(-'+(viewerCur*100/PD.imgCount)+'%)';var c=document.getElementById('viewerCount');if(c)c.textContent=(viewerCur+1)+' / '+PD.imgCount;var dl=document.getElementById('dlBtn');if(dl&&PD.imgs[viewerCur]){dl.href=PD.imgs[viewerCur].path;dl.download=PD.imgs[viewerCur].path.split('/').pop()||'image.jpg';}document.querySelectorAll('.vdot').forEach(function(d,i){d.style.background=i===viewerCur?'var(--g)':'rgba(255,255,255,.35)';d.style.width=i===viewerCur?'18px':'6px';});}
(function(){var vw=document.getElementById('imgViewer'),sx=0;if(!vw)return;vw.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;},{passive:true});vw.addEventListener('touchend',function(e){var d=sx-e.changedTouches[0].clientX;if(Math.abs(d)>40)viewerGo(d>0?viewerCur+1:viewerCur-1);},{passive:true});})();

/* Video */
function ovFull(url){var u=url;if(u.includes('youtube.com/watch?v='))u=u.replace('watch?v=','embed/')+'?autoplay=1&rel=0';else if(u.includes('youtu.be/')){var vid=u.split('youtu.be/')[1].split('?')[0];u='https://www.youtube.com/embed/'+vid+'?autoplay=1&rel=0';}document.getElementById('vf').src=u;document.getElementById('vov').style.display='flex';document.body.style.overflow='hidden';}
function closeVideo(){document.getElementById('vf').src='';document.getElementById('vov').style.display='none';document.body.style.overflow='';}

/* Panel */
function openPanel(){document.getElementById('pov').classList.add('show');document.getElementById('panel').classList.add('show');document.body.style.overflow='hidden';}
function closePanel(){document.getElementById('pov').classList.remove('show');document.getElementById('panel').classList.remove('show');document.body.style.overflow='';}

/* Calculate total extra from selected options (including nested sub-options) */
function calcExtraFromOpts(itemId) {
  var it = sel[itemId]; if (!it) return 0;
  var extra = 0;
  PD.opts.forEach(function(o) {
    var sv = it.opts[o.name];
    if (sv) {
      var found = o.vals.find(function(v){return v.val===sv;});
      if (found) {
        extra += found.price||0;
        // Add nested sub-option prices for this value
        var subSel = (it.subopts && it.subopts[o.name] && it.subopts[o.name][sv]) || {};
        (found.suboptions||[]).forEach(function(sub){
          var ssv = subSel[sub.name];
          if (ssv) {
            var sfound = sub.vals.find(function(v){return v.val===ssv;});
            if (sfound) extra += sfound.price||0;
          }
        });
      }
    }
  });
  return extra;
}

/* Update main price display */
function updateMainPrice() {
  if (PD.fixedPrice !== null) {
    // Fixed display price mode: never changes on selection
    document.getElementById('displayPrice').textContent = '৳' + PD.fixedPrice.toFixed(0);
    document.getElementById('extraPriceBadge').style.display = 'none';
    if (PD.advancePct > 0) {
      var adv0 = document.getElementById('advanceAmtDisplay');
      if (adv0) adv0.textContent = 'Advance: ৳' + Math.ceil(PD.fixedPrice * PD.advancePct / 100).toFixed(0);
    }
    return;
  }
  var firstItem = Object.values(sel)[0];
  if (!firstItem) {
    document.getElementById('displayPrice').textContent = '৳' + PD.base.toFixed(0);
    document.getElementById('extraPriceBadge').style.display = 'none';
    if (PD.advancePct > 0) {
      var adv = document.getElementById('advanceAmtDisplay');
      if (adv) adv.textContent = 'Advance: ৳' + Math.ceil(PD.base * PD.advancePct / 100).toFixed(0);
    }
    return;
  }
  var extra = firstItem.extraPrice || 0;
  var total = firstItem.price + extra;
  document.getElementById('displayPrice').textContent = '৳' + total.toFixed(0);
  var eb = document.getElementById('extraPriceBadge');
  if (eb) { if(extra>0){eb.textContent='+ ৳'+extra+' design extra';eb.style.display='block';}else eb.style.display='none'; }
  if (PD.advancePct > 0) {
    var adv = document.getElementById('advanceAmtDisplay');
    if (adv) adv.textContent = 'Advance: ৳' + Math.ceil(total * PD.advancePct / 100).toFixed(0);
  }
}

/* Image select */
function selImg(idx, id, path, price) {
  if (sel[id]) { delete sel[id]; document.getElementById('ic'+idx).classList.remove('pk'); }
  else {
    if (PD.maxQty > 0) {
      var ts=0; Object.values(sel).forEach(function(it){ts+=it.qty;});
      if (ts >= PD.maxQty) { document.getElementById('tMaxQty').style.display='block'; setTimeout(function(){document.getElementById('tMaxQty').style.display='none';},3000); return; }
    }
    sel[id] = {idx:idx,id:id,path:path,price:price,qty:1,opts:{},subopts:{},extraPrice:0};
    document.getElementById('ic'+idx).classList.add('pk');
  }
  renderSel(); calcTotal(); updateMainPrice();
}

function renderSel() {
  var keys=Object.keys(sel);
  document.getElementById('nextBtn1').disabled=!keys.length;
  if(!keys.length){document.getElementById('selItems').innerHTML='';document.getElementById('priceSec').style.display='none';return;}
  document.getElementById('priceSec').style.display='block';
  var html='<p class="selbl" style="margin-top:10px">Selected:</p>';
  keys.forEach(function(id){
    var it=sel[id];
    var oh=PD.opts.map(function(o){
      var reqMark = o.is_required ? ' <span class="req">*</span>' : ' <span class="optnl">(optional)</span>';
      var curVal = it.opts[o.name] || '';
      var curObj = curVal ? o.vals.find(function(v){return v.val===curVal;}) : null;
      var barValTxt = curVal ? curVal : (o.is_required ? '-- Select --' : '-- Optional --');
      var barValCls = curVal ? '' : ' placeholder';
      var barPrice = (curObj && curObj.price>0) ? ('+৳'+curObj.price) : '';

      var optBlock = '<div class="opt-block">'+
        '<div class="opt-bar'+(curVal?' has-val':'')+'" onclick="openOptSheet(\''+id+'\',\''+o.name.replace(/'/g,"\\'")+'\')">'+
          '<div class="opt-bar-left">'+
            '<span class="opt-bar-label">'+o.name+reqMark+'</span>'+
            '<span class="opt-bar-val'+barValCls+'">'+barValTxt+'</span>'+
          '</div>'+
          '<div class="opt-bar-right">'+
            (barPrice?'<span class="opt-bar-price">'+barPrice+'</span>':'')+
            '<span class="opt-bar-arrow">▼</span>'+
          '</div>'+
        '</div>';

      // Sub-option bars for the currently selected value (e.g. Color inside size 43)
      if (curVal && curObj && curObj.suboptions && curObj.suboptions.length) {
        curObj.suboptions.forEach(function(sub){
          var subSel = (it.subopts[o.name] && it.subopts[o.name][curVal]) || {};
          var subCurVal = subSel[sub.name] || '';
          var subCurObj = subCurVal ? sub.vals.find(function(v){return v.val===subCurVal;}) : null;
          var subValTxt = subCurVal ? subCurVal : '-- Select --';
          var subValCls = subCurVal ? '' : ' placeholder';
          var subPrice = (subCurObj && subCurObj.price>0) ? ('+৳'+subCurObj.price) : '';
          optBlock += '<div class="opt-bar'+(subCurVal?' has-val':'')+'" style="margin-top:7px;margin-left:14px;border-left:2px solid var(--g)" onclick="openSubOptSheet(\''+id+'\',\''+o.name.replace(/'/g,"\\'")+'\',\''+curVal.replace(/'/g,"\\'")+'\',\''+sub.name.replace(/'/g,"\\'")+'\')">'+
            '<div class="opt-bar-left">'+
              '<span class="opt-bar-label">'+sub.name+'</span>'+
              '<span class="opt-bar-val'+subValCls+'">'+subValTxt+'</span>'+
            '</div>'+
            '<div class="opt-bar-right">'+
              (subPrice?'<span class="opt-bar-price">'+subPrice+'</span>':'')+
              '<span class="opt-bar-arrow">▼</span>'+
            '</div>'+
          '</div>';
        });
      }
      optBlock += '</div>';
      return optBlock;
    }).join('');
    var extra=it.extraPrice||0;var dp=it.price+extra;
    html+='<div class="sec"><img src="'+it.path+'" alt=""><div class="seci"><span class="secp">৳'+dp.toFixed(0)+'/pc'+(extra>0?' <small style="color:var(--g)">(+৳'+extra+' extra)</small>':'')+'</span>'+oh+
      '<div class="qr"><button class="qb" onclick="chQty(\''+id+'\',-1)">−</button><span class="qn" id="qn'+id+'">'+it.qty+'</span><button class="qb" onclick="chQty(\''+id+'\',1)">+</button><button class="qd" onclick="delSel(\''+id+'\','+it.idx+')"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button></div></div></div>';
  });
  document.getElementById('selItems').innerHTML=html;
}

/* Option bottom sheet open/close */
function openOptSheet(itemId, optName) {
  var o = PD.opts.find(function(x){return x.name===optName;});
  if (!o) return;
  optSheetCtx = {itemId:itemId, optName:optName};
  document.getElementById('optSheetTitle').textContent = optName;
  var curVal = (sel[itemId] && sel[itemId].opts[optName]) || '';
  var listHtml = '';
  if (!o.is_required) {
    listHtml += '<div class="opt-sheet-item'+(curVal===''?' sel':'')+'" onclick="pickOptValue(\'\')">'+
      '<div class="opt-sheet-item-txt"><span class="opt-sheet-radio"></span><span class="opt-sheet-item-name">-- কিছু না --</span></div>'+
      '</div>';
  }
  o.vals.forEach(function(v){
    var isSel = curVal === v.val;
    var pt = v.price>0 ? '+৳'+v.price : '';
    listHtml += '<div class="opt-sheet-item'+(isSel?' sel':'')+'" onclick="pickOptValue(\''+v.val.replace(/'/g,"\\'")+'\')">'+
      '<div class="opt-sheet-item-txt"><span class="opt-sheet-radio"></span><span class="opt-sheet-item-name">'+v.val+'</span></div>'+
      (pt?'<span class="opt-sheet-item-price">'+pt+'</span>':'')+
      '</div>';
  });
  document.getElementById('optSheetList').innerHTML = listHtml;
  document.getElementById('optSheetOv').classList.add('show');
  document.getElementById('optSheet').classList.add('show');
  document.body.style.overflow='hidden';
}
function closeOptSheet() {
  document.getElementById('optSheetOv').classList.remove('show');
  document.getElementById('optSheet').classList.remove('show');
  document.body.style.overflow='';
  optSheetCtx=null;
}
function pickOptValue(value) {
  if (!optSheetCtx) return;
  setOpt(optSheetCtx.itemId, optSheetCtx.optName, value);
  closeOptSheet();
}

/* Sub-option bottom sheet (popup within a popup, e.g. Color inside size) */
function openSubOptSheet(itemId, optName, parentVal, subName) {
  var o = PD.opts.find(function(x){return x.name===optName;});
  if (!o) return;
  var parentValObj = o.vals.find(function(v){return v.val===parentVal;});
  if (!parentValObj) return;
  var sub = (parentValObj.suboptions||[]).find(function(s){return s.name===subName;});
  if (!sub) return;
  subOptSheetCtx = {itemId:itemId, optName:optName, parentVal:parentVal, subName:subName};
  document.getElementById('subOptSheetTitle').textContent = subName;
  var subSel = (sel[itemId].subopts[optName] && sel[itemId].subopts[optName][parentVal]) || {};
  var curSubVal = subSel[subName] || '';
  var listHtml = '<div class="opt-sheet-item'+(curSubVal===''?' sel':'')+'" onclick="pickSubOptValue(\'\')">'+
    '<div class="opt-sheet-item-txt"><span class="opt-sheet-radio"></span><span class="opt-sheet-item-name">-- কিছু না --</span></div>'+
    '</div>';
  sub.vals.forEach(function(v){
    var isSel = curSubVal === v.val;
    var pt = v.price>0 ? '+৳'+v.price : '';
    listHtml += '<div class="opt-sheet-item'+(isSel?' sel':'')+'" onclick="pickSubOptValue(\''+v.val.replace(/'/g,"\\'")+'\')">'+
      '<div class="opt-sheet-item-txt"><span class="opt-sheet-radio"></span><span class="opt-sheet-item-name">'+v.val+'</span></div>'+
      (pt?'<span class="opt-sheet-item-price">'+pt+'</span>':'')+
      '</div>';
  });
  document.getElementById('subOptSheetList').innerHTML = listHtml;
  document.getElementById('subOptSheetOv').classList.add('show');
  document.getElementById('subOptSheet').classList.add('show');
  document.body.style.overflow='hidden';
}
function closeSubOptSheet() {
  document.getElementById('subOptSheetOv').classList.remove('show');
  document.getElementById('subOptSheet').classList.remove('show');
  document.body.style.overflow='';
  subOptSheetCtx=null;
}
function pickSubOptValue(value) {
  if (!subOptSheetCtx) return;
  var ctx = subOptSheetCtx;
  var it = sel[ctx.itemId];
  if (!it) return;
  if (!it.subopts[ctx.optName]) it.subopts[ctx.optName] = {};
  if (!it.subopts[ctx.optName][ctx.parentVal]) it.subopts[ctx.optName][ctx.parentVal] = {};
  it.subopts[ctx.optName][ctx.parentVal][ctx.subName] = value;
  it.extraPrice = calcExtraFromOpts(ctx.itemId);
  closeSubOptSheet();
  renderSel(); calcTotal(); updateMainPrice();
}

function setOpt(id,n,v){
  if(!sel[id])return;
  sel[id].opts[n]=v;
  sel[id].extraPrice=calcExtraFromOpts(id);
  renderSel();calcTotal();updateMainPrice();
}

function chQty(id,d){
  if(!sel[id])return;var nq=sel[id].qty+d;if(nq<1)return;
  if(PD.maxQty>0&&d>0){var ts=0;Object.values(sel).forEach(function(it){ts+=it.qty;});if(ts>=PD.maxQty){document.getElementById('tMaxQty').style.display='block';setTimeout(function(){document.getElementById('tMaxQty').style.display='none';},3000);return;}}
  sel[id].qty=nq;var el=document.getElementById('qn'+id);if(el)el.textContent=nq;calcTotal();
}
function delSel(id,idx){delete sel[id];var el=document.getElementById('ic'+idx);if(el)el.classList.remove('pk');renderSel();calcTotal();updateMainPrice();}

function calcTotal(){
  var s=0;Object.values(sel).forEach(function(it){s+=(it.price+(it.extraPrice||0))*it.qty;});
  document.getElementById('subtotalTxt').textContent='৳'+s.toFixed(0);
  document.getElementById('totalTxt').textContent='৳'+(s+PD.del).toFixed(0);
  if(PD.advancePct>0){
    var adv=Math.ceil(s*PD.advancePct/100);
    var rem=s-adv+PD.del;
    var at=document.getElementById('advanceTxt');if(at)at.textContent='৳'+adv.toFixed(0);
    var rt=document.getElementById('remainingTxt');if(rt)rt.textContent='৳'+rem.toFixed(0);
  }
  var isD=PD.payOpt==='delivery_only';
  var isA=PD.payOpt==='advance_only';
  var amt;
  if(isD){amt=PD.del;}
  else if(isA){amt=Math.ceil(s*PD.advancePct/100);}
  else if(PD.advancePct>0){amt=Math.ceil(s*PD.advancePct/100);}
  else{amt=s+PD.del;}
  var as='৳'+amt.toFixed(0);
  var ba=document.getElementById('bkashAmt');if(ba)ba.textContent=as;
  var na=document.getElementById('nagadAmt');if(na)na.textContent=as;
}

/* Steps */
function goStep2(){
  var keys=Object.keys(sel);if(!keys.length)return;
  for(var id in sel){
    for(var j=0;j<PD.opts.length;j++){
      var o=PD.opts[j];
      if(o.is_required&&!sel[id].opts[o.name]){alert('"'+o.name+'" select করুন (আবশ্যক)');return;}
    }
  }
  document.getElementById('s1').style.display='none';
  if(PD.hasCD){buildCD();document.getElementById('s2cd').style.display='block';}
  else{buildSummary();document.getElementById('s3').style.display='block';}
}
function backTo1(){document.getElementById('s3').style.display='none';if(PD.hasCD)document.getElementById('s2cd').style.display='none';document.getElementById('s1').style.display='block';}
function backTo2cd(){document.getElementById('s3').style.display='none';document.getElementById('s2cd').style.display='block';}
function goStep3(){document.getElementById('s2cd').style.display='none';buildSummary();document.getElementById('s3').style.display='block';}

/* Custom Design */
function buildCD(){
  var c=document.getElementById('cdContainer');c.innerHTML='';
  Object.values(sel).forEach(function(it){
    if(!cdData[it.id])cdData[it.id]={text:'',slots:{},slotPreviews:{}};
    var extra=it.extraPrice||0;var dp=it.price+extra;
    var d=document.createElement('div');d.style.cssText='background:var(--k3);border:1px solid var(--bdr2);border-radius:var(--r);padding:12px;margin-bottom:12px';
    var html='<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px"><img src="'+it.path+'" style="width:48px;height:48px;object-fit:cover;border-radius:7px;flex-shrink:0"><div><p style="font-weight:700;font-size:.86rem">'+PD.name+'</p><p style="font-size:.74rem;color:var(--g)">৳'+dp.toFixed(0)+' × '+it.qty+'</p></div></div>';
    html+='<div class="fd" style="margin-bottom:12px"><label style="font-size:.78rem;font-weight:600;display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75z"/></svg>Custom Text (optional)</label><input type="text" class="inp" placeholder="কাস্টম লেখা..." id="cdt_'+it.id+'" value="'+(cdData[it.id].text||'')+'" oninput="cdData[\''+it.id+'\'].text=this.value"></div>';
    PD.cdSlots.forEach(function(slot,si){
      var slotKey='slot_'+si;
      var preview=cdData[it.id].slotPreviews[slotKey]||'';
      var reqTag=slot.required?'<span style="color:#F44336;font-size:.72rem"> *আবশ্যক</span>':'<span style="font-size:.72rem;color:var(--gray)"> (optional)</span>';
      html+='<div class="fd" style="margin-bottom:10px"><label style="font-size:.78rem;font-weight:600;display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9.4 10.5l4.77-8.26C13.47 2.09 12.75 2 12 2c-2.4 0-4.6.85-6.32 2.25l3.66 6.35.06-.1zM21.54 9c-.92-2.92-3.15-5.26-6-6.34L11.88 9h9.66zm.26 1h-7.49l.29.5 4.76 8.25C21.07 16.17 22 14.21 22 12c0-.69-.07-1.36-.2-2zM8.54 12l-3.9-6.75C3.01 7.03 2 9.39 2 12c0 .69.07 1.36.2 2h7.49l-1.15-2zm-6.08 3c.92 2.92 3.15 5.26 6 6.34L12.12 15H2.46zm11.27 0-3.9 6.76c.7.15 1.42.24 2.17.24 2.4 0 4.6-.85 6.32-2.25l-2.44-4.75H13.73z"/></svg>'+slot.title+reqTag+'</label>';
      html+='<div id="cdprev_'+it.id+'_'+si+'" style="display:'+(preview?'block':'none')+';margin-bottom:6px"><img id="cdprevimg_'+it.id+'_'+si+'" src="'+(preview||'')+'" style="max-width:100%;max-height:100px;border-radius:7px"><button type="button" onclick="clrSlot(\''+it.id+'\','+si+')" style="display:flex;align-items:center;gap:4px;margin-top:4px;background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);border-radius:6px;padding:3px 10px;font-size:.74rem;cursor:pointer"><svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>Remove</button></div>';
      html+='<div class="aup" style="padding:14px;display:flex;align-items:center;justify-content:center;gap:6px" onclick="document.getElementById(\'cdfile_'+it.id+'_'+si+'\').click()"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9.4 10.5l4.77-8.26C13.47 2.09 12.75 2 12 2c-2.4 0-4.6.85-6.32 2.25l3.66 6.35.06-.1zM21.54 9c-.92-2.92-3.15-5.26-6-6.34L11.88 9h9.66zm.26 1h-7.49l.29.5 4.76 8.25C21.07 16.17 22 14.21 22 12c0-.69-.07-1.36-.2-2zM8.54 12l-3.9-6.75C3.01 7.03 2 9.39 2 12c0 .69.07 1.36.2 2h7.49l-1.15-2zm-6.08 3c.92 2.92 3.15 5.26 6 6.34L12.12 15H2.46zm11.27 0-3.9 6.76c.7.15 1.42.24 2.17.24 2.4 0 4.6-.85 6.32-2.25l-2.44-4.75H13.73z"/></svg><p style="font-size:.8rem">'+slot.title+' upload করুন</p></div>';
      html+='<input type="file" id="cdfile_'+it.id+'_'+si+'" accept="image/*" style="display:none" onchange="upSlot(\''+it.id+'\','+si+',this)"></div>';
    });
    d.innerHTML=html;c.appendChild(d);
  });
}

async function upSlot(itemId,slotIdx,input){
  if(!input.files||!input.files[0])return;
  var file=input.files[0];var slotKey='slot_'+slotIdx;
  var reader=new FileReader();
  reader.onload=function(e){
    if(!cdData[itemId])cdData[itemId]={text:'',slots:{},slotPreviews:{}};
    cdData[itemId].slotPreviews[slotKey]=e.target.result;
    var prev=document.getElementById('cdprev_'+itemId+'_'+slotIdx);
    var img=document.getElementById('cdprevimg_'+itemId+'_'+slotIdx);
    if(prev)prev.style.display='block';if(img)img.src=e.target.result;
  };
  reader.readAsDataURL(file);
  var fd=new FormData();fd.append('image',file);
  try{var r=await fetch('/api/upload',{method:'POST',body:fd});var d=await r.json();if(d.ok){if(!cdData[itemId])cdData[itemId]={text:'',slots:{},slotPreviews:{}};cdData[itemId].slots[slotKey]=d.url;}else{alert('Upload failed: '+(d.reason||d.error||'unknown'));showToast('tErr');}}
catch(e){alert('Network error during upload.');showToast('tErr');}
  input.value='';
}

function clrSlot(itemId,slotIdx){
  var sk='slot_'+slotIdx;
  if(!cdData[itemId])return;
  delete cdData[itemId].slots[sk];delete cdData[itemId].slotPreviews[sk];
  var prev=document.getElementById('cdprev_'+itemId+'_'+slotIdx);if(prev)prev.style.display='none';
}

/* Order Summary */
function buildSummary(){
  var s=0;Object.values(sel).forEach(function(i){s+=(i.price+(i.extraPrice||0))*i.qty;});
  var html='<div class="s2i">';
  Object.values(sel).forEach(function(it){
    var cd=cdData[it.id]||{};var extra=it.extraPrice||0;var dp=it.price+extra;
    html+='<div class="s2it"><img src="'+it.path+'" alt=""><div class="s2ii"><span>Qty: '+it.qty+'</span>';
    Object.entries(it.opts).forEach(function(e2){
      html+='<span>'+e2[0]+': '+e2[1]+'</span>';
      var subSel = (it.subopts[e2[0]] && it.subopts[e2[0]][e2[1]]) || {};
      Object.entries(subSel).forEach(function(se){ if(se[1]) html+='<span style="padding-left:8px;color:var(--gray)">'+se[0]+': '+se[1]+'</span>'; });
    });
    if(extra>0)html+='<span style="color:var(--g);font-size:.74rem">Extra: +৳'+extra+'</span>';
    html+='<span class="s2p">৳'+(dp*it.qty).toFixed(0)+'</span>';
    if(cd.text)html+='<span style="font-size:.72rem;color:var(--gray)">'+cd.text+'</span>';
    if(cd.slots){Object.entries(cd.slots).forEach(function(se){var si=parseInt(se[0].split('_')[1]);var slot=PD.cdSlots[si];html+='<div style="display:flex;align-items:center;gap:5px;margin-top:3px"><span style="font-size:.7rem;color:var(--gray)">'+(slot?slot.title:'Image')+':</span><img src="'+se[1]+'" style="width:32px;height:32px;object-fit:cover;border-radius:4px"></div>';});}
    html+='</div></div>';
  });
  var delTxt=PD.isFreeD?'Free':'৳'+PD.del.toFixed(0);
  if(PD.advancePct>0){
    var adv=Math.ceil(s*PD.advancePct/100);
    html+='</div><div class="s2t"><span>Product: ৳'+s.toFixed(0)+'</span><span>Delivery: '+delTxt+'</span>';
    html+='<span style="color:var(--g);font-weight:700">Advance ('+PD.advancePct+'%): ৳'+adv.toFixed(0)+'</span>';
    html+='<span style="color:var(--gray)">Remaining after delivery: ৳'+(s-adv+PD.del).toFixed(0)+'</span>';
    html+='<strong>Total: ৳'+(s+PD.del).toFixed(0)+'</strong></div>';
  } else {
    html+='</div><div class="s2t"><span>Product: ৳'+s.toFixed(0)+'</span><span>Delivery: '+delTxt+'</span><strong>Total: ৳'+(s+PD.del).toFixed(0)+'</strong></div>';
  }
  document.getElementById('orderSummary').innerHTML='<div class="s2s">'+html+'</div>';
  calcTotal();
}

/* Payment selection */
function selPM(method,btn){
  document.querySelectorAll('.pmb').forEach(function(b){b.classList.remove('sel');});btn.classList.add('sel');
  ['bkashBox','nagadBox','codBox'].forEach(function(id){var el=document.getElementById(id);if(el)el.style.display='none';});
  var s=0;Object.values(sel).forEach(function(i){s+=(i.price+(i.extraPrice||0))*i.qty;});
  var isD=PD.payOpt==='delivery_only';
  var isA=PD.payOpt==='advance_only';
  var amt;
  if(isD){amt=PD.del;}
  else if(isA){amt=Math.ceil(s*PD.advancePct/100);}
  else if(PD.advancePct>0){amt=Math.ceil(s*PD.advancePct/100);}
  else{amt=s+PD.del;}
  var as='৳'+amt.toFixed(0);
  if(method==='bkash'){var box=document.getElementById('bkashBox');if(box){box.style.display='block';var t=document.getElementById('bkashAmtTxt');if(t)t.textContent=as;var inp=document.getElementById('bkashL4');if(inp)inp.value='';}}
  else if(method==='nagad'){var box2=document.getElementById('nagadBox');if(box2){box2.style.display='block';var t2=document.getElementById('nagadAmtTxt');if(t2)t2.textContent=as;var inp2=document.getElementById('nagadL4');if(inp2)inp2.value='';}}
  else if(method==='cod'){var box3=document.getElementById('codBox');if(box3)box3.style.display='block';}
}

/* Place Order */
async function placeOrder(method,last4Id,btn){
  var n=document.getElementById('oN').value.trim();var m=document.getElementById('oM').value.trim();
  var di=document.getElementById('oDi').value.trim();var up=document.getElementById('oUp').value.trim();
  var un=document.getElementById('oUn').value.trim();var vi=document.getElementById('oVi').value.trim();
  if(!n||!m||!di||!up||!un||!vi){alert('Please fill all required fields.');return;}

  if(PD.hasCD){
    for(var id in sel){
      for(var si=0;si<PD.cdSlots.length;si++){
        if(PD.cdSlots[si].required){
          var sk='slot_'+si;
          if(!cdData[id]||!cdData[id].slots||!cdData[id].slots[sk]){
            alert('"'+PD.cdSlots[si].title+'" ছবি দেওয়া আবশ্যক।');return;
          }
        }
      }
    }
  }

  var last4='';
  if(last4Id){last4=(document.getElementById(last4Id)||{}).value||'';last4=last4.trim();if(last4.length!==4||!/^\d{4}$/.test(last4)){alert('Enter valid 4 digits.');return;}}

  var s=0;
  var items=Object.values(sel).map(function(it){
    s+=(it.price+(it.extraPrice||0))*it.qty;
    var cd=cdData[it.id]||{};
    // Flatten selected_options to include sub-option selections too
    var flatOpts = Object.assign({}, it.opts);
    Object.keys(it.opts).forEach(function(optName){
      var pv = it.opts[optName];
      var subSel = (it.subopts[optName] && it.subopts[optName][pv]) || {};
      Object.entries(subSel).forEach(function(se){ if(se[1]) flatOpts[optName+' - '+se[0]] = se[1]; });
    });
    return{
      product_id:PD.id,product_image_id:it.id,product_name:PD.name,image_path:it.path,
      quantity:it.qty,price:it.price+(it.extraPrice||0),extra_price:it.extraPrice||0,
      selected_options:flatOpts,
      custom_design_text:cd.text||'',
      custom_design_slots:cd.slots||{},
      custom_design_image1:'',custom_design_image2:''
    };
  });

  var advanceAmt = PD.advancePct > 0 ? Math.ceil(s * PD.advancePct / 100) : 0;
  var origTxt=btn.textContent;btn.disabled=true;btn.textContent='Placing order...';
  try{
    var r=await fetch('/api/place-order',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({items:items,name:n,mobile:m,district:di,upazila:up,union_name:un,village:vi,
        road_name:document.getElementById('oRo').value.trim(),
        holding_number:document.getElementById('oHo').value.trim(),
        delivery_charge:PD.del,total_amount:s+PD.del,
        advance_amount:advanceAmt,advance_percent:PD.advancePct,
        payment_method:method,sender_last4:last4,
        payment_status:method==='cod'?'cod':'pending_verification'})});
    var d=await r.json();
    if(d.ok){closePanel();showToast('tOk');setTimeout(function(){window.location.href='/orders';},2000);}
    else alert('Error: '+(d.error||''));
  }catch(e){alert('Network error.');}
  finally{btn.disabled=false;btn.textContent=origTxt;}
}

/* Quick Cart */
async function qc(){
  if(!PD.imgs.length){alert('No images');return;}
  var img=PD.imgs[carCur]||PD.imgs[0];
  var fd=new FormData();fd.append('product_id',PD.id);fd.append('product_image_id',img.id);fd.append('product_name',PD.name);fd.append('image_path',img.path);fd.append('quantity','1');fd.append('price',img.price.toString());
  try{var r=await fetch('/api/cart/add',{method:'POST',body:fd});var d=await r.json();if(d.error==='not_logged_in'){showToast('tNoAcc');return;}if(d.ok)showToast('tCart');else showToast('tErr');}catch(e){showToast('tErr');}
}

function showToast(id){var el=document.getElementById(id);if(!el)return;el.classList.add('show');setTimeout(function(){el.classList.remove('show');},2800);}
</script>

<?php render_nav('home'); render_foot(); ?><?php
require_once __DIR__ . '/../templates/layout.php';

$u  = rz_get_user();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /home'); exit; }

$p = DB::row("SELECT * FROM products WHERE id=? AND is_active=1", [$id]);
if (!$p) { header('Location: /home'); exit; }

$images   = DB::rows("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC", [$id]);
$opts_raw = DB::rows("SELECT * FROM product_options WHERE product_id=?", [$id]);
$options  = [];
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

$pay_opt        = $cfg['payment_options'] ?? 'cod';
$bkash_num      = $cfg['bkash_number']   ?? '';
$nagad_num      = $cfg['nagad_number']   ?? '';
$img_count      = count($images);
$regular_price  = (float)($p['regular_price']    ?? 0);
$discount_pct   = (float)($p['discount_percent'] ?? 0);
$base_price     = (float)$p['base_price'];
$is_free_del    = (int)($p['is_free_delivery']   ?? 0);
$delivery_charge= $is_free_del ? 0 : (float)$p['delivery_charge'];
$max_qty        = (int)($p['max_quantity']        ?? 0);
$advance_pct    = (float)($p['advance_percent']   ?? 0);
$prod_pay       = $p['product_payment_method']   ?? 'default';
if ($prod_pay !== 'default') $pay_opt = $prod_pay;
$has_discount   = $regular_price > 0 && $discount_pct > 0;
$offer_price    = $has_discount ? round($regular_price * (1 - $discount_pct / 100)) : $base_price;
$fixed_price    = isset($p['fixed_display_price']) && $p['fixed_display_price'] !== null ? (float)$p['fixed_display_price'] : null;
$display_price  = $fixed_price !== null ? $fixed_price : $offer_price;

$cd_slots = json_decode($p['cd_image_slots'] ?? 'null', true);
if (!$cd_slots) {
    $cd_slots = [
        ['title' => 'সামনের ছবি', 'required' => false],
        ['title' => 'পিছনের ছবি', 'required' => false],
    ];
}

// ── Related Products: same category হলে category থেকে, না হলে random সব পণ্য থেকে ──
if (!empty($p['category'])) {
    $related = DB::rows(
        "SELECT pr.*,
         (SELECT pi2.image_path FROM product_images pi2 WHERE pi2.product_id=pr.id ORDER BY pi2.sort_order ASC LIMIT 1) AS thumb
         FROM products pr
         WHERE pr.is_active=1 AND pr.id != ? AND pr.category = ?
         ORDER BY RAND() LIMIT 10",
        [$id, $p['category']]
    );
} else {
    $related = [];
}
if (!$related) {
    $related = DB::rows(
        "SELECT pr.*,
         (SELECT pi2.image_path FROM product_images pi2 WHERE pi2.product_id=pr.id ORDER BY pi2.sort_order ASC LIMIT 1) AS thumb
         FROM products pr
         WHERE pr.is_active=1 AND pr.id != ?
         ORDER BY RAND() LIMIT 10",
        [$id]
    );
}

$imgs_json = json_encode(array_map(fn($img) => [
    'id'    => (string)$img['id'],
    'path'  => $img['image_path'],
    'price' => (float)($img['price'] ?: $offer_price)
], $images), JSON_UNESCAPED_UNICODE);

$opts_json = json_encode(array_map(fn($o) => [
    'name'        => $o['option_name'],
    'is_required' => (bool)($o['is_required'] ?? true),
    'vals'        => array_map(fn($v) => [
        'val'        => $v['value'],
        'price'      => (float)($v['extra_price'] ?? 0),
        'suboptions' => array_map(fn($sub) => [
            'name' => $sub['suboption_name'],
            'vals' => array_map(fn($sv) => [
                'val'   => $sv['value'],
                'price' => (float)($sv['extra_price'] ?? 0)
            ], $sub['values'])
        ], $v['suboptions'] ?? [])
    ], $o['values'])
], $options), JSON_UNESCAPED_UNICODE);

$cd_slots_json = json_encode($cd_slots, JSON_UNESCAPED_UNICODE);

render_head(htmlspecialchars($p['name']), $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg></a>
    <span class="st"><?= htmlspecialchars($p['name']) ?></span>
    <a href="/cart" class="ib"><svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z"/></svg></a>
  </div>

  <!-- Carousel -->
  <div class="car" id="car">
    <?php if ($images): ?>
    <div class="ct" id="ct">
      <?php foreach ($images as $i => $img): ?>
      <div class="cs"><img src="<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" onclick="openViewer(<?= $i ?>)" style="cursor:zoom-in"></div>
      <?php endforeach; ?>
    </div>
    <div class="cd">
      <?php foreach ($images as $i => $img): ?><span class="dot<?= $i===0?' on':'' ?>" onclick="gs(<?= $i ?>)"></span><?php endforeach; ?>
    </div>
    <div style="position:absolute;bottom:36px;right:10px;background:rgba(0,0,0,.55);color:#fff;font-size:.6rem;padding:3px 7px;border-radius:50px;pointer-events:none;display:flex;align-items:center;gap:4px"><svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>Tap to zoom</div>
    <?php if ($has_discount && $fixed_price === null): ?>
    <div style="position:absolute;top:10px;left:10px;background:linear-gradient(135deg,#F44336,#C62828);color:#fff;font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:50px;z-index:2">-<?= (int)$discount_pct ?>% OFF</div>
    <?php endif; ?>
    <?php else: ?><div class="ce"><svg viewBox="0 0 24 24" style="width:56px;height:56px;fill:var(--gray)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></div><?php endif; ?>
    <?php if ($p['video_url']): ?><button class="vb2" onclick="ovFull('<?= htmlspecialchars($p['video_url']) ?>')" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M8 5v14l11-7z"/></svg>Video</button><?php endif; ?>
  </div>

  <!-- Product Info: Name → Price → Description -->
  <div class="pdi">
    <h1 class="pdn"><?= htmlspecialchars($p['name']) ?></h1>

    <!-- Price Block — dynamic (or fixed) -->
    <div id="priceBlock" style="background:var(--k3);border:1px solid var(--bdr2);border-radius:var(--r);padding:12px 14px;margin:8px 0 10px">
      <?php if ($has_discount && $fixed_price === null): ?>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px">
        <span style="font-size:.88rem;color:var(--gray);text-decoration:line-through">৳<?= number_format($regular_price, 0) ?></span>
        <span style="background:linear-gradient(135deg,#F44336,#C62828);color:#fff;border-radius:50px;padding:2px 9px;font-size:.7rem;font-weight:700">-<?= round($discount_pct) ?>%</span>
      </div>
      <?php endif; ?>

      <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:4px">
        <span style="font-size:1.55rem;font-weight:700;color:var(--g)" id="displayPrice">৳<?= number_format($display_price, 0) ?></span>
        <span style="font-size:.78rem;color:var(--gray)" id="priceNote"><?= ($p['has_custom_design'] && $fixed_price === null) ? '— size বা option select এ দাম পরিবর্তন হতে পারে' : '' ?></span>
      </div>

      <!-- Extra price badge (hidden when fixed price mode) -->
      <div id="extraPriceBadge" style="display:none;font-size:.78rem;color:var(--g);font-weight:600;margin-bottom:5px"></div>

      <!-- Advance payment notice -->
      <?php if ($advance_pct > 0): ?>
      <div id="advanceBox" style="background:rgba(201,168,76,.1);border:1px solid var(--g);border-radius:8px;padding:8px 12px;margin-bottom:6px">
        <p style="font-size:.78rem;font-weight:700;color:var(--g);margin-bottom:2px;display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>Advance Payment Required</p>
        <p style="font-size:.76rem;color:var(--gray)">মোট দামের <strong style="color:var(--g)"><?= $advance_pct ?>%</strong> advance pay করতে হবে।</p>
        <p style="font-size:.82rem;font-weight:700;color:var(--g)" id="advanceAmtDisplay">Advance: ৳<?= number_format($display_price * $advance_pct / 100, 0) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($is_free_del): ?>
      <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(76,175,80,.1);border:1px solid rgba(76,175,80,.3);border-radius:50px;padding:3px 11px;font-size:.76rem;color:#4CAF50;font-weight:700"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zM18 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>Free Delivery</div>
      <?php else: ?>
      <div style="display:inline-flex;align-items:center;gap:5px;background:var(--k2);border:1px solid var(--bdr2);border-radius:50px;padding:3px 11px;font-size:.76rem;color:var(--gray)"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zM18 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>Delivery: ৳<?= number_format($delivery_charge, 0) ?></div>
      <?php endif; ?>

      <?php if ($max_qty > 0): ?>
      <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,152,0,.1);border:1px solid #FF9800;border-radius:50px;padding:3px 11px;margin-left:5px;font-size:.74rem;color:#FF9800;font-weight:600"><svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>Max <?= $max_qty ?>/order</div>
      <?php endif; ?>
      <?php if ($p['has_custom_design']): ?>
      <div style="display:block;margin-top:6px;font-size:.76rem;color:var(--g);font-weight:600;display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>Custom Design Available</div>
      <?php endif; ?>
    </div>

    <!-- Description -->
    <?php if ($p['description']): ?>
    <p class="pdd"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if ($related): ?>
    <div style="margin-top:18px">
      <p style="font-size:.86rem;font-weight:700;color:var(--g);margin-bottom:10px;display:flex;align-items:center;gap:6px">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>
        Related Products
      </p>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">
        <?php foreach ($related as $rp):
          $rFixed   = isset($rp['fixed_display_price']) && $rp['fixed_display_price'] !== null ? (float)$rp['fixed_display_price'] : null;
          $rHasDisc = $rp['discount_percent'] > 0 && $rp['regular_price'] > 0;
          $rOffer   = $rHasDisc ? round($rp['regular_price'] * (1 - $rp['discount_percent']/100)) : (float)$rp['base_price'];
          $rDisplay = $rFixed !== null ? $rFixed : $rOffer;
        ?>
        <a href="/product/<?= $rp['id'] ?>" class="pc">
          <div class="pci">
            <?php if ($rp['thumb']): ?>
            <img src="<?= htmlspecialchars($rp['thumb']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>" loading="lazy">
            <?php else: ?>
            <div class="ni"><svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:var(--gray)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></div>
            <?php endif; ?>
            <?php if ($rHasDisc && $rFixed === null): ?><span class="disc-badge">-<?= (int)$rp['discount_percent'] ?>%</span><?php endif; ?>
          </div>
          <div class="pin">
            <p class="pn"><?= htmlspecialchars($rp['name']) ?></p>
            <div class="pr">
              <span class="pp">৳<?= number_format($rDisplay, 0) ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div style="height:calc(var(--nav) + 60px)"></div>
</div>

<!-- Action Buttons -->
<div class="pdacts">
  <button class="bca" onclick="qc()" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z"/></svg>Add to Cart</button>
  <button class="boa" onclick="openPanel()" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>Order Now</button>
</div>

<!-- Fullscreen Image Viewer -->
<div id="imgViewer" style="display:none;position:fixed;inset:0;background:#000;z-index:700">
  <div style="position:absolute;top:0;left:0;right:0;height:54px;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:space-between;padding:0 14px;z-index:3">
    <span id="viewerCount" style="color:rgba(255,255,255,.85);font-size:.86rem;font-weight:600"></span>
    <div style="display:flex;gap:8px;align-items:center">
      <a id="dlBtn" href="#" download style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;padding:6px 13px;border-radius:50px;font-size:.76rem;text-decoration:none;display:flex;align-items:center;gap:4px" onclick="event.stopPropagation()"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>Download</a>
      <button onclick="closeViewer()" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;width:34px;height:34px;border-radius:50%;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center">✕</button>
    </div>
  </div>
  <div style="position:absolute;top:54px;bottom:44px;left:0;right:0;overflow:hidden">
    <div id="viewerTrack" style="display:flex;height:100%;width:<?= max($img_count,1)*100 ?>%;transition:transform .38s cubic-bezier(.25,1,.5,1);will-change:transform">
      <?php foreach ($images as $img): ?>
      <div style="width:<?= $img_count>0?round(100/$img_count,4):100 ?>%;height:100%;flex-shrink:0;display:flex;align-items:center;justify-content:center;padding:8px" onclick="event.stopPropagation()">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:6px;display:block;-webkit-user-drag:none;user-select:none">
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if ($img_count > 1): ?>
  <button onclick="viewerGo(viewerCur-1)" style="position:absolute;left:6px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.2);color:#fff;width:38px;height:38px;border-radius:50%;font-size:1.2rem;cursor:pointer;z-index:3;display:flex;align-items:center;justify-content:center">‹</button>
  <button onclick="viewerGo(viewerCur+1)" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.2);color:#fff;width:38px;height:38px;border-radius:50%;font-size:1.2rem;cursor:pointer;z-index:3;display:flex;align-items:center;justify-content:center">›</button>
  <?php endif; ?>
  <div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:5px;z-index:3">
    <?php foreach ($images as $i => $img): ?>
    <span class="vdot" onclick="viewerGo(<?= $i ?>)" style="display:block;height:6px;border-radius:3px;cursor:pointer;transition:all .3s;background:<?= $i===0?'var(--g)':'rgba(255,255,255,.35)' ?>;width:<?= $i===0?'18px':'6px' ?>"></span>
    <?php endforeach; ?>
  </div>
</div>

<!-- Video -->
<div id="vov" style="display:none;position:fixed;inset:0;background:#000;z-index:800;flex-direction:column">
  <div style="position:absolute;top:0;left:0;right:0;height:50px;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:flex-end;padding:0 14px;z-index:2">
    <button onclick="closeVideo()" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;width:36px;height:36px;border-radius:50%;font-size:.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center">✕</button>
  </div>
  <div style="position:absolute;top:50px;bottom:0;left:0;right:0">
    <iframe id="vf" src="" frameborder="0" allowfullscreen allow="autoplay" style="width:100%;height:100%;display:block"></iframe>
  </div>
</div>

<!-- Order Panel -->
<div class="pov" id="pov" onclick="if(event.target===this)closePanel()"></div>
<div class="panel" id="panel">
  <div class="ph2"></div>
  <div class="phd"><h3>Place Order</h3><button class="pcl" onclick="closePanel()">✕</button></div>

  <!-- Step 1 -->
  <div id="s1" class="pst">
    <p class="slbl">STEP 1 — SELECT PRODUCT</p>
    <?php if ($images): ?>
    <div class="isg">
      <?php foreach ($images as $i => $img):
        $imgPrice = (float)($img['price'] ?: $offer_price);
      ?>
      <div class="isi" id="ic<?= $i ?>" onclick="selImg(<?= $i ?>,'<?= $img['id'] ?>','<?= addslashes($img['image_path']) ?>',<?= $imgPrice ?>)">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="">
        <div class="ick">✓</div>
        <div class="isp">৳<?= number_format($imgPrice, 0) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?><div style="padding:16px;text-align:center;color:var(--gray)">Order directly</div><?php endif; ?>
    <div id="selItems"></div>
    <div id="tMaxQty" style="display:none;background:rgba(255,152,0,.12);border:1px solid #FF9800;border-radius:var(--r2);padding:9px 13px;font-size:.82rem;color:#FF9800;margin:8px 0;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>Maximum quantity reached</div>
    <div id="priceSec" style="display:none" class="pbx">
      <div class="prow"><span>Product:</span><span id="subtotalTxt">৳0</span></div>
      <?php if ($advance_pct > 0): ?>
      <div class="prow" style="color:var(--g);font-weight:600"><span>Advance (<?= $advance_pct ?>%):</span><span id="advanceTxt">৳0</span></div>
      <div class="prow"><span>Remaining:</span><span id="remainingTxt">৳0</span></div>
      <?php endif; ?>
      <?php if ($is_free_del): ?>
      <div class="prow"><span>Delivery:</span><span style="color:#4CAF50;font-weight:700">Free</span></div>
      <?php else: ?>
      <div class="prow"><span>Delivery:</span><span>৳<?= number_format($delivery_charge, 0) ?></span></div>
      <?php endif; ?>
      <div class="ptot"><span>Total:</span><span id="totalTxt">৳0</span></div>
    </div>
    <button class="bn" id="nextBtn1" onclick="goStep2()" disabled>Next Step →</button>
  </div>

  <!-- Step 2: Custom Design -->
  <?php if ($p['has_custom_design']): ?>
  <div id="s2cd" class="pst" style="display:none">
    <button class="bbk" onclick="backTo1()">← Back</button>
    <p class="slbl">STEP 2 — CUSTOM DESIGN</p>
    <div id="cdContainer"></div>
    <button class="bn" onclick="goStep3()">Next →</button>
  </div>
  <?php endif; ?>

  <!-- Step 3: Delivery + Payment -->
  <div id="s3" class="pst" style="display:none">
    <button class="bbk" onclick="<?= $p['has_custom_design'] ? 'backTo2cd()' : 'backTo1()' ?>">← Back</button>
    <p class="slbl">STEP <?= $p['has_custom_design']?'3':'2' ?> — DELIVERY INFO</p>
    <div id="orderSummary"></div>
    <div class="fs" style="margin-top:12px">
      <div class="fd"><label>Name *</label><input id="oN" class="inp" placeholder="Full name" value="<?= htmlspecialchars($u['name'] ?? '') ?>"></div>
      <div class="fd"><label>Mobile *</label><input id="oM" class="inp" type="tel" placeholder="01XXXXXXXXX" value="<?= htmlspecialchars($u['mobile'] ?? '') ?>"></div>
      <div class="fd"><label>District *</label><input id="oDi" class="inp" placeholder="e.g. Dhaka"></div>
      <div class="fd"><label>Upazila *</label><input id="oUp" class="inp" placeholder="Upazila name"></div>
      <div class="fd"><label>Union *</label><input id="oUn" class="inp" placeholder="Union name"></div>
      <div class="fd"><label>Village *</label><input id="oVi" class="inp" placeholder="Village name"></div>
      <div class="fd"><label>Road</label><input id="oRo" class="inp" placeholder="Road (optional)"></div>
      <div class="fd"><label>Holding</label><input id="oHo" class="inp" placeholder="Holding (optional)"></div>
    </div>

    <!-- Payment -->
    <div style="margin-top:14px">
      <?php if ($pay_opt === 'cod' || $pay_opt === 'free_delivery'): ?>
      <div class="pay-cod-box"><span class="pico2"><svg viewBox="0 0 24 24" style="width:24px;height:24px;fill:#4CAF50"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></span><div class="ptxt2"><span class="ptitle2">Cash on Delivery</span><span class="psub2">Pay when you receive</span></div></div>
      <button class="bpl" onclick="placeOrder('cod',null,this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Confirm Order</button>

      <?php elseif ($pay_opt === 'delivery_only' || $pay_opt === 'advance_only'): ?>
      <p class="pay-title"><?= $pay_opt === 'advance_only' ? 'PAY ADVANCE AMOUNT (' . $advance_pct . '%)' : 'PAY DELIVERY CHARGE ONLY' ?></p>
      <div class="pay-methods">
        <?php if ($bkash_num): ?><button class="pmb" onclick="selPM('bkash',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#E2136E"><path d="M3 7h18v2H3zm2 4h14v2H5zm-2 4h18v2H3z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Bkash</span><span class="pmb-sub"><?= $pay_opt === 'advance_only' ? 'Advance payment' : 'Delivery only' ?></span></div><span class="pmb-amt" id="bkashAmt">৳—</span></button><?php endif; ?>
        <?php if ($nagad_num): ?><button class="pmb" onclick="selPM('nagad',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#F6921E"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Nagad</span><span class="pmb-sub"><?= $pay_opt === 'advance_only' ? 'Advance payment' : 'Delivery only' ?></span></div><span class="pmb-amt" id="nagadAmt">৳—</span></button><?php endif; ?>
      </div>
      <?php if ($bkash_num): ?><div id="bkashBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="bkashAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($bkash_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="bkashL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('bkash','bkashL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Bkash — Order Now</button></div><?php endif; ?>
      <?php if ($nagad_num): ?><div id="nagadBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="nagadAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($nagad_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="nagadL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('nagad','nagadL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Nagad — Order Now</button></div><?php endif; ?>

      <?php elseif ($pay_opt === 'full'): ?>
      <p class="pay-title">FULL PAYMENT</p>
      <div class="pay-methods">
        <?php if ($bkash_num): ?><button class="pmb" onclick="selPM('bkash',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#E2136E"><path d="M3 7h18v2H3zm2 4h14v2H5zm-2 4h18v2H3z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Bkash</span><span class="pmb-sub">Full payment</span></div><span class="pmb-amt" id="bkashAmt">৳—</span></button><?php endif; ?>
        <?php if ($nagad_num): ?><button class="pmb" onclick="selPM('nagad',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#F6921E"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Nagad</span><span class="pmb-sub">Full payment</span></div><span class="pmb-amt" id="nagadAmt">৳—</span></button><?php endif; ?>
      </div>
      <?php if ($bkash_num): ?><div id="bkashBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="bkashAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($bkash_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="bkashL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('bkash','bkashL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Bkash — Order Now</button></div><?php endif; ?>
      <?php if ($nagad_num): ?><div id="nagadBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="nagadAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($nagad_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="nagadL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('nagad','nagadL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Nagad — Order Now</button></div><?php endif; ?>

      <?php else: /* all */ ?>
      <p class="pay-title">SELECT PAYMENT METHOD</p>
      <div class="pay-methods">
        <button class="pmb" onclick="selPM('cod',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#4CAF50"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Cash on Delivery</span><span class="pmb-sub">Pay on delivery</span></div></button>
        <?php if ($bkash_num): ?><button class="pmb" onclick="selPM('bkash',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#E2136E"><path d="M3 7h18v2H3zm2 4h14v2H5zm-2 4h18v2H3z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Bkash</span><span class="pmb-sub">Full payment</span></div><span class="pmb-amt" id="bkashAmt">৳—</span></button><?php endif; ?>
        <?php if ($nagad_num): ?><button class="pmb" onclick="selPM('nagad',this)"><div class="pmb-ico"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#F6921E"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg></div><div class="pmb-txt"><span class="pmb-title">Nagad</span><span class="pmb-sub">Full payment</span></div><span class="pmb-amt" id="nagadAmt">৳—</span></button><?php endif; ?>
      </div>
      <?php if ($bkash_num): ?><div id="bkashBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="bkashAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($bkash_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="bkashL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('bkash','bkashL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Bkash — Order Now</button></div><?php endif; ?>
      <?php if ($nagad_num): ?><div id="nagadBox" class="smbox" style="display:none"><p class="smbox-title">Send Money to: <strong id="nagadAmtTxt"></strong></p><div class="smnum"><?= htmlspecialchars($nagad_num) ?></div><div class="fd"><label>Last 4 digits *</label><input type="text" id="nagadL4" class="inp last4" placeholder="4 digits" maxlength="4" inputmode="numeric"></div><button class="sm-confirm" onclick="placeOrder('nagad','nagadL4',this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Sent via Nagad — Order Now</button></div><?php endif; ?>
      <div id="codBox" style="display:none;margin-top:8px"><div class="pay-cod-box" style="margin-bottom:10px"><span class="pico2"><svg viewBox="0 0 24 24" style="width:24px;height:24px;fill:#4CAF50"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></span><div class="ptxt2"><span class="ptitle2">Cash on Delivery</span><span class="psub2">Pay when you receive</span></div></div><button class="bpl" onclick="placeOrder('cod',null,this)" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Confirm Order</button></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Option Bottom Sheet (shared, reused for every option) -->
<div class="opt-sheet-ov" id="optSheetOv" onclick="if(event.target===this)closeOptSheet()"></div>
<div class="opt-sheet" id="optSheet">
  <div class="opt-sheet-handle"></div>
  <div class="opt-sheet-head">
    <h4 id="optSheetTitle">Select</h4>
    <button class="opt-sheet-close" onclick="closeOptSheet()">✕</button>
  </div>
  <div class="opt-sheet-list" id="optSheetList"></div>
</div>

<!-- Sub-option Bottom Sheet (popup within popup, e.g. Color inside a size) -->
<div class="opt-sheet-ov" id="subOptSheetOv" style="z-index:970" onclick="if(event.target===this)closeSubOptSheet()"></div>
<div class="opt-sheet" id="subOptSheet" style="z-index:971">
  <div class="opt-sheet-handle"></div>
  <div class="opt-sheet-head">
    <h4 id="subOptSheetTitle">Select</h4>
    <button class="opt-sheet-close" onclick="closeSubOptSheet()">✕</button>
  </div>
  <div class="opt-sheet-list" id="subOptSheetList"></div>
</div>

<!-- Toasts -->
<div class="toast" id="tOk" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>Order placed!</div>
<div class="toast" id="tCart" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z"/></svg>Added to cart!</div>
<div class="toast" id="tNoAcc" style="border-color:#FF9800;color:#FF9800;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>No account. Please login.</div>
<div class="toast" id="tErr" style="border-color:#F44336;color:#F44336;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>Error occurred</div>

<script>
var PD = {
  id:         <?= (int)$p['id'] ?>,
  name:       <?= json_encode($p['name'], JSON_UNESCAPED_UNICODE) ?>,
  base:       <?= $offer_price ?>,
  fixedPrice: <?= $fixed_price !== null ? $fixed_price : 'null' ?>,
  del:        <?= $delivery_charge ?>,
  isFreeD:    <?= $is_free_del ? 'true' : 'false' ?>,
  hasCD:      <?= $p['has_custom_design'] ? 'true' : 'false' ?>,
  maxQty:     <?= $max_qty ?>,
  advancePct: <?= $advance_pct ?>,
  imgs:       <?= $imgs_json ?>,
  opts:       <?= $opts_json ?>,
  cdSlots:    <?= $cd_slots_json ?>,
  payOpt:     <?= json_encode($pay_opt) ?>,
  imgCount:   <?= max($img_count, 1) ?>
};
var carCur=0, sel={}, cdData={};
var optSheetCtx=null;    // {itemId, optName}
var subOptSheetCtx=null; // {itemId, optName, subName}

/* Carousel */
function gs(n){carCur=n;var t=document.getElementById('ct');if(t)t.style.transform='translateX(-'+(n*100)+'%)';document.querySelectorAll('.dot').forEach(function(d,i){d.classList.toggle('on',i===n);});}
(function(){var c=document.getElementById('car'),sx=0;if(!c)return;c.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;},{passive:true});c.addEventListener('touchend',function(e){var d=sx-e.changedTouches[0].clientX;if(Math.abs(d)>50)gs(d>0?Math.min(carCur+1,PD.imgs.length-1):Math.max(carCur-1,0));},{passive:true});})();

/* Viewer */
var viewerCur=0;
function openViewer(idx){viewerCur=idx;document.getElementById('imgViewer').style.display='block';document.body.style.overflow='hidden';_updateViewer();}
function closeViewer(){document.getElementById('imgViewer').style.display='none';document.body.style.overflow='';}
function viewerGo(n){viewerCur=((n%PD.imgCount)+PD.imgCount)%PD.imgCount;_updateViewer();}
function _updateViewer(){var t=document.getElementById('viewerTrack');if(t)t.style.transform='translateX(-'+(viewerCur*100/PD.imgCount)+'%)';var c=document.getElementById('viewerCount');if(c)c.textContent=(viewerCur+1)+' / '+PD.imgCount;var dl=document.getElementById('dlBtn');if(dl&&PD.imgs[viewerCur]){dl.href=PD.imgs[viewerCur].path;dl.download=PD.imgs[viewerCur].path.split('/').pop()||'image.jpg';}document.querySelectorAll('.vdot').forEach(function(d,i){d.style.background=i===viewerCur?'var(--g)':'rgba(255,255,255,.35)';d.style.width=i===viewerCur?'18px':'6px';});}
(function(){var vw=document.getElementById('imgViewer'),sx=0;if(!vw)return;vw.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;},{passive:true});vw.addEventListener('touchend',function(e){var d=sx-e.changedTouches[0].clientX;if(Math.abs(d)>40)viewerGo(d>0?viewerCur+1:viewerCur-1);},{passive:true});})();

/* Video */
function ovFull(url){var u=url;if(u.includes('youtube.com/watch?v='))u=u.replace('watch?v=','embed/')+'?autoplay=1&rel=0';else if(u.includes('youtu.be/')){var vid=u.split('youtu.be/')[1].split('?')[0];u='https://www.youtube.com/embed/'+vid+'?autoplay=1&rel=0';}document.getElementById('vf').src=u;document.getElementById('vov').style.display='flex';document.body.style.overflow='hidden';}
function closeVideo(){document.getElementById('vf').src='';document.getElementById('vov').style.display='none';document.body.style.overflow='';}

/* Panel */
function openPanel(){document.getElementById('pov').classList.add('show');document.getElementById('panel').classList.add('show');document.body.style.overflow='hidden';}
function closePanel(){document.getElementById('pov').classList.remove('show');document.getElementById('panel').classList.remove('show');document.body.style.overflow='';}

/* Calculate total extra from selected options (including nested sub-options) */
function calcExtraFromOpts(itemId) {
  var it = sel[itemId]; if (!it) return 0;
  var extra = 0;
  PD.opts.forEach(function(o) {
    var sv = it.opts[o.name];
    if (sv) {
      var found = o.vals.find(function(v){return v.val===sv;});
      if (found) {
        extra += found.price||0;
        // Add nested sub-option prices for this value
        var subSel = (it.subopts && it.subopts[o.name] && it.subopts[o.name][sv]) || {};
        (found.suboptions||[]).forEach(function(sub){
          var ssv = subSel[sub.name];
          if (ssv) {
            var sfound = sub.vals.find(function(v){return v.val===ssv;});
            if (sfound) extra += sfound.price||0;
          }
        });
      }
    }
  });
  return extra;
}

/* Update main price display */
function updateMainPrice() {
  if (PD.fixedPrice !== null) {
    // Fixed display price mode: never changes on selection
    document.getElementById('displayPrice').textContent = '৳' + PD.fixedPrice.toFixed(0);
    document.getElementById('extraPriceBadge').style.display = 'none';
    if (PD.advancePct > 0) {
      var adv0 = document.getElementById('advanceAmtDisplay');
      if (adv0) adv0.textContent = 'Advance: ৳' + Math.ceil(PD.fixedPrice * PD.advancePct / 100).toFixed(0);
    }
    return;
  }
  var firstItem = Object.values(sel)[0];
  if (!firstItem) {
    document.getElementById('displayPrice').textContent = '৳' + PD.base.toFixed(0);
    document.getElementById('extraPriceBadge').style.display = 'none';
    if (PD.advancePct > 0) {
      var adv = document.getElementById('advanceAmtDisplay');
      if (adv) adv.textContent = 'Advance: ৳' + Math.ceil(PD.base * PD.advancePct / 100).toFixed(0);
    }
    return;
  }
  var extra = firstItem.extraPrice || 0;
  var total = firstItem.price + extra;
  document.getElementById('displayPrice').textContent = '৳' + total.toFixed(0);
  var eb = document.getElementById('extraPriceBadge');
  if (eb) { if(extra>0){eb.textContent='+ ৳'+extra+' design extra';eb.style.display='block';}else eb.style.display='none'; }
  if (PD.advancePct > 0) {
    var adv = document.getElementById('advanceAmtDisplay');
    if (adv) adv.textContent = 'Advance: ৳' + Math.ceil(total * PD.advancePct / 100).toFixed(0);
  }
}

/* Image select */
function selImg(idx, id, path, price) {
  if (sel[id]) { delete sel[id]; document.getElementById('ic'+idx).classList.remove('pk'); }
  else {
    if (PD.maxQty > 0) {
      var ts=0; Object.values(sel).forEach(function(it){ts+=it.qty;});
      if (ts >= PD.maxQty) { document.getElementById('tMaxQty').style.display='block'; setTimeout(function(){document.getElementById('tMaxQty').style.display='none';},3000); return; }
    }
    sel[id] = {idx:idx,id:id,path:path,price:price,qty:1,opts:{},subopts:{},extraPrice:0};
    document.getElementById('ic'+idx).classList.add('pk');
  }
  renderSel(); calcTotal(); updateMainPrice();
}

function renderSel() {
  var keys=Object.keys(sel);
  document.getElementById('nextBtn1').disabled=!keys.length;
  if(!keys.length){document.getElementById('selItems').innerHTML='';document.getElementById('priceSec').style.display='none';return;}
  document.getElementById('priceSec').style.display='block';
  var html='<p class="selbl" style="margin-top:10px">Selected:</p>';
  keys.forEach(function(id){
    var it=sel[id];
    var oh=PD.opts.map(function(o){
      var reqMark = o.is_required ? ' <span class="req">*</span>' : ' <span class="optnl">(optional)</span>';
      var curVal = it.opts[o.name] || '';
      var curObj = curVal ? o.vals.find(function(v){return v.val===curVal;}) : null;
      var barValTxt = curVal ? curVal : (o.is_required ? '-- Select --' : '-- Optional --');
      var barValCls = curVal ? '' : ' placeholder';
      var barPrice = (curObj && curObj.price>0) ? ('+৳'+curObj.price) : '';

      var optBlock = '<div class="opt-block">'+
        '<div class="opt-bar'+(curVal?' has-val':'')+'" onclick="openOptSheet(\''+id+'\',\''+o.name.replace(/'/g,"\\'")+'\')">'+
          '<div class="opt-bar-left">'+
            '<span class="opt-bar-label">'+o.name+reqMark+'</span>'+
            '<span class="opt-bar-val'+barValCls+'">'+barValTxt+'</span>'+
          '</div>'+
          '<div class="opt-bar-right">'+
            (barPrice?'<span class="opt-bar-price">'+barPrice+'</span>':'')+
            '<span class="opt-bar-arrow">▼</span>'+
          '</div>'+
        '</div>';

      // Sub-option bars for the currently selected value (e.g. Color inside size 43)
      if (curVal && curObj && curObj.suboptions && curObj.suboptions.length) {
        curObj.suboptions.forEach(function(sub){
          var subSel = (it.subopts[o.name] && it.subopts[o.name][curVal]) || {};
          var subCurVal = subSel[sub.name] || '';
          var subCurObj = subCurVal ? sub.vals.find(function(v){return v.val===subCurVal;}) : null;
          var subValTxt = subCurVal ? subCurVal : '-- Select --';
          var subValCls = subCurVal ? '' : ' placeholder';
          var subPrice = (subCurObj && subCurObj.price>0) ? ('+৳'+subCurObj.price) : '';
          optBlock += '<div class="opt-bar'+(subCurVal?' has-val':'')+'" style="margin-top:7px;margin-left:14px;border-left:2px solid var(--g)" onclick="openSubOptSheet(\''+id+'\',\''+o.name.replace(/'/g,"\\'")+'\',\''+curVal.replace(/'/g,"\\'")+'\',\''+sub.name.replace(/'/g,"\\'")+'\')">'+
            '<div class="opt-bar-left">'+
              '<span class="opt-bar-label">'+sub.name+'</span>'+
              '<span class="opt-bar-val'+subValCls+'">'+subValTxt+'</span>'+
            '</div>'+
            '<div class="opt-bar-right">'+
              (subPrice?'<span class="opt-bar-price">'+subPrice+'</span>':'')+
              '<span class="opt-bar-arrow">▼</span>'+
            '</div>'+
          '</div>';
        });
      }
      optBlock += '</div>';
      return optBlock;
    }).join('');
    var extra=it.extraPrice||0;var dp=it.price+extra;
    html+='<div class="sec"><img src="'+it.path+'" alt=""><div class="seci"><span class="secp">৳'+dp.toFixed(0)+'/pc'+(extra>0?' <small style="color:var(--g)">(+৳'+extra+' extra)</small>':'')+'</span>'+oh+
      '<div class="qr"><button class="qb" onclick="chQty(\''+id+'\',-1)">−</button><span class="qn" id="qn'+id+'">'+it.qty+'</span><button class="qb" onclick="chQty(\''+id+'\',1)">+</button><button class="qd" onclick="delSel(\''+id+'\','+it.idx+')"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button></div></div></div>';
  });
  document.getElementById('selItems').innerHTML=html;
}

/* Option bottom sheet open/close */
function openOptSheet(itemId, optName) {
  var o = PD.opts.find(function(x){return x.name===optName;});
  if (!o) return;
  optSheetCtx = {itemId:itemId, optName:optName};
  document.getElementById('optSheetTitle').textContent = optName;
  var curVal = (sel[itemId] && sel[itemId].opts[optName]) || '';
  var listHtml = '';
  if (!o.is_required) {
    listHtml += '<div class="opt-sheet-item'+(curVal===''?' sel':'')+'" onclick="pickOptValue(\'\')">'+
      '<div class="opt-sheet-item-txt"><span class="opt-sheet-radio"></span><span class="opt-sheet-item-name">-- কিছু না --</span></div>'+
      '</div>';
  }
  o.vals.forEach(function(v){
    var isSel = curVal === v.val;
    var pt = v.price>0 ? '+৳'+v.price : '';
    listHtml += '<div class="opt-sheet-item'+(isSel?' sel':'')+'" onclick="pickOptValue(\''+v.val.replace(/'/g,"\\'")+'\')">'+
      '<div class="opt-sheet-item-txt"><span class="opt-sheet-radio"></span><span class="opt-sheet-item-name">'+v.val+'</span></div>'+
      (pt?'<span class="opt-sheet-item-price">'+pt+'</span>':'')+
      '</div>';
  });
  document.getElementById('optSheetList').innerHTML = listHtml;
  document.getElementById('optSheetOv').classList.add('show');
  document.getElementById('optSheet').classList.add('show');
  document.body.style.overflow='hidden';
}
function closeOptSheet() {
  document.getElementById('optSheetOv').classList.remove('show');
  document.getElementById('optSheet').classList.remove('show');
  document.body.style.overflow='';
  optSheetCtx=null;
}
function pickOptValue(value) {
  if (!optSheetCtx) return;
  setOpt(optSheetCtx.itemId, optSheetCtx.optName, value);
  closeOptSheet();
}

/* Sub-option bottom sheet (popup within a popup, e.g. Color inside size) */
function openSubOptSheet(itemId, optName, parentVal, subName) {
  var o = PD.opts.find(function(x){return x.name===optName;});
  if (!o) return;
  var parentValObj = o.vals.find(function(v){return v.val===parentVal;});
  if (!parentValObj) return;
  var sub = (parentValObj.suboptions||[]).find(function(s){return s.name===subName;});
  if (!sub) return;
  subOptSheetCtx = {itemId:itemId, optName:optName, parentVal:parentVal, subName:subName};
  document.getElementById('subOptSheetTitle').textContent = subName;
  var subSel = (sel[itemId].subopts[optName] && sel[itemId].subopts[optName][parentVal]) || {};
  var curSubVal = subSel[subName] || '';
  var listHtml = '<div class="opt-sheet-item'+(curSubVal===''?' sel':'')+'" onclick="pickSubOptValue(\'\')">'+
    '<div class="opt-sheet-item-txt"><span class="opt-sheet-radio"></span><span class="opt-sheet-item-name">-- কিছু না --</span></div>'+
    '</div>';
  sub.vals.forEach(function(v){
    var isSel = curSubVal === v.val;
    var pt = v.price>0 ? '+৳'+v.price : '';
    listHtml += '<div class="opt-sheet-item'+(isSel?' sel':'')+'" onclick="pickSubOptValue(\''+v.val.replace(/'/g,"\\'")+'\')">'+
      '<div class="opt-sheet-item-txt"><span class="opt-sheet-radio"></span><span class="opt-sheet-item-name">'+v.val+'</span></div>'+
      (pt?'<span class="opt-sheet-item-price">'+pt+'</span>':'')+
      '</div>';
  });
  document.getElementById('subOptSheetList').innerHTML = listHtml;
  document.getElementById('subOptSheetOv').classList.add('show');
  document.getElementById('subOptSheet').classList.add('show');
  document.body.style.overflow='hidden';
}
function closeSubOptSheet() {
  document.getElementById('subOptSheetOv').classList.remove('show');
  document.getElementById('subOptSheet').classList.remove('show');
  document.body.style.overflow='';
  subOptSheetCtx=null;
}
function pickSubOptValue(value) {
  if (!subOptSheetCtx) return;
  var ctx = subOptSheetCtx;
  var it = sel[ctx.itemId];
  if (!it) return;
  if (!it.subopts[ctx.optName]) it.subopts[ctx.optName] = {};
  if (!it.subopts[ctx.optName][ctx.parentVal]) it.subopts[ctx.optName][ctx.parentVal] = {};
  it.subopts[ctx.optName][ctx.parentVal][ctx.subName] = value;
  it.extraPrice = calcExtraFromOpts(ctx.itemId);
  closeSubOptSheet();
  renderSel(); calcTotal(); updateMainPrice();
}

function setOpt(id,n,v){
  if(!sel[id])return;
  sel[id].opts[n]=v;
  sel[id].extraPrice=calcExtraFromOpts(id);
  renderSel();calcTotal();updateMainPrice();
}

function chQty(id,d){
  if(!sel[id])return;var nq=sel[id].qty+d;if(nq<1)return;
  if(PD.maxQty>0&&d>0){var ts=0;Object.values(sel).forEach(function(it){ts+=it.qty;});if(ts>=PD.maxQty){document.getElementById('tMaxQty').style.display='block';setTimeout(function(){document.getElementById('tMaxQty').style.display='none';},3000);return;}}
  sel[id].qty=nq;var el=document.getElementById('qn'+id);if(el)el.textContent=nq;calcTotal();
}
function delSel(id,idx){delete sel[id];var el=document.getElementById('ic'+idx);if(el)el.classList.remove('pk');renderSel();calcTotal();updateMainPrice();}

function calcTotal(){
  var s=0;Object.values(sel).forEach(function(it){s+=(it.price+(it.extraPrice||0))*it.qty;});
  document.getElementById('subtotalTxt').textContent='৳'+s.toFixed(0);
  document.getElementById('totalTxt').textContent='৳'+(s+PD.del).toFixed(0);
  if(PD.advancePct>0){
    var adv=Math.ceil(s*PD.advancePct/100);
    var rem=s-adv+PD.del;
    var at=document.getElementById('advanceTxt');if(at)at.textContent='৳'+adv.toFixed(0);
    var rt=document.getElementById('remainingTxt');if(rt)rt.textContent='৳'+rem.toFixed(0);
  }
  var isD=PD.payOpt==='delivery_only';
  var isA=PD.payOpt==='advance_only';
  var amt;
  if(isD){amt=PD.del;}
  else if(isA){amt=Math.ceil(s*PD.advancePct/100);}
  else if(PD.advancePct>0){amt=Math.ceil(s*PD.advancePct/100);}
  else{amt=s+PD.del;}
  var as='৳'+amt.toFixed(0);
  var ba=document.getElementById('bkashAmt');if(ba)ba.textContent=as;
  var na=document.getElementById('nagadAmt');if(na)na.textContent=as;
}

/* Steps */
function goStep2(){
  var keys=Object.keys(sel);if(!keys.length)return;
  for(var id in sel){
    for(var j=0;j<PD.opts.length;j++){
      var o=PD.opts[j];
      if(o.is_required&&!sel[id].opts[o.name]){alert('"'+o.name+'" select করুন (আবশ্যক)');return;}
    }
  }
  document.getElementById('s1').style.display='none';
  if(PD.hasCD){buildCD();document.getElementById('s2cd').style.display='block';}
  else{buildSummary();document.getElementById('s3').style.display='block';}
}
function backTo1(){document.getElementById('s3').style.display='none';if(PD.hasCD)document.getElementById('s2cd').style.display='none';document.getElementById('s1').style.display='block';}
function backTo2cd(){document.getElementById('s3').style.display='none';document.getElementById('s2cd').style.display='block';}
function goStep3(){document.getElementById('s2cd').style.display='none';buildSummary();document.getElementById('s3').style.display='block';}

/* Custom Design */
function buildCD(){
  var c=document.getElementById('cdContainer');c.innerHTML='';
  Object.values(sel).forEach(function(it){
    if(!cdData[it.id])cdData[it.id]={text:'',slots:{},slotPreviews:{}};
    var extra=it.extraPrice||0;var dp=it.price+extra;
    var d=document.createElement('div');d.style.cssText='background:var(--k3);border:1px solid var(--bdr2);border-radius:var(--r);padding:12px;margin-bottom:12px';
    var html='<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px"><img src="'+it.path+'" style="width:48px;height:48px;object-fit:cover;border-radius:7px;flex-shrink:0"><div><p style="font-weight:700;font-size:.86rem">'+PD.name+'</p><p style="font-size:.74rem;color:var(--g)">৳'+dp.toFixed(0)+' × '+it.qty+'</p></div></div>';
    html+='<div class="fd" style="margin-bottom:12px"><label style="font-size:.78rem;font-weight:600;display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75z"/></svg>Custom Text (optional)</label><input type="text" class="inp" placeholder="কাস্টম লেখা..." id="cdt_'+it.id+'" value="'+(cdData[it.id].text||'')+'" oninput="cdData[\''+it.id+'\'].text=this.value"></div>';
    PD.cdSlots.forEach(function(slot,si){
      var slotKey='slot_'+si;
      var preview=cdData[it.id].slotPreviews[slotKey]||'';
      var reqTag=slot.required?'<span style="color:#F44336;font-size:.72rem"> *আবশ্যক</span>':'<span style="font-size:.72rem;color:var(--gray)"> (optional)</span>';
      html+='<div class="fd" style="margin-bottom:10px"><label style="font-size:.78rem;font-weight:600;display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9.4 10.5l4.77-8.26C13.47 2.09 12.75 2 12 2c-2.4 0-4.6.85-6.32 2.25l3.66 6.35.06-.1zM21.54 9c-.92-2.92-3.15-5.26-6-6.34L11.88 9h9.66zm.26 1h-7.49l.29.5 4.76 8.25C21.07 16.17 22 14.21 22 12c0-.69-.07-1.36-.2-2zM8.54 12l-3.9-6.75C3.01 7.03 2 9.39 2 12c0 .69.07 1.36.2 2h7.49l-1.15-2zm-6.08 3c.92 2.92 3.15 5.26 6 6.34L12.12 15H2.46zm11.27 0-3.9 6.76c.7.15 1.42.24 2.17.24 2.4 0 4.6-.85 6.32-2.25l-2.44-4.75H13.73z"/></svg>'+slot.title+reqTag+'</label>';
      html+='<div id="cdprev_'+it.id+'_'+si+'" style="display:'+(preview?'block':'none')+';margin-bottom:6px"><img id="cdprevimg_'+it.id+'_'+si+'" src="'+(preview||'')+'" style="max-width:100%;max-height:100px;border-radius:7px"><button type="button" onclick="clrSlot(\''+it.id+'\','+si+')" style="display:flex;align-items:center;gap:4px;margin-top:4px;background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);border-radius:6px;padding:3px 10px;font-size:.74rem;cursor:pointer"><svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>Remove</button></div>';
      html+='<div class="aup" style="padding:14px;display:flex;align-items:center;justify-content:center;gap:6px" onclick="document.getElementById(\'cdfile_'+it.id+'_'+si+'\').click()"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9.4 10.5l4.77-8.26C13.47 2.09 12.75 2 12 2c-2.4 0-4.6.85-6.32 2.25l3.66 6.35.06-.1zM21.54 9c-.92-2.92-3.15-5.26-6-6.34L11.88 9h9.66zm.26 1h-7.49l.29.5 4.76 8.25C21.07 16.17 22 14.21 22 12c0-.69-.07-1.36-.2-2zM8.54 12l-3.9-6.75C3.01 7.03 2 9.39 2 12c0 .69.07 1.36.2 2h7.49l-1.15-2zm-6.08 3c.92 2.92 3.15 5.26 6 6.34L12.12 15H2.46zm11.27 0-3.9 6.76c.7.15 1.42.24 2.17.24 2.4 0 4.6-.85 6.32-2.25l-2.44-4.75H13.73z"/></svg><p style="font-size:.8rem">'+slot.title+' upload করুন</p></div>';
      html+='<input type="file" id="cdfile_'+it.id+'_'+si+'" accept="image/*" style="display:none" onchange="upSlot(\''+it.id+'\','+si+',this)"></div>';
    });
    d.innerHTML=html;c.appendChild(d);
  });
}

async function upSlot(itemId,slotIdx,input){
  if(!input.files||!input.files[0])return;
  var file=input.files[0];var slotKey='slot_'+slotIdx;
  var reader=new FileReader();
  reader.onload=function(e){
    if(!cdData[itemId])cdData[itemId]={text:'',slots:{},slotPreviews:{}};
    cdData[itemId].slotPreviews[slotKey]=e.target.result;
    var prev=document.getElementById('cdprev_'+itemId+'_'+slotIdx);
    var img=document.getElementById('cdprevimg_'+itemId+'_'+slotIdx);
    if(prev)prev.style.display='block';if(img)img.src=e.target.result;
  };
  reader.readAsDataURL(file);
  var fd=new FormData();fd.append('image',file);
  try{var r=await fetch('/api/upload',{method:'POST',body:fd});var d=await r.json();if(d.ok){if(!cdData[itemId])cdData[itemId]={text:'',slots:{},slotPreviews:{}};cdData[itemId].slots[slotKey]=d.url;}else{alert('Upload failed: '+(d.reason||d.error||'unknown'));showToast('tErr');}}
catch(e){alert('Network error during upload.');showToast('tErr');}
  input.value='';
}

function clrSlot(itemId,slotIdx){
  var sk='slot_'+slotIdx;
  if(!cdData[itemId])return;
  delete cdData[itemId].slots[sk];delete cdData[itemId].slotPreviews[sk];
  var prev=document.getElementById('cdprev_'+itemId+'_'+slotIdx);if(prev)prev.style.display='none';
}

/* Order Summary */
function buildSummary(){
  var s=0;Object.values(sel).forEach(function(i){s+=(i.price+(i.extraPrice||0))*i.qty;});
  var html='<div class="s2i">';
  Object.values(sel).forEach(function(it){
    var cd=cdData[it.id]||{};var extra=it.extraPrice||0;var dp=it.price+extra;
    html+='<div class="s2it"><img src="'+it.path+'" alt=""><div class="s2ii"><span>Qty: '+it.qty+'</span>';
    Object.entries(it.opts).forEach(function(e2){
      html+='<span>'+e2[0]+': '+e2[1]+'</span>';
      var subSel = (it.subopts[e2[0]] && it.subopts[e2[0]][e2[1]]) || {};
      Object.entries(subSel).forEach(function(se){ if(se[1]) html+='<span style="padding-left:8px;color:var(--gray)">'+se[0]+': '+se[1]+'</span>'; });
    });
    if(extra>0)html+='<span style="color:var(--g);font-size:.74rem">Extra: +৳'+extra+'</span>';
    html+='<span class="s2p">৳'+(dp*it.qty).toFixed(0)+'</span>';
    if(cd.text)html+='<span style="font-size:.72rem;color:var(--gray)">'+cd.text+'</span>';
    if(cd.slots){Object.entries(cd.slots).forEach(function(se){var si=parseInt(se[0].split('_')[1]);var slot=PD.cdSlots[si];html+='<div style="display:flex;align-items:center;gap:5px;margin-top:3px"><span style="font-size:.7rem;color:var(--gray)">'+(slot?slot.title:'Image')+':</span><img src="'+se[1]+'" style="width:32px;height:32px;object-fit:cover;border-radius:4px"></div>';});}
    html+='</div></div>';
  });
  var delTxt=PD.isFreeD?'Free':'৳'+PD.del.toFixed(0);
  if(PD.advancePct>0){
    var adv=Math.ceil(s*PD.advancePct/100);
    html+='</div><div class="s2t"><span>Product: ৳'+s.toFixed(0)+'</span><span>Delivery: '+delTxt+'</span>';
    html+='<span style="color:var(--g);font-weight:700">Advance ('+PD.advancePct+'%): ৳'+adv.toFixed(0)+'</span>';
    html+='<span style="color:var(--gray)">Remaining after delivery: ৳'+(s-adv+PD.del).toFixed(0)+'</span>';
    html+='<strong>Total: ৳'+(s+PD.del).toFixed(0)+'</strong></div>';
  } else {
    html+='</div><div class="s2t"><span>Product: ৳'+s.toFixed(0)+'</span><span>Delivery: '+delTxt+'</span><strong>Total: ৳'+(s+PD.del).toFixed(0)+'</strong></div>';
  }
  document.getElementById('orderSummary').innerHTML='<div class="s2s">'+html+'</div>';
  calcTotal();
}

/* Payment selection */
function selPM(method,btn){
  document.querySelectorAll('.pmb').forEach(function(b){b.classList.remove('sel');});btn.classList.add('sel');
  ['bkashBox','nagadBox','codBox'].forEach(function(id){var el=document.getElementById(id);if(el)el.style.display='none';});
  var s=0;Object.values(sel).forEach(function(i){s+=(i.price+(i.extraPrice||0))*i.qty;});
  var isD=PD.payOpt==='delivery_only';
  var isA=PD.payOpt==='advance_only';
  var amt;
  if(isD){amt=PD.del;}
  else if(isA){amt=Math.ceil(s*PD.advancePct/100);}
  else if(PD.advancePct>0){amt=Math.ceil(s*PD.advancePct/100);}
  else{amt=s+PD.del;}
  var as='৳'+amt.toFixed(0);
  if(method==='bkash'){var box=document.getElementById('bkashBox');if(box){box.style.display='block';var t=document.getElementById('bkashAmtTxt');if(t)t.textContent=as;var inp=document.getElementById('bkashL4');if(inp)inp.value='';}}
  else if(method==='nagad'){var box2=document.getElementById('nagadBox');if(box2){box2.style.display='block';var t2=document.getElementById('nagadAmtTxt');if(t2)t2.textContent=as;var inp2=document.getElementById('nagadL4');if(inp2)inp2.value='';}}
  else if(method==='cod'){var box3=document.getElementById('codBox');if(box3)box3.style.display='block';}
}

/* Place Order */
async function placeOrder(method,last4Id,btn){
  var n=document.getElementById('oN').value.trim();var m=document.getElementById('oM').value.trim();
  var di=document.getElementById('oDi').value.trim();var up=document.getElementById('oUp').value.trim();
  var un=document.getElementById('oUn').value.trim();var vi=document.getElementById('oVi').value.trim();
  if(!n||!m||!di||!up||!un||!vi){alert('Please fill all required fields.');return;}

  if(PD.hasCD){
    for(var id in sel){
      for(var si=0;si<PD.cdSlots.length;si++){
        if(PD.cdSlots[si].required){
          var sk='slot_'+si;
          if(!cdData[id]||!cdData[id].slots||!cdData[id].slots[sk]){
            alert('"'+PD.cdSlots[si].title+'" ছবি দেওয়া আবশ্যক।');return;
          }
        }
      }
    }
  }

  var last4='';
  if(last4Id){last4=(document.getElementById(last4Id)||{}).value||'';last4=last4.trim();if(last4.length!==4||!/^\d{4}$/.test(last4)){alert('Enter valid 4 digits.');return;}}

  var s=0;
  var items=Object.values(sel).map(function(it){
    s+=(it.price+(it.extraPrice||0))*it.qty;
    var cd=cdData[it.id]||{};
    // Flatten selected_options to include sub-option selections too
    var flatOpts = Object.assign({}, it.opts);
    Object.keys(it.opts).forEach(function(optName){
      var pv = it.opts[optName];
      var subSel = (it.subopts[optName] && it.subopts[optName][pv]) || {};
      Object.entries(subSel).forEach(function(se){ if(se[1]) flatOpts[optName+' - '+se[0]] = se[1]; });
    });
    return{
      product_id:PD.id,product_image_id:it.id,product_name:PD.name,image_path:it.path,
      quantity:it.qty,price:it.price+(it.extraPrice||0),extra_price:it.extraPrice||0,
      selected_options:flatOpts,
      custom_design_text:cd.text||'',
      custom_design_slots:cd.slots||{},
      custom_design_image1:'',custom_design_image2:''
    };
  });

  var advanceAmt = PD.advancePct > 0 ? Math.ceil(s * PD.advancePct / 100) : 0;
  var origTxt=btn.textContent;btn.disabled=true;btn.textContent='Placing order...';
  try{
    var r=await fetch('/api/place-order',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({items:items,name:n,mobile:m,district:di,upazila:up,union_name:un,village:vi,
        road_name:document.getElementById('oRo').value.trim(),
        holding_number:document.getElementById('oHo').value.trim(),
        delivery_charge:PD.del,total_amount:s+PD.del,
        advance_amount:advanceAmt,advance_percent:PD.advancePct,
        payment_method:method,sender_last4:last4,
        payment_status:method==='cod'?'cod':'pending_verification'})});
    var d=await r.json();
    if(d.ok){closePanel();showToast('tOk');setTimeout(function(){window.location.href='/orders';},2000);}
    else alert('Error: '+(d.error||''));
  }catch(e){alert('Network error.');}
  finally{btn.disabled=false;btn.textContent=origTxt;}
}

/* Quick Cart */
async function qc(){
  if(!PD.imgs.length){alert('No images');return;}
  var img=PD.imgs[carCur]||PD.imgs[0];
  var fd=new FormData();fd.append('product_id',PD.id);fd.append('product_image_id',img.id);fd.append('product_name',PD.name);fd.append('image_path',img.path);fd.append('quantity','1');fd.append('price',img.price.toString());
  try{var r=await fetch('/api/cart/add',{method:'POST',body:fd});var d=await r.json();if(d.error==='not_logged_in'){showToast('tNoAcc');return;}if(d.ok)showToast('tCart');else showToast('tErr');}catch(e){showToast('tErr');}
}

function showToast(id){var el=document.getElementById(id);if(!el)return;el.classList.add('show');setTimeout(function(){el.classList.remove('show');},2800);}
</script>

<?php render_nav('home'); render_foot(); ?>