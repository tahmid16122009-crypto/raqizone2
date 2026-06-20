<?php
require_once __DIR__ . '/base.php';

$pid     = (int)($_GET['id'] ?? 0);
$product = null;
$images  = [];
$options = [];
$error   = '';

$categories = json_decode($cfg['product_categories'] ?? '[]', true) ?: [];

if ($pid) {
    $product = DB::row("SELECT * FROM products WHERE id = ?", [$pid]);
    if (!$product) { header('Location: /admin/products'); exit; }
    $images   = DB::rows("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC", [$pid]);
    $opts_raw = DB::rows("SELECT * FROM product_options WHERE product_id=?", [$pid]);
    foreach ($opts_raw as $opt) {
        $opt['values'] = DB::rows("SELECT * FROM product_option_values WHERE option_id=? ORDER BY sort_order ASC", [$opt['id']]);
        $options[] = $opt;
    }
}

$cd_slots = json_decode($product['cd_image_slots'] ?? 'null', true);
if (!$cd_slots) {
    $cd_slots = [
        ['title' => 'সামনের ছবি', 'required' => false],
        ['title' => 'পিছনের ছবি', 'required' => false],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name                   = trim($_POST['name']        ?? '');
    $description            = trim($_POST['description'] ?? '');
    $video_url              = trim($_POST['video_url']   ?? '');
    $gender                 = trim($_POST['gender']      ?? 'all');
    $category               = trim($_POST['category']   ?? '');
    $has_custom_design      = isset($_POST['has_custom_design']) ? 1 : 0;
    $base_price             = (float)($_POST['base_price']          ?? 0);
    $regular_price          = (float)($_POST['regular_price']       ?? 0);
    $discount_percent       = (float)($_POST['discount_percent']    ?? 0);
    $delivery_charge        = (float)($_POST['delivery_charge']     ?? 60);
    $is_free_delivery       = isset($_POST['is_free_delivery']) ? 1 : 0;
    $product_payment_method = trim($_POST['product_payment_method'] ?? 'default');
    $max_quantity           = (int)($_POST['max_quantity']    ?? 0);
    $advance_percent        = (float)($_POST['advance_percent'] ?? 0);
    $options_data           = $_POST['options_data']      ?? '[]';
    $deleted_ids_raw        = $_POST['deleted_image_ids'] ?? '[]';

    // CD slots
    $slot_titles   = $_POST['cd_slot_title']    ?? [];
    $slot_required = $_POST['cd_slot_required'] ?? [];
    $new_cd_slots  = [];
    foreach ($slot_titles as $si => $st) {
        if (trim($st) === '') continue;
        $new_cd_slots[] = [
            'title'    => trim($st),
            'required' => in_array((string)$si, array_keys($slot_required)),
        ];
    }

    if ($regular_price > 0 && $discount_percent > 0) {
        $base_price = round($regular_price * (1 - $discount_percent / 100));
    } elseif ($regular_price > 0 && $discount_percent == 0) {
        $base_price = $regular_price;
    }
    if ($is_free_delivery) $delivery_charge = 0;

    $img_prices     = json_decode($_POST['image_prices']     ?? '[]', true) ?: [];
    $new_img_prices = json_decode($_POST['new_image_prices'] ?? '[]', true) ?: [];
    $deleted_ids    = array_filter(json_decode($deleted_ids_raw, true) ?: []);

    if (!$name) {
        $error = 'Product name is required';
    } else {
        $fields = [
            'name'                   => $name,
            'description'            => $description,
            'base_price'             => $base_price,
            'regular_price'          => $regular_price,
            'discount_percent'       => $discount_percent,
            'delivery_charge'        => $delivery_charge,
            'is_free_delivery'       => $is_free_delivery,
            'product_payment_method' => $product_payment_method,
            'max_quantity'           => $max_quantity,
            'advance_percent'        => $advance_percent,
            'cd_image_slots'         => json_encode($new_cd_slots, JSON_UNESCAPED_UNICODE),
            'video_url'              => $video_url ?: null,
            'gender'                 => $gender,
            'category'               => $category,
            'has_custom_design'      => $has_custom_design,
        ];

        if ($pid) {
            $sets   = implode(',', array_map(fn($k) => "`$k`=?", array_keys($fields)));
            $vals   = array_values($fields);
            $vals[] = $pid;
            DB::run("UPDATE products SET {$sets} WHERE id=?", $vals);

            foreach ($deleted_ids as $did) {
                DB::run("DELETE FROM product_images WHERE id=? AND product_id=?", [(int)$did, $pid]);
            }
            $mo = DB::row("SELECT MAX(sort_order) AS mo FROM product_images WHERE product_id=?", [$pid]);
            $sort_idx = ((int)($mo['mo'] ?? -1)) + 1;

            if (!empty($_FILES['new_images']['name'][0])) {
                $files = $_FILES['new_images'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file = ['name'=>$files['name'][$i],'type'=>$files['type'][$i],'tmp_name'=>$files['tmp_name'][$i],'error'=>$files['error'][$i],'size'=>$files['size'][$i]];
                    $url = upload_image($file, 'prod', 'products');
                    if ($url) {
                        $price = (float)($new_img_prices[$i] ?? $base_price) ?: $base_price;
                        DB::run("INSERT INTO product_images (product_id,image_path,price,sort_order,created_at) VALUES (?,?,?,?,NOW())", [$pid,$url,$price,$sort_idx++]);
                    }
                }
            }
        } else {
            $cols    = '`' . implode('`,`', array_keys($fields)) . '`,`is_active`,`created_at`';
            $phs     = implode(',', array_fill(0, count($fields), '?')) . ',1,NOW()';
            $new_pid = DB::exec("INSERT INTO products ($cols) VALUES ($phs)", array_values($fields));

            if (!empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file = ['name'=>$files['name'][$i],'type'=>$files['type'][$i],'tmp_name'=>$files['tmp_name'][$i],'error'=>$files['error'][$i],'size'=>$files['size'][$i]];
                    $url = upload_image($file, 'prod', 'products');
                    if ($url) {
                        $price = (float)($img_prices[$i] ?? $base_price) ?: $base_price;
                        DB::run("INSERT INTO product_images (product_id,image_path,price,sort_order,created_at) VALUES (?,?,?,?,NOW())", [$new_pid,$url,$price,$i]);
                    }
                }
            }
            $pid = $new_pid;
        }

        // Options — name, is_required, values with extra_price
        $opts = json_decode($options_data, true) ?: [];
        $eo   = DB::rows("SELECT id FROM product_options WHERE product_id=?", [$pid]);
        foreach ($eo as $o) DB::run("DELETE FROM product_option_values WHERE option_id=?", [$o['id']]);
        DB::run("DELETE FROM product_options WHERE product_id=?", [$pid]);
        foreach ($opts as $opt) {
            if (empty($opt['name'])) continue;
            $is_req = isset($opt['is_required']) ? (int)$opt['is_required'] : 1;
            $oid = DB::exec(
                "INSERT INTO product_options (product_id,option_name,is_required) VALUES (?,?,?)",
                [$pid, $opt['name'], $is_req]
            );
            foreach (($opt['values'] ?? []) as $j => $v) {
                if (trim($v['val'] ?? '') === '') continue;
                DB::run(
                    "INSERT INTO product_option_values (option_id,value,extra_price,sort_order) VALUES (?,?,?,?)",
                    [$oid, trim($v['val']), (float)($v['price'] ?? 0), $j]
                );
            }
        }
        header('Location: /admin/products'); exit;
    }
}

admin_head($product ? 'Edit Product' : 'New Product');
admin_nav('products');
?>

<style>
.opt-val-row{display:flex;align-items:center;gap:7px;margin-bottom:7px;background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px;padding:8px 10px}
.opt-val-row .val-inp{flex:2;min-width:0}
.opt-val-row .price-inp{width:90px;flex-shrink:0}
.opt-val-row .brv{background:none;border:none;color:#F44336;cursor:pointer;font-size:.9rem;flex-shrink:0;padding:0 4px}
.cd-slot-row{display:flex;align-items:center;gap:9px;background:var(--ak3);border:1px solid var(--abdr2);border-radius:9px;padding:9px 12px;margin-bottom:8px}
.opt-header-row{display:grid;grid-template-columns:1fr 90px auto;gap:7px;margin-bottom:4px;padding:0 10px}
.opt-header-row span{font-size:.7rem;color:var(--agray);font-weight:600;text-transform:uppercase}
</style>

<div class="aph">
  <a href="/admin/products" class="abl">← Products</a>
  <h1><?= $product ? '✏️ Edit Product' : '📦 New Product' ?></h1>
</div>

<?php if ($error): ?>
<div style="background:rgba(244,67,54,.1);color:#F44336;border:1px solid rgba(244,67,54,.25);padding:10px 14px;border-radius:8px;margin-bottom:16px"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form action="<?= $product ? '/admin/products/'.$product['id'].'/edit' : '/admin/products/add' ?>"
      method="POST" enctype="multipart/form-data" class="aform" id="pf">

  <!-- Basic Info -->
  <div class="afs">
    <h3>📝 Product Info</h3>
    <div class="frow"><label>Product Name *</label><input type="text" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required class="ai" placeholder="Product name"></div>
    <div class="frow"><label>Description</label><textarea name="description" class="ata" placeholder="Product description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea></div>
    <div class="frow"><label>YouTube Video URL</label><input type="url" name="video_url" value="<?= htmlspecialchars($product['video_url'] ?? '') ?>" class="ai" placeholder="https://www.youtube.com/watch?v=..."></div>
  </div>

  <!-- Pricing -->
  <div class="afs">
    <h3>💰 Pricing</h3>
    <p class="afn">Regular price দিন। Discount % দিলে Offer price auto calculate হবে।</p>
    <div class="fr2">
      <div class="frow">
        <label>Regular Price (৳)</label>
        <input type="number" name="regular_price" id="regularPrice" value="<?= $product['regular_price'] ?? '0' ?>" class="ai" min="0" step="0.01" oninput="calcOfferPrice()">
      </div>
      <div class="frow">
        <label>Discount (%)</label>
        <input type="number" name="discount_percent" id="discountPct" value="<?= $product['discount_percent'] ?? '0' ?>" class="ai" min="0" max="99" step="0.01" oninput="calcOfferPrice()">
      </div>
    </div>
    <div id="offerPreview" style="display:none;background:var(--ak3);border:1px solid var(--g);border-radius:var(--r);padding:12px;margin-bottom:13px">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <div><p style="font-size:.72rem;color:var(--agray)">Regular:</p><p id="prevRegular" style="font-size:.9rem;text-decoration:line-through;color:var(--agray)"></p></div>
        <div style="font-size:1.2rem;color:var(--g)">→</div>
        <div><p style="font-size:.72rem;color:var(--agray)">Offer:</p><p id="prevOffer" style="font-size:1.1rem;font-weight:700;color:var(--g)"></p></div>
        <div style="background:linear-gradient(135deg,#F44336,#C62828);color:#fff;border-radius:50px;padding:4px 11px;font-size:.78rem;font-weight:700" id="prevBadge"></div>
      </div>
    </div>
    <input type="hidden" name="base_price" id="basePriceInput" value="<?= $product['base_price'] ?? '0' ?>">
  </div>

  <!-- Advance Payment -->
  <div class="afs">
    <h3>💳 Advance Payment</h3>
    <p class="afn">0 দিলে advance লাগবে না। যত % দেবেন user order এর মোট দামের তত % advance pay করবে।</p>
    <div class="frow">
      <label>Advance Payment (%) — 0 = disabled</label>
      <input type="number" name="advance_percent" value="<?= $product['advance_percent'] ?? '0' ?>" class="ai" min="0" max="100" step="0.01" placeholder="0">
    </div>
  </div>

  <!-- Max Quantity -->
  <div class="afs">
    <h3>⚡ Quantity Limit</h3>
    <div class="frow">
      <label>Max Quantity Per Order (0 = unlimited)</label>
      <input type="number" name="max_quantity" value="<?= $product['max_quantity'] ?? '0' ?>" class="ai" min="0" placeholder="0">
    </div>
  </div>

  <!-- Delivery -->
  <div class="afs">
    <h3>🚚 Delivery</h3>
    <div style="display:flex;align-items:center;gap:14px;padding:12px;background:var(--ak3);border-radius:var(--r);border:1px solid var(--abdr2);margin-bottom:12px">
      <div style="flex:1"><p style="font-weight:700;font-size:.88rem;margin-bottom:3px">🆓 Free Delivery</p><p style="font-size:.76rem;color:var(--agray)">চালু করলে ডেলিভারি ফ্রি</p></div>
      <label style="position:relative;width:50px;height:27px;cursor:pointer;flex-shrink:0">
        <input type="checkbox" name="is_free_delivery" id="freeDelivToggle" style="opacity:0;width:0;height:0;position:absolute" <?= ($product['is_free_delivery']??0)?'checked':'' ?> onchange="toggleFreeDelivery(this)">
        <span id="fdTrack" style="position:absolute;inset:0;border-radius:50px;background:<?= ($product['is_free_delivery']??0)?'#4CAF50':'var(--abdr2)' ?>;transition:background .3s"><span id="fdKnob" style="position:absolute;top:3px;left:<?= ($product['is_free_delivery']??0)?'26px':'3px' ?>;width:21px;height:21px;border-radius:50%;background:white;transition:left .3s;box-shadow:0 1px 4px rgba(0,0,0,.3)"></span></span>
      </label>
    </div>
    <div class="frow" id="delivChargeRow" style="<?= ($product['is_free_delivery']??0)?'display:none':'' ?>">
      <label>Delivery Charge (৳)</label>
      <input type="number" name="delivery_charge" id="delivCharge" value="<?= $product['delivery_charge'] ?? '60' ?>" class="ai" min="0" step="0.01">
    </div>
  </div>

  <!-- Payment Method -->
  <div class="afs">
    <h3>💳 Payment Method</h3>
    <div class="frow">
      <label>এই পণ্যের জন্য payment method</label>
      <select name="product_payment_method" class="ai">
        <?php foreach (['default'=>'🔧 Default','cod'=>'💵 COD Only','delivery_only'=>'🏦 Delivery Charge Online','full'=>'💳 Full Payment','all'=>'🔀 All Options'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= ($product['product_payment_method']??'default')===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Gender & Category -->
  <div class="afs">
    <h3>👥 Gender & Category</h3>
    <div class="fr2">
      <div class="frow">
        <label>Gender</label>
        <?php $cg = $product['gender'] ?? 'all'; ?>
        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-top:4px">
          <button type="button" class="gender-btn<?= $cg==='all'?' gender-on':'' ?>" onclick="setGender('all',this)">🌟 All</button>
          <button type="button" class="gender-btn<?= $cg==='male'?' gender-on':'' ?>" onclick="setGender('male',this)">👨 Male</button>
          <button type="button" class="gender-btn<?= $cg==='female'?' gender-on':'' ?>" onclick="setGender('female',this)">👩 Female</button>
        </div>
        <input type="hidden" name="gender" id="genderInput" value="<?= htmlspecialchars($cg) ?>">
      </div>
      <div class="frow">
        <label>Category</label>
        <select name="category" class="ai">
          <option value="">-- Select --</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= ($product['category']??'')===$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Custom Design -->
  <div class="afs">
    <h3>🎨 Custom Design</h3>
    <div style="display:flex;align-items:center;gap:14px;padding:12px;background:var(--ak3);border-radius:var(--r);border:1px solid var(--abdr2);margin-bottom:14px">
      <div style="flex:1"><p style="font-weight:700;font-size:.88rem;margin-bottom:3px">🎨 Custom Design Enable</p><p style="font-size:.76rem;color:var(--agray)">User ছবি ও লেখা দিতে পারবে</p></div>
      <label style="position:relative;width:50px;height:27px;cursor:pointer;flex-shrink:0">
        <input type="checkbox" name="has_custom_design" id="cdToggle" style="opacity:0;width:0;height:0;position:absolute" <?= ($product['has_custom_design']??0)?'checked':'' ?> onchange="toggleCD(this);toggleCDSlots(this.checked)">
        <span id="cdTrack" style="position:absolute;inset:0;border-radius:50px;background:<?= ($product['has_custom_design']??0)?'var(--g)':'var(--abdr2)' ?>;transition:background .3s"><span id="cdKnob" style="position:absolute;top:3px;left:<?= ($product['has_custom_design']??0)?'26px':'3px' ?>;width:21px;height:21px;border-radius:50%;background:white;transition:left .3s;box-shadow:0 1px 4px rgba(0,0,0,.3)"></span></span>
      </label>
    </div>

    <div id="cdSlotsSection" style="display:<?= ($product['has_custom_design']??0)?'block':'none' ?>">
      <p style="font-size:.82rem;font-weight:700;color:var(--g);margin-bottom:10px">📸 Image Slots — user কতটা ছবি দিতে পারবে</p>
      <div style="display:grid;grid-template-columns:1fr auto auto;gap:8px;margin-bottom:6px;padding:0 2px">
        <span style="font-size:.7rem;color:var(--agray);font-weight:600">TITLE</span>
        <span style="font-size:.7rem;color:var(--agray);font-weight:600">REQUIRED</span>
        <span></span>
      </div>
      <div id="cdSlotList">
        <?php foreach ($cd_slots as $si => $slot): ?>
        <div class="cd-slot-row">
          <input type="text" name="cd_slot_title[]" value="<?= htmlspecialchars($slot['title']) ?>" class="ai" placeholder="e.g. সামনের ছবি" style="flex:1">
          <label style="display:flex;align-items:center;gap:5px;font-size:.78rem;color:var(--w);cursor:pointer;flex-shrink:0;white-space:nowrap">
            <input type="checkbox" name="cd_slot_required[<?= $si ?>]" value="1" <?= ($slot['required']??false)?'checked':'' ?> style="accent-color:var(--g);width:16px;height:16px">
            Required
          </label>
          <button type="button" onclick="this.closest('.cd-slot-row').remove()" style="background:none;border:none;color:#F44336;cursor:pointer;font-size:.9rem;flex-shrink:0;padding:0 4px">✕</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="abs" onclick="addCDSlot()" style="margin-top:6px">+ Add Image Slot</button>
    </div>
  </div>

  <!-- Product Options — with per-value price + is_required per option -->
  <div class="afs">
    <h3>⚙️ Product Options</h3>
    <p class="afn">প্রতিটা option এর জন্য "Required" সেট করুন। প্রতিটা value এর extra price দিন (0 = base price)।<br>
    যেমন: "দৈর্ঘ্য" option → 1ফুট: +0৳, 2ফুট: +100৳ | "Extra Color" → Yes: +200৳</p>
    <div id="optContainer">
      <?php foreach ($options as $i => $opt): ?>
      <div class="og" id="og<?= $i ?>">
        <div class="ohd" style="flex-wrap:wrap;gap:8px">
          <input type="text" class="ai on-i" placeholder="Option name (e.g. দৈর্ঘ্য)" value="<?= htmlspecialchars($opt['option_name']) ?>" style="flex:1;min-width:120px">
          <label style="display:flex;align-items:center;gap:6px;font-size:.78rem;cursor:pointer;flex-shrink:0;background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px;padding:5px 10px">
            <input type="checkbox" class="req-i" value="1" <?= ($opt['is_required']??1)?'checked':'' ?> style="accent-color:var(--g);width:15px;height:15px">
            <span style="font-size:.76rem;font-weight:600">Required</span>
          </label>
          <button type="button" class="bro" onclick="removeOpt(this)">✕ Remove</button>
        </div>
        <div class="opt-header-row" style="margin-top:8px">
          <span>Value</span>
          <span>Extra Price (৳)</span>
          <span></span>
        </div>
        <div class="ovs" id="ov<?= $i ?>">
          <?php foreach ($opt['values'] as $val): ?>
          <div class="opt-val-row">
            <input type="text" class="ai val-inp" placeholder="e.g. 1 ফুট" value="<?= htmlspecialchars($val['value']) ?>">
            <input type="number" class="ai price-inp" placeholder="0" min="0" step="0.01" value="<?= (float)($val['extra_price'] ?? 0) ?>">
            <button type="button" class="brv" onclick="this.parentElement.remove()">✕</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="bav" onclick="addVal(<?= $i ?>)">+ Add value</button>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="abs" style="margin-top:10px" onclick="addOpt()">+ Add Option</button>
    <input type="hidden" name="options_data" id="optData" value="[]">
  </div>

  <!-- Images -->
  <div class="afs">
    <h3>📸 Product Images</h3>
    <p class="afn">Multiple images। First image = thumbnail।</p>
    <?php if ($images): ?>
    <div class="ig" id="eig">
      <?php foreach ($images as $img): ?>
      <div class="ipc" id="eiw<?= $img['id'] ?>">
        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="">
        <div class="iov"><span style="font-size:.7rem;color:#fff">৳<?= $img['price'] ?></span></div>
        <button type="button" class="img-del-btn" onclick="markDel(<?= $img['id'] ?>)">✕</button>
      </div>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="deleted_image_ids" id="deletedIds" value="[]">
    <?php endif; ?>
    <div class="aup" onclick="document.getElementById('imgInput').click()" style="margin-top:10px">
      <div class="ui">📷</div><p><?= $product ? 'Add new images' : 'Select images' ?></p><p class="us">Multiple images at once</p>
    </div>
    <input type="file" id="imgInput" accept="image/*" multiple style="display:none" onchange="handleImgs(this)">
    <div class="ig" id="newImgGrid" style="margin-top:10px"></div>
    <div id="imgFilesContainer"></div>
    <input type="hidden" name="<?= $product ? 'new_image_prices' : 'image_prices' ?>" id="imgPrices" value="[]">
  </div>

  <div class="afact">
    <button type="submit" class="abg" onclick="prepSub(event)"><?= $product ? '✅ Save Changes' : '✅ Add Product' ?></button>
    <a href="/admin/products" class="abs">Cancel</a>
  </div>
</form>

<script>
var cdSlotCount = <?= count($cd_slots) ?>;

function setGender(v,btn){document.getElementById('genderInput').value=v;document.querySelectorAll('.gender-btn').forEach(b=>b.classList.remove('gender-on'));btn.classList.add('gender-on');}
function toggleCD(cb){const t=document.getElementById('cdTrack'),k=document.getElementById('cdKnob');if(cb.checked){t.style.background='var(--g)';k.style.left='26px';}else{t.style.background='var(--abdr2)';k.style.left='3px';}}
function toggleCDSlots(show){document.getElementById('cdSlotsSection').style.display=show?'block':'none';}
function toggleFreeDelivery(cb){const t=document.getElementById('fdTrack'),k=document.getElementById('fdKnob'),row=document.getElementById('delivChargeRow');if(cb.checked){t.style.background='#4CAF50';k.style.left='26px';row.style.display='none';document.getElementById('delivCharge').value='0';}else{t.style.background='var(--abdr2)';k.style.left='3px';row.style.display='block';}}

function calcOfferPrice(){
  const reg=parseFloat(document.getElementById('regularPrice').value)||0;
  const pct=parseFloat(document.getElementById('discountPct').value)||0;
  const preview=document.getElementById('offerPreview');
  if(reg>0&&pct>0){const offer=Math.round(reg*(1-pct/100));document.getElementById('prevRegular').textContent='৳'+reg.toFixed(0);document.getElementById('prevOffer').textContent='৳'+offer.toFixed(0);document.getElementById('prevBadge').textContent='-'+pct+'%';document.getElementById('basePriceInput').value=offer;preview.style.display='block';}
  else if(reg>0){document.getElementById('basePriceInput').value=reg;preview.style.display='none';}
  else{document.getElementById('basePriceInput').value=0;preview.style.display='none';}
}
calcOfferPrice();

function addCDSlot(){
  const i=cdSlotCount++;
  document.getElementById('cdSlotList').insertAdjacentHTML('beforeend',`
    <div class="cd-slot-row">
      <input type="text" name="cd_slot_title[]" class="ai" placeholder="e.g. তৃতীয় ছবি" style="flex:1">
      <label style="display:flex;align-items:center;gap:5px;font-size:.78rem;color:var(--w);cursor:pointer;flex-shrink:0;white-space:nowrap">
        <input type="checkbox" name="cd_slot_required[${i}]" value="1" style="accent-color:var(--g);width:16px;height:16px"> Required
      </label>
      <button type="button" onclick="this.closest('.cd-slot-row').remove()" style="background:none;border:none;color:#F44336;cursor:pointer;font-size:.9rem;flex-shrink:0;padding:0 4px">✕</button>
    </div>`);
}

const delIds=[];
function markDel(id){if(!delIds.includes(id))delIds.push(id);document.getElementById('deletedIds').value=JSON.stringify(delIds);const w=document.getElementById('eiw'+id);if(w){w.style.opacity='.2';w.style.pointerEvents='none';}}
let newFiles=[];
function handleImgs(input){Array.from(input.files).forEach(f=>newFiles.push({file:f,price:'',url:URL.createObjectURL(f),removed:false}));input.value='';renderNewImgs();}
function renderNewImgs(){const g=document.getElementById('newImgGrid');g.innerHTML='';newFiles.forEach((item,i)=>{if(item.removed)return;const d=document.createElement('div');d.className='ipc';d.style.position='relative';d.innerHTML=`<img src="${item.url}" style="width:100%;height:100%;object-fit:cover"><div class="iov" style="opacity:1;background:rgba(0,0,0,.5)"><input type="number" class="ip" placeholder="৳ Price" value="${item.price}" style="opacity:1;width:70px" onchange="newFiles[${i}].price=this.value"></div><button type="button" class="img-del-btn" onclick="removeNewImg(${i})">✕</button>`;g.appendChild(d);});}
function removeNewImg(i){newFiles[i].removed=true;renderNewImgs();}

let optCount=<?= count($options) ?>;

function addOpt(){
  const i=optCount++;
  const d=document.createElement('div');d.className='og';d.id='og'+i;
  d.innerHTML=`
    <div class="ohd" style="flex-wrap:wrap;gap:8px">
      <input type="text" class="ai on-i" placeholder="Option name (e.g. দৈর্ঘ্য)" style="flex:1;min-width:120px">
      <label style="display:flex;align-items:center;gap:6px;font-size:.78rem;cursor:pointer;flex-shrink:0;background:var(--ak3);border:1px solid var(--abdr2);border-radius:8px;padding:5px 10px">
        <input type="checkbox" class="req-i" value="1" checked style="accent-color:var(--g);width:15px;height:15px">
        <span style="font-size:.76rem;font-weight:600">Required</span>
      </label>
      <button type="button" class="bro" onclick="removeOpt(this)">✕ Remove</button>
    </div>
    <div class="opt-header-row" style="margin-top:8px">
      <span>Value</span><span>Extra Price (৳)</span><span></span>
    </div>
    <div class="ovs" id="ov${i}">
      <div class="opt-val-row">
        <input type="text" class="ai val-inp" placeholder="e.g. 1 ফুট">
        <input type="number" class="ai price-inp" placeholder="0" min="0" step="0.01">
        <button type="button" class="brv" onclick="this.parentElement.remove()">✕</button>
      </div>
    </div>
    <button type="button" class="bav" onclick="addVal(${i})">+ Add value</button>`;
  document.getElementById('optContainer').appendChild(d);
}

function removeOpt(btn){btn.closest('.og').remove();}

function addVal(i){
  const c=document.getElementById('ov'+i);
  const r=document.createElement('div');r.className='opt-val-row';
  r.innerHTML=`<input type="text" class="ai val-inp" placeholder="Enter value"><input type="number" class="ai price-inp" placeholder="0" min="0" step="0.01"><button type="button" class="brv" onclick="this.parentElement.remove()">✕</button>`;
  c.appendChild(r);
}

function prepSub(e){
  e.preventDefault();
  calcOfferPrice();
  const opts=[];
  document.querySelectorAll('.og').forEach(g=>{
    const n=g.querySelector('.on-i')?.value?.trim();if(!n)return;
    const reqCb=g.querySelector('.req-i');
    const is_required=reqCb&&reqCb.checked?1:0;
    const vals=Array.from(g.querySelectorAll('.opt-val-row')).map(row=>{
      const val=row.querySelector('.val-inp')?.value?.trim()||'';
      const price=parseFloat(row.querySelector('.price-inp')?.value||'0')||0;
      return val?{val,price}:null;
    }).filter(Boolean);
    opts.push({name:n,is_required,values:vals});
  });
  document.getElementById('optData').value=JSON.stringify(opts);
  const prices=newFiles.filter(i=>!i.removed).map(i=>i.price||'');
  document.getElementById('imgPrices').value=JSON.stringify(prices);
  const container=document.getElementById('imgFilesContainer');container.innerHTML='';
  const validFiles=newFiles.filter(i=>!i.removed);
  if(validFiles.length>0){
    try{const dt=new DataTransfer();validFiles.forEach(item=>dt.items.add(item.file));const fi=document.createElement('input');fi.type='file';fi.name='<?= $product?'new_images[]':'images[]' ?>';fi.multiple=true;fi.style.display='none';fi.files=dt.files;container.appendChild(fi);}
    catch(ex){console.warn(ex);}
  }
  document.getElementById('pf').submit();
}
</script>

<?php admin_foot(); ?>