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
?>

<div class="aph">
  <a href="/admin/orders" class="abl">тЖР Orders</a>
  <h1>ЁЯУЛ Order #<?= htmlspecialchars($order['serial_number'] ?? $order['id']) ?></h1>
  <?php if ($updated): ?><div style="background:rgba(76,175,80,.12);color:#4CAF50;border:1px solid rgba(76,175,80,.25);padding:7px 13px;border-radius:8px;font-size:.82rem;margin-top:8px">тЬЕ Updated!</div><?php endif; ?>
</div>

<!-- Status Update -->
<div class="afs" style="margin-bottom:14px">
  <h3>тЪб Order Status</h3>
  <form action="/admin/orders/<?= $id ?>" method="POST" style="display:flex;gap:9px;flex-wrap:wrap">
    <select name="status" class="ai" style="flex:1">
      <?php foreach(['pending'=>'тП│ Pending','accepted'=>'тЬЕ Accepted','processing'=>'ЁЯФз Processing','delivering'=>'ЁЯЪЪ Delivering','delivered'=>'ЁЯУж Delivered','cancelled'=>'тЭМ Cancelled'] as $v=>$l): ?>
      <option value="<?= $v ?>" <?= $order['status']===$v?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="abg" style="flex-shrink:0">тЬЕ Update</button>
  </form>
</div>

<!-- Order Info -->
<div class="afs">
  <h3>ЁЯУМ Order Info</h3>
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
  <h3>ЁЯТ│ Payment</h3>
  <div style="display:flex;flex-direction:column;gap:6px">
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Method:</span><span><?= htmlspecialchars($order['payment_method']) ?></span></div>
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Status:</span><span><?= htmlspecialchars($order['payment_status']) ?></span></div>
    <?php if ($order['sender_last4']): ?>
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Sender Last 4:</span><span style="letter-spacing:3px;font-weight:700;color:var(--g)">****<?= htmlspecialchars($order['sender_last4']) ?></span></div>
    <?php endif; ?>
    <?php if ($order['advance_percent'] ?? 0): ?>
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Advance (<?= $order['advance_percent'] ?>%):</span><span style="color:var(--g);font-weight:700">рз│<?= number_format($order['advance_amount'] ?? 0, 0) ?></span></div>
    <?php endif; ?>
    <div style="display:flex;justify-content:space-between;font-size:.84rem"><span style="color:var(--agray)">Delivery:</span><span>рз│<?= number_format($order['delivery_charge'], 0) ?></span></div>
    <div style="display:flex;justify-content:space-between;font-size:.9rem;font-weight:700;border-top:1px solid var(--abdr);padding-top:6px;margin-top:2px"><span>Total:</span><span style="color:var(--g)">рз│<?= number_format($order['total_amount'], 0) ?></span></div>
  </div>
</div>

<!-- Order Items -->
<div class="afs">
  <h3>ЁЯУж Order Items</h3>
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
            <span style="font-size:.8rem;font-weight:700;color:var(--g)">рз│<?= number_format($item['price'], 0) ?>/pc</span>
            <?php if (($item['extra_price'] ?? 0) > 0): ?>
            <span style="font-size:.76rem;color:var(--g);background:var(--gl);border-radius:50px;padding:1px 8px">+рз│<?= number_format($item['extra_price'], 0) ?> extra</span>
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
          <p style="font-size:.94rem;font-weight:700;color:var(--g)">рз│<?= number_format($item['price'] * $item['quantity'], 0) ?></p>
        </div>
      </div>

      <!-- Custom Design Section -->
      <?php if ($cdText || $cdSlots || $cdImg1 || $cdImg2): ?>
      <div style="border-top:1px solid var(--abdr2);padding:12px 13px;background:var(--ak2)">
        <p style="font-size:.78rem;font-weight:700;color:var(--g);margin-bottom:10px">ЁЯОи Custom Design</p>

        <?php if ($cdText): ?>
        <div style="background:var(--ak3);border:1px solid var(--abdr);border-radius:8px;padding:9px 12px;margin-bottom:10px">
          <p style="font-size:.72rem;color:var(--agray);margin-bottom:3px">тЬПя╕П Custom Text:</p>
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
              <p style="font-size:.72rem;color:var(--agray);font-weight:600">ЁЯУ╕ Slot <?= $si+1 ?></p>
            </div>
            <img src="<?= htmlspecialchars($imgUrl) ?>" style="width:100%;aspect-ratio:1;object-fit:cover;display:block">
            <div style="padding:8px 10px;display:flex;gap:6px;flex-wrap:wrap">
              <a href="<?= htmlspecialchars($imgUrl) ?>" download target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;background:var(--gl);border:1px solid var(--g);color:var(--g);padding:5px 10px;border-radius:6px;font-size:.72rem;font-weight:700;text-decoration:none">
                тмЗя╕П Download
              </a>
              <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;background:var(--ak2);border:1px solid var(--abdr);color:var(--w);padding:5px 10px;border-radius:6px;font-size:.72rem;text-decoration:none">
                ЁЯФН View
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
              <p style="font-size:.72rem;color:var(--agray);font-weight:600">ЁЯУ╕ <?= $idx===0?'рж╕рж╛ржоржирзЗрж░ ржЫржмрж┐':'ржкрж┐ржЫржирзЗрж░ ржЫржмрж┐' ?></p>
            </div>
            <img src="<?= htmlspecialchars($imgUrl) ?>" style="width:100%;aspect-ratio:1;object-fit:cover;display:block">
            <div style="padding:8px 10px;display:flex;gap:6px">
              <a href="<?= htmlspecialchars($imgUrl) ?>" download target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;background:var(--gl);border:1px solid var(--g);color:var(--g);padding:5px 10px;border-radius:6px;font-size:.72rem;font-weight:700;text-decoration:none">тмЗя╕П Download</a>
              <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank"
                 style="display:inline-flex;align-items:center;gap:5px;background:var(--ak2);border:1px solid var(--abdr);color:var(--w);padding:5px 10px;border-radius:6px;font-size:.72rem;text-decoration:none">ЁЯФН View</a>
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