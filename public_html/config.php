<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST',   'localhost');
define('DB_NAME',   'raqizone_raqizone_db');
define('DB_USER',   'raqizone_raqizone_user');
define('DB_PASS',   '******');
define('ADMIN_PASS','******');
define('SECRET_KEY','****************');
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

if (!function_exists('upload_image')) {
    function upload_image(array $file, string $prefix = 'img', string $folder = 'products'): ?string {
        $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'] ?? '', $allowed)) return null;
        if (($file['size'] ?? 0) > 10 * 1024 * 1024) return null;
        $ext  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dir  = __DIR__ . '/uploads/' . $folder . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'] ?? '', $dir . $name)) return null;
        return '/uploads/' . $folder . '/' . $name;
    }
}