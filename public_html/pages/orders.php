<?php
require_once __DIR__ . '/../templates/layout.php';

$u = rz_get_user();

render_head('আমার অর্ডার — ' . ($cfg['site_name'] ?? 'Raqizone'), $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st" data-bn="আমার অর্ডার" data-en="My Orders">আমার অর্ডার</span>
  </div>

  <?php if (!$u): ?>
  <div class="nacc">
    <div class="ni">📦</div>
    <h2>লগিন করুন</h2>
    <p>অর্ডার দেখতে লগিন করুন</p>
    <div class="nacc-b"><a href="/" class="bg">লগিন করুন</a></div>
  </div>
  <?php else: ?>

  <?php
  $orders = DB::rows(
      "SELECT id, serial_number, name, status, total_amount, delivery_charge,
       payment_method, payment_status, created_at
       FROM orders WHERE user_id = ? ORDER BY created_at DESC",
      [$u['user_id']]
  );
  ?>

  <div class="ol">
    <?php if ($orders): ?>
    <?php foreach ($orders as $o): ?>
    <a href="/orders/<?= $o['id'] ?>" class="oc">
      <div class="ot">
        <div class="om">
          <span class="on"><?= htmlspecialchars($o['name']) ?></span>
          <?php if ($o['serial_number']): ?>
          <span style="font-size:.7rem;color:var(--g);font-weight:700">🔖 <?= htmlspecialchars($o['serial_number']) ?></span>
          <?php endif; ?>
          <span class="od2"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></span>
        </div>
        <span class="sb s-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span>
      </div>
      <div class="ob">
        <span class="ot2">৳<?= number_format($o['total_amount'], 0) ?></span>
        <span class="oa">›</span>
      </div>
    </a>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="emp">
      <div class="ei">📦</div>
      <h3 data-bn="কোনো অর্ডার নেই" data-en="No orders yet">কোনো অর্ডার নেই</h3>
      <p>এখনো কোনো অর্ডার করা হয়নি</p>
      <a href="/home" style="display:inline-flex;padding:10px 20px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border-radius:50px;font-weight:700;text-decoration:none;font-size:.84rem;margin-top:8px">পণ্য দেখুন</a>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>

<?php render_nav('orders'); render_foot(); ?>