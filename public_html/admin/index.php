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

<div class="aph"><h1 style="display:flex;align-items:center;gap:7px"><svg viewBox="0 0 24 24" style="width:19px;height:19px;fill:currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>Dashboard</h1></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:16px;text-align:center">
    <div style="margin-bottom:6px;display:flex;justify-content:center;color:var(--g)"><svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg></div>
    <p style="font-size:1.5rem;font-weight:700;color:var(--g)"><?= $total_products ?></p>
    <p style="font-size:.78rem;color:var(--agray)">Products</p>
  </div>
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:16px;text-align:center">
    <div style="margin-bottom:6px;display:flex;justify-content:center;color:var(--g)"><svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg></div>
    <p style="font-size:1.5rem;font-weight:700;color:var(--g)"><?= $total_orders ?></p>
    <p style="font-size:.78rem;color:var(--agray)">Total Orders</p>
  </div>
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:16px;text-align:center">
    <div style="margin-bottom:6px;display:flex;justify-content:center;color:#FFC107"><svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 11 12.41V7h2v4.59l4 4z"/></svg></div>
    <p style="font-size:1.5rem;font-weight:700;color:#FFC107"><?= $pending_orders ?></p>
    <p style="font-size:.78rem;color:var(--agray)">Pending</p>
  </div>
  <div style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:16px;text-align:center">
    <div style="margin-bottom:6px;display:flex;justify-content:center;color:var(--g)"><svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div>
    <p style="font-size:1.2rem;font-weight:700;color:var(--g)">৳<?= number_format($total_revenue, 0) ?></p>
    <p style="font-size:.78rem;color:var(--agray)">Revenue</p>
  </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
  <a href="/admin/products/add" class="abg" style="text-decoration:none;flex:1;text-align:center;min-width:120px">+ Add Product</a>
  <a href="/admin/orders" class="abs" style="text-decoration:none;flex:1;text-align:center;min-width:120px;display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>Orders</a>
  <a href="/admin/posts" class="abs" style="text-decoration:none;flex:1;text-align:center;min-width:120px;display:flex;align-items:center;justify-content:center;gap:6px"><svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>Updates</a>
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