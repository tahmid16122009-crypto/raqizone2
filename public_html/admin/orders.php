<?php
require_once __DIR__ . '/base.php';

$status = trim($_GET['status'] ?? 'all');
$q      = trim($_GET['q']      ?? '');

$sql    = "SELECT * FROM orders";
$where  = [];
$params = [];
if ($status !== 'all') {
    $where[]  = "status = ?";
    $params[] = $status;
}
$sql .= $where ? ' WHERE ' . implode(' AND ', $where) : '';
$sql .= " ORDER BY created_at DESC";
$orders = DB::rows($sql, $params);

if ($q) {
    $qu     = strtoupper(trim($q));
    $orders = array_filter($orders, fn($o) => str_contains(strtoupper($o['serial_number'] ?? ''), $qu));
}

admin_head('অর্ডার ব্যবস্থাপনা');
admin_nav('orders');
?>

<div class="aph"><h1>📋 অর্ডার ব্যবস্থাপনা</h1></div>

<form action="/admin/orders" method="GET" style="margin-bottom:14px">
  <div style="display:flex;gap:8px">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="অর্ডার নম্বর (ORD-...) দিয়ে খুঁজুন" class="ai" style="flex:1">
    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
    <button type="submit" class="abg">🔍</button>
    <?php if ($q): ?><a href="/admin/orders?status=<?= htmlspecialchars($status) ?>" class="abs">✕</a><?php endif; ?>
  </div>
</form>

<div class="sf">
  <?php foreach (['all'=>'সব','pending'=>'⏳ Pending','accepted'=>'✅ Accepted','processing'=>'⚙️ Processing','delivering'=>'🚚 Delivering','delivered'=>'📦 Delivered','cancelled'=>'❌ Cancelled'] as $s=>$l): ?>
  <a href="/admin/orders?status=<?= $s ?><?= $q?'&q='.urlencode($q):'' ?>" class="sfb<?= $status===$s?' on':'' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<?php if ($orders): ?>
<div class="aol">
  <?php foreach ($orders as $o): ?>
  <a href="/admin/orders/<?= $o['id'] ?>" class="aoc">
    <div class="aol2">
      <span class="aon"><?= htmlspecialchars($o['name']) ?> — <?= htmlspecialchars($o['mobile']) ?></span>
      <?php if ($o['serial_number']): ?><span style="font-size:.72rem;color:var(--g);font-weight:700">🔖 <?= htmlspecialchars($o['serial_number']) ?></span><?php endif; ?>
      <span class="aom"><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></span>
      <span class="aod"><?= $o['payment_method']==='bkash'?'🏦 বিকাশ':($o['payment_method']==='nagad'?'📱 নগদ':'💵 COD') ?><?php if ($o['sender_last4']): ?> | শেষ ৪: ****<?= htmlspecialchars($o['sender_last4']) ?><?php endif; ?></span>
    </div>
    <div class="aor">
      <span class="aot">৳<?= number_format($o['total_amount'], 0) ?></span>
      <span class="sb s-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="aemp"><p style="font-size:2rem;margin-bottom:9px">📋</p><p>কোনো অর্ডার নেই</p></div>
<?php endif; ?>

<?php admin_foot(); ?>