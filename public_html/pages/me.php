<?php
require_once __DIR__ . '/../templates/layout.php';

$u = rz_get_user();

$contact_persons = json_decode($cfg['contact_persons']    ?? '[]', true) ?: [];
$contact_wp      = json_decode($cfg['contact_wp_numbers'] ?? '[]', true) ?: [];
$contact_emails  = json_decode($cfg['contact_emails']     ?? '[]', true) ?: [];
$social_media    = json_decode($cfg['social_media']       ?? '[]', true) ?: [];

render_head('Info — ' . ($cfg['site_name'] ?? 'Raqizone'), $cfg);
?>

<div class="page">
  <div class="sbar">
    <a href="/home" class="bk"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg></a>
    <span class="st" data-bn="তথ্য" data-en="Info">Info</span>
  </div>

  <div class="mw">
    <div class="pcd">
      <?php if ($u): ?>
      <div class="pav"><?= strtoupper(mb_substr($u['name'], 0, 1)) ?></div>
      <p class="pnm"><?= htmlspecialchars($u['name']) ?></p>
      <p class="pmb2">📱 <?= htmlspecialchars($u['mobile']) ?></p>
      <?php else: ?>
      <div class="pav" style="background:var(--bdr2);color:var(--gray)">👤</div>
      <p class="pnm" style="color:var(--gray)" data-bn="অতিথি ব্যবহারকারী" data-en="Guest User">Guest User</p>
      <p class="pmb2" data-bn="লগিন করলে সব সুবিধা পাবেন" data-en="Login for full access">Login for full access</p>
      <div style="display:flex;gap:9px;margin-top:12px;width:100%">
        <button onclick="om('ml')" class="bg" style="font-size:.82rem;padding:10px 14px">🔐 Login</button>
        <button onclick="om('mr')" class="bo" style="font-size:.82rem;padding:10px 14px">✨ Register</button>
      </div>
      <?php endif; ?>
    </div>

    <div class="mlit">
      <a href="/orders" class="mei"><span class="mico">📦</span><div class="mtx"><span class="mtt" data-bn="আমার অর্ডার" data-en="My Orders">My Orders</span><span class="mts" data-bn="সব অর্ডার দেখুন" data-en="View all orders">View all orders</span></div><span class="mar">›</span></a>
      <a href="/cart" class="mei"><span class="mico">🛒</span><div class="mtx"><span class="mtt" data-bn="আমার কার্ট" data-en="My Cart">My Cart</span><span class="mts" data-bn="কার্টের পণ্য" data-en="View cart">View cart</span></div><span class="mar">›</span></a>
      <a href="/updates" class="mei"><span class="mico">📢</span><div class="mtx"><span class="mtt" data-bn="আপডেটস" data-en="Updates">Updates</span><span class="mts" data-bn="নতুন পোস্ট ও অফার" data-en="Latest posts & offers">Latest posts & offers</span></div><span class="mar">›</span></a>

      <!-- Language -->
      <p class="msl" data-bn="ভাষা" data-en="Language">Language</p>
      <div class="mei" style="cursor:default">
        <span class="mico">🌐</span>
        <div class="mtx"><span class="mtt" data-bn="ভাষা নির্বাচন" data-en="Select Language">Select Language</span></div>
        <div style="display:flex;gap:7px">
          <button class="lang-btn lang-btn-item" data-lang="bn" onclick="setLang('bn')" style="padding:5px 12px;border-radius:50px;font-size:.78rem;font-weight:600;cursor:pointer;border:2px solid var(--bdr2);background:transparent;color:var(--gray);font-family:inherit">বাংলা</button>
          <button class="lang-btn lang-btn-item" data-lang="en" onclick="setLang('en')" style="padding:5px 12px;border-radius:50px;font-size:.78rem;font-weight:600;cursor:pointer;border:2px solid var(--bdr2);background:transparent;color:var(--gray);font-family:inherit">English</button>
        </div>
      </div>

      <!-- Contact -->
      <?php if ($contact_persons || $contact_wp || $contact_emails): ?>
      <p class="msl" data-bn="যোগাযোগ করুন" data-en="Contact Us">Contact Us</p>
      <?php if ($contact_persons): ?>
      <div class="mei" style="cursor:pointer" onclick="openContact('call')">
        <span class="mico">📞</span>
        <div class="mtx"><span class="mtt" data-bn="ফোন করুন" data-en="Call Us">Call Us</span><span class="mts"><?= count($contact_persons) ?> contact<?= count($contact_persons)>1?'s':'' ?></span></div>
        <span class="mar">›</span>
      </div>
      <?php endif; ?>
      <?php if ($contact_wp): ?>
      <div class="mei" style="cursor:pointer" onclick="openContact('wp')">
        <span class="mico">💬</span>
        <div class="mtx"><span class="mtt">WhatsApp</span><span class="mts"><?= count($contact_wp) ?> contact<?= count($contact_wp)>1?'s':'' ?></span></div>
        <span class="mar">›</span>
      </div>
      <?php endif; ?>
      <?php if ($contact_emails): ?>
      <div class="mei" style="cursor:pointer" onclick="openContact('email')">
        <span class="mico">📧</span>
        <div class="mtx"><span class="mtt" data-bn="ইমেইল করুন" data-en="Email Us">Email Us</span><span class="mts"><?= count($contact_emails) ?> address<?= count($contact_emails)>1?'es':'' ?></span></div>
        <span class="mar">›</span>
      </div>
      <?php endif; ?>
      <?php if (!empty($cfg['contact_facebook'])): ?>
      <a href="<?= htmlspecialchars($cfg['contact_facebook']) ?>" target="_blank" class="mei"><span class="mico">📘</span><div class="mtx"><span class="mtt">Facebook</span><span class="mts" data-bn="আমাদের পেজে যান" data-en="Visit our page">Visit our page</span></div><span class="mar">›</span></a>
      <?php endif; ?>
      <?php endif; ?>

      <!-- Social Media -->
      <?php if ($social_media): ?>
      <p class="msl" data-bn="সোশ্যাল মিডিয়া" data-en="Social Media">Social Media</p>
      <?php foreach ($social_media as $sm): if (!($sm['link']??'')) continue; ?>
      <a href="<?= htmlspecialchars($sm['link']) ?>" target="_blank" class="mei">
        <?php if (!empty($sm['icon'])): ?>
        <img src="<?= htmlspecialchars($sm['icon']) ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover;flex-shrink:0">
        <?php else: ?><span class="mico">📱</span><?php endif; ?>
        <div class="mtx"><span class="mtt"><?= htmlspecialchars($sm['name']) ?></span></div>
        <span class="mar">›</span>
      </a>
      <?php endforeach; ?>
      <?php endif; ?>

      <!-- Info & Terms -->
      <?php if ($cfg['about_us'] || $cfg['terms_and_conditions'] || $cfg['return_policy'] || $cfg['extra_info']): ?>
      <p class="msl" data-bn="তথ্য ও শর্তাবলী" data-en="Info & Terms">Info & Terms</p>
      <?php foreach ([['about_us','ℹ️','আমাদের সম্পর্কে','About Us'],['terms_and_conditions','📋','শর্তাবলী','Terms'],['return_policy','🔄','রিটার্ন পলিসি','Return Policy'],['extra_info','📌','অতিরিক্ত তথ্য','Extra Info']] as [$key,$ico,$bn,$en]): ?>
      <?php if (!empty($cfg[$key])): ?>
      <div class="mei" style="flex-direction:column;align-items:flex-start;gap:8px;cursor:default">
        <div style="display:flex;align-items:center;gap:11px;width:100%"><span class="mico"><?= $ico ?></span><span class="mtt" data-bn="<?= $bn ?>" data-en="<?= $en ?>"><?= $en ?></span></div>
        <div class="info-box"><p><?= nl2br(htmlspecialchars($cfg[$key])) ?></p></div>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($u): ?><a href="/logout" class="blo" data-bn="লগআউট করুন" data-en="Logout">Logout</a><?php endif; ?>
    <div style="height:20px"></div>
  </div>
</div>

<!-- Contact Popup -->
<div id="contactOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:900;align-items:flex-end;justify-content:center;backdrop-filter:blur(6px)" onclick="if(event.target===this)closeContact()">
  <div style="background:var(--k2);border-radius:20px 20px 0 0;border-top:2px solid var(--g);width:100%;max-width:500px;max-height:80vh;overflow-y:auto;padding-bottom:32px">
    <div style="width:36px;height:3px;background:var(--bdr2);border-radius:2px;margin:10px auto 0"></div>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--bdr)">
      <h3 id="contactTitle" style="font-size:.97rem;font-weight:700;color:var(--g)"></h3>
      <button onclick="closeContact()" style="background:var(--k3);border:none;color:var(--gray);width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:.82rem">✕</button>
    </div>
    <div id="contactList" style="padding:12px 16px;display:flex;flex-direction:column;gap:10px"></div>
  </div>
</div>

<!-- Login Modals -->
<div class="overlay" id="ml">
  <div class="modal" style="position:relative">
    <button class="mc" onclick="cm('ml')">✕</button>
    <div class="mt"><span class="ic">🔐</span><h3>Login</h3></div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/me">
      <input type="text" name="name" class="inp" placeholder="Full name" required>
      <input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required>
      <button type="submit" class="bg">Login →</button>
    </form>
  </div>
</div>
<div class="overlay" id="mr">
  <div class="modal" style="position:relative">
    <button class="mc" onclick="cm('mr')">✕</button>
    <div class="mt"><span class="ic">✨</span><h3>Register</h3></div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/me">
      <input type="text" name="name" class="inp" placeholder="Full name" required>
      <input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required>
      <button type="submit" class="bg">Create Account ✨</button>
    </form>
  </div>
</div>

<script>
var CONTACTS = {
  call:  <?= json_encode($contact_persons, JSON_UNESCAPED_UNICODE) ?>,
  wp:    <?= json_encode($contact_wp,      JSON_UNESCAPED_UNICODE) ?>,
  email: <?= json_encode($contact_emails,  JSON_UNESCAPED_UNICODE) ?>
};

function openContact(type) {
  var overlay = document.getElementById('contactOverlay');
  var title   = document.getElementById('contactTitle');
  var list    = document.getElementById('contactList');
  var lang    = (typeof _getLang === 'function') ? _getLang() : 'en';

  var titles = {
    call:  lang === 'bn' ? '📞 ফোন করুন' : '📞 Call Us',
    wp:    '💬 WhatsApp',
    email: lang === 'bn' ? '📧 ইমেইল করুন' : '📧 Email Us'
  };
  title.textContent = titles[type] || '';

  var items = CONTACTS[type] || [];
  var html  = '';
  items.forEach(function(c) {
    var href = '', icon = '', val = '';
    if (type === 'call')  { href = 'tel:' + c.phone;             icon = '📞'; val = c.phone; }
    if (type === 'wp')    { href = 'https://wa.me/' + c.num;     icon = '💬'; val = c.num; }
    if (type === 'email') { href = 'mailto:' + c.email;          icon = '📧'; val = c.email; }

    html += '<a href="' + href + '" style="display:flex;align-items:center;gap:13px;background:var(--k3);border:1px solid var(--bdr2);border-radius:var(--r);padding:12px 14px;text-decoration:none;color:inherit;transition:border-color .2s" onclick="closeContact()">';
    if (c.photo) {
      html += '<img src="' + c.photo + '" style="width:50px;height:50px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid var(--g)">';
    } else {
      html += '<div style="width:50px;height:50px;border-radius:50%;background:var(--gl);border:2px solid var(--g);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0">' + icon + '</div>';
    }
    html += '<div style="flex:1;min-width:0">';
    if (c.name)  html += '<p style="font-weight:700;font-size:.9rem;margin-bottom:1px">' + c.name + '</p>';
    if (c.title) html += '<p style="font-size:.74rem;color:var(--g);margin-bottom:3px;font-weight:600">' + c.title + '</p>';
    html += '<p style="font-size:.8rem;color:var(--gray)">' + val + '</p>';
    html += '</div><span style="color:var(--g);font-size:1.2rem;flex-shrink:0">' + icon + '</span>';
    html += '</a>';
  });

  if (!html) html = '<p style="color:var(--gray);text-align:center;padding:24px">No contacts available</p>';
  list.innerHTML = html;
  overlay.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeContact() {
  document.getElementById('contactOverlay').style.display = 'none';
  document.body.style.overflow = '';
}

function om(id){document.getElementById(id).classList.add('show');document.body.style.overflow='hidden';}
function cm(id){document.getElementById(id).classList.remove('show');document.body.style.overflow='';}
document.querySelectorAll('.overlay').forEach(function(el){el.addEventListener('click',function(e){if(e.target===this)cm(this.id);});});
</script>

<?php render_nav('me'); render_foot(); ?>