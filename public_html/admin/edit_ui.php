<?php
require_once __DIR__ . '/base.php';

$section = $_GET['section'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sec = $_POST['_section'] ?? '';

    if ($sec === 'logo') {
        if (!empty($_FILES['site_logo']['name']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $url = upload_image($_FILES['site_logo'], 'logo', 'logos');
            if ($url) save_setting('site_logo', $url);
        }
        if (!empty($_FILES['site_favicon']['name']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/x-icon','image/png','image/jpeg','image/gif','image/webp'];
            if (in_array($_FILES['site_favicon']['type'], $allowed)) {
                $ext = strtolower(pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION));
                move_uploaded_file($_FILES['site_favicon']['tmp_name'], __DIR__ . '/../favicon.' . $ext);
                save_setting('site_favicon_ext', $ext);
            }
        }
        save_setting('site_logo_text', trim($_POST['site_logo_text'] ?? ''));
        save_setting('site_name',      trim($_POST['site_name']      ?? ''));
    }

    elseif ($sec === 'seo') {
        save_setting('meta_description', trim($_POST['meta_description'] ?? ''));
        save_setting('meta_keywords',    trim($_POST['meta_keywords']    ?? ''));
        save_setting('welcome_subtitle', trim($_POST['welcome_subtitle'] ?? ''));
        save_setting('home_tagline',     trim($_POST['home_tagline']     ?? ''));
    }

    elseif ($sec === 'theme') {
        save_setting('site_theme', trim($_POST['site_theme'] ?? 'golden'));
    }

    elseif ($sec === 'banner') {
        // Mobile banners
        $mb = json_decode($_POST['existing_mobile_banners'] ?? '[]', true) ?: [];
        $fm = $_FILES['new_mobile_banner_images'] ?? [];
        if (!empty($fm['name'][0])) {
            for ($i = 0; $i < count($fm['name']); $i++) {
                if (($fm['error'][$i] ?? 99) !== UPLOAD_ERR_OK) continue;
                $f = ['name'=>$fm['name'][$i],'type'=>$fm['type'][$i],'tmp_name'=>$fm['tmp_name'][$i],'error'=>$fm['error'][$i],'size'=>$fm['size'][$i]];
                $u = upload_image($f,'mbnr','banners'); if ($u) $mb[] = $u;
            }
        }
        // Desktop banners
        $db2 = json_decode($_POST['existing_desktop_banners'] ?? '[]', true) ?: [];
        $fd2 = $_FILES['new_desktop_banner_images'] ?? [];
        if (!empty($fd2['name'][0])) {
            for ($i = 0; $i < count($fd2['name']); $i++) {
                if (($fd2['error'][$i] ?? 99) !== UPLOAD_ERR_OK) continue;
                $f = ['name'=>$fd2['name'][$i],'type'=>$fd2['type'][$i],'tmp_name'=>$fd2['tmp_name'][$i],'error'=>$fd2['error'][$i],'size'=>$fd2['size'][$i]];
                $u = upload_image($f,'dbnr','banners'); if ($u) $db2[] = $u;
            }
        }
        // Mobile BG
        if (!empty($_FILES['mobile_bg_image']['name']) && $_FILES['mobile_bg_image']['error'] === UPLOAD_ERR_OK) {
            $u = upload_image($_FILES['mobile_bg_image'],'mbg','backgrounds');
            if ($u) save_setting('mobile_bg_image', $u);
        }
        if (isset($_POST['clear_mobile_bg'])) save_setting('mobile_bg_image', '');
        // Desktop BG
        if (!empty($_FILES['desktop_bg_image']['name']) && $_FILES['desktop_bg_image']['error'] === UPLOAD_ERR_OK) {
            $u = upload_image($_FILES['desktop_bg_image'],'dbg','backgrounds');
            if ($u) save_setting('desktop_bg_image', $u);
        }
        if (isset($_POST['clear_desktop_bg'])) save_setting('desktop_bg_image', '');

        save_setting('mobile_banners',   json_encode($mb,  JSON_UNESCAPED_UNICODE));
        save_setting('desktop_banners',  json_encode($db2, JSON_UNESCAPED_UNICODE));
        save_setting('banners',          json_encode($mb,  JSON_UNESCAPED_UNICODE));
        save_setting('banner_animation', trim($_POST['banner_animation'] ?? 'slide'));
    }

    elseif ($sec === 'categories') {
        $cats = json_decode($_POST['product_categories_json'] ?? '[]', true) ?: [];
        save_setting('product_categories', json_encode($cats, JSON_UNESCAPED_UNICODE));
    }

    elseif ($sec === 'identity') {
        save_setting('site_name',        trim($_POST['site_name']        ?? ''));
        save_setting('welcome_title',    trim($_POST['welcome_title']    ?? ''));
        save_setting('welcome_subtitle', trim($_POST['welcome_subtitle'] ?? ''));
        save_setting('home_tagline',     trim($_POST['home_tagline']     ?? ''));
        save_setting('footer_note',      trim($_POST['footer_note']      ?? ''));
    }

    elseif ($sec === 'delivery') {
        save_setting('delivery_charge_default', trim($_POST['delivery_charge_default'] ?? '60'));
        save_setting('payment_options',         trim($_POST['payment_options']         ?? 'cod'));
        save_setting('bkash_number',            trim($_POST['bkash_number']            ?? ''));
        save_setting('nagad_number',            trim($_POST['nagad_number']            ?? ''));
        save_setting('bkash_app_key',           trim($_POST['bkash_app_key']           ?? ''));
        save_setting('bkash_app_secret',        trim($_POST['bkash_app_secret']        ?? ''));
    }

    elseif ($sec === 'contact') {
        // Phone contacts
        $persons   = [];
        $cp_names  = $_POST['cp_name']   ?? [];
        $cp_titles = $_POST['cp_title']  ?? [];
        $cp_phones = $_POST['cp_phone']  ?? [];
        $cp_orders = $_POST['cp_order']  ?? [];
        $cp_exists = $_POST['cp_existing_photo'] ?? [];
        $cp_files  = $_FILES['cp_photo'] ?? [];
        $count     = max(count($cp_names), count($cp_phones));
        for ($i = 0; $i < $count; $i++) {
            $nm = trim($cp_names[$i] ?? '');
            $ph = trim($cp_phones[$i] ?? '');
            if (!$nm && !$ph) continue;
            $photo = $cp_exists[$i] ?? '';
            if (!empty($cp_files['name'][$i]) && ($cp_files['error'][$i] ?? 99) === UPLOAD_ERR_OK) {
                $f = ['name'=>$cp_files['name'][$i],'type'=>$cp_files['type'][$i],'tmp_name'=>$cp_files['tmp_name'][$i],'error'=>$cp_files['error'][$i],'size'=>$cp_files['size'][$i]];
                $u = upload_image($f, 'cp'.$i, 'logos'); if ($u) $photo = $u;
            }
            $persons[] = ['name'=>$nm,'title'=>trim($cp_titles[$i]??''),'phone'=>$ph,'photo'=>$photo,'order'=>(int)($cp_orders[$i]??$i)];
        }
        usort($persons, fn($a,$b) => $a['order'] <=> $b['order']);
        save_setting('contact_persons', json_encode($persons, JSON_UNESCAPED_UNICODE));

        // WhatsApp contacts
        $wpList    = [];
        $wp_names  = $_POST['wp_name']   ?? [];
        $wp_titles = $_POST['wp_title']  ?? [];
        $wp_nums   = $_POST['wp_num']    ?? [];
        $wp_orders = $_POST['wp_order']  ?? [];
        $wp_exists = $_POST['wp_existing_photo'] ?? [];
        $wp_files  = $_FILES['wp_photo'] ?? [];
        $count2    = max(count($wp_names), count($wp_nums));
        for ($i = 0; $i < $count2; $i++) {
            $nm = trim($wp_names[$i] ?? '');
            $nu = trim($wp_nums[$i]  ?? '');
            if (!$nm && !$nu) continue;
            $photo = $wp_exists[$i] ?? '';
            if (!empty($wp_files['name'][$i]) && ($wp_files['error'][$i] ?? 99) === UPLOAD_ERR_OK) {
                $f = ['name'=>$wp_files['name'][$i],'type'=>$wp_files['type'][$i],'tmp_name'=>$wp_files['tmp_name'][$i],'error'=>$wp_files['error'][$i],'size'=>$wp_files['size'][$i]];
                $u = upload_image($f, 'wp'.$i, 'logos'); if ($u) $photo = $u;
            }
            $wpList[] = ['name'=>$nm,'title'=>trim($wp_titles[$i]??''),'num'=>$nu,'photo'=>$photo,'order'=>(int)($wp_orders[$i]??$i)];
        }
        usort($wpList, fn($a,$b) => $a['order'] <=> $b['order']);
        save_setting('contact_wp_numbers', json_encode($wpList, JSON_UNESCAPED_UNICODE));

        // Emails
        $emails    = [];
        $em_names  = $_POST['em_name']   ?? [];
        $em_mails  = $_POST['em_email']  ?? [];
        $em_orders = $_POST['em_order']  ?? [];
        $em_exists = $_POST['em_existing_photo'] ?? [];
        $em_files  = $_FILES['em_photo'] ?? [];
        $count3    = max(count($em_names), count($em_mails));
        for ($i = 0; $i < $count3; $i++) {
            $nm = trim($em_names[$i] ?? '');
            $em = trim($em_mails[$i] ?? '');
            if (!$nm && !$em) continue;
            $photo = $em_exists[$i] ?? '';
            if (!empty($em_files['name'][$i]) && ($em_files['error'][$i] ?? 99) === UPLOAD_ERR_OK) {
                $f = ['name'=>$em_files['name'][$i],'type'=>$em_files['type'][$i],'tmp_name'=>$em_files['tmp_name'][$i],'error'=>$em_files['error'][$i],'size'=>$em_files['size'][$i]];
                $u = upload_image($f, 'em'.$i, 'logos'); if ($u) $photo = $u;
            }
            $emails[] = ['name'=>$nm,'email'=>$em,'photo'=>$photo,'order'=>(int)($em_orders[$i]??$i)];
        }
        usort($emails, fn($a,$b) => $a['order'] <=> $b['order']);
        save_setting('contact_emails',   json_encode($emails, JSON_UNESCAPED_UNICODE));
        save_setting('contact_facebook', trim($_POST['contact_facebook'] ?? ''));
        save_setting('contact_website',  trim($_POST['contact_website']  ?? ''));
    }

    elseif ($sec === 'social') {
        $socials   = [];
        $sm_names  = $_POST['sm_name']   ?? [];
        $sm_links  = $_POST['sm_link']   ?? [];
        $sm_orders = $_POST['sm_order']  ?? [];
        $sm_exists = $_POST['sm_existing_icon'] ?? [];
        $sm_files  = $_FILES['sm_icon']  ?? [];
        $count     = max(count($sm_names), count($sm_links));
        for ($i = 0; $i < $count; $i++) {
            $nm = trim($sm_names[$i] ?? '');
            $lk = trim($sm_links[$i] ?? '');
            if (!$nm && !$lk) continue;
            $icon = $sm_exists[$i] ?? '';
            if (!empty($sm_files['name'][$i]) && ($sm_files['error'][$i] ?? 99) === UPLOAD_ERR_OK) {
                $f = ['name'=>$sm_files['name'][$i],'type'=>$sm_files['type'][$i],'tmp_name'=>$sm_files['tmp_name'][$i],'error'=>$sm_files['error'][$i],'size'=>$sm_files['size'][$i]];
                $u = upload_image($f, 'sm'.$i, 'logos'); if ($u) $icon = $u;
            }
            $socials[] = ['name'=>$nm,'link'=>$lk,'icon'=>$icon,'order'=>(int)($sm_orders[$i]??$i)];
        }
        usort($socials, fn($a,$b) => $a['order'] <=> $b['order']);
        save_setting('social_media', json_encode($socials, JSON_UNESCAPED_UNICODE));
    }

    elseif ($sec === 'info') {
        save_setting('about_us',             trim($_POST['about_us']             ?? ''));
        save_setting('terms_and_conditions', trim($_POST['terms_and_conditions'] ?? ''));
        save_setting('return_policy',        trim($_POST['return_policy']        ?? ''));
        save_setting('extra_info',           trim($_POST['extra_info']           ?? ''));
    }

    header('Location: /admin/edit-ui?section=' . $sec . '&saved=1'); exit;
}

// Load data
$saved           = isset($_GET['saved']);
$mobile_banners  = json_decode($cfg['mobile_banners']     ?? '[]', true) ?: [];
$desktop_banners = json_decode($cfg['desktop_banners']    ?? '[]', true) ?: [];
$categories      = json_decode($cfg['product_categories'] ?? '[]', true) ?: [];
$contact_persons = json_decode($cfg['contact_persons']    ?? '[]', true) ?: [];
$contact_wp      = json_decode($cfg['contact_wp_numbers'] ?? '[]', true) ?: [];
$contact_emails  = json_decode($cfg['contact_emails']     ?? '[]', true) ?: [];
$social_media    = json_decode($cfg['social_media']       ?? '[]', true) ?: [];
$anim            = $cfg['banner_animation'] ?? 'slide';

// ── SVG icons used across this settings page ──
$ic = [
  'gear'    => '<svg viewBox="0 0 24 24"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65A.488.488 0 0 0 14 2h-4c-.24 0-.43.17-.47.41l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.23.41.47.41h4c.24 0 .44-.17.47-.41l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>',
  'check'   => '<svg viewBox="0 0 24 24"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
  'image'   => '<svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>',
  'search'  => '<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
  'palette' => '<svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>',
  'camera'  => '<svg viewBox="0 0 24 24"><path d="M9.4 10.5l4.77-8.26C13.47 2.09 12.75 2 12 2c-2.4 0-4.6.85-6.32 2.25l3.66 6.35.06-.1zM21.54 9c-.92-2.92-3.15-5.26-6-6.34L11.88 9h9.66zm.26 1h-7.49l.29.5 4.76 8.25C21.07 16.17 22 14.21 22 12c0-.69-.07-1.36-.2-2zM8.54 12l-3.9-6.75C3.01 7.03 2 9.39 2 12c0 .69.07 1.36.2 2h7.49l-1.15-2zm-6.08 3c.92 2.92 3.15 5.26 6 6.34L12.12 15H2.46zm11.27 0-3.9 6.76c.7.15 1.42.24 2.17.24 2.4 0 4.6-.85 6.32-2.25l-2.44-4.75H13.73z"/></svg>',
  'folder'  => '<svg viewBox="0 0 24 24"><path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>',
  'shop'    => '<svg viewBox="0 0 24 24"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg>',
  'truck'   => '<svg viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zM18 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>',
  'phone'   => '<svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
  'mobile'  => '<svg viewBox="0 0 24 24"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg>',
  'desktop' => '<svg viewBox="0 0 24 24"><path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z"/></svg>',
  'doc'     => '<svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>',
  'wp'      => '<svg viewBox="0 0 24 24"><path d="M17.6 6.32A8.86 8.86 0 0 0 12.05 4a8.94 8.94 0 0 0-7.7 13.46L3 21l3.65-1.32a8.9 8.9 0 0 0 4.27 1.09h.02a8.94 8.94 0 0 0 6.66-14.45zm-5.55 13.7a7.4 7.4 0 0 1-3.79-1.03l-.27-.16-2.85 1.04.95-2.82-.18-.28a7.45 7.45 0 0 1 11.65-9.06 7.4 7.4 0 0 1 2.18 5.27 7.46 7.46 0 0 1-7.45 7.46z"/></svg>',
  'mail'    => '<svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>',
  'globe'   => '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>',
  'tag'     => '<svg viewBox="0 0 24 24"><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"/></svg>',
  'person'  => '<svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
  'cash'    => '<svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>',
  'bank'    => '<svg viewBox="0 0 24 24"><path d="M11.5 1 2 6v2h19V6M16 10v7h3v-7M2 22h19v-3H2M6 10v7h3v-7m4.5 0v7h3v-7"/></svg>',
  'card'    => '<svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>',
  'shuffle' => '<svg viewBox="0 0 24 24"><path d="M10.59 9.17 5.41 4 4 5.41l5.17 5.17zm4.78-4.42-1.17 1.17 1.93 1.94L18 6h-2.5l-2.41-1.25zM4 18.99l1.41 1.41L18 7.81V10h2V4h-6v2h2.19L4 18.99z"/></svg>',
  'cross'   => '<svg viewBox="0 0 24 24"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
  'photo'   => '<svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>',
];

admin_head('Settings');
admin_nav('edit-ui');
?>

<style>
.smenu-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:4px 0}
.smenu-btn{display:flex;flex-direction:column;align-items:flex-start;gap:6px;background:var(--ak2);border:1px solid var(--abdr);border-radius:14px;padding:16px 14px;text-decoration:none;color:inherit;transition:border-color .2s,transform .15s;cursor:pointer}
.smenu-btn:active{transform:scale(.97);border-color:var(--g)}
.smenu-icon{font-size:1.7rem;width:28px;height:28px;display:flex;align-items:center;justify-content:center;color:var(--g)}
.smenu-icon svg{width:26px;height:26px;fill:currentColor}
.smenu-title{font-weight:700;font-size:.9rem;color:var(--w)}
.smenu-desc{font-size:.72rem;color:var(--agray);line-height:1.4}
.contact-row{background:var(--ak3);border:1px solid var(--abdr2);border-radius:10px;padding:10px;margin-bottom:9px}
h3 svg{width:16px;height:16px;fill:currentColor;flex-shrink:0;vertical-align:-3px;margin-right:4px}
.aph h1 svg{width:18px;height:18px;fill:currentColor;vertical-align:-3px;margin-right:5px}
.upload-icon-wrap svg{width:22px;height:22px;fill:currentColor}
</style>

<div class="aph">
  <?php if ($section): ?>
  <a href="/admin/edit-ui" class="abl">← Settings</a>
  <?php endif; ?>
  <h1><?= $ic['gear'] ?>Settings<?= $section ? ' — '.ucfirst($section) : '' ?></h1>
  <?php if ($saved): ?>
  <div style="background:rgba(76,175,80,.12);color:#4CAF50;border:1px solid rgba(76,175,80,.25);padding:8px 14px;border-radius:8px;font-size:.84rem;margin-top:8px;display:flex;align-items:center;gap:6px"><?= $ic['check'] ?>Saved!</div>
  <?php endif; ?>
</div>

<?php if (!$section): ?>
<!-- ── MAIN MENU ── -->
<div class="smenu-grid">
  <?php
  $menus = [
    ['logo',       $ic['image'],   'Logo & Favicon',     'Site logo, favicon, name'],
    ['seo',        $ic['search'],  'SEO / Google',        'Meta description, keywords'],
    ['theme',      $ic['palette'], 'UI Theme',             'Color theme selection'],
    ['banner',     $ic['camera'],  'Banner & Background',  'Banners, BG images'],
    ['categories', $ic['folder'],  'Categories',           'Product categories'],
    ['identity',   $ic['shop'],    'Site Identity',        'Title, taglines, footer'],
    ['delivery',   $ic['truck'],   'Delivery & Payment',   'Charges, payment methods'],
    ['contact',    $ic['phone'],   'Contact Us',           'Phone, WhatsApp, Email'],
    ['social',     $ic['mobile'],  'Social Media',         'Links & icons'],
    ['info',       $ic['doc'],     'Info & Terms',         'About, terms, policy'],
  ];
  foreach ($menus as [$key,$ico,$title,$desc]):
  ?>
  <a href="/admin/edit-ui?section=<?= $key ?>" class="smenu-btn">
    <span class="smenu-icon"><?= $ico ?></span>
    <span class="smenu-title"><?= $title ?></span>
    <span class="smenu-desc"><?= $desc ?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php elseif ($section === 'logo'): ?>
<form action="/admin/edit-ui?section=logo" method="POST" enctype="multipart/form-data" class="aform">
  <input type="hidden" name="_section" value="logo">
  <div class="afs">
    <h3><?= $ic['image'] ?>Logo & Favicon</h3>
    <?php if (!empty($cfg['site_logo'])): ?>
    <div style="margin-bottom:12px"><p style="font-size:.78rem;color:var(--agray);margin-bottom:6px">Current Logo:</p>
    <img src="<?= htmlspecialchars($cfg['site_logo']) ?>" style="max-height:60px;max-width:200px;object-fit:contain;background:var(--ak3);border-radius:8px;padding:8px;border:1px solid var(--abdr)"></div>
    <?php endif; ?>
    <div class="fr2">
      <div class="frow">
        <label>Site Logo (PNG)</label>
        <div class="aup upload-icon-wrap" onclick="document.getElementById('logoInp').click()"><div class="ui"><?= $ic['image'] ?></div><p>Upload Logo</p></div>
        <input type="file" name="site_logo" id="logoInp" accept="image/*" style="display:none" onchange="pv(this,'lPrev')">
        <img id="lPrev" src="" style="display:none;max-height:60px;margin-top:8px;border-radius:8px">
      </div>
      <div class="frow">
        <label>Favicon (ICO/PNG)</label>
        <?php if (!empty($cfg['site_favicon_ext'])): ?><p style="font-size:.74rem;color:#4CAF50;margin-bottom:6px;display:flex;align-items:center;gap:5px"><?= $ic['check'] ?>Favicon set</p><?php endif; ?>
        <div class="aup upload-icon-wrap" onclick="document.getElementById('favInp').click()"><div class="ui"><?= $ic['tag'] ?></div><p>Upload Favicon</p></div>
        <input type="file" name="site_favicon" id="favInp" accept=".ico,.png,image/*" style="display:none" onchange="pv(this,'fPrev')">
        <img id="fPrev" src="" style="display:none;width:32px;height:32px;margin-top:8px;border-radius:4px">
      </div>
    </div>
    <div class="frow"><label>Site Name</label><input type="text" name="site_name" value="<?= htmlspecialchars($cfg['site_name']??'') ?>" class="ai"></div>
    <div class="frow"><label>Logo Text (if no image)</label><input type="text" name="site_logo_text" value="<?= htmlspecialchars($cfg['site_logo_text']??'') ?>" class="ai" placeholder="RAQI ZONE"></div>
  </div>
  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save</button></div>
</form>

<?php elseif ($section === 'seo'): ?>
<form action="/admin/edit-ui?section=seo" method="POST" class="aform">
  <input type="hidden" name="_section" value="seo">
  <div class="afs">
    <h3><?= $ic['search'] ?>SEO / Google Search</h3>
    <div class="frow"><label>Meta Description (max 160)</label><textarea name="meta_description" class="ata" rows="3"><?= htmlspecialchars($cfg['meta_description']??'') ?></textarea></div>
    <div class="frow"><label>Meta Keywords</label><input type="text" name="meta_keywords" value="<?= htmlspecialchars($cfg['meta_keywords']??'') ?>" class="ai" placeholder="shoes, fashion, raqizone"></div>
    <div class="frow"><label>Welcome Subtitle</label><input type="text" name="welcome_subtitle" value="<?= htmlspecialchars($cfg['welcome_subtitle']??'') ?>" class="ai"></div>
    <div class="frow"><label>Home Tagline</label><input type="text" name="home_tagline" value="<?= htmlspecialchars($cfg['home_tagline']??'') ?>" class="ai"></div>
  </div>
  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save</button></div>
</form>

<?php elseif ($section === 'theme'): ?>
<form action="/admin/edit-ui?section=theme" method="POST" class="aform">
  <input type="hidden" name="_section" value="theme">
  <div class="afs">
    <h3><?= $ic['palette'] ?>UI Theme</h3>
    <div id="themeGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:9px"></div>
    <input type="hidden" name="site_theme" id="siteTheme" value="<?= htmlspecialchars($cfg['site_theme']??'golden') ?>">
  </div>
  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save Theme</button></div>
</form>
<script>
const TH=[{id:'golden',label:'Golden',g:'#C9A84C',k:'#080808'},{id:'black',label:'Black',g:'#FFF',k:'#000'},{id:'white',label:'White',g:'#333',k:'#F0F0F0'},{id:'red',label:'Red',g:'#E53935',k:'#0A0000'},{id:'blue',label:'Blue',g:'#1E88E5',k:'#020810'},{id:'skyblue',label:'Sky',g:'#29B6F6',k:'#020A10'},{id:'green',label:'Green',g:'#43A047',k:'#020A02'},{id:'purple',label:'Purple',g:'#8E24AA',k:'#08020A'},{id:'orange',label:'Orange',g:'#FB8C00',k:'#0A0500'},{id:'brown',label:'Brown',g:'#795548',k:'#080402'},{id:'pink',label:'Pink',g:'#E91E63',k:'#0A0205'},{id:'cyan',label:'Cyan',g:'#00BCD4',k:'#00080A'},{id:'yellow',label:'Yellow',g:'#F9A825',k:'#080600'}];
const curT=document.getElementById('siteTheme').value;
const grid=document.getElementById('themeGrid');
TH.forEach(t=>{const btn=document.createElement('button');btn.type='button';btn.style.cssText='padding:10px 6px;border-radius:9px;border:3px solid '+(t.id===curT?t.g:'var(--abdr)')+';background:'+t.k+';color:'+t.g+';font-size:.72rem;font-weight:700;cursor:pointer;font-family:inherit;text-align:center';btn.textContent=t.label;btn.onclick=()=>{document.getElementById('siteTheme').value=t.id;grid.querySelectorAll('button').forEach(b=>b.style.borderColor='var(--abdr)');btn.style.borderColor=t.g;};grid.appendChild(btn);});
</script>

<?php elseif ($section === 'banner'): ?>
<form action="/admin/edit-ui?section=banner" method="POST" enctype="multipart/form-data" class="aform">
  <input type="hidden" name="_section" value="banner">

  <div class="afs">
    <h3><?= $ic['mobile'] ?>Mobile Banners</h3>
    <div id="mBnrList"></div>
    <input type="hidden" name="existing_mobile_banners" id="exMBnr" value="<?= htmlspecialchars(json_encode($mobile_banners,JSON_UNESCAPED_UNICODE)) ?>">
    <div class="aup upload-icon-wrap" onclick="document.getElementById('mBnrInp').click()" style="margin-top:8px"><div class="ui"><?= $ic['camera'] ?></div><p>Add mobile banners</p></div>
    <input type="file" name="new_mobile_banner_images[]" id="mBnrInp" accept="image/*" multiple style="display:none" onchange="addBnr(this,'mBnrPrev')">
    <div id="mBnrPrev" style="margin-top:8px"></div>
  </div>

  <div class="afs">
    <h3><?= $ic['desktop'] ?>Desktop Banners</h3>
    <div id="dBnrList"></div>
    <input type="hidden" name="existing_desktop_banners" id="exDBnr" value="<?= htmlspecialchars(json_encode($desktop_banners,JSON_UNESCAPED_UNICODE)) ?>">
    <div class="aup upload-icon-wrap" onclick="document.getElementById('dBnrInp').click()" style="margin-top:8px"><div class="ui"><?= $ic['desktop'] ?></div><p>Add desktop banners</p></div>
    <input type="file" name="new_desktop_banner_images[]" id="dBnrInp" accept="image/*" multiple style="display:none" onchange="addBnr(this,'dBnrPrev')">
    <div id="dBnrPrev" style="margin-top:8px"></div>
    <div class="frow" style="margin-top:12px">
      <label>Banner Animation</label>
      <select name="banner_animation" class="ai">
        <?php foreach(['slide'=>'Slide','fade'=>'Fade','zoom'=>'Zoom','flip'=>'Flip'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= $anim===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Mobile BG -->
  <div class="afs">
    <h3><?= $ic['mobile'] ?>Mobile Background</h3>
    <?php if (!empty($cfg['mobile_bg_image'])): ?>
    <img src="<?= htmlspecialchars($cfg['mobile_bg_image']) ?>" style="max-width:100%;max-height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:8px">
      <input type="checkbox" name="clear_mobile_bg" style="accent-color:#F44336">
      <span style="color:#F44336;font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:4px"><?= $ic['cross'] ?>Remove mobile BG</span>
    </label>
    <?php endif; ?>
    <div class="aup upload-icon-wrap" onclick="document.getElementById('mBgInp').click()"><div class="ui"><?= $ic['image'] ?></div><p>Upload Mobile BG</p></div>
    <input type="file" name="mobile_bg_image" id="mBgInp" accept="image/*" style="display:none" onchange="pv(this,'mBgPrev')">
    <img id="mBgPrev" src="" style="display:none;max-width:100%;margin-top:8px;border-radius:8px">
  </div>

  <!-- Desktop BG -->
  <div class="afs">
    <h3><?= $ic['desktop'] ?>Desktop Background</h3>
    <?php if (!empty($cfg['desktop_bg_image'])): ?>
    <img src="<?= htmlspecialchars($cfg['desktop_bg_image']) ?>" style="max-width:100%;max-height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:8px">
      <input type="checkbox" name="clear_desktop_bg" style="accent-color:#F44336">
      <span style="color:#F44336;font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:4px"><?= $ic['cross'] ?>Remove desktop BG</span>
    </label>
    <?php endif; ?>
    <div class="aup upload-icon-wrap" onclick="document.getElementById('dBgInp').click()"><div class="ui"><?= $ic['desktop'] ?></div><p>Upload Desktop BG</p></div>
    <input type="file" name="desktop_bg_image" id="dBgInp" accept="image/*" style="display:none" onchange="pv(this,'dBgPrev')">
    <img id="dBgPrev" src="" style="display:none;max-width:100%;margin-top:8px;border-radius:8px">
  </div>

  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save</button></div>
</form>

<?php elseif ($section === 'categories'): ?>
<form action="/admin/edit-ui?section=categories" method="POST" class="aform">
  <input type="hidden" name="_section" value="categories">
  <div class="afs">
    <h3><?= $ic['folder'] ?>Product Categories</h3>
    <div id="catList" style="display:flex;flex-direction:column;gap:7px;margin-bottom:10px"></div>
    <div style="display:flex;gap:8px">
      <input type="text" id="catInput" class="ai" placeholder="New category..." style="flex:1">
      <button type="button" class="abg" onclick="addCat()">+ Add</button>
    </div>
    <input type="hidden" name="product_categories_json" id="catsJson" value="<?= htmlspecialchars(json_encode($categories,JSON_UNESCAPED_UNICODE)) ?>">
  </div>
  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save</button></div>
</form>
<script>
let cats=[];try{cats=JSON.parse(document.getElementById('catsJson').value||'[]');}catch(e){}
const ICO_FOLDER='<?= addslashes($ic['folder']) ?>';
function renderCats(){const list=document.getElementById('catList');list.innerHTML='';cats.forEach((cat,i)=>{const d=document.createElement('div');d.style.cssText='display:flex;align-items:center;gap:8px;background:var(--ak3);border:1px solid var(--abdr);border-radius:8px;padding:8px 12px';d.innerHTML='<span style="flex:1;font-size:.86rem;display:flex;align-items:center;gap:6px">'+ICO_FOLDER+' '+cat+'</span><button type="button" class="idl" onclick="rmCat('+i+')">✕</button>';list.appendChild(d);});document.getElementById('catsJson').value=JSON.stringify(cats);}
function addCat(){const inp=document.getElementById('catInput');const v=inp.value.trim();if(!v||cats.includes(v))return;cats.push(v);inp.value='';renderCats();}
function rmCat(i){cats.splice(i,1);renderCats();}
renderCats();
</script>

<?php elseif ($section === 'identity'): ?>
<form action="/admin/edit-ui?section=identity" method="POST" class="aform">
  <input type="hidden" name="_section" value="identity">
  <div class="afs">
    <h3><?= $ic['shop'] ?>Site Identity</h3>
    <div class="frow"><label>Site Name</label><input type="text" name="site_name" value="<?= htmlspecialchars($cfg['site_name']??'') ?>" class="ai"></div>
    <div class="frow"><label>Welcome Title</label><input type="text" name="welcome_title" value="<?= htmlspecialchars($cfg['welcome_title']??'') ?>" class="ai"></div>
    <div class="frow"><label>Welcome Subtitle</label><input type="text" name="welcome_subtitle" value="<?= htmlspecialchars($cfg['welcome_subtitle']??'') ?>" class="ai"></div>
    <div class="frow"><label>Home Tagline</label><input type="text" name="home_tagline" value="<?= htmlspecialchars($cfg['home_tagline']??'') ?>" class="ai"></div>
    <div class="frow"><label>Footer Note</label><input type="text" name="footer_note" value="<?= htmlspecialchars($cfg['footer_note']??'') ?>" class="ai"></div>
  </div>
  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save</button></div>
</form>

<?php elseif ($section === 'delivery'): ?>
<form action="/admin/edit-ui?section=delivery" method="POST" class="aform">
  <input type="hidden" name="_section" value="delivery">
  <div class="afs">
    <h3><?= $ic['truck'] ?>Delivery & Payment</h3>
    <div class="frow"><label>Default Delivery Charge (৳)</label><input type="number" name="delivery_charge_default" value="<?= htmlspecialchars($cfg['delivery_charge_default']??'60') ?>" class="ai" min="0"></div>
    <div class="frow">
      <label>Payment Options</label>
      <select name="payment_options" class="ai" id="payOptSel" onchange="showPS()">
        <?php foreach(['cod'=>'COD Only','delivery_only'=>'Delivery Charge Online','advance_only'=>'% Advance Payment','full'=>'Full Payment','all'=>'All Options'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= ($cfg['payment_options']??'cod')===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <p style="font-size:.72rem;color:var(--agray);margin-top:5px">"% Advance Payment" — global default; প্রতিটা product এর own settings (Edit Product → Payment Method) এটাকে override করতে পারে।</p>
    </div>
    <div id="onlineInfo" style="display:none">
      <div class="frow"><label>Bkash Number</label><input type="text" name="bkash_number" value="<?= htmlspecialchars($cfg['bkash_number']??'') ?>" class="ai" placeholder="01XXXXXXXXX"></div>
      <div class="frow"><label>Nagad Number</label><input type="text" name="nagad_number" value="<?= htmlspecialchars($cfg['nagad_number']??'') ?>" class="ai" placeholder="01XXXXXXXXX"></div>
      <div class="frow"><label>Bkash App Key</label><input type="text" name="bkash_app_key" value="<?= htmlspecialchars($cfg['bkash_app_key']??'') ?>" class="ai"></div>
      <div class="frow"><label>Bkash App Secret</label><input type="password" name="bkash_app_secret" value="<?= htmlspecialchars($cfg['bkash_app_secret']??'') ?>" class="ai"></div>
    </div>
  </div>
  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save</button></div>
</form>
<script>function showPS(){const v=document.getElementById('payOptSel').value;document.getElementById('onlineInfo').style.display=v==='cod'?'none':'block';}showPS();</script>

<?php elseif ($section === 'contact'): ?>
<form action="/admin/edit-ui?section=contact" method="POST" enctype="multipart/form-data" class="aform">
  <input type="hidden" name="_section" value="contact">

  <!-- Phone Contacts -->
  <div class="afs">
    <h3><?= $ic['phone'] ?>Phone Contacts</h3>
    <p class="afn">নাম, পদবী, নম্বর ও ছবি দিন। একাধিক যোগ করতে পারবেন।</p>
    <div id="cpList">
      <?php foreach ($contact_persons as $i => $cp): ?>
      <div class="contact-row">
        <div style="display:flex;gap:9px;align-items:center">
          <div style="flex-shrink:0;cursor:pointer" onclick="document.getElementById('cpf<?= $i ?>').click()">
            <?php if($cp['photo']): ?>
            <img id="cpimg<?= $i ?>" src="<?= htmlspecialchars($cp['photo']) ?>" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid var(--g)">
            <?php else: ?>
            <div id="cpimg<?= $i ?>" style="width:46px;height:46px;border-radius:50%;background:var(--ak2);border:2px dashed var(--abdr);display:flex;align-items:center;justify-content:center;color:var(--agray)"><?= str_replace('width:16px;height:16px','width:20px;height:20px',$ic['person']) ?></div>
            <?php endif; ?>
            <input type="file" name="cp_photo[]" id="cpf<?= $i ?>" accept="image/*" style="display:none" onchange="prevCP(this,'cpimg<?= $i ?>')">
          </div>
          <input type="hidden" name="cp_existing_photo[]" value="<?= htmlspecialchars($cp['photo']) ?>">
          <div style="flex:1;display:flex;flex-direction:column;gap:5px">
            <input type="text" name="cp_name[]" value="<?= htmlspecialchars($cp['name']) ?>" class="ai" placeholder="Name" style="padding:7px 10px">
            <input type="text" name="cp_title[]" value="<?= htmlspecialchars($cp['title']??'') ?>" class="ai" placeholder="Title/পদবী (optional)" style="padding:7px 10px">
            <input type="text" name="cp_phone[]" value="<?= htmlspecialchars($cp['phone']) ?>" class="ai" placeholder="01XXXXXXXXX" style="padding:7px 10px">
          </div>
          <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
            <input type="number" name="cp_order[]" value="<?= $cp['order']??$i ?>" class="ai" style="width:46px;padding:5px 6px;text-align:center" title="Order">
            <button type="button" class="idl" onclick="this.closest('.contact-row').remove()">✕</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="abs" onclick="addCP()">+ Add Phone Contact</button>
  </div>

  <!-- WhatsApp Contacts -->
  <div class="afs">
    <h3 style="color:#25D366"><?= $ic['wp'] ?>WhatsApp Contacts</h3>
    <p class="afn">একাধিক WhatsApp নম্বর, নাম ও ছবি সহ।</p>
    <div id="wpList">
      <?php foreach ($contact_wp as $i => $wp): ?>
      <div class="contact-row">
        <div style="display:flex;gap:9px;align-items:center">
          <div style="flex-shrink:0;cursor:pointer" onclick="document.getElementById('wpf<?= $i ?>').click()">
            <?php if($wp['photo']): ?>
            <img id="wpimg<?= $i ?>" src="<?= htmlspecialchars($wp['photo']) ?>" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid #25D366">
            <?php else: ?>
            <div id="wpimg<?= $i ?>" style="width:46px;height:46px;border-radius:50%;background:var(--ak2);border:2px dashed var(--abdr);display:flex;align-items:center;justify-content:center;color:#25D366"><?= str_replace('width:16px;height:16px','width:20px;height:20px',$ic['wp']) ?></div>
            <?php endif; ?>
            <input type="file" name="wp_photo[]" id="wpf<?= $i ?>" accept="image/*" style="display:none" onchange="prevCP(this,'wpimg<?= $i ?>')">
          </div>
          <input type="hidden" name="wp_existing_photo[]" value="<?= htmlspecialchars($wp['photo']) ?>">
          <div style="flex:1;display:flex;flex-direction:column;gap:5px">
            <input type="text" name="wp_name[]" value="<?= htmlspecialchars($wp['name']) ?>" class="ai" placeholder="Name" style="padding:7px 10px">
            <input type="text" name="wp_title[]" value="<?= htmlspecialchars($wp['title']??'') ?>" class="ai" placeholder="Title/পদবী (optional)" style="padding:7px 10px">
            <input type="text" name="wp_num[]" value="<?= htmlspecialchars($wp['num']) ?>" class="ai" placeholder="880XXXXXXXXX" style="padding:7px 10px">
          </div>
          <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
            <input type="number" name="wp_order[]" value="<?= $wp['order']??$i ?>" class="ai" style="width:46px;padding:5px 6px;text-align:center">
            <button type="button" class="idl" onclick="this.closest('.contact-row').remove()">✕</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="abs" onclick="addWP()">+ Add WhatsApp</button>
  </div>

  <!-- Email Contacts -->
  <div class="afs">
    <h3><?= $ic['mail'] ?>Email Contacts</h3>
    <div id="emList">
      <?php foreach ($contact_emails as $i => $em): ?>
      <div class="contact-row">
        <div style="display:flex;gap:9px;align-items:center">
          <div style="flex-shrink:0;cursor:pointer" onclick="document.getElementById('emf<?= $i ?>').click()">
            <?php if($em['photo']??''): ?>
            <img id="emimg<?= $i ?>" src="<?= htmlspecialchars($em['photo']??'') ?>" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid #EA4335">
            <?php else: ?>
            <div id="emimg<?= $i ?>" style="width:46px;height:46px;border-radius:50%;background:var(--ak2);border:2px dashed var(--abdr);display:flex;align-items:center;justify-content:center;color:#EA4335"><?= str_replace('width:16px;height:16px','width:20px;height:20px',$ic['mail']) ?></div>
            <?php endif; ?>
            <input type="file" name="em_photo[]" id="emf<?= $i ?>" accept="image/*" style="display:none" onchange="prevCP(this,'emimg<?= $i ?>')">
          </div>
          <input type="hidden" name="em_existing_photo[]" value="<?= htmlspecialchars($em['photo']??'') ?>">
          <div style="flex:1;display:flex;flex-direction:column;gap:5px">
            <input type="text" name="em_name[]" value="<?= htmlspecialchars($em['name']) ?>" class="ai" placeholder="Name" style="padding:7px 10px">
            <input type="email" name="em_email[]" value="<?= htmlspecialchars($em['email']) ?>" class="ai" placeholder="email@example.com" style="padding:7px 10px">
          </div>
          <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
            <input type="number" name="em_order[]" value="<?= $em['order']??$i ?>" class="ai" style="width:46px;padding:5px 6px;text-align:center">
            <button type="button" class="idl" onclick="this.closest('.contact-row').remove()">✕</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="abs" onclick="addEM()">+ Add Email</button>
  </div>

  <!-- Other Links -->
  <div class="afs">
    <h3><?= $ic['globe'] ?>Other Links</h3>
    <div class="frow"><label>Facebook Page URL</label><input type="url" name="contact_facebook" value="<?= htmlspecialchars($cfg['contact_facebook']??'') ?>" class="ai" placeholder="https://facebook.com/..."></div>
    <div class="frow"><label>Website URL</label><input type="url" name="contact_website" value="<?= htmlspecialchars($cfg['contact_website']??'') ?>" class="ai" placeholder="https://..."></div>
  </div>

  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save All</button></div>
</form>

<script>
const ICO_PERSON='<?= addslashes(str_replace('width:16px;height:16px','width:20px;height:20px',$ic['person'])) ?>';
const ICO_WP='<?= addslashes(str_replace('width:16px;height:16px','width:20px;height:20px',$ic['wp'])) ?>';
const ICO_MAIL='<?= addslashes(str_replace('width:16px;height:16px','width:20px;height:20px',$ic['mail'])) ?>';

function prevCP(inp,id){
  if(inp.files&&inp.files[0]){
    const r=new FileReader();
    r.onload=e=>{
      const el=document.getElementById(id);
      if(el){
        if(el.tagName==='IMG'){el.src=e.target.result;}
        else{const img=document.createElement('img');img.id=id;img.src=e.target.result;img.style.cssText='width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid var(--g)';el.parentNode.replaceChild(img,el);}
      }
    };
    r.readAsDataURL(inp.files[0]);
  }
}

var cpC=<?= count($contact_persons) ?>, wpC=<?= count($contact_wp) ?>, emC=<?= count($contact_emails) ?>;

function addCP(){
  const i=cpC++;
  document.getElementById('cpList').insertAdjacentHTML('beforeend',`
    <div class="contact-row">
      <div style="display:flex;gap:9px;align-items:center">
        <div style="flex-shrink:0;cursor:pointer" onclick="document.getElementById('cpf${i}').click()">
          <div id="cpimg${i}" style="width:46px;height:46px;border-radius:50%;background:var(--ak2);border:2px dashed var(--abdr);display:flex;align-items:center;justify-content:center;color:var(--agray)">${ICO_PERSON}</div>
          <input type="file" name="cp_photo[]" id="cpf${i}" accept="image/*" style="display:none" onchange="prevCP(this,'cpimg${i}')">
        </div>
        <input type="hidden" name="cp_existing_photo[]" value="">
        <div style="flex:1;display:flex;flex-direction:column;gap:5px">
          <input type="text" name="cp_name[]" class="ai" placeholder="Name" style="padding:7px 10px">
          <input type="text" name="cp_title[]" class="ai" placeholder="Title/পদবী (optional)" style="padding:7px 10px">
          <input type="text" name="cp_phone[]" class="ai" placeholder="01XXXXXXXXX" style="padding:7px 10px">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
          <input type="number" name="cp_order[]" value="${i}" class="ai" style="width:46px;padding:5px 6px;text-align:center">
          <button type="button" class="idl" onclick="this.closest('.contact-row').remove()">✕</button>
        </div>
      </div>
    </div>`);
}

function addWP(){
  const i=wpC++;
  document.getElementById('wpList').insertAdjacentHTML('beforeend',`
    <div class="contact-row">
      <div style="display:flex;gap:9px;align-items:center">
        <div style="flex-shrink:0;cursor:pointer" onclick="document.getElementById('wpf${i}').click()">
          <div id="wpimg${i}" style="width:46px;height:46px;border-radius:50%;background:var(--ak2);border:2px dashed var(--abdr);display:flex;align-items:center;justify-content:center;color:#25D366">${ICO_WP}</div>
          <input type="file" name="wp_photo[]" id="wpf${i}" accept="image/*" style="display:none" onchange="prevCP(this,'wpimg${i}')">
        </div>
        <input type="hidden" name="wp_existing_photo[]" value="">
        <div style="flex:1;display:flex;flex-direction:column;gap:5px">
          <input type="text" name="wp_name[]" class="ai" placeholder="Name" style="padding:7px 10px">
          <input type="text" name="wp_title[]" class="ai" placeholder="Title/পদবী (optional)" style="padding:7px 10px">
          <input type="text" name="wp_num[]" class="ai" placeholder="880XXXXXXXXX" style="padding:7px 10px">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
          <input type="number" name="wp_order[]" value="${i}" class="ai" style="width:46px;padding:5px 6px;text-align:center">
          <button type="button" class="idl" onclick="this.closest('.contact-row').remove()">✕</button>
        </div>
      </div>
    </div>`);
}

function addEM(){
  const i=emC++;
  document.getElementById('emList').insertAdjacentHTML('beforeend',`
    <div class="contact-row">
      <div style="display:flex;gap:9px;align-items:center">
        <div style="flex-shrink:0;cursor:pointer" onclick="document.getElementById('emf${i}').click()">
          <div id="emimg${i}" style="width:46px;height:46px;border-radius:50%;background:var(--ak2);border:2px dashed var(--abdr);display:flex;align-items:center;justify-content:center;color:#EA4335">${ICO_MAIL}</div>
          <input type="file" name="em_photo[]" id="emf${i}" accept="image/*" style="display:none" onchange="prevCP(this,'emimg${i}')">
        </div>
        <input type="hidden" name="em_existing_photo[]" value="">
        <div style="flex:1;display:flex;flex-direction:column;gap:5px">
          <input type="text" name="em_name[]" class="ai" placeholder="Name" style="padding:7px 10px">
          <input type="email" name="em_email[]" class="ai" placeholder="email@example.com" style="padding:7px 10px">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
          <input type="number" name="em_order[]" value="${i}" class="ai" style="width:46px;padding:5px 6px;text-align:center">
          <button type="button" class="idl" onclick="this.closest('.contact-row').remove()">✕</button>
        </div>
      </div>
    </div>`);
}
</script>

<?php elseif ($section === 'social'): ?>
<form action="/admin/edit-ui?section=social" method="POST" enctype="multipart/form-data" class="aform">
  <input type="hidden" name="_section" value="social">
  <div class="afs">
    <h3><?= $ic['mobile'] ?>Social Media</h3>
    <p class="afn">নাম, লিংক ও আইকন ছবি দিন।</p>
    <div id="smList">
      <?php foreach ($social_media as $i => $sm): ?>
      <div class="contact-row">
        <div style="display:flex;gap:9px;align-items:center">
          <div style="flex-shrink:0;cursor:pointer" onclick="document.getElementById('smf<?= $i ?>').click()">
            <?php if($sm['icon']): ?>
            <img id="smimg<?= $i ?>" src="<?= htmlspecialchars($sm['icon']) ?>" style="width:46px;height:46px;border-radius:10px;object-fit:cover;border:2px solid var(--g)">
            <?php else: ?>
            <div id="smimg<?= $i ?>" style="width:46px;height:46px;border-radius:10px;background:var(--ak2);border:2px dashed var(--abdr);display:flex;align-items:center;justify-content:center;color:var(--agray)"><?= str_replace('width:16px;height:16px','width:21px;height:21px',$ic['mobile']) ?></div>
            <?php endif; ?>
            <input type="file" name="sm_icon[]" id="smf<?= $i ?>" accept="image/*" style="display:none" onchange="prevSM(this,'smimg<?= $i ?>')">
          </div>
          <input type="hidden" name="sm_existing_icon[]" value="<?= htmlspecialchars($sm['icon']) ?>">
          <div style="flex:1;display:flex;flex-direction:column;gap:5px">
            <input type="text" name="sm_name[]" value="<?= htmlspecialchars($sm['name']) ?>" class="ai" placeholder="e.g. Facebook" style="padding:7px 10px">
            <input type="url" name="sm_link[]" value="<?= htmlspecialchars($sm['link']) ?>" class="ai" placeholder="https://..." style="padding:7px 10px">
          </div>
          <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
            <input type="number" name="sm_order[]" value="<?= $sm['order']??$i ?>" class="ai" style="width:46px;padding:5px 6px;text-align:center">
            <button type="button" class="idl" onclick="this.closest('.contact-row').remove()">✕</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="abs" onclick="addSM()">+ Add Social Media</button>
  </div>
  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save</button></div>
</form>
<script>
var smC=<?= count($social_media) ?>;
const ICO_MOBILE_SM='<?= addslashes(str_replace('width:16px;height:16px','width:21px;height:21px',$ic['mobile'])) ?>';
function prevSM(inp,id){
  if(inp.files&&inp.files[0]){
    const r=new FileReader();
    r.onload=e=>{
      const el=document.getElementById(id);
      if(el){
        if(el.tagName==='IMG'){el.src=e.target.result;}
        else{const img=document.createElement('img');img.id=id;img.src=e.target.result;img.style.cssText='width:46px;height:46px;border-radius:10px;object-fit:cover;border:2px solid var(--g)';el.parentNode.replaceChild(img,el);}
      }
    };
    r.readAsDataURL(inp.files[0]);
  }
}
function addSM(){
  const i=smC++;
  document.getElementById('smList').insertAdjacentHTML('beforeend',`
    <div class="contact-row">
      <div style="display:flex;gap:9px;align-items:center">
        <div style="flex-shrink:0;cursor:pointer" onclick="document.getElementById('smf${i}').click()">
          <div id="smimg${i}" style="width:46px;height:46px;border-radius:10px;background:var(--ak2);border:2px dashed var(--abdr);display:flex;align-items:center;justify-content:center;color:var(--agray)">${ICO_MOBILE_SM}</div>
          <input type="file" name="sm_icon[]" id="smf${i}" accept="image/*" style="display:none" onchange="prevSM(this,'smimg${i}')">
        </div>
        <input type="hidden" name="sm_existing_icon[]" value="">
        <div style="flex:1;display:flex;flex-direction:column;gap:5px">
          <input type="text" name="sm_name[]" class="ai" placeholder="e.g. Facebook" style="padding:7px 10px">
          <input type="url" name="sm_link[]" class="ai" placeholder="https://..." style="padding:7px 10px">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
          <input type="number" name="sm_order[]" value="${i}" class="ai" style="width:46px;padding:5px 6px;text-align:center">
          <button type="button" class="idl" onclick="this.closest('.contact-row').remove()">✕</button>
        </div>
      </div>
    </div>`);
}
</script>

<?php elseif ($section === 'info'): ?>
<form action="/admin/edit-ui?section=info" method="POST" class="aform">
  <input type="hidden" name="_section" value="info">
  <div class="afs">
    <h3><?= $ic['doc'] ?>Info & Terms</h3>
    <div class="frow"><label>About Us</label><textarea name="about_us" class="ata" rows="4"><?= htmlspecialchars($cfg['about_us']??'') ?></textarea></div>
    <div class="frow"><label>Terms & Conditions</label><textarea name="terms_and_conditions" class="ata" rows="4"><?= htmlspecialchars($cfg['terms_and_conditions']??'') ?></textarea></div>
    <div class="frow"><label>Return Policy</label><textarea name="return_policy" class="ata" rows="3"><?= htmlspecialchars($cfg['return_policy']??'') ?></textarea></div>
    <div class="frow"><label>Extra Info</label><textarea name="extra_info" class="ata" rows="3"><?= htmlspecialchars($cfg['extra_info']??'') ?></textarea></div>
  </div>
  <div class="afact"><button type="submit" class="abg" style="display:flex;align-items:center;justify-content:center;gap:6px"><?= $ic['check'] ?>Save</button></div>
</form>
<?php endif; ?>

<!-- Shared JS -->
<script>
function pv(inp,id){if(inp.files&&inp.files[0]){const r=new FileReader();r.onload=e=>{const p=document.getElementById(id);if(p){p.src=e.target.result;p.style.display='block';}};r.readAsDataURL(inp.files[0]);}}

function addBnr(inp,id){
  const c=document.getElementById(id);if(!c)return;
  Array.from(inp.files).forEach(f=>{
    const r=new FileReader();
    r.onload=e=>{const d=document.createElement('div');d.style.cssText='display:flex;align-items:center;gap:9px;background:var(--ak3);border:1px solid var(--abdr);border-radius:8px;padding:8px;margin-bottom:7px';d.innerHTML='<img src="'+e.target.result+'" style="width:80px;height:50px;object-fit:cover;border-radius:6px;flex-shrink:0"><span style="flex:1;font-size:.78rem;color:var(--agray)">'+f.name+'</span><span style="color:#4CAF50;display:flex"><svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>';c.appendChild(d);};
    r.readAsDataURL(f);
  });
}

function renderBnrList(cid,hid){
  let b=[];try{b=JSON.parse(document.getElementById(hid).value||'[]');}catch(e){}
  const list=document.getElementById(cid);if(!list)return;list.innerHTML='';
  b.forEach((url,i)=>{const d=document.createElement('div');d.style.cssText='display:flex;align-items:center;gap:9px;background:var(--ak3);border:1px solid var(--abdr);border-radius:8px;padding:8px;margin-bottom:7px';d.innerHTML='<img src="'+url+'" style="width:80px;height:50px;object-fit:cover;border-radius:6px;flex-shrink:0"><span style="flex:1;font-size:.78rem;color:var(--agray)">Banner '+(i+1)+'</span><button type="button" class="idl" onclick="rmBnr('+i+',\''+hid+'\',\''+cid+'\')">✕</button>';list.appendChild(d);});
}
function rmBnr(i,hid,cid){let b=[];try{b=JSON.parse(document.getElementById(hid).value||'[]');}catch(e){}b.splice(i,1);document.getElementById(hid).value=JSON.stringify(b);renderBnrList(cid,hid);}
if(document.getElementById('mBnrList'))renderBnrList('mBnrList','exMBnr');
if(document.getElementById('dBnrList'))renderBnrList('dBnrList','exDBnr');
</script>

<?php admin_foot(); ?>