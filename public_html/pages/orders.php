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
    <div class="ni"><svg viewBox="0 0 24 24" style="width:56px;height:56px;fill:var(--gray)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg></div>
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
          <span style="font-size:.7rem;color:var(--g);font-weight:700;display:flex;align-items:center;gap:3px"><svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"/></svg><?= htmlspecialchars($o['serial_number']) ?></span>
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
      <div class="ei"><svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--gray)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg></div>
      <h3 data-bn="কোনো অর্ডার নেই" data-en="No orders yet">কোনো অর্ডার নেই</h3>
      <p>এখনো কোনো অর্ডার করা হয়নি</p>
      <a href="/home" style="display:inline-flex;padding:10px 20px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border-radius:50px;font-weight:700;text-decoration:none;font-size:.84rem;margin-top:8px">পণ্য দেখুন</a>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>

<?php render_nav('orders'); render_foot(); ?>