<?php
require_once __DIR__ . '/../templates/layout.php';

$u = rz_get_user();
if (!$u) { header('Location: /'); exit; }

$id    = (int)($_GET['id'] ?? 0);
$order = DB::row("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$id, $u['user_id']]);
if (!$order) { header('Location: /orders'); exit; }

$items    = DB::rows("SELECT * FROM order_items WHERE order_id = ?", [$id]);
$created  = strtotime($order['created_at']);
$editable = (time() - $created) < 86400 && $order['status'] === 'pending';

render_head('অর্ডার বিস্তারিত', $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/orders" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st">অর্ডার বিস্তারিত</span>
  </div>

  <div class="odw">

    <!-- Status + Serial -->
    <div class="odst">
      <span class="sb s-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
      <?php if ($order['serial_number']): ?>
      <div style="display:flex;flex-direction:column;align-items:center;gap:6px;margin-top:6px;width:100%">
        <span style="font-size:.72rem;color:var(--gray)">অর্ডার নম্বর</span>
        <div style="display:flex;align-items:center;gap:8px;background:var(--k3);border:2px solid var(--g);border-radius:10px;padding:10px 16px;width:100%;justify-content:space-between">
          <span style="font-size:1rem;font-weight:700;color:var(--g);letter-spacing:1px" id="serialNum"><?= htmlspecialchars($order['serial_number']) ?></span>
          <button id="copyBtn" onclick="copySerial()" style="background:var(--gl);border:1px solid var(--g);color:var(--g);border-radius:6px;padding:5px 12px;font-size:.76rem;cursor:pointer;font-family:inherit;font-weight:700;display:flex;align-items:center;gap:4px"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>কপি</button>
        </div>
      </div>
      <?php endif; ?>
      <span class="odid"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
    </div>

    <!-- Delivery Info -->
    <div class="odsc">
      <p class="odst2" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:var(--g)"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>ডেলিভারি ঠিকানা</p>
      <div class="odig">
        <div class="odr"><span class="odl">নাম:</span><span class="odv"><?= htmlspecialchars($order['name']) ?></span></div>
        <div class="odr"><span class="odl">মোবাইল:</span><span class="odv"><?= htmlspecialchars($order['mobile']) ?></span></div>
        <div class="odr"><span class="odl">জেলা:</span><span class="odv"><?= htmlspecialchars($order['district']) ?></span></div>
        <div class="odr"><span class="odl">উপজেলা:</span><span class="odv"><?= htmlspecialchars($order['upazila']) ?></span></div>
        <div class="odr"><span class="odl">ইউনিয়ন:</span><span class="odv"><?= htmlspecialchars($order['union_name']) ?></span></div>
        <div class="odr"><span class="odl">গ্রাম:</span><span class="odv"><?= htmlspecialchars($order['village']) ?></span></div>
        <?php if ($order['road_name']): ?><div class="odr"><span class="odl">রাস্তা:</span><span class="odv"><?= htmlspecialchars($order['road_name']) ?></span></div><?php endif; ?>
        <?php if ($order['holding_number']): ?><div class="odr"><span class="odl">হোল্ডিং:</span><span class="odv"><?= htmlspecialchars($order['holding_number']) ?></span></div><?php endif; ?>
      </div>
    </div>

    <!-- Order Items -->
    <div class="odsc">
      <p class="odst2" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:var(--g)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>অর্ডার করা পণ্য</p>
      <div class="odit">
        <?php foreach ($items as $item):
          $opts = json_decode($item['selected_options'] ?? '{}', true) ?: [];
        ?>
        <div class="odim">
          <?php if ($item['image_path']): ?><img src="<?= htmlspecialchars($item['image_path']) ?>" alt=""><?php endif; ?>
          <div class="odii">
            <span class="odin"><?= htmlspecialchars($item['product_name']) ?></span>
            <?php if ($opts): ?>
            <div class="odop"><?php foreach ($opts as $k=>$v): ?><span class="odc"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></span><?php endforeach; ?></div>
            <?php endif; ?>
            <span class="odiq"><?= $item['quantity'] ?> × ৳<?= number_format($item['price'], 0) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="odp">
        <div class="odpr"><span>পণ্যের মূল্য:</span><span>৳<?= number_format($order['total_amount'] - $order['delivery_charge'], 0) ?></span></div>
        <div class="odpr"><span>ডেলিভারি:</span><span>৳<?= number_format($order['delivery_charge'], 0) ?></span></div>
        <div class="odpt"><span>মোট:</span><strong>৳<?= number_format($order['total_amount'], 0) ?></strong></div>
      </div>
    </div>

    <!-- Payment Info -->
    <div class="odsc">
      <p class="odst2" style="display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:var(--g)"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>Payment তথ্য</p>
      <div class="odig">
        <div class="odr">
          <span class="odl">পদ্ধতি:</span>
          <span class="odv" style="display:inline-flex;align-items:center;gap:5px">
            <?php if ($order['payment_method']==='bkash'): ?>
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#E2136E"><path d="M3 7h18v2H3zm2 4h14v2H5zm-2 4h18v2H3z"/></svg>বিকাশ
            <?php elseif ($order['payment_method']==='nagad'): ?>
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#F6921E"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg>নগদ
            <?php else: ?>
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#4CAF50"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>Cash on Delivery
            <?php endif; ?>
          </span>
        </div>
        <div class="odr">
          <span class="odl">Status:</span>
          <span class="odv" style="display:inline-flex;align-items:center;gap:5px">
            <?php if (in_array($order['payment_status'],['paid','cod'])): ?>
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#4CAF50"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>পরিশোধ হয়েছে
            <?php elseif ($order['payment_status']==='pending_verification'): ?>
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#FFC107"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 11 12.41V7h2v4.59l4 4z"/></svg>যাচাই বাকি
            <?php else: ?>
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#FFC107"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 11 12.41V7h2v4.59l4 4z"/></svg>অপেক্ষায়
            <?php endif; ?>
          </span>
        </div>
        <?php if ($order['sender_last4']): ?>
        <div class="odr"><span class="odl">শেষ ৪:</span><span class="odv" style="color:var(--g);font-weight:700;letter-spacing:3px">****<?= htmlspecialchars($order['sender_last4']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Edit / Cancel -->
    <?php if ($editable): ?>
    <div class="odsc">
      <p class="edit-24-note" style="display:flex;align-items:flex-start;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:#FFC107;flex-shrink:0;margin-top:1px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 11 12.41V7h2v4.59l4 4z"/></svg>২৪ ঘণ্টার মধ্যে পরিবর্তন বা বাতিল করতে পারবেন</p>
      <button class="bed" id="editBtn" onclick="document.getElementById('editForm').style.display='block';this.style.display='none'" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75z"/></svg>ঠিকানা পরিবর্তন</button>
      <button class="bcn" onclick="cancelOrder()" style="display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>অর্ডার বাতিল</button>
      <form id="editForm" action="/api/orders/<?= $id ?>/edit" method="POST" style="display:none;margin-top:12px" class="fs">
        <div class="fd"><label>নাম</label><input name="name" class="inp" value="<?= htmlspecialchars($order['name']) ?>"></div>
        <div class="fd"><label>মোবাইল</label><input name="mobile" class="inp" value="<?= htmlspecialchars($order['mobile']) ?>"></div>
        <div class="fd"><label>জেলা</label><input name="district" class="inp" value="<?= htmlspecialchars($order['district']) ?>"></div>
        <div class="fd"><label>উপজেলা</label><input name="upazila" class="inp" value="<?= htmlspecialchars($order['upazila']) ?>"></div>
        <div class="fd"><label>ইউনিয়ন</label><input name="union_name" class="inp" value="<?= htmlspecialchars($order['union_name']) ?>"></div>
        <div class="fd"><label>গ্রাম</label><input name="village" class="inp" value="<?= htmlspecialchars($order['village']) ?>"></div>
        <div class="fd"><label>রাস্তা</label><input name="road_name" class="inp" value="<?= htmlspecialchars($order['road_name'] ?? '') ?>"></div>
        <div class="fd"><label>হোল্ডিং</label><input name="holding_number" class="inp" value="<?= htmlspecialchars($order['holding_number'] ?? '') ?>"></div>
        <button type="submit" class="bg" style="margin-top:5px;display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>সংরক্ষণ</button>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
function copySerial() {
  var text = document.getElementById('serialNum').textContent.trim();
  var btn = document.getElementById('copyBtn');
  var orig = btn.innerHTML;
  var doneHtml = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>কপি!';
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(function() { btn.innerHTML = doneHtml; setTimeout(function(){btn.innerHTML=orig;}, 2000); });
  } else {
    var inp = document.createElement('input'); inp.value = text;
    inp.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
    document.body.appendChild(inp); inp.focus(); inp.select();
    try { document.execCommand('copy'); btn.innerHTML = doneHtml; } catch(e) {}
    document.body.removeChild(inp);
    setTimeout(function(){btn.innerHTML=orig;}, 2000);
  }
}
async function cancelOrder() {
  if (!confirm('অর্ডারটি বাতিল করতে চান?')) return;
  var r = await fetch('/api/orders/<?= $id ?>/cancel', {method:'POST'});
  var d = await r.json();
  if (d.ok) window.location.reload();
  else alert('সমস্যা: ' + (d.error || ''));
}
</script>

<?php render_nav('orders'); render_foot(); ?>