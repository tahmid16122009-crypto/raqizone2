<?php
require_once __DIR__ . '/base.php';

$total_products = DB::row("SELECT COUNT(*) AS c FROM products WHERE is_active=1")['c'] ?? 0;
$total_orders   = DB::row("SELECT COUNT(*) AS c FROM orders")['c'] ?? 0;
$pending_orders = DB::row("SELECT COUNT(*) AS c FROM orders WHERE status='pending'")['c'] ?? 0;
$total_revenue  = DB::row("SELECT COALESCE(SUM(total_amount),0) AS s FROM orders WHERE status NOT IN ('cancelled')")['s'] ?? 0;

try {
    $recent_orders = DB::rows("SELECT id,serial_number,name,status,total_amount,created_at FROM orders ORDER BY created_at DESC LIMIT 8");
} catch (Throwable $e) { $recent_orders = []; }

admin_head('Dashboard');
admin_nav('dashboard');
?>

<div class="aph"><h1>🏠 Dashboard</h1></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:16px;text-align:center">
    <div style="font-size:1.6rem;margin-bottom:6px">📦</div>
    <p style="font-size:1.5rem;font-weight:700;color:var(--g)"><?= $total_products ?></p>
    <p style="font-size:.78rem;color:var(--agray)">Products</p>
  </div>
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:16px;text-align:center">
    <div style="font-size:1.6rem;margin-bottom:6px">📋</div>
    <p style="font-size:1.5rem;font-weight:700;color:var(--g)"><?= $total_orders ?></p>
    <p style="font-size:.78rem;color:var(--agray)">Total Orders</p>
  </div>
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:16px;text-align:center">
    <div style="font-size:1.6rem;margin-bottom:6px">⏳</div>
    <p style="font-size:1.5rem;font-weight:700;color:#FFC107"><?= $pending_orders ?></p>
    <p style="font-size:.78rem;color:var(--agray)">Pending</p>
  </div>
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:16px;text-align:center">
    <div style="font-size:1.6rem;margin-bottom:6px">💰</div>
    <p style="font-size:1.2rem;font-weight:700;color:var(--g)">৳<?= number_format($total_revenue, 0) ?></p>
    <p style="font-size:.78rem;color:var(--agray)">Revenue</p>
  </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
  <a href="/admin/products/add" class="abg" style="text-decoration:none;flex:1;text-align:center;min-width:120px">+ Add Product</a>
  <a href="/admin/orders" class="abs" style="text-decoration:none;flex:1;text-align:center;min-width:120px">📋 Orders</a>
  <a href="/admin/posts" class="abs" style="text-decoration:none;flex:1;text-align:center;min-width:120px">📢 Updates</a>
</div>

<?php if ($recent_orders): ?>
<h3 style="font-size:.88rem;color:var(--agray);margin-bottom:10px;font-weight:600;text-transform:uppercase">Recent Orders</h3>
<div style="display:flex;flex-direction:column;gap:8px">
  <?php foreach ($recent_orders as $o): ?>
  <a href="/admin/orders/<?= $o['id'] ?>" style="display:flex;align-items:center;justify-content:space-between;background:var(--ak2);border:1px solid var(--abdr);border-radius:10px;padding:10px 13px;text-decoration:none;color:inherit">
    <div>
      <p style="font-weight:700;font-size:.84rem"><?= htmlspecialchars($o['name']) ?></p>
      <?php if ($o['serial_number']): ?><p style="font-size:.7rem;color:var(--g)"><?= htmlspecialchars($o['serial_number']) ?></p><?php endif; ?>
      <p style="font-size:.72rem;color:var(--agray)"><?= date('d M, h:i A', strtotime($o['created_at'])) ?></p>
    </div>
    <div style="text-align:right">
      <p style="font-weight:700;color:var(--g);font-size:.88rem">৳<?= number_format($o['total_amount'], 0) ?></p>
      <span style="font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:50px;background:<?= $o['status']==='pending'?'rgba(255,193,7,.15)':($o['status']==='delivered'?'rgba(76,175,80,.15)':'rgba(33,150,243,.15)') ?>;color:<?= $o['status']==='pending'?'#FFC107':($o['status']==='delivered'?'#4CAF50':'#2196F3') ?>"><?= $o['status'] ?></span>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php admin_foot(); ?>