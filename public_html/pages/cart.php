<?php
require_once __DIR__ . '/../templates/layout.php';

$u = rz_get_user();

render_head('কার্ট — ' . ($cfg['site_name'] ?? 'Raqizone'), $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st" data-bn="কার্ট" data-en="Cart">কার্ট</span>
  </div>

  <?php if (!$u): ?>
  <div class="nacc">
    <div class="ni">🛒</div>
    <h2 data-bn="লগিন করুন" data-en="Login">লগিন করুন</h2>
    <p data-bn="কার্ট দেখতে লগিন করুন" data-en="Login to view cart">কার্ট দেখতে লগিন করুন</p>
    <div class="nacc-b"><a href="/" class="bg">লগিন করুন</a></div>
  </div>
  <?php else: ?>

  <?php
  $items = DB::rows(
      "SELECT * FROM cart_items WHERE user_id = ? ORDER BY created_at DESC",
      [$u['user_id']]
  );
  ?>

  <?php if ($items): ?>
  <div class="cl">
    <?php
    $total = 0;
    foreach ($items as $item):
      $opts     = json_decode($item['selected_options'] ?? '{}', true) ?: [];
      $subtotal = $item['price'] * $item['quantity'];
      $total   += $subtotal;
    ?>
    <div class="ci" id="ci<?= $item['id'] ?>">
      <?php if ($item['image_path']): ?>
      <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
      <?php endif; ?>
      <div class="cdt">
        <p class="cn"><?= htmlspecialchars($item['product_name']) ?></p>
        <?php if ($opts): ?>
        <div class="cps">
          <?php foreach ($opts as $k => $v): ?>
          <span class="cp"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="cpr2">
          <span class="cprc" id="cp<?= $item['id'] ?>">৳<?= number_format($subtotal, 0) ?></span>
          <div class="qr">
            <button class="qb" onclick="upCart(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>, <?= $item['price'] ?>)">−</button>
            <span class="qn" id="qty<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
            <button class="qb" onclick="upCart(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>, <?= $item['price'] ?>)">+</button>
          </div>
          <button class="crm" onclick="rmCart(<?= $item['id'] ?>)">🗑 সরান</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="csum">
    <div class="ctrow">
      <span data-bn="মোট:" data-en="Total:">মোট:</span>
      <span id="cartTotal">৳<?= number_format($total, 0) ?></span>
    </div>
    <p class="cnote" data-bn="ডেলিভারি চার্জ অর্ডারে যোগ হবে" data-en="Delivery charge will be added">ডেলিভারি চার্জ অর্ডারে যোগ হবে</p>
  </div>

  <?php else: ?>
  <div class="emp">
    <div class="ei">🛒</div>
    <h3 data-bn="কার্ট খালি" data-en="Cart is empty">কার্ট খালি</h3>
    <p data-bn="কোনো পণ্য যোগ করা হয়নি" data-en="No items added">কোনো পণ্য যোগ করা হয়নি</p>
    <a href="/home" style="display:inline-flex;padding:10px 20px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border-radius:50px;font-weight:700;text-decoration:none;font-size:.84rem;margin-top:8px">পণ্য দেখুন</a>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<div class="toast" id="tOk">✅ আপডেট হয়েছে</div>

<script>
async function rmCart(id) {
  var r = await fetch('/api/cart/remove/' + id, {method:'POST'});
  var d = await r.json();
  if (d.ok) {
    var el = document.getElementById('ci' + id);
    if (el) el.remove();
    recalc();
  }
}
async function upCart(id, qty, price) {
  if (qty < 0) return;
  var fd = new FormData(); fd.append('quantity', qty);
  var r = await fetch('/api/cart/update/' + id, {method:'POST', body:fd});
  var d = await r.json();
  if (d.ok) {
    if (qty === 0) {
      var el = document.getElementById('ci' + id); if (el) el.remove();
    } else {
      var qel = document.getElementById('qty' + id); if (qel) qel.textContent = qty;
      var cel = document.getElementById('cp' + id); if (cel) cel.textContent = '৳' + (price * qty).toFixed(0);
    }
    recalc();
    var t = document.getElementById('tOk'); t.classList.add('show'); setTimeout(function(){t.classList.remove('show');}, 2000);
  }
}
function recalc() {
  var total = 0;
  document.querySelectorAll('.cprc').forEach(function(el) {
    var v = parseFloat(el.textContent.replace('৳','').replace(',','')) || 0;
    total += v;
  });
  var el = document.getElementById('cartTotal');
  if (el) el.textContent = '৳' + total.toLocaleString('bn-BD');
}
</script>

<?php render_nav('cart'); render_foot(); ?><?php
require_once __DIR__ . '/../templates/layout.php';

$u = rz_get_user();

render_head('কার্ট — ' . ($cfg['site_name'] ?? 'Raqizone'), $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st" data-bn="কার্ট" data-en="Cart">কার্ট</span>
  </div>

  <?php if (!$u): ?>
  <div class="nacc">
    <div class="ni">🛒</div>
    <h2 data-bn="লগিন করুন" data-en="Login">লগিন করুন</h2>
    <p data-bn="কার্ট দেখতে লগিন করুন" data-en="Login to view cart">কার্ট দেখতে লগিন করুন</p>
    <div class="nacc-b"><a href="/" class="bg">লগিন করুন</a></div>
  </div>
  <?php else: ?>

  <?php
  $items = DB::rows(
      "SELECT * FROM cart_items WHERE user_id = ? ORDER BY created_at DESC",
      [$u['user_id']]
  );
  ?>

  <?php if ($items): ?>
  <div class="cl">
    <?php
    $total = 0;
    foreach ($items as $item):
      $opts     = json_decode($item['selected_options'] ?? '{}', true) ?: [];
      $subtotal = $item['price'] * $item['quantity'];
      $total   += $subtotal;
    ?>
    <div class="ci" id="ci<?= $item['id'] ?>">
      <?php if ($item['image_path']): ?>
      <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
      <?php endif; ?>
      <div class="cdt">
        <p class="cn"><?= htmlspecialchars($item['product_name']) ?></p>
        <?php if ($opts): ?>
        <div class="cps">
          <?php foreach ($opts as $k => $v): ?>
          <span class="cp"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="cpr2">
          <span class="cprc" id="cp<?= $item['id'] ?>">৳<?= number_format($subtotal, 0) ?></span>
          <div class="qr">
            <button class="qb" onclick="upCart(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>, <?= $item['price'] ?>)">−</button>
            <span class="qn" id="qty<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
            <button class="qb" onclick="upCart(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>, <?= $item['price'] ?>)">+</button>
          </div>
          <button class="crm" onclick="rmCart(<?= $item['id'] ?>)">🗑 সরান</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="csum">
    <div class="ctrow">
      <span data-bn="মোট:" data-en="Total:">মোট:</span>
      <span id="cartTotal">৳<?= number_format($total, 0) ?></span>
    </div>
    <p class="cnote" data-bn="ডেলিভারি চার্জ অর্ডারে যোগ হবে" data-en="Delivery charge will be added">ডেলিভারি চার্জ অর্ডারে যোগ হবে</p>
  </div>

  <?php else: ?>
  <div class="emp">
    <div class="ei">🛒</div>
    <h3 data-bn="কার্ট খালি" data-en="Cart is empty">কার্ট খালি</h3>
    <p data-bn="কোনো পণ্য যোগ করা হয়নি" data-en="No items added">কোনো পণ্য যোগ করা হয়নি</p>
    <a href="/home" style="display:inline-flex;padding:10px 20px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border-radius:50px;font-weight:700;text-decoration:none;font-size:.84rem;margin-top:8px">পণ্য দেখুন</a>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<div class="toast" id="tOk">✅ আপডেট হয়েছে</div>

<script>
async function rmCart(id) {
  var r = await fetch('/api/cart/remove/' + id, {method:'POST'});
  var d = await r.json();
  if (d.ok) {
    var el = document.getElementById('ci' + id);
    if (el) el.remove();
    recalc();
  }
}
async function upCart(id, qty, price) {
  if (qty < 0) return;
  var fd = new FormData(); fd.append('quantity', qty);
  var r = await fetch('/api/cart/update/' + id, {method:'POST', body:fd});
  var d = await r.json();
  if (d.ok) {
    if (qty === 0) {
      var el = document.getElementById('ci' + id); if (el) el.remove();
    } else {
      var qel = document.getElementById('qty' + id); if (qel) qel.textContent = qty;
      var cel = document.getElementById('cp' + id); if (cel) cel.textContent = '৳' + (price * qty).toFixed(0);
    }
    recalc();
    var t = document.getElementById('tOk'); t.classList.add('show'); setTimeout(function(){t.classList.remove('show');}, 2000);
  }
}
function recalc() {
  var total = 0;
  document.querySelectorAll('.cprc').forEach(function(el) {
    var v = parseFloat(el.textContent.replace('৳','').replace(',','')) || 0;
    total += v;
  });
  var el = document.getElementById('cartTotal');
  if (el) el.textContent = '৳' + total.toLocaleString('bn-BD');
}
</script>

<?php render_nav('cart'); render_foot(); ?><?php
require_once __DIR__ . '/../templates/layout.php';

$u = rz_get_user();

render_head('কার্ট — ' . ($cfg['site_name'] ?? 'Raqizone'), $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk">
      <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
    <span class="st" data-bn="কার্ট" data-en="Cart">কার্ট</span>
  </div>

  <?php if (!$u): ?>
  <div class="nacc">
    <div class="ni">🛒</div>
    <h2 data-bn="লগিন করুন" data-en="Login">লগিন করুন</h2>
    <p data-bn="কার্ট দেখতে লগিন করুন" data-en="Login to view cart">কার্ট দেখতে লগিন করুন</p>
    <div class="nacc-b"><a href="/" class="bg">লগিন করুন</a></div>
  </div>
  <?php else: ?>

  <?php
  $items = DB::rows(
      "SELECT * FROM cart_items WHERE user_id = ? ORDER BY created_at DESC",
      [$u['user_id']]
  );
  ?>

  <?php if ($items): ?>
  <div class="cl">
    <?php
    $total = 0;
    foreach ($items as $item):
      $opts     = json_decode($item['selected_options'] ?? '{}', true) ?: [];
      $subtotal = $item['price'] * $item['quantity'];
      $total   += $subtotal;
    ?>
    <div class="ci" id="ci<?= $item['id'] ?>">
      <?php if ($item['image_path']): ?>
      <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
      <?php endif; ?>
      <div class="cdt">
        <p class="cn"><?= htmlspecialchars($item['product_name']) ?></p>
        <?php if ($opts): ?>
        <div class="cps">
          <?php foreach ($opts as $k => $v): ?>
          <span class="cp"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="cpr2">
          <span class="cprc" id="cp<?= $item['id'] ?>">৳<?= number_format($subtotal, 0) ?></span>
          <div class="qr">
            <button class="qb" onclick="upCart(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>, <?= $item['price'] ?>)">−</button>
            <span class="qn" id="qty<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
            <button class="qb" onclick="upCart(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>, <?= $item['price'] ?>)">+</button>
          </div>
          <button class="crm" onclick="rmCart(<?= $item['id'] ?>)">🗑 সরান</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="csum">
    <div class="ctrow">
      <span data-bn="মোট:" data-en="Total:">মোট:</span>
      <span id="cartTotal">৳<?= number_format($total, 0) ?></span>
    </div>
    <p class="cnote" data-bn="ডেলিভারি চার্জ অর্ডারে যোগ হবে" data-en="Delivery charge will be added">ডেলিভারি চার্জ অর্ডারে যোগ হবে</p>
  </div>

  <?php else: ?>
  <div class="emp">
    <div class="ei">🛒</div>
    <h3 data-bn="কার্ট খালি" data-en="Cart is empty">কার্ট খালি</h3>
    <p data-bn="কোনো পণ্য যোগ করা হয়নি" data-en="No items added">কোনো পণ্য যোগ করা হয়নি</p>
    <a href="/home" style="display:inline-flex;padding:10px 20px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border-radius:50px;font-weight:700;text-decoration:none;font-size:.84rem;margin-top:8px">পণ্য দেখুন</a>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<div class="toast" id="tOk">✅ আপডেট হয়েছে</div>

<script>
async function rmCart(id) {
  var r = await fetch('/api/cart/remove/' + id, {method:'POST'});
  var d = await r.json();
  if (d.ok) {
    var el = document.getElementById('ci' + id);
    if (el) el.remove();
    recalc();
  }
}
async function upCart(id, qty, price) {
  if (qty < 0) return;
  var fd = new FormData(); fd.append('quantity', qty);
  var r = await fetch('/api/cart/update/' + id, {method:'POST', body:fd});
  var d = await r.json();
  if (d.ok) {
    if (qty === 0) {
      var el = document.getElementById('ci' + id); if (el) el.remove();
    } else {
      var qel = document.getElementById('qty' + id); if (qel) qel.textContent = qty;
      var cel = document.getElementById('cp' + id); if (cel) cel.textContent = '৳' + (price * qty).toFixed(0);
    }
    recalc();
    var t = document.getElementById('tOk'); t.classList.add('show'); setTimeout(function(){t.classList.remove('show');}, 2000);
  }
}
function recalc() {
  var total = 0;
  document.querySelectorAll('.cprc').forEach(function(el) {
    var v = parseFloat(el.textContent.replace('৳','').replace(',','')) || 0;
    total += v;
  });
  var el = document.getElementById('cartTotal');
  if (el) el.textContent = '৳' + total.toLocaleString('bn-BD');
}
</script>

<?php render_nav('cart'); render_foot(); ?>