<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');
$u      = rz_get_user();
$action = $_GET['action'] ?? '';

if ($action === 'unseen_count') {
    if (!$u) { echo json_encode(['count' => 0]); exit; }
    try {
        $latest   = DB::row("SELECT MAX(id) AS lid FROM posts WHERE is_active=1");
        $lid      = (int)($latest['lid'] ?? 0);
        $seen     = DB::row("SELECT last_seen_post_id FROM user_post_seen WHERE user_id=?", [$u['user_id']]);
        $lastSeen = (int)($seen['last_seen_post_id'] ?? 0);
        $count    = ($lid > $lastSeen) ? (DB::row("SELECT COUNT(*) AS c FROM posts WHERE is_active=1 AND id > ?", [$lastSeen])['c'] ?? 0) : 0;
        echo json_encode(['count' => (int)$count]);
    } catch (Throwable $e) { echo json_encode(['count' => 0]); }
    exit;
}

if ($action === 'list' || !$action) {
    try {
        $posts = DB::rows("SELECT * FROM posts WHERE is_active=1 ORDER BY created_at DESC LIMIT 30");
        if ($u) {
            $latest = DB::row("SELECT MAX(id) AS lid FROM posts WHERE is_active=1");
            $lid    = (int)($latest['lid'] ?? 0);
            if ($lid > 0) {
                DB::run("INSERT INTO user_post_seen (user_id,last_seen_post_id,seen_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE last_seen_post_id=?,seen_at=NOW()", [$u['user_id'],$lid,$lid]);
            }
        }
        foreach ($posts as &$post) {
            $post['react_count']   = (int)(DB::row("SELECT COUNT(*) AS c FROM post_reactions WHERE post_id=?", [$post['id']])['c'] ?? 0);
            $post['comment_count'] = (int)(DB::row("SELECT COUNT(*) AS c FROM post_comments WHERE post_id=?", [$post['id']])['c'] ?? 0);
            $post['my_reaction']   = '';
            if ($u) {
                $r = DB::row("SELECT reaction FROM post_reactions WHERE post_id=? AND user_id=?", [$post['id'], $u['user_id']]);
                $post['my_reaction'] = $r['reaction'] ?? '';
            }
            $post['comments'] = DB::rows("SELECT * FROM post_comments WHERE post_id=? ORDER BY created_at ASC LIMIT 20", [$post['id']]);
        }
        unset($post);
        echo json_encode(['ok' => true, 'posts' => $posts], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

if ($action === 'react') {
    if (!$u) { echo json_encode(['error' => 'not_logged_in']); exit; }
    $data    = json_decode(file_get_contents('php://input'), true) ?: [];
    $post_id = (int)($data['post_id'] ?? 0);
    $react   = trim($data['reaction'] ?? 'like');
    if (!$post_id) { echo json_encode(['error' => 'bad']); exit; }
    try {
        $existing = DB::row("SELECT id, reaction FROM post_reactions WHERE post_id=? AND user_id=?", [$post_id, $u['user_id']]);
        if ($existing) {
            if ($existing['reaction'] === $react) {
                DB::run("DELETE FROM post_reactions WHERE id=?", [$existing['id']]);
                $my_reaction = '';
            } else {
                DB::run("UPDATE post_reactions SET reaction=? WHERE id=?", [$react, $existing['id']]);
                $my_reaction = $react;
            }
        } else {
            DB::run("INSERT INTO post_reactions (post_id,user_id,reaction,created_at) VALUES (?,?,?,NOW())", [$post_id,$u['user_id'],$react]);
            $my_reaction = $react;
        }
        $count = (int)(DB::row("SELECT COUNT(*) AS c FROM post_reactions WHERE post_id=?", [$post_id])['c'] ?? 0);
        echo json_encode(['ok' => true, 'react_count' => $count, 'my_reaction' => $my_reaction]);
    } catch (Throwable $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

if ($action === 'comment') {
    if (!$u) { echo json_encode(['error' => 'not_logged_in']); exit; }
    $data    = json_decode(file_get_contents('php://input'), true) ?: [];
    $post_id = (int)($data['post_id'] ?? 0);
    $comment = trim($data['comment'] ?? '');
    if (!$post_id || !$comment) { echo json_encode(['error' => 'bad']); exit; }
    try {
        $cid   = DB::exec("INSERT INTO post_comments (post_id,user_id,user_name,comment,created_at) VALUES (?,?,?,?,NOW())", [$post_id,$u['user_id'],$u['name'],$comment]);
        $count = (int)(DB::row("SELECT COUNT(*) AS c FROM post_comments WHERE post_id=?", [$post_id])['c'] ?? 0);
        echo json_encode(['ok' => true, 'comment_id' => $cid, 'comment_count' => $count, 'user_name' => $u['name']]);
    } catch (Throwable $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

echo json_encode(['error' => 'unknown_action']);