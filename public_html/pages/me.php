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
      <p class="pmb2" style="display:flex;align-items:center;justify-content:center;gap:5px"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg><?= htmlspecialchars($u['mobile']) ?></p>
      <?php else: ?>
      <div class="pav" style="background:var(--bdr2);color:var(--gray)"><svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
      <p class="pnm" style="color:var(--gray)" data-bn="অতিথি ব্যবহারকারী" data-en="Guest User">Guest User</p>
      <p class="pmb2" data-bn="লগিন করলে সব সুবিধা পাবেন" data-en="Login for full access">Login for full access</p>
      <div style="display:flex;gap:9px;margin-top:12px;width:100%">
        <button onclick="om('ml')" class="bg" style="font-size:.82rem;padding:10px 14px;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor;flex-shrink:0"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>Login</button>
        <button onclick="om('mr')" class="bo" style="font-size:.82rem;padding:10px 14px;gap:6px"><svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor;flex-shrink:0"><path d="M15 14c-2.67 0-8 1.33-8 4v2h16v-2c0-2.67-5.33-4-8-4zm-8.94-6H4v2h2.06v2.06h2V10H10V8H8.06V5.94h-2V8zM15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/></svg>Register</button>
      </div>
      <?php endif; ?>
    </div>

    <div class="mlit">
      <a href="/orders" class="mei"><span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg></span><div class="mtx"><span class="mtt" data-bn="আমার অর্ডার" data-en="My Orders">My Orders</span><span class="mts" data-bn="সব অর্ডার দেখুন" data-en="View all orders">View all orders</span></div><span class="mar">›</span></a>
      <a href="/cart" class="mei"><span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.84 15h8.45l2.21-4.5H6.21L5.27 6H2v2h2.14l3.36 7.03L6.25 17H19v-2H7.84z"/></svg></span><div class="mtx"><span class="mtt" data-bn="আমার কার্ট" data-en="My Cart">My Cart</span><span class="mts" data-bn="কার্টের পণ্য" data-en="View cart">View cart</span></div><span class="mar">›</span></a>
      <a href="/updates" class="mei"><span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg></span><div class="mtx"><span class="mtt" data-bn="আপডেটস" data-en="Updates">Updates</span><span class="mts" data-bn="নতুন পোস্ট ও অফার" data-en="Latest posts & offers">Latest posts & offers</span></div><span class="mar">›</span></a>

      <!-- Language -->
      <p class="msl" data-bn="ভাষা" data-en="Language">Language</p>
      <div class="mei" style="cursor:default">
        <span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg></span>
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
        <span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg></span>
        <div class="mtx"><span class="mtt" data-bn="ফোন করুন" data-en="Call Us">Call Us</span><span class="mts"><?= count($contact_persons) ?> contact<?= count($contact_persons)>1?'s':'' ?></span></div>
        <span class="mar">›</span>
      </div>
      <?php endif; ?>
      <?php if ($contact_wp): ?>
      <div class="mei" style="cursor:pointer" onclick="openContact('wp')">
        <span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:#25D366"><path d="M17.6 6.32A8.86 8.86 0 0 0 12.05 4a8.94 8.94 0 0 0-7.7 13.46L3 21l3.65-1.32a8.9 8.9 0 0 0 4.27 1.09h.02a8.94 8.94 0 0 0 6.66-14.45zm-5.55 13.7a7.4 7.4 0 0 1-3.79-1.03l-.27-.16-2.85 1.04.95-2.82-.18-.28a7.45 7.45 0 0 1 11.65-9.06 7.4 7.4 0 0 1 2.18 5.27 7.46 7.46 0 0 1-7.45 7.46zm4.09-5.59c-.22-.11-1.32-.65-1.52-.73-.2-.07-.36-.11-.5.11-.15.22-.58.73-.71.88-.13.15-.26.16-.49.05-.22-.11-.95-.35-1.81-1.12-.67-.6-1.12-1.34-1.25-1.56-.13-.22-.01-.34.11-.46.11-.11.24-.28.36-.42.13-.14.17-.24.26-.4.08-.16.04-.29-.02-.41-.07-.11-.62-1.49-.85-2.04-.22-.53-.45-.46-.62-.47h-.53c-.18 0-.46.07-.7.33-.24.27-.93.91-.93 2.21s.95 2.57 1.08 2.75c.13.18 1.82 2.79 4.45 3.81 2.62 1.02 2.62.68 3.09.64.47-.04 1.32-.54 1.5-1.06.19-.52.19-.97.13-1.06-.05-.1-.22-.16-.45-.27z"/></svg></span>
        <div class="mtx"><span class="mtt">WhatsApp</span><span class="mts"><?= count($contact_wp) ?> contact<?= count($contact_wp)>1?'s':'' ?></span></div>
        <span class="mar">›</span>
      </div>
      <?php endif; ?>
      <?php if ($contact_emails): ?>
      <div class="mei" style="cursor:pointer" onclick="openContact('email')">
        <span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg></span>
        <div class="mtx"><span class="mtt" data-bn="ইমেইল করুন" data-en="Email Us">Email Us</span><span class="mts"><?= count($contact_emails) ?> address<?= count($contact_emails)>1?'es':'' ?></span></div>
        <span class="mar">›</span>
      </div>
      <?php endif; ?>
      <?php if (!empty($cfg['contact_facebook'])): ?>
      <a href="<?= htmlspecialchars($cfg['contact_facebook']) ?>" target="_blank" class="mei"><span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:#1877F2"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg></span><div class="mtx"><span class="mtt">Facebook</span><span class="mts" data-bn="আমাদের পেজে যান" data-en="Visit our page">Visit our page</span></div><span class="mar">›</span></a>
      <?php endif; ?>
      <?php endif; ?>

      <!-- Social Media -->
      <?php if ($social_media): ?>
      <p class="msl" data-bn="সোশ্যাল মিডিয়া" data-en="Social Media">Social Media</p>
      <?php foreach ($social_media as $sm): if (!($sm['link']??'')) continue; ?>
      <a href="<?= htmlspecialchars($sm['link']) ?>" target="_blank" class="mei">
        <?php if (!empty($sm['icon'])): ?>
        <img src="<?= htmlspecialchars($sm['icon']) ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover;flex-shrink:0">
        <?php else: ?><span class="mico"><svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L7.05 9.81C6.5 9.31 5.8 9 5 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.8 0 1.5-.31 2.05-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg></span><?php endif; ?>
        <div class="mtx"><span class="mtt"><?= htmlspecialchars($sm['name']) ?></span></div>
        <span class="mar">›</span>
      </a>
      <?php endforeach; ?>
      <?php endif; ?>

      <!-- Info & Terms -->
      <?php if ($cfg['about_us'] || $cfg['terms_and_conditions'] || $cfg['return_policy'] || $cfg['extra_info']): ?>
      <p class="msl" data-bn="তথ্য ও শর্তাবলী" data-en="Info & Terms">Info & Terms</p>
      <?php
      $infoIcons = [
        'about_us' => '<svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>',
        'terms_and_conditions' => '<svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>',
        'return_policy' => '<svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>',
        'extra_info' => '<svg viewBox="0 0 24 24" style="width:21px;height:21px;fill:var(--g)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm1-13h-2v6h2zm0 8h-2v2h2z"/></svg>',
      ];
      $infoLabels = [
        'about_us' => ['আমাদের সম্পর্কে', 'About Us'],
        'terms_and_conditions' => ['শর্তাবলী', 'Terms'],
        'return_policy' => ['রিটার্ন পলিসি', 'Return Policy'],
        'extra_info' => ['অতিরিক্ত তথ্য', 'Extra Info'],
      ];
      foreach ($infoLabels as $key => [$bn, $en]):
      ?>
      <?php if (!empty($cfg[$key])): ?>
      <div class="mei" style="flex-direction:column;align-items:flex-start;gap:8px;cursor:default">
        <div style="display:flex;align-items:center;gap:11px;width:100%"><span class="mico"><?= $infoIcons[$key] ?></span><span class="mtt" data-bn="<?= $bn ?>" data-en="<?= $en ?>"><?= $en ?></span></div>
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
      <h3 id="contactTitle" style="font-size:.97rem;font-weight:700;color:var(--g);display:flex;align-items:center;gap:7px"></h3>
      <button onclick="closeContact()" style="background:var(--k3);border:none;color:var(--gray);width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:.82rem">✕</button>
    </div>
    <div id="contactList" style="padding:12px 16px;display:flex;flex-direction:column;gap:10px"></div>
  </div>
</div>

<!-- Login Modals -->
<div class="overlay" id="ml">
  <div class="modal" style="position:relative">
    <button class="mc" onclick="cm('ml')">✕</button>
    <div class="mt"><span class="ic"><svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:var(--g)"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg></span><h3>Login</h3></div>
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
    <div class="mt"><span class="ic"><svg viewBox="0 0 24 24" style="width:32px;height:32px;fill:var(--g)"><path d="M15 14c-2.67 0-8 1.33-8 4v2h16v-2c0-2.67-5.33-4-8-4zm-8.94-6H4v2h2.06v2.06h2V10H10V8H8.06V5.94h-2V8zM15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/></svg></span><h3>Register</h3></div>
    <form action="/auth/login" method="POST" class="fs">
      <input type="hidden" name="next" value="/me">
      <input type="text" name="name" class="inp" placeholder="Full name" required>
      <input type="tel" name="mobile" class="inp" placeholder="01XXXXXXXXX" required>
      <button type="submit" class="bg">Create Account →</button>
    </form>
  </div>
</div>

<script>
var CONTACTS = {
  call:  <?= json_encode($contact_persons, JSON_UNESCAPED_UNICODE) ?>,
  wp:    <?= json_encode($contact_wp,      JSON_UNESCAPED_UNICODE) ?>,
  email: <?= json_encode($contact_emails,  JSON_UNESCAPED_UNICODE) ?>
};

var ICONS = {
  call:  '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
  wp:    '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:#25D366"><path d="M17.6 6.32A8.86 8.86 0 0 0 12.05 4a8.94 8.94 0 0 0-7.7 13.46L3 21l3.65-1.32a8.9 8.9 0 0 0 4.27 1.09h.02a8.94 8.94 0 0 0 6.66-14.45zm-5.55 13.7a7.4 7.4 0 0 1-3.79-1.03l-.27-.16-2.85 1.04.95-2.82-.18-.28a7.45 7.45 0 0 1 11.65-9.06 7.4 7.4 0 0 1 2.18 5.27 7.46 7.46 0 0 1-7.45 7.46zm4.09-5.59c-.22-.11-1.32-.65-1.52-.73-.2-.07-.36-.11-.5.11-.15.22-.58.73-.71.88-.13.15-.26.16-.49.05-.22-.11-.95-.35-1.81-1.12-.67-.6-1.12-1.34-1.25-1.56-.13-.22-.01-.34.11-.46.11-.11.24-.28.36-.42.13-.14.17-.24.26-.4.08-.16.04-.29-.02-.41-.07-.11-.62-1.49-.85-2.04-.22-.53-.45-.46-.62-.47h-.53c-.18 0-.46.07-.7.33-.24.27-.93.91-.93 2.21s.95 2.57 1.08 2.75c.13.18 1.82 2.79 4.45 3.81 2.62 1.02 2.62.68 3.09.64.47-.04 1.32-.54 1.5-1.06.19-.52.19-.97.13-1.06-.05-.1-.22-.16-.45-.27z"/></svg>',
  email: '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>'
};

function openContact(type) {
  var overlay = document.getElementById('contactOverlay');
  var title   = document.getElementById('contactTitle');
  var list    = document.getElementById('contactList');
  var lang    = (typeof _getLang === 'function') ? _getLang() : 'en';

  var titleTexts = {
    call:  lang === 'bn' ? 'ফোন করুন' : 'Call Us',
    wp:    'WhatsApp',
    email: lang === 'bn' ? 'ইমেইল করুন' : 'Email Us'
  };
  title.innerHTML = ICONS[type] + ' ' + (titleTexts[type] || '');

  var items = CONTACTS[type] || [];
  var html  = '';
  items.forEach(function(c) {
    var href = '', val = '';
    if (type === 'call')  { href = 'tel:' + c.phone;             val = c.phone; }
    if (type === 'wp')    { href = 'https://wa.me/' + c.num;     val = c.num; }
    if (type === 'email') { href = 'mailto:' + c.email;          val = c.email; }

    html += '<a href="' + href + '" style="display:flex;align-items:center;gap:13px;background:var(--k3);border:1px solid var(--bdr2);border-radius:var(--r);padding:12px 14px;text-decoration:none;color:inherit;transition:border-color .2s" onclick="closeContact()">';
    if (c.photo) {
      html += '<img src="' + c.photo + '" style="width:50px;height:50px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid var(--g)">';
    } else {
      html += '<div style="width:50px;height:50px;border-radius:50%;background:var(--gl);border:2px solid var(--g);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--g)">' + ICONS[type].replace('width:16px;height:16px','width:24px;height:24px') + '</div>';
    }
    html += '<div style="flex:1;min-width:0">';
    if (c.name)  html += '<p style="font-weight:700;font-size:.9rem;margin-bottom:1px">' + c.name + '</p>';
    if (c.title) html += '<p style="font-size:.74rem;color:var(--g);margin-bottom:3px;font-weight:600">' + c.title + '</p>';
    html += '<p style="font-size:.8rem;color:var(--gray)">' + val + '</p>';
    html += '</div><span style="color:var(--g);flex-shrink:0">' + ICONS[type] + '</span>';
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