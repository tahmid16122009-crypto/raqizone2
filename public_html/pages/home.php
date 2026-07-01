<?php
require_once __DIR__ . '/../templates/layout.php';

$u      = rz_get_user();
$q      = trim($_GET['q']      ?? '');
$cat    = trim($_GET['cat']    ?? '');
$gender = trim($_GET['gender'] ?? '');

$sql    = "SELECT p.*,
           GROUP_CONCAT(pi.image_path ORDER BY pi.sort_order SEPARATOR '||') AS img_paths,
           GROUP_CONCAT(pi.price      ORDER BY pi.sort_order SEPARATOR '||') AS img_prices,
           GROUP_CONCAT(pi.id         ORDER BY pi.sort_order SEPARATOR '||') AS img_ids,
           COUNT(pi.id) AS img_count
           FROM products p
           LEFT JOIN product_images pi ON pi.product_id = p.id
           WHERE p.is_active = 1";
$params = [];
if ($q)                           { $sql .= " AND p.name LIKE ?";  $params[] = "%$q%"; }
if ($cat)                         { $sql .= " AND p.category = ?"; $params[] = $cat; }
if ($gender && $gender !== 'all') { $sql .= " AND p.gender = ?";   $params[] = $gender; }
$sql .= " GROUP BY p.id ORDER BY RAND()";

$products = DB::rows($sql, $params);
foreach ($products as &$p) {
    $paths  = $p['img_paths']  ? explode('||', $p['img_paths'])  : [];
    $prices = $p['img_prices'] ? explode('||', $p['img_prices']) : [];
    $ids    = $p['img_ids']    ? explode('||', $p['img_ids'])    : [];
    $imgs   = [];
    foreach ($paths as $i => $path) {
        $imgs[] = ['id'=>$ids[$i]??'','image_path'=>$path,'price'=>(float)($prices[$i]??$p['base_price'])];
    }
    $p['product_images'] = $imgs;
    $p['offer_price'] = ($p['discount_percent'] > 0 && $p['regular_price'] > 0)
        ? round($p['regular_price'] * (1 - $p['discount_percent']/100))
        : (float)$p['base_price'];
}
unset($p);

$categories = json_decode($cfg['product_categories'] ?? '[]', true) ?: [];
$mob_bnrs   = json_decode($cfg['mobile_banners']     ?? '[]', true) ?: [];
$dsk_bnrs   = json_decode($cfg['desktop_banners']    ?? '[]', true) ?: [];
$anim       = $cfg['banner_animation'] ?? 'slide';

if ($q && $u) {
    try { DB::run("INSERT INTO search_history (user_id, query, created_at) VALUES (?,?,NOW())", [$u['user_id'], $q]); } catch(Throwable $e){}
}

render_head(($cfg['site_name'] ?? 'Raqizone') . ' — Home', $cfg);
?>

<div class="page">
  <!-- Top Bar -->
  <div class="tbar">
    <span class="tt"><?= htmlspecialchars($cfg['site_name'] ?? 'Raqizone') ?></span>
    <div style="display:flex;align-items:center;gap:8px">
      <!-- Updates button with notification dot -->
      <a href="/updates" style="position:relative;display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:var(--k3);border:1px solid var(--bdr2);text-decoration:none;color:var(--g)" title="Updates">
        <svg viewBox="0 0 24 24" style="width:17px;height:17px;fill:currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
        <span id="updDot" style="display:none;position:absolute;top:2px;right:2px;width:8px;height:8px;background:#F44336;border-radius:50%;border:2px solid var(--k2)"></span>
      </a>
      <?php if ($u): ?>
      <a href="/me" class="av"><?= strtoupper(mb_substr($u['name'], 0, 1)) ?></a>
      <?php else: ?>
      <button class="bsm" onclick="openLogin()" data-bn="লগিন" data-en="Login">Login</button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Mobile Banner -->
  <div class="bnr-wrap d-mobile" id="mBnrWrap">
    <?php if ($mob_bnrs): ?>
    <div class="bnr-track" id="mBnrTrack">
      <?php foreach ($mob_bnrs as $b): ?><div class="bnr-slide"><img src="<?= htmlspecialchars($b) ?>" alt="" loading="lazy"></div><?php endforeach; ?>
    </div>
    <?php if (count($mob_bnrs) > 1): ?>
    <div class="bnr-dots" id="mBnrDots">
      <?php foreach ($mob_bnrs as $i => $b): ?><button class="bnr-dot<?= $i===0?' on':'' ?>" onclick="goBnr(<?= $i ?>,false)"></button><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php else: ?><div class="bnr-e"><span><svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:var(--g)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></span><p><?= htmlspecialchars($cfg['home_tagline'] ?? '') ?></p></div><?php endif; ?>
  </div>

  <!-- Desktop Banner -->
  <div class="bnr-wrap d-desktop" id="dBnrWrap" style="height:320px">
    <?php if ($dsk_bnrs): ?>
    <div class="bnr-track" id="dBnrTrack">
      <?php foreach ($dsk_bnrs as $b): ?><div class="bnr-slide"><img src="<?= htmlspecialchars($b) ?>" alt="" loading="lazy"></div><?php endforeach; ?>
    </div>
    <?php if (count($dsk_bnrs) > 1): ?>
    <div class="bnr-dots" id="dBnrDots">
      <?php foreach ($dsk_bnrs as $i => $b): ?><button class="bnr-dot<?= $i===0?' on':'' ?>" onclick="goBnr(<?= $i ?>,true)"></button><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php else: ?><div class="bnr-e"><span><svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:var(--g)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></span><p><?= htmlspecialchars($cfg['home_tagline'] ?? '') ?></p></div><?php endif; ?>
  </div>

  <!-- Search -->
  <div class="sw" style="padding:8px 14px">
    <div style="display:flex;gap:7px;align-items:center">
      <button type="button" onclick="document.getElementById('fp').style.display=document.getElementById('fp').style.display==='none'?'block':'none'"
        style="padding:9px 11px;background:<?= ($cat||($gender&&$gender!='all'))?'linear-gradient(135deg,var(--g),var(--gd));color:var(--k)':'var(--k3);color:var(--gray)' ?>;border:2px solid var(--bdr2);border-radius:50px;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center">
        <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65A.488.488 0 0 0 14 2h-4c-.24 0-.43.17-.47.41l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.23.41.47.41h4c.24 0 .44-.17.47-.41l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
      </button>
      <div style="position:relative;flex:1">
        <div class="sb" id="searchBox">
          <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
          <input type="text" id="sq" value="<?= htmlspecialchars($q) ?>"
                 data-bn-placeholder="পণ্য খুঁজুন..." data-en-placeholder="Search products..."
                 placeholder="Search products..." autocomplete="off"
                 oninput="onSugInput(this.value)"
                 onkeydown="if(event.key==='Enter'){closeSug();doSearch();}"
                 onfocus="if(this.value.length>0)onSugInput(this.value)"
                 onblur="setTimeout(closeSug,200)">
          <?php if ($q): ?><button onclick="clearSearch()" style="background:none;border:none;color:var(--gray);cursor:pointer;font-size:.9rem;padding:0">✕</button><?php endif; ?>
        </div>
        <div id="sugBox" style="display:none;position:absolute;top:calc(100% + 5px);left:0;right:0;background:var(--k2);border:1px solid var(--bdr2);border-radius:var(--r);overflow:hidden;z-index:200;box-shadow:0 8px 24px rgba(0,0,0,.5);max-height:300px;overflow-y:auto"></div>
      </div>
      <button onclick="doSearch()" style="padding:9px 13px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border:none;border-radius:50px;cursor:pointer;font-weight:700;font-family:inherit;flex-shrink:0;display:flex;align-items:center;justify-content:center">
        <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      </button>
    </div>

    <div id="fp" style="display:none;margin-top:10px;padding:12px;background:var(--k3);border-radius:var(--r);border:1px solid var(--bdr2)">
      <?php if ($categories): ?>
      <p style="font-size:.72rem;color:var(--gray);margin-bottom:8px;font-weight:600;text-transform:uppercase;display:flex;align-items:center;gap:5px">
        <svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:var(--gray)"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
        Category
      </p>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
        <button type="button" class="fcat<?= !$cat?' fcat-on':'' ?>" onclick="qCat('')" data-bn="সব" data-en="All">All</button>
        <?php foreach ($categories as $c): ?>
        <button type="button" class="fcat<?= $cat===$c?' fcat-on':'' ?>" onclick="qCat('<?= htmlspecialchars(addslashes($c)) ?>')"><?= htmlspecialchars($c) ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <p style="font-size:.72rem;color:var(--gray);margin-bottom:8px;font-weight:600;text-transform:uppercase;display:flex;align-items:center;gap:5px">
        <svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:var(--gray)"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        Gender
      </p>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button type="button" class="fcat<?= (!$gender||$gender==='all')?' fcat-on':'' ?>" onclick="qGender('')">All</button>
        <button type="button" class="fcat<?= $gender==='male'?' fcat-on':'' ?>" onclick="qGender('male')" style="display:inline-flex;align-items:center;gap:4px"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M9.5 2C7.01 2 5 4.01 5 6.5S7.01 11 9.5 11c1.36 0 2.57-.61 3.41-1.56l4.65 4.65 1.41-1.41-4.65-4.65C15.39 7.07 16 5.86 16 4.5 16 2.01 13.99 0 11.5 0M9.5 4C10.88 4 12 5.12 12 6.5S10.88 9 9.5 9 7 7.88 7 6.5 8.12 4 9.5 4z"/></svg>Male</button>
        <button type="button" class="fcat<?= $gender==='female'?' fcat-on':'' ?>" onclick="qGender('female')" style="display:inline-flex;align-items:center;gap:4px"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M12 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3m0 14.2c1.71 0 3.2.5 3.2 1.3 0 .47-1.21 1.5-3.2 1.5s-3.2-1.03-3.2-1.5c0-.8 1.49-1.3 3.2-1.3M12 2C9.24 2 7 4.24 7 7c0 2.24 1.49 4.13 3.5 4.78V14H8v2h2.5v2H8v2h2.5v2h3v-2H16v-2h-2.5v-2H16v-2h-2.5v-2.22C15.51 11.13 17 9.24 17 7c0-2.76-2.24-5-5-5z"/></svg>Female</button>
      </div>
    </div>
  </div>

  <!-- Active filters -->
  <?php if ($cat || ($gender && $gender !== 'all')): ?>
  <div style="padding:5px 14px;display:flex;gap:6px;flex-wrap:wrap">
    <?php if ($cat): ?><a href="/home<?= $gender&&$gender!=='all'?'?gender='.$gender:'' ?>" style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;background:var(--gl);color:var(--g);border-radius:50px;font-size:.74rem;font-weight:600;text-decoration:none"><?= htmlspecialchars($cat) ?> ✕</a><?php endif; ?>
    <?php if ($gender && $gender !== 'all'): ?><a href="/home<?= $cat?'?cat='.$cat:'' ?>" style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;background:var(--gl);color:var(--g);border-radius:50px;font-size:.74rem;font-weight:600;text-decoration:none"><?= $gender ?> ✕</a><?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Products -->
  <div class="psec">
    <?php if ($q): ?><p style="color:var(--gray);font-size:.8rem;margin-bottom:9px">"<?= htmlspecialchars($q) ?>" — <?= count($products) ?> results</p><?php endif; ?>
    <?php if ($products): ?>
    <div class="pg">
      <?php foreach ($products as $p):
        $hasDisc  = $p['discount_percent'] > 0 && $p['regular_price'] > 0;
        $offerP   = $hasDisc ? round($p['regular_price'] * (1-$p['discount_percent']/100)) : (float)$p['base_price'];
        $minP     = $offerP;
        foreach ($p['product_images'] as $img) { if ($img['price'] && $img['price'] < $minP) $minP=(float)$img['price']; }
        $isFreeD  = (bool)($p['is_free_delivery'] ?? 0);
        $delivAmt = (float)$p['delivery_charge'];
      ?>
      <a href="/product/<?= $p['id'] ?>" class="pc">
        <div class="pci">
          <?php if ($p['product_images']): ?>
          <img src="<?= htmlspecialchars($p['product_images'][0]['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
          <?php if (count($p['product_images']) > 1): ?><span class="pb">+<?= count($p['product_images'])-1 ?></span><?php endif; ?>
          <?php else: ?><div class="ni"><svg viewBox="0 0 24 24" style="width:36px;height:36px;fill:var(--gray)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></div><?php endif; ?>
          <?php if ($p['video_url']): ?><span class="vb"><svg viewBox="0 0 24 24" style="width:11px;height:11px;fill:currentColor"><path d="M8 5v14l11-7z"/></svg></span><?php endif; ?>
          <?php if ($hasDisc): ?><span class="disc-badge">-<?= (int)$p['discount_percent'] ?>%</span><?php endif; ?>
          <?php if ($p['gender']==='male'): ?><span class="gender-badge"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:#fff"><path d="M9.5 2C7.01 2 5 4.01 5 6.5S7.01 11 9.5 11c1.36 0 2.57-.61 3.41-1.56l4.65 4.65 1.41-1.41-4.65-4.65C15.39 7.07 16 5.86 16 4.5 16 2.01 13.99 0 11.5 0M9.5 4C10.88 4 12 5.12 12 6.5S10.88 9 9.5 9 7 7.88 7 6.5 8.12 4 9.5 4z"/></svg></span><?php elseif ($p['gender']==='female'): ?><span class="gender-badge"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:#fff"><path d="M12 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3m0 14.2c1.71 0 3.2.5 3.2 1.3 0 .47-1.21 1.5-3.2 1.5s-3.2-1.03-3.2-1.5c0-.8 1.49-1.3 3.2-1.3M12 2C9.24 2 7 4.24 7 7c0 2.24 1.49 4.13 3.5 4.78V14H8v2h2.5v2H8v2h2.5v2h3v-2H16v-2h-2.5v-2H16v-2h-2.5v-2.22C15.51 11.13 17 9.24 17 7c0-2.76-2.24-5-5-5z"/></svg></span><?php endif; ?>
        </div>
        <div class="pin">
          <p class="pn"><?= htmlspecialchars($p['name']) ?></p>
          <div class="pr">
            <div style="display:flex;flex-direction:column;gap:1px">
              <?php if ($hasDisc): ?>
              <span style="font-size:.72rem;color:var(--gray);text-decoration:line-through">৳<?= number_format($p['regular_price'], 0) ?></span>
              <span class="pp">৳<?= number_format($minP, 0) ?></span>
              <?php else: ?>
              <span class="pp">৳<?= number_format($minP, 0) ?></span>
              <?php endif; ?>
            </div>
            <?php if ($isFreeD || $delivAmt == 0): ?>
            <span style="font-size:.63rem;color:#4CAF50;font-weight:700">Free</span>
            <?php else: ?>
            <span class="pd">৳<?= number_format($delivAmt, 0) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="emp">
      <div class="ei">
        <?php if ($q||$cat||$gender): ?>
        <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--gray)"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <?php else: ?>
        <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--gray)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg>
        <?php endif; ?>
      </div>
      <h3 data-bn="<?= ($q||$cat||$gender)?'কোনো ফলাফল নেই':'কোনো পণ্য নেই' ?>" data-en="<?= ($q||$cat||$gender)?'No results found':'No products yet' ?>"><?= ($q||$cat||$gender)?'No results found':'No products yet' ?></h3>
      <?php if ($q||$cat||$gender): ?>
      <a href="/home" style="display:inline-flex;padding:10px 20px;background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border-radius:50px;font-weight:700;text-decoration:none;font-size:.84rem;margin-top:8px">All Products</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <div style="height:10px"></div>
</div>

<!-- Login Modal -->
<div class="overlay" id="ls">
  <div class="modal" style="position:relative">
    <button class="mc" onclick="closeLogin()">✕</button>
    <div class="mt"><span class="ic"><svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:var(--g)"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg></span><h3 data-bn="লগিন করুন" data-en="Login">Login</h3></div>
    <div id="lb" style="display:flex;flex-direction:column;gap:11px">
      <button class="bg" onclick="showForm('sl')" data-bn="লগিন করুন" data-en="Login">Login</button>
      <button class="bo" onclick="showForm('sr')" data-bn="অ্যাকাউন্ট খুলুন" data-en="Create Account">Create Account</button>
    </div>
    <div id="sl" style="display:none">
      <form action="/auth/login" method="POST" class="fs">
        <input type="hidden" name="next" value="/home">
        <input type="text" name="name" data-bn-placeholder="আপনার নাম" data-en-placeholder="Your name" placeholder="Your name" required class="inp">
        <input type="tel" name="mobile" placeholder="01XXXXXXXXX" required class="inp">
        <button type="submit" class="bg" data-bn="লগিন →" data-en="Login →">Login →</button>
      </form>
      <button onclick="backForm()" style="background:none;border:none;color:var(--gray);cursor:pointer;margin-top:7px;font-family:inherit;font-size:.82rem">← Back</button>
    </div>
    <div id="sr" style="display:none">
      <form action="/auth/login" method="POST" class="fs">
        <input type="hidden" name="next" value="/home">
        <input type="text" name="name" data-bn-placeholder="আপনার নাম" data-en-placeholder="Your name" placeholder="Your name" required class="inp">
        <input type="tel" name="mobile" placeholder="01XXXXXXXXX" required class="inp">
        <button type="submit" class="bg" data-bn="অ্যাকাউন্ট খুলুন →" data-en="Create Account →">Create Account →</button>
      </form>
      <button onclick="backForm()" style="background:none;border:none;color:var(--gray);cursor:pointer;margin-top:7px;font-family:inherit;font-size:.82rem">← Back</button>
    </div>
  </div>
</div>

<style>
.disc-badge{position:absolute;top:6px;right:6px;background:linear-gradient(135deg,#F44336,#C62828);color:#fff;font-size:.62rem;font-weight:700;padding:2px 6px;border-radius:8px;z-index:2}
.sug-item{display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--bdr);transition:background .15s}
.sug-item:hover,.sug-item:active{background:var(--k3)}
.sug-item:last-child{border-bottom:none}
</style>

<script>
// Login
function openLogin(){document.getElementById('ls').classList.add('show');document.body.style.overflow='hidden';}
function closeLogin(){document.getElementById('ls').classList.remove('show');document.body.style.overflow='';backForm();}
function showForm(id){document.getElementById('lb').style.display='none';document.getElementById(id).style.display='block';}
function backForm(){document.getElementById('lb').style.display='flex';document.getElementById('sl').style.display='none';document.getElementById('sr').style.display='none';}
document.getElementById('ls').addEventListener('click',function(e){if(e.target===this)closeLogin();});

// Search
function doSearch(){var q=document.getElementById('sq').value.trim();var p=new URLSearchParams(window.location.search);if(q)p.set('q',q);else p.delete('q');window.location.href='/home'+(p.toString()?'?'+p.toString():'');}
function clearSearch(){var p=new URLSearchParams(window.location.search);p.delete('q');window.location.href='/home'+(p.toString()?'?'+p.toString():'');}

// Search suggestion
var sugTimer=null,sugBox=document.getElementById('sugBox');
function onSugInput(val){clearTimeout(sugTimer);val=val.trim();if(!val){closeSug();return;}sugTimer=setTimeout(function(){fetchSug(val);},220);}
async function fetchSug(q){try{var r=await fetch('/api/search?q='+encodeURIComponent(q));var d=await r.json();renderSug(d,q);}catch(e){closeSug();}}
function renderSug(items,q){
  if(!items||!items.length){closeSug();return;}
  var html='';
  items.forEach(function(item){
    var nm=item.name;
    var re=new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi');
    var nameHl=nm.replace(re,'<mark style="background:var(--gl);color:var(--g);border-radius:3px;padding:0 2px">$1</mark>');
    var delHtml=item.free_delivery||item.delivery==0?'<span style="color:#4CAF50;font-size:.68rem;font-weight:700">Free</span>':'<span style="color:var(--gray);font-size:.68rem">৳'+item.delivery+'</span>';
    html+='<div class="sug-item" onclick="window.location.href=\'/product/\'+'+item.id+'">'+
      (item.thumb?'<img src="'+item.thumb+'" style="width:38px;height:38px;object-fit:cover;border-radius:6px;flex-shrink:0">':'<div style="width:38px;height:38px;border-radius:6px;background:var(--k3);flex-shrink:0;display:flex;align-items:center;justify-content:center"><svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:var(--gray)"><path d="M19 7h-1V6c0-2.76-2.24-5-5-5S8 3.24 8 6v1H7c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-7-4c1.66 0 3 1.34 3 3v1h-6V6c0-1.66 1.34-3 3-3zm5 16H7V9h10v10z"/></svg></div>')+
      '<div style="flex:1;min-width:0"><p style="font-size:.84rem;font-weight:600;color:var(--w);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+nameHl+'</p>'+
      '<div style="display:flex;align-items:center;gap:8px;margin-top:2px"><span style="font-size:.78rem;font-weight:700;color:var(--g)">৳'+item.price+'</span>'+delHtml+'</div></div>'+
      '</div>';
  });
  sugBox.innerHTML=html;sugBox.style.display='block';
}
function closeSug(){sugBox.style.display='none';}

// Filter
function qCat(cat){var p=new URLSearchParams(window.location.search);if(cat)p.set('cat',cat);else p.delete('cat');var g=p.get('gender')||'';if(!g||g==='all')p.delete('gender');window.location.href='/home'+(p.toString()?'?'+p.toString():'');}
function qGender(g){var p=new URLSearchParams(window.location.search);if(g&&g!=='all')p.set('gender',g);else p.delete('gender');var cat=p.get('cat')||'';if(!cat)p.delete('cat');window.location.href='/home'+(p.toString()?'?'+p.toString():'');}

// Banner
function BannerSlider(wrapId,trackId,dotsId,anim){
  var wrap=document.getElementById(wrapId);var track=document.getElementById(trackId);
  if(!wrap||!track)return null;
  var slides=Array.from(track.querySelectorAll('.bnr-slide'));if(!slides.length)return null;
  var dotsWrap=dotsId?document.getElementById(dotsId):null;
  var dots=dotsWrap?Array.from(dotsWrap.querySelectorAll('.bnr-dot')):[];
  var cur=0,timer=null;
  var wrapH=wrap.offsetHeight||200;
  wrap.style.overflow='hidden';wrap.style.position='relative';wrap.style.height=wrapH+'px';
  function initSlide(){track.style.cssText='display:flex;width:100%;height:100%;transition:transform .7s cubic-bezier(.25,1,.5,1)';slides.forEach(function(s){s.style.cssText='min-width:100%;width:100%;height:100%;flex-shrink:0;overflow:hidden';});}
  function initFade(){track.style.cssText='position:relative;width:100%;height:'+wrapH+'px';slides.forEach(function(s,i){s.style.cssText='position:absolute;top:0;left:0;width:100%;height:100%;opacity:'+(i===0?'1':'0')+';transition:opacity .9s ease;z-index:'+(i===0?'2':'1');});}
  if(anim==='fade')initFade();else initSlide();
  function go(n){var prev=cur;cur=((n%slides.length)+slides.length)%slides.length;if(prev===cur)return;
    if(anim==='fade'){slides[prev].style.opacity='0';slides[prev].style.zIndex='1';slides[cur].style.opacity='1';slides[cur].style.zIndex='2';}
    else{track.style.transform='translateX(-'+(cur*100)+'%)';}
    dots.forEach(function(d,i){d.classList.toggle('on',i===cur);});}
  function start(){if(slides.length>1)timer=setInterval(function(){go(cur+1);},4200);}
  function stop(){clearInterval(timer);}
  start();
  var sx=0;
  wrap.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;stop();},{passive:true});
  wrap.addEventListener('touchend',function(e){var d=sx-e.changedTouches[0].clientX;if(Math.abs(d)>40)go(d>0?cur+1:cur-1);start();},{passive:true});
  return go;
}
var ANIM='<?= $anim ?>';
var mS=null,dS=null;
requestAnimationFrame(function(){requestAnimationFrame(function(){
  mS=BannerSlider('mBnrWrap','mBnrTrack','mBnrDots',ANIM);
  dS=BannerSlider('dBnrWrap','dBnrTrack','dBnrDots',ANIM);
});});
function goBnr(n,isD){if(isD&&dS)dS(n);else if(!isD&&mS)mS(n);}

// Updates notification dot
(async function(){
  try{
    var r=await fetch('/api/posts?action=unseen_count');
    var d=await r.json();
    if(d.count>0){var dot=document.getElementById('updDot');if(dot)dot.style.display='block';}
  }catch(e){}
})();
</script>

<?php render_nav('home'); render_foot(); ?>