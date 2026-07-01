<?php
// ── Secure session cookie settings (must run BEFORE session_start) ──
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('DB_HOST',   '*******');
define('DB_NAME',   '*******');
define('DB_USER',   '*******');
define('DB_PASS',   '*******');
define('ADMIN_PASS','*******');
define('SECRET_KEY','*******');
define('SITE_URL',  'https://raqizone.com');

if (!function_exists('get_all_settings')) {
    function get_all_settings(): array {
        try {
            if (!class_exists('DB')) return [];
            $rows = DB::rows("SELECT `key`, value FROM site_settings");
            $out  = [];
            foreach ($rows as $r) $out[$r['key']] = $r['value'];
            return $out;
        } catch (Throwable $e) { return []; }
    }
}

if (!function_exists('save_setting')) {
    function save_setting(string $key, string $value): void {
        DB::run(
            "INSERT INTO site_settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?",
            [$key, $value, $value]
        );
    }
}

// upload_image() — কোনো ফাইল সাইজ সীমা নেই, শুধু সঠিক image format নিশ্চিত করে
if (!function_exists('upload_image')) {
    function upload_image(array $file, string $prefix = 'img', string $folder = 'products'): ?string {
        $GLOBALS['_upload_error'] = '';

        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE   => 'Server php.ini upload_max_filesize সীমা ছাড়িয়ে গেছে — hosting control panel থেকে বাড়াতে হবে',
                UPLOAD_ERR_FORM_SIZE  => 'Form-এর MAX_FILE_SIZE ছাড়িয়ে গেছে',
                UPLOAD_ERR_PARTIAL    => 'File আংশিক upload হয়েছে (network interrupt)',
                UPLOAD_ERR_NO_FILE    => 'কোনো file পাঠানো হয়নি',
                UPLOAD_ERR_NO_TMP_DIR => 'Server এ temp folder নেই',
                UPLOAD_ERR_CANT_WRITE => 'Server এ disk এ write করা যায়নি',
                UPLOAD_ERR_EXTENSION  => 'PHP extension upload বন্ধ করেছে',
            ];
            $GLOBALS['_upload_error'] = $map[$err] ?? ('Unknown upload error code: ' . $err);
            return null;
        }

        // কোনো file size limit নেই — যত বড় ছবি ইচ্ছা upload করা যাবে
        $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'] ?? '', $allowed)) {
            $GLOBALS['_upload_error'] = 'Unsupported file type: ' . ($file['type'] ?? 'unknown');
            return null;
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowed_ext = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed_ext)) {
            $GLOBALS['_upload_error'] = 'Unsupported file extension: ' . $ext;
            return null;
        }

        $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dir  = __DIR__ . '/uploads/' . $folder . '/';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                $GLOBALS['_upload_error'] = 'Upload folder তৈরি করা যায়নি: ' . $dir;
                return null;
            }
        }
        if (!is_writable($dir)) {
            $GLOBALS['_upload_error'] = 'Upload folder এ লেখার permission নেই: ' . $dir;
            return null;
        }
        if (!move_uploaded_file($file['tmp_name'] ?? '', $dir . $name)) {
            $GLOBALS['_upload_error'] = 'move_uploaded_file() ব্যর্থ হয়েছে';
            return null;
        }
        return '/uploads/' . $folder . '/' . $name;
    }
}