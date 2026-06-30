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

$ic_box     = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>';
$ic_search  = '<svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>';
$ic_bag     = '<svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:currentColor"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg>';
$ic_pal     = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
$ic_folder  = '<svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>';
$ic_truck   = '<svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zM18 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
$ic_pencil  = '<svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75z"/></svg>';
$ic_check   = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
$ic_cross   = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
$ic_trash   = '<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
?>

<div class="aph">
  <h1 style="display:flex;align-items:center;gap:7px"><?= $ic_box ?>পণ্য ব্যবস্থাপনা</h1>
  <a href="/admin/products/add" class="abg">+ নতুন পণ্য</a>
</div>

<form action="/admin/products" method="GET" style="margin-bottom:16px">
  <div style="display:flex;gap:8px">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="পণ্যের নাম দিয়ে খুঁজুন..." class="ai" style="flex:1">
    <button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center"><?= $ic_search ?></button>
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
      <?php else: ?><div class="ni"><?= $ic_bag ?></div><?php endif; ?>
      <?php if (!$p['is_active']): ?><span class="off">বন্ধ</span><?php endif; ?>
      <?php if ($p['has_custom_design']): ?><span style="position:absolute;top:4px;left:4px;background:rgba(33,150,243,.9);color:#fff;font-size:.58rem;padding:2px 5px;border-radius:5px;font-weight:700;display:flex;align-items:center"><?= $ic_pal ?></span><?php endif; ?>
    </div>
    <div class="apif">
      <h3><?= htmlspecialchars($p['name']) ?></h3>
      <?php if ($p['category']): ?><p style="font-size:.68rem;color:var(--g);margin-bottom:2px;display:flex;align-items:center;gap:4px"><?= $ic_folder ?><?= htmlspecialchars($p['category']) ?></p><?php endif; ?>
      <p class="apc-p">৳<?= number_format($p['base_price'], 0) ?></p>
      <p class="apc-d" style="display:flex;align-items:center;gap:4px"><?= $ic_truck ?>৳<?= number_format($p['delivery_charge'], 0) ?></p>
      <p class="apc-c"><?= (int)$p['img_count'] ?>টি ছবি</p>
    </div>
    <div class="apca">
      <a href="/admin/products/<?= $p['id'] ?>/edit" class="abe" style="display:flex;align-items:center;gap:5px"><?= $ic_pencil ?>এডিট</a>
      <button class="abt<?= !$p['is_active'] ? ' off' : '' ?>" onclick="tg(<?= $p['id'] ?>,this)" style="display:flex;align-items:center;justify-content:center"><?= $p['is_active'] ? $ic_check : $ic_cross ?></button>
      <button class="abd" onclick="dl(<?= $p['id'] ?>,this)" style="display:flex;align-items:center;justify-content:center"><?= $ic_trash ?></button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="aemp">
  <p style="margin-bottom:9px;display:flex;justify-content:center"><?= $ic_bag ?></p>
  <p><?= $q ? '"'.htmlspecialchars($q).'" এ কোনো পণ্য নেই' : 'কোনো পণ্য নেই' ?></p>
  <?php if (!$q): ?><a href="/admin/products/add" class="abg" style="margin-top:12px;display:inline-flex">+ প্রথম পণ্য যোগ করুন</a><?php endif; ?>
</div>
<?php endif; ?>

<script>
async function tg(id,btn){
  const r=await fetch('/api/admin?action=toggle&id='+id,{method:'POST'});
  const d=await r.json();
  if(d.ok){btn.innerHTML=d.active?'<?= addslashes($ic_check) ?>':'<?= addslashes($ic_cross) ?>';btn.classList.toggle('off',!d.active);}
}
async function dl(id,btn){
  if(!confirm('এই পণ্যটি মুছে ফেলবেন?'))return;
  const r=await fetch('/api/admin?action=delete&id='+id,{method:'POST'});
  const d=await r.json();
  if(d.ok)btn.closest('.apc').remove();
}
</script>

<?php admin_foot(); ?>