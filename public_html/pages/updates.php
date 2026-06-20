<?php
require_once __DIR__ . '/../templates/layout.php';
$u = rz_get_user();
render_head('Updates — ' . ($cfg['site_name'] ?? 'Raqizone'), $cfg);
?>
<div class="page">
  <div class="sbar">
    <a href="/home" class="bk"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg></a>
    <span class="st" data-bn="আপডেটস" data-en="Updates">Updates</span>
  </div>
  <div id="postFeed" style="padding:12px 14px;display:flex;flex-direction:column;gap:14px">
    <div style="text-align:center;padding:32px;color:var(--gray)"><div style="font-size:2rem;margin-bottom:8px">📢</div><p>Loading...</p></div>
  </div>
</div>

<!-- Video overlay -->
<div id="vov2" style="display:none;position:fixed;inset:0;background:#000;z-index:800;flex-direction:column">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:rgba(0,0,0,.8);flex-shrink:0">
    <span style="color:rgba(255,255,255,.8);font-size:.84rem;font-weight:600">Video</span>
    <button onclick="closeVid()" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;width:34px;height:34px;border-radius:50%;font-size:.9rem;cursor:pointer">✕</button>
  </div>
  <div style="flex:1;position:relative">
    <iframe id="vf2" src="" frameborder="0" allow="autoplay;fullscreen" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%"></iframe>
  </div>
</div>

<style>
.post-card{background:var(--k2);border:1px solid var(--bdr);border-radius:var(--r);overflow:hidden}
.post-img{width:100%;max-height:320px;object-fit:cover;display:block;cursor:pointer}
.post-body{padding:13px 14px}
.post-title{font-size:1rem;font-weight:700;margin-bottom:6px;color:var(--w);line-height:1.4}
.post-content{font-size:.86rem;color:var(--gray);line-height:1.6;margin-bottom:10px;white-space:pre-wrap;word-break:break-word}
.post-date{font-size:.72rem;color:var(--gray);margin-bottom:10px}
.post-link{display:inline-flex;align-items:center;gap:6px;background:var(--gl);border:1px solid var(--g);color:var(--g);padding:7px 14px;border-radius:50px;font-size:.8rem;font-weight:700;text-decoration:none;margin-bottom:10px}
.react-bar{display:flex;align-items:center;gap:4px;padding:8px 0;border-top:1px solid var(--bdr);flex-wrap:wrap}
.react-btn{background:none;border:none;cursor:pointer;font-size:1.1rem;padding:4px 6px;border-radius:8px;line-height:1}
.react-btn.active{background:var(--gl)}
.react-count{font-size:.8rem;font-weight:700;color:var(--g);margin-right:4px}
.cmts-sec{border-top:1px solid var(--bdr);padding:10px 14px;display:flex;flex-direction:column;gap:7px}
.cmt-item{background:var(--k3);border-radius:var(--r2);padding:8px 11px}
.cmt-name{font-size:.76rem;font-weight:700;color:var(--g)}
.cmt-text{font-size:.82rem;color:var(--w);margin-top:2px;word-break:break-word}
.cmt-form{display:flex;gap:7px;align-items:center;margin-top:6px}
.cmt-inp{flex:1;background:var(--k3);border:1.5px solid var(--bdr2);border-radius:50px;padding:8px 14px;font-size:.84rem;font-family:inherit;color:var(--w);outline:none}
.cmt-inp:focus{border-color:var(--g)}
.cmt-send{background:linear-gradient(135deg,var(--g),var(--gd));color:var(--k);border:none;width:34px;height:34px;border-radius:50%;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}
</style>

<script>
var IS_USER = <?= $u ? 'true' : 'false' ?>;
var EMOJIS = ['👍','❤️','😂','😮','😢','🙏'];

function esc(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

async function loadPosts(){
  try{
    var r=await fetch('/api/posts?action=list');
    var d=await r.json();
    if(!d.ok||!d.posts){renderEmpty();return;}
    renderPosts(d.posts);
  }catch(e){renderEmpty();}
}

function renderEmpty(){
  document.getElementById('postFeed').innerHTML='<div style="text-align:center;padding:48px 20px;color:var(--gray)"><div style="font-size:3rem;margin-bottom:12px">📢</div><p>এখনো কোনো আপডেট নেই</p></div>';
}

function renderPosts(posts){
  var feed=document.getElementById('postFeed');
  if(!posts.length){renderEmpty();return;}
  feed.innerHTML='';
  posts.forEach(function(post){
    var card=document.createElement('div');
    card.className='post-card';
    card.id='post_'+post.id;
    var dateStr=new Date(post.created_at).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
    var html='';
    if(post.image_path)html+='<img src="'+post.image_path+'" class="post-img" alt="" onclick="openImgFull(\''+post.image_path+'\')">';
    html+='<div class="post-body">';
    if(post.title)html+='<p class="post-title">'+esc(post.title)+'</p>';
    html+='<p class="post-date">📅 '+esc(dateStr)+'</p>';
    if(post.content)html+='<p class="post-content">'+esc(post.content)+'</p>';
    if(post.video_url)html+='<button onclick="openVid(\''+esc(post.video_url)+'\')" style="display:inline-flex;align-items:center;gap:6px;background:var(--k3);border:1.5px solid var(--g);color:var(--g);padding:8px 16px;border-radius:50px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;margin-bottom:10px">▶ Video দেখুন</button>';
    if(post.link_url)html+='<a href="'+esc(post.link_url)+'" target="_blank" class="post-link">'+esc(post.link_text||'🔗 দেখুন')+'</a>';
    html+='<div class="react-bar" id="rb_'+post.id+'"><span class="react-count" id="rc_'+post.id+'">'+post.react_count+'</span>';
    EMOJIS.forEach(function(e){html+='<button class="react-btn'+(post.my_reaction===e?' active':'')+'" title="'+e+'" onclick="react('+post.id+',\''+e+'\')">'+e+'</button>';});
    html+='</div></div>';
    html+='<div class="cmts-sec" id="cmts_'+post.id+'">';
    post.comments.forEach(function(c){html+='<div class="cmt-item"><p class="cmt-name">'+esc(c.user_name||'User')+'</p><p class="cmt-text">'+esc(c.comment)+'</p></div>';});
    if(IS_USER)html+='<div class="cmt-form"><input class="cmt-inp" id="ci_'+post.id+'" placeholder="মন্তব্য করুন..." onkeydown="if(event.key===\'Enter\')sendComment('+post.id+')"><button class="cmt-send" onclick="sendComment('+post.id+')">➤</button></div>';
    html+='</div>';
    card.innerHTML=html;
    feed.appendChild(card);
  });
}

async function react(postId,emoji){
  if(!IS_USER){alert('লাইক দিতে লগিন করুন');return;}
  try{
    var r=await fetch('/api/posts?action=react',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:postId,reaction:emoji})});
    var d=await r.json();if(!d.ok)return;
    var rc=document.getElementById('rc_'+postId);if(rc)rc.textContent=d.react_count;
    var rb=document.getElementById('rb_'+postId);
    if(rb)rb.querySelectorAll('.react-btn').forEach(function(b){b.classList.toggle('active',b.title===d.my_reaction);});
  }catch(e){}
}

async function sendComment(postId){
  if(!IS_USER)return;
  var inp=document.getElementById('ci_'+postId);
  var text=(inp.value||'').trim();if(!text)return;inp.value='';
  try{
    var r=await fetch('/api/posts?action=comment',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({post_id:postId,comment:text})});
    var d=await r.json();if(!d.ok)return;
    var cmts=document.getElementById('cmts_'+postId);
    if(cmts){var c=document.createElement('div');c.className='cmt-item';c.innerHTML='<p class="cmt-name">'+esc(d.user_name)+'</p><p class="cmt-text">'+esc(text)+'</p>';cmts.insertBefore(c,cmts.querySelector('.cmt-form'));}
  }catch(e){}
}

function openVid(url){
  var u=url;
  if(u.includes('youtube.com/watch?v='))u=u.replace('watch?v=','embed/')+'?autoplay=1';
  else if(u.includes('youtu.be/')){var vid=u.split('youtu.be/')[1].split('?')[0];u='https://www.youtube.com/embed/'+vid+'?autoplay=1';}
  document.getElementById('vf2').src=u;
  document.getElementById('vov2').style.display='flex';
  document.body.style.overflow='hidden';
}
function closeVid(){
  document.getElementById('vf2').src='';
  document.getElementById('vov2').style.display='none';
  document.body.style.overflow='';
}

function openImgFull(src){
  var ov=document.createElement('div');
  ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:900;display:flex;align-items:center;justify-content:center;padding:20px';
  ov.innerHTML='<img src="'+src+'" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:8px"><button onclick="this.parentElement.remove();document.body.style.overflow=\'\'" style="position:absolute;top:14px;right:14px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;width:36px;height:36px;border-radius:50%;font-size:.9rem;cursor:pointer">✕</button>';
  ov.onclick=function(e){if(e.target===ov){ov.remove();document.body.style.overflow='';}};
  document.body.appendChild(ov);
  document.body.style.overflow='hidden';
}

loadPosts();
</script>

<?php render_nav('me'); render_foot(); ?>