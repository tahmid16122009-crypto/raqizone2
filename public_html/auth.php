<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('DB_HOST'))  require_once __DIR__ . '/config.php';
if (!class_exists('DB'))  require_once __DIR__ . '/database.php';

// ── User auth functions (rz_ prefix — PHP built-in conflict avoid করতে)

function rz_get_user(): ?array {
    if (empty($_SESSION['rz_user'])) return null;
    return $_SESSION['rz_user'];
}

function rz_login_user(string $name, string $mobile): void {
    $name   = trim($name);
    $mobile = trim($mobile);
    if (!$name || !$mobile) return;

    // Existing user খোঁজো
    $user = DB::row("SELECT * FROM users WHERE mobile = ? LIMIT 1", [$mobile]);

    if (!$user) {
        // নতুন user তৈরি করো
        $uid = DB::exec(
            "INSERT INTO users (name, mobile, created_at) VALUES (?, ?, NOW())",
            [$name, $mobile]
        );
        $user = DB::row("SELECT * FROM users WHERE id = ?", [$uid]);
    }

    if ($user) {
        $_SESSION['rz_user'] = [
            'user_id' => $user['id'],
            'name'    => $user['name'],
            'mobile'  => $user['mobile'],
        ];
    }
}

function rz_logout_user(): void {
    unset($_SESSION['rz_user']);
}

// ── Admin auth functions

function rz_is_admin(): bool {
    return !empty($_SESSION['rz_admin']);
}

function rz_admin_login(string $password): bool {
    // ── Brute-force protection: max 5 failed attempts, then 15 min lockout ──
    $now          = time();
    $lockUntil    = $_SESSION['rz_admin_lock_until']   ?? 0;
    $failCount    = $_SESSION['rz_admin_fail_count']   ?? 0;

    if ($lockUntil > $now) {
        return false; // still locked out
    }

    // ADMIN_PASS — config.php তে define করা আছে
    // hash_equals() ব্যবহার করছি timing attack এড়াতে (string === তুলনায় নিরাপদ)
    if (hash_equals(ADMIN_PASS, $password)) {
        $_SESSION['rz_admin'] = true;
        unset($_SESSION['rz_admin_fail_count'], $_SESSION['rz_admin_lock_until']);
        return true;
    }

    $failCount++;
    $_SESSION['rz_admin_fail_count'] = $failCount;
    if ($failCount >= 5) {
        $_SESSION['rz_admin_lock_until'] = $now + (15 * 60); // 15 minutes lockout
        $_SESSION['rz_admin_fail_count'] = 0;
    }
    return false;
}

function rz_admin_login_locked(): bool {
    $lockUntil = $_SESSION['rz_admin_lock_until'] ?? 0;
    return $lockUntil > time();
}

function rz_admin_login_lock_seconds_left(): int {
    $lockUntil = $_SESSION['rz_admin_lock_until'] ?? 0;
    return max(0, $lockUntil - time());
}

function rz_admin_logout(): void {
    unset($_SESSION['rz_admin']);
}