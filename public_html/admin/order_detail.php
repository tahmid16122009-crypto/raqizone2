<?php
require_once __DIR__ . '/base.php';

$id    = (int)($_GET['id'] ?? 0);
$order = DB::row("SELECT * FROM orders WHERE id = ?", [$id]);
if (!$order) { header('Location: /admin/orders'); exit; }

$items = DB::rows("SELECT * FROM order_items WHERE order_id = ?", [$id]);

// Status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    DB::run("UPDATE orders SET status=? WHERE id=?", [trim($_POST['status']), $id]);
    header('Location: /admin/orders/' . $id . '?updated=1'); exit;
}

$updated = isset($_GET['updated']);

admin_head('Order Detail');
admin_nav('orders');

// SVG icons used in this page
$ic_clip   = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
$ic_check  = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
$ic_bolt   = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>';
$ic_pin    = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
$ic_card   = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>';
$ic_box    = '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
$ic_pal    = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
$ic_pencil = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75z"/></svg>';
$ic_camera = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M9.4 10.5l4.77-8.26C13.47 2.09 12.75 2 12 2c-2.4 0-4.6.85-6.32 2.25l3.66 6.35.06-.1zM21.54 9c-.92-2.92-3.15-5.26-6-6.34L11.88 9h9.66zm.26 1h-7.49l.29.5 4.76 8.25C21.07 16.17 22 14.21 22 12c0-.69-.07-1.36-.2-2zM8.54 12l-3.9-6.75C3.01 7.03 2 9.39 2 12c0 .69.07 1.36.2 2h7.49l-1.15-2zm-6.08 3c.92 2.92 3.15 5.26 6 6.34L12.12 15H2.46zm11.27 0-3.9 6.76c.7.15 1.42.24 2.17.24 2.4 0 4.6-.85 6.32-2.25l-2.44-4.75H13.73z"/></svg>';
$ic_dl     = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>';
$ic_view   = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
?>

<div class="aph">
  <a href="/admin/orders" class="abl">← Orders</a>
  <h1 style="display:flex;align-items:center;gap:7px"><?= $ic_clip ?>Order #<?= htmlspecialchars($order['serial_number'] ?? $order['id']) ?></h1>
  <?php if ($updated): ?><div style="background:rgba(76,175,80,.12);color:#4CAF50;border:1px solid rgba(76,175,80,.25);padding:7px 13px;border-radius:8px;font-size:.82rem;margin-top:8px;display:flex;align-items:center;gap:6px"><?= $ic_check ?>Updated!</div><?php endif; ?>
</div>

<!-- Status Update -->
<div class="afs" style="margin-bottom:14px">
  <h3 style="display:flex;align-items:center;gap:6px"><?= $ic_bolt ?>Order Status</h3>
  <form action="/admin/orders/<?= $id ?>" method="POST" style="display:flex;gap:9px;flex-wrap:wrap">
    <select name="status" class="ai" style="flex:1">
      <?php foreach(['pending'=>'Pending','accepted'=>'Accepted','processing'=>'Processing','delivering'=>'Delivering','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $v=>$l): ?>
      <option value="<?= $v ?>" <?= $order['status']===$v?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="abg" style="flex-shrink:0;display:flex;align-items:center;gap:6px"><?= $ic_check ?>Update</button>
  </form>
</div>

<!-- Order Info -->
<div class="afs">
  <h3 style="display:flex;align-items:center;gap:6px"><?= $ic_pin ?>Order Info</h3>
  <div style="display:flex;flex-direction:column;gap:6px">
    <?php if ($order['serial_number']): ?>
    <div style="display:flex;gap:9px;font-size:.84rem">
      <span style="color:var(--agray);min-width:100px;flex-shrink:0">Serial:</span>
      <span style="font-weight:700;color:var(--g)"><?= htmlspecialchars($order['serial_number']) ?></span>
    </div>
    <?php endif; ?>
    <?php foreach([
      ['Name','name'],['Mobile','mobile'],['District','district'],
      ['Upazila','upazila'],['Union','union_name'],['Village','village'],
      ['Road','road_name'],['Holding','holding_number'],['Date','created_at']
    ] as [$label,$key]): ?>
    <?php if (!empty($order[$key])): ?>
    <div style="display:flex;gap:9px;font-size:.84rem">
      <span style="color:var(--agray);min-width:100px;flex-shrink:0"><?= $label ?>:</span>
      <span><?= htmlspecialchars($order[$key]) ?></span>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<!-- Payment Info -->
<div class="afs">
  <h3 style="display:flex;align-items:center;gap:6px"><?= $ic_card ?>Payment</h3>
  <div style="display:flex;flex-direction:column;gap:6px">
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Method:</span><span><?= htmlspecialchars($order['payment_method']) ?></span></div>
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Status:</span><span><?= htmlspecialchars($order['payment_status']) ?></span></div>
    <?php if ($order['sender_last4']): ?>
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Sender Last 4:</span><span style="letter-spacing:3px;font-weight:700;color:var(--g)">****<?= htmlspecialchars($order['sender_last4']) ?></span></div>
    <?php endif; ?>
    <?php if ($order['advance_percent'] ?? 0): ?>
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Advance (<?= $order['advance_percent'] ?>%):</span><span style="color:var(--g);font-weight:700">৳<?= number_format($order['advance_amount'] ?? 0, 0) ?></span></div>
    <?php endif; ?>
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Delivery:</span><span>৳<?= number_format($order['delivery_charge'], 0) ?></span></div>
    <div style="display:flex;justify-content:space-between;font-size:.9rem;font-weight:700;border-top:1px solid var(--abdr);padding-top:6px;margin-top:2px"><span>Total:</span><span style="color:var(--g)">৳<?= number_format($order['total_amount'], 0) ?></span></div>
  </div>
</div>

<!-- Order Items -->
<div class="afs">
  <h3 style="display:flex;align-items:center;gap:6px"><?= $ic_box ?>Order Items</h3>
  <div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($items as $item):
      $opts       = json_decode($item['selected_options']  ?? '{}', true) ?: [];
      $cdSlots    = json_decode($item['custom_design_slots'] ?? '{}', true) ?: [];
      $cdText     = $item['custom_design_text'] ?? '';
      // Legacy support
      $cdImg1     = $item['custom_design_image1'] ?? '';
      $cdImg2     = $item['custom_design_image2'] ?? '';
    ?>
    <div style="background:var(--ak3);border:1px solid var(--abdr2);border-radius:12px;overflow:hidden">
      <!-- Item header -->
      <div style="display:flex;gap:11px;padding:12px 13px;align-items:flex-start">
        <?php if ($item['image_path']): ?>
        <img src="<?= htmlspecialchars($item['image_path']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0">
        <?php endif; ?>
        <div style="flex:1;min-width:0">
          <p style="font-weight:700;font-size:.9rem;margin-bottom:4px"><?= htmlspecialchars($item['product_name']) ?></p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <span style="font-size:.8rem;color:var(--agray)">Qty: <?= $item['quantity'] ?></span>
            <span style="font-size:.8rem;font-weight:700;color:var(--g)">৳<?= number_format($item['price'], 0) ?>/pc</span>
            <?php if (($item['extra_price'] ?? 0) > 0): ?>
            <span style="font-size:.76rem;color:var(--g);background:var(--gl);border-radius:50px;padding:1px 8px">+৳<?= number_format($item['extra_price'], 0) ?> extra</span>
            <?php endif; ?>
          </div>
          <?php if ($opts): ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php foreach ($opts as $k => $v): ?>
            <span style="background:var(--ak2);border:1px solid var(--abdr);border-radius:50px;font-size:.72rem;padding:2px 9px;color:var(--g);font-weight:600"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <p style="font-size:.94rem;font-weight:700;color:var(--g)">৳<?= number_format($item['price'] * $item['quantity'], 0) ?></p>
        </div>
      </div>

      <!-- Custom Design Section -->
      <?php if ($cdText || $cdSlots || $cdImg1 || $cdImg2): ?>
      <div style="border-top:1px solid var(--abdr2);padding:12px 13px;background:var(--ak2)">
        <p style="font-size:.78rem;font-weight:700;color:var(--g);margin-bottom:10px;display:flex;align-items:center;gap:5px"><?= $ic_pal ?>Custom Design</p>

        <?php if ($cdText): ?>
        <div style="background:var(--ak3);border:1px solid var(--abdr);border-radius:8px;padding:9px 12px;margin-bottom:10px">
          <p style="font-size:.72rem;color:var(--agray);margin-bottom:3px;display:flex;align-items:center;gap:4px"><?= $ic_pencil ?>Custom Text:</p>
          <p style="font-size:.86rem;font-weight:600"><?= htmlspecialchars($cdText) ?></p>
        </div>
        <?php endif; ?>

        <!-- Dynamic slots -->
        <?php if ($cdSlots): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px">
          <?php foreach ($cdSlots as $slotKey => $imgUrl):
            $si    = (int)str_replace('slot_','',$slotKey);
          ?>
          <div style="background:var(--ak3);border:1px solid var(--abdr);border-radius:10px;overflow:hidden">
            <div style="padding:8px 10px;border-bottom:1px solid var(--abdr)">
              <p style="font-size:.72rem;color:var(--agray);font-weight:600;display:flex;align-items:center;gap:4px"><?= $ic_camera ?>Slot <?= $si+1 ?></p>
            </div>
            <img src="<?= htmlspecialchars($imgUrl) ?>" style="width:100%;aspect-ratio:1;object-fit:cover;display:block">
            <div style="padding:8px 10px;display:flex;gap:6px;flex-wrap:wrap">
              <a href="<?= htmlspecialchars($imgUrl) ?>" download target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;background:var(--gl);border:1px solid var(--g);color:var(--g);padding:5px 10px;border-radius:6px;font-size:.72rem;font-weight:700;text-decoration:none">
                <?= $ic_dl ?>Download
              </a>
              <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;background:var(--ak2);border:1px solid var(--abdr);color:var(--w);padding:5px 10px;border-radius:6px;font-size:.72rem;text-decoration:none">
                <?= $ic_view ?>View
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Legacy images (older orders) -->
        <?php if (!$cdSlots && ($cdImg1 || $cdImg2)): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px">
          <?php foreach (array_filter([$cdImg1, $cdImg2]) as $idx => $imgUrl): ?>
          <div style="background:var(--ak3);border:1px solid var(--abdr);border-radius:10px;overflow:hidden">
            <div style="padding:8px 10px;border-bottom:1px solid var(--abdr)">
              <p style="font-size:.72rem;color:var(--agray);font-weight:600;display:flex;align-items:center;gap:4px"><?= $ic_camera ?><?= $idx===0?'সামনের ছবি':'পিছনের ছবি' ?></p>
            </div>
            <img src="<?= htmlspecialchars($imgUrl) ?>" style="width:100%;aspect-ratio:1;object-fit:cover;display:block">
            <div style="padding:8px 10px;display:flex;gap:6px">
              <a href="<?= htmlspecialchars($imgUrl) ?>" download target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;background:var(--gl);border:1px solid var(--g);color:var(--g);padding:5px 10px;border-radius:6px;font-size:.72rem;font-weight:700;text-decoration:none"><?= $ic_dl ?>Download</a>
              <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;background:var(--ak2);border:1px solid var(--abdr);color:var(--w);padding:5px 10px;border-radius:6px;font-size:.72rem;text-decoration:none"><?= $ic_view ?>View</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php admin_foot(); ?>