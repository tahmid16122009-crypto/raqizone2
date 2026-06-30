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

$ic_clip   = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
$ic_search = '<svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>';
$ic_tag    = '<svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"/></svg>';
$ic_bank   = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M11.5 1 2 6v2h19V6M16 10v7h3v-7M2 22h19v-3H2M6 10v7h3v-7m4.5 0v7h3v-7"/></svg>';
$ic_mobile = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg>';
$ic_cash   = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>';
$ic_box    = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
?>

<div class="aph"><h1 style="display:flex;align-items:center;gap:7px"><?= $ic_clip ?>অর্ডার ব্যবস্থাপনা</h1></div>

<form action="/admin/orders" method="GET" style="margin-bottom:14px">
  <div style="display:flex;gap:8px">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="অর্ডার নম্বর (ORD-...) দিয়ে খুঁজুন" class="ai" style="flex:1">
    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
    <button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center"><?= $ic_search ?></button>
    <?php if ($q): ?><a href="/admin/orders?status=<?= htmlspecialchars($status) ?>" class="abs">✕</a><?php endif; ?>
  </div>
</form>

<div class="sf">
  <?php foreach (['all'=>'সব','pending'=>'Pending','accepted'=>'Accepted','processing'=>'Processing','delivering'=>'Delivering','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $s=>$l): ?>
  <a href="/admin/orders?status=<?= $s ?><?= $q?'&q='.urlencode($q):'' ?>" class="sfb<?= $status===$s?' on':'' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<?php if ($orders): ?>
<div class="aol">
  <?php foreach ($orders as $o): ?>
  <a href="/admin/orders/<?= $o['id'] ?>" class="aoc">
    <div class="aol2">
      <span class="aon"><?= htmlspecialchars($o['name']) ?> — <?= htmlspecialchars($o['mobile']) ?></span>
      <?php if ($o['serial_number']): ?><span style="font-size:.72rem;color:var(--g);font-weight:700;display:inline-flex;align-items:center;gap:3px"><?= $ic_tag ?><?= htmlspecialchars($o['serial_number']) ?></span><?php endif; ?>
      <span class="aom"><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></span>
      <span class="aod" style="display:inline-flex;align-items:center;gap:4px">
        <?php if ($o['payment_method']==='bkash'): ?><?= $ic_bank ?>বিকাশ
        <?php elseif ($o['payment_method']==='nagad'): ?><?= $ic_mobile ?>নগদ
        <?php else: ?><?= $ic_cash ?>COD
        <?php endif; ?>
        <?php if ($o['sender_last4']): ?> | শেষ ৪: ****<?= htmlspecialchars($o['sender_last4']) ?><?php endif; ?>
      </span>
    </div>
    <div class="aor">
      <span class="aot">৳<?= number_format($o['total_amount'], 0) ?></span>
      <span class="sb s-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="aemp"><p style="margin-bottom:9px;display:flex;justify-content:center"><?= str_replace('width:14px;height:14px','width:32px;height:32px',$ic_box) ?></p><p>কোনো অর্ডার নেই</p></div>
<?php endif; ?>

<?php admin_foot(); ?>