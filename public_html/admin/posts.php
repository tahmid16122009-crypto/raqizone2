<?php
require_once __DIR__ . '/base.php';

// Delete comment
if (!empty($_GET['delcmt'])) {
    $did = (int)$_GET['delcmt'];
    DB::run("DELETE FROM post_comments WHERE id=?", [$did]);
    $from = (int)($_GET['from'] ?? 0);
    header('Location: /admin/posts' . ($from ? '#p'.$from : '')); exit;
}

// Delete post
if (!empty($_GET['delete'])) {
    DB::run("DELETE FROM posts WHERE id=?", [(int)$_GET['delete']]);
    header('Location: /admin/posts'); exit;
}

// Toggle active
if (!empty($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    DB::run("UPDATE posts SET is_active = 1 - is_active WHERE id=?", [$tid]);
    header('Location: /admin/posts'); exit;
}

// Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid     = (int)($_POST['pid']      ?? 0);
    $title   = trim($_POST['title']     ?? '');
    $content = trim($_POST['content']   ?? '');
    $video   = trim($_POST['video_url'] ?? '');
    $link    = trim($_POST['link_url']  ?? '');
    $linkTxt = trim($_POST['link_text'] ?? '');
    $active  = isset($_POST['is_active']) ? 1 : 0;
    $imgPath = trim($_POST['existing_image'] ?? '');

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $url = upload_image($_FILES['image'], 'post', 'posts');
        if ($url) $imgPath = $url;
    }

    if ($pid) {
        DB::run(
            "UPDATE posts SET title=?,content=?,image_path=?,video_url=?,link_url=?,link_text=?,is_active=? WHERE id=?",
            [$title,$content,$imgPath,$video,$link,$linkTxt,$active,$pid]
        );
    } else {
        DB::exec(
            "INSERT INTO posts (title,content,image_path,video_url,link_url,link_text,is_active,created_at) VALUES (?,?,?,?,?,?,?,NOW())",
            [$title,$content,$imgPath,$video,$link,$linkTxt,$active]
        );
    }
    header('Location: /admin/posts'); exit;
}

$edit_post = null;
if (!empty($_GET['edit'])) {
    $edit_post = DB::row("SELECT * FROM posts WHERE id=?", [(int)$_GET['edit']]);
}

$posts = DB::rows(
    "SELECT p.*,
     (SELECT COUNT(*) FROM post_reactions WHERE post_id=p.id) AS react_count,
     (SELECT COUNT(*) FROM post_comments WHERE post_id=p.id) AS comment_count
     FROM posts p ORDER BY p.created_at DESC"
);

admin_head('Updates / Posts');
admin_nav('posts');
?>

<div class="aph">
  <h1>📢 Updates / Posts</h1>
  <a href="#pform" class="abg" style="text-decoration:none;font-size:.82rem">+ New Post</a>
</div>

<!-- Posts List -->
<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">
  <?php if ($posts): ?>
  <?php foreach ($posts as $post): ?>
  <div id="p<?= $post['id'] ?>" style="background:var(--ak2);border:1px solid var(--abdr);border-radius:12px;padding:13px 14px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap">
          <span style="background:<?= $post['is_active']?'rgba(76,175,80,.12)':'rgba(244,67,54,.12)' ?>;color:<?= $post['is_active']?'#4CAF50':'#F44336' ?>;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:50px">
            <?= $post['is_active']?'✅ Active':'🙈 Hidden' ?>
          </span>
          <span style="font-size:.72rem;color:var(--agray)"><?= date('d M Y, h:i A', strtotime($post['created_at'])) ?></span>
        </div>
        <?php if ($post['title']): ?>
        <p style="font-weight:700;font-size:.88rem;margin-bottom:3px"><?= htmlspecialchars($post['title']) ?></p>
        <?php endif; ?>
        <?php if ($post['content']): ?>
        <p style="font-size:.78rem;color:var(--agray);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars($post['content']) ?></p>
        <?php endif; ?>
        <?php if ($post['image_path']): ?>
        <img src="<?= htmlspecialchars($post['image_path']) ?>" style="max-height:60px;border-radius:6px;margin-top:6px;display:block">
        <?php endif; ?>
        <div style="display:flex;gap:10px;margin-top:6px;font-size:.74rem;color:var(--agray);flex-wrap:wrap">
          <span>👍 <?= $post['react_count'] ?></span>
          <span>💬 <?= $post['comment_count'] ?></span>
          <?php if ($post['video_url']): ?><span>▶️ Video</span><?php endif; ?>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:5px;flex-shrink:0">
        <a href="/admin/posts?edit=<?= $post['id'] ?>#pform" class="abs" style="font-size:.72rem;padding:4px 10px;text-decoration:none">✏️ Edit</a>
        <a href="/admin/posts?toggle=<?= $post['id'] ?>" class="abs" style="font-size:.72rem;padding:4px 10px;text-decoration:none"><?= $post['is_active']?'🙈 Hide':'👁 Show' ?></a>
        <a href="/admin/posts?delete=<?= $post['id'] ?>" class="abs" style="font-size:.72rem;padding:4px 10px;text-decoration:none;color:#F44336;border-color:rgba(244,67,54,.4)" onclick="return confirm('Delete?')">🗑</a>
      </div>
    </div>

    <!-- Comments -->
    <?php if ($post['comment_count'] > 0):
      $cmts = DB::rows("SELECT * FROM post_comments WHERE post_id=? ORDER BY created_at DESC LIMIT 5", [$post['id']]);
    ?>
    <div style="border-top:1px solid var(--abdr);margin-top:10px;padding-top:10px">
      <p style="font-size:.72rem;color:var(--agray);margin-bottom:7px;font-weight:600">Recent Comments</p>
      <?php foreach ($cmts as $cmt): ?>
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;background:var(--ak3);border-radius:8px;padding:7px 10px">
        <div style="flex:1;min-width:0">
          <span style="font-size:.74rem;font-weight:700;color:var(--g)"><?= htmlspecialchars($cmt['user_name']) ?></span>
          <p style="font-size:.8rem;margin-top:2px;word-break:break-word"><?= htmlspecialchars($cmt['comment']) ?></p>
        </div>
        <a href="/admin/posts?delcmt=<?= $cmt['id'] ?>&from=<?= $post['id'] ?>" style="color:#F44336;font-size:.82rem;text-decoration:none;flex-shrink:0;padding:2px 6px" onclick="return confirm('Delete comment?')">✕</a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div class="emp"><div class="ei">📢</div><h3>No posts yet</h3><p>Create your first post below.</p></div>
  <?php endif; ?>
</div>

<!-- Add/Edit Form -->
<div id="pform" style="background:var(--ak2);border:2px solid var(--g);border-radius:14px;padding:18px">
  <h3 style="margin-bottom:14px;font-size:.96rem;color:var(--g)"><?= $edit_post ? '✏️ Edit Post' : '✨ New Post' ?></h3>
  <form action="/admin/posts" method="POST" enctype="multipart/form-data" class="aform">
    <?php if ($edit_post): ?><input type="hidden" name="pid" value="<?= $edit_post['id'] ?>"><?php endif; ?>

    <div class="frow">
      <label>Title (optional)</label>
      <input type="text" name="title" value="<?= htmlspecialchars($edit_post['title']??'') ?>" class="ai" placeholder="Post title...">
    </div>
    <div class="frow">
      <label>Content</label>
      <textarea name="content" class="ata" rows="5" placeholder="Write your update..."><?= htmlspecialchars($edit_post['content']??'') ?></textarea>
    </div>

    <div class="frow">
      <label>Image (optional)</label>
      <?php if (!empty($edit_post['image_path'])): ?>
      <img src="<?= htmlspecialchars($edit_post['image_path']) ?>" style="max-height:80px;border-radius:8px;margin-bottom:8px;display:block">
      <?php endif; ?>
      <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_post['image_path']??'') ?>">
      <div class="aup" onclick="document.getElementById('postImg').click()">
        <div class="ui">🖼️</div><p>Upload image</p><p class="us">JPG, PNG, WebP</p>
      </div>
      <input type="file" name="image" id="postImg" accept="image/*" style="display:none" onchange="pvPost(this)">
      <img id="postImgPrev" src="" style="display:none;max-height:80px;margin-top:8px;border-radius:8px">
    </div>

    <div class="frow">
      <label>Video URL (YouTube)</label>
      <input type="url" name="video_url" value="<?= htmlspecialchars($edit_post['video_url']??'') ?>" class="ai" placeholder="https://www.youtube.com/watch?v=...">
    </div>
    <div class="fr2">
      <div class="frow">
        <label>Link URL</label>
        <input type="url" name="link_url" value="<?= htmlspecialchars($edit_post['link_url']??'') ?>" class="ai" placeholder="https://...">
      </div>
      <div class="frow">
        <label>Link Text</label>
        <input type="text" name="link_text" value="<?= htmlspecialchars($edit_post['link_text']??'') ?>" class="ai" placeholder="Click here">
      </div>
    </div>

    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--ak3);border-radius:8px;margin-top:4px">
      <input type="checkbox" name="is_active" id="postActive" style="width:18px;height:18px;accent-color:var(--g)" <?= ($edit_post===null||$edit_post['is_active'])?'checked':'' ?>>
      <label for="postActive" style="cursor:pointer;font-size:.86rem;font-weight:600">✅ Active (visible to users)</label>
    </div>

    <div class="afact">
      <button type="submit" class="abg"><?= $edit_post ? '✅ Update Post' : '✅ Publish Post' ?></button>
      <?php if ($edit_post): ?><a href="/admin/posts" class="abs" style="text-decoration:none">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<script>
function pvPost(inp){if(inp.files&&inp.files[0]){const r=new FileReader();r.onload=e=>{const p=document.getElementById('postImgPrev');if(p){p.src=e.target.result;p.style.display='block';}};r.readAsDataURL(inp.files[0]);}}
</script>

<?php admin_foot(); ?>