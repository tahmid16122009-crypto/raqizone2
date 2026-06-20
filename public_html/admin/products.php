<?php
require_once __DIR__ . '/base.php';

$q = trim($_GET['q'] ?? '');

$sql    = "SELECT p.*, 
           GROUP_CONCAT(pi.image_path ORDER BY pi.sort_order SEPARATOR '||') AS img_paths,
           COUNT(pi.id) AS img_count
           FROM products p
           LEFT JOIN product_images pi ON pi.product_id = p.id";
$params = [];
if ($q) {
    $sql    .= " WHERE p.name LIKE ?";
    $params[] = "%$q%";
}
$sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
$products = DB::rows($sql, $params);

foreach ($products as &$p) {
    $p['first_image'] = $p['img_paths'] ? explode('||', $p['img_paths'])[0] : '';
}
unset($p);

admin_head('পণ্য ব্যবস্থাপনা');
admin_nav('products');
?>

<div class="aph">
  <h1>📦 পণ্য ব্যবস্থাপনা</h1>
  <a href="/admin/products/add" class="abg">+ নতুন পণ্য</a>
</div>

<form action="/admin/products" method="GET" style="margin-bottom:16px">
  <div style="display:flex;gap:8px">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="পণ্যের নাম দিয়ে খুঁজুন..." class="ai" style="flex:1">
    <button type="submit" class="abg">🔍</button>
    <?php if ($q): ?><a href="/admin/products" class="abs">✕</a><?php endif; ?>
  </div>
</form>

<?php if ($products): ?>
<p style="font-size:.78rem;color:var(--gray);margin-bottom:12px">মোট <?= count($products) ?>টি পণ্য</p>
<div class="apg">
  <?php foreach ($products as $p): ?>
  <div class="apc">
    <div class="apci">
      <?php if ($p['first_image']): ?>
      <img src="<?= htmlspecialchars($p['first_image']) ?>" alt="">
      <?php else: ?><div class="ni">🛍️</div><?php endif; ?>
      <?php if (!$p['is_active']): ?><span class="off">বন্ধ</span><?php endif; ?>
      <?php if ($p['has_custom_design']): ?><span style="position:absolute;top:4px;left:4px;background:rgba(33,150,243,.9);color:#fff;font-size:.58rem;padding:2px 5px;border-radius:5px;font-weight:700">🎨</span><?php endif; ?>
    </div>
    <div class="apif">
      <h3><?= htmlspecialchars($p['name']) ?></h3>
      <?php if ($p['category']): ?><p style="font-size:.68rem;color:var(--g);margin-bottom:2px">📂 <?= htmlspecialchars($p['category']) ?></p><?php endif; ?>
      <p class="apc-p">৳<?= number_format($p['base_price'], 0) ?></p>
      <p class="apc-d">🚚 ৳<?= number_format($p['delivery_charge'], 0) ?></p>
      <p class="apc-c"><?= (int)$p['img_count'] ?>টি ছবি</p>
    </div>
    <div class="apca">
      <a href="/admin/products/<?= $p['id'] ?>/edit" class="abe">✏️ এডিট</a>
      <button class="abt<?= !$p['is_active'] ? ' off' : '' ?>" onclick="tg(<?= $p['id'] ?>,this)"><?= $p['is_active'] ? '✅' : '❌' ?></button>
      <button class="abd" onclick="dl(<?= $p['id'] ?>,this)">🗑️</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="aemp">
  <p style="font-size:2rem;margin-bottom:9px">📦</p>
  <p><?= $q ? '"'.htmlspecialchars($q).'" এ কোনো পণ্য নেই' : 'কোনো পণ্য নেই' ?></p>
  <?php if (!$q): ?><a href="/admin/products/add" class="abg" style="margin-top:12px;display:inline-flex">+ প্রথম পণ্য যোগ করুন</a><?php endif; ?>
</div>
<?php endif; ?>

<script>
async function tg(id,btn){
  const r=await fetch('/api/admin?action=toggle&id='+id,{method:'POST'});
  const d=await r.json();
  if(d.ok){btn.textContent=d.active?'✅':'❌';btn.classList.toggle('off',!d.active);}
}
async function dl(id,btn){
  if(!confirm('এই পণ্যটি মুছে ফেলবেন?'))return;
  const r=await fetch('/api/admin?action=delete&id='+id,{method:'POST'});
  const d=await r.json();
  if(d.ok)btn.closest('.apc').remove();
}
</script>

<?php admin_foot(); ?>