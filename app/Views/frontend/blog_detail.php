<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
require_once __DIR__ . '/_posthelp.php';

$title  = hi_dec($post['title'] ?? '');
$type   = ucfirst(trim((string) ($post['blog_type'] ?? '')) ?: 'News');
$cover  = ! empty($post['blog_thumbnail']) ? hi_media($post['blog_thumbnail'], 'blog') : hi_topic_img('blog', (int) $post['id'], 1200, 500, $title);
$body   = hi_fix_mojibake(hi_rich($post['blog_text'] ?? ''));   // stored double-encoded; hi_rich decodes it
$lines  = hi_post_lines($body);
$sub    = '';
foreach ($lines as $ln) { if (mb_strlen($ln) > 50) { $sub = mb_strimwidth($ln, 0, 150, '…'); break; } }
$pubTs  = hi_valid_ts($post['added_date'] ?? '');
$pub    = $pubTs ? date('d M Y', $pubTs) : '';
$mins   = hi_read_mins($body);
$nCom   = count($comments ?? []);

$chips = [['newspaper', $type, 'blue']];
if ($pub) $chips[] = ['calendar', $pub];
$chips[] = ['clock', $mins . ' min read', 'ok'];
?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['News', hi_page('news')], [$title, null]],
  'title'  => esc($title),
  'sub'    => $sub,
  'chips'  => $chips,
  'facts'  => [['calendar', 'Published', $pub], ['clock', 'Reading time', $mins . ' min'], ['comments', 'Comments', (string) $nCom]],
  'actions'=> [['book-open', 'Read now', '#read', 'primary'], ['comments', 'Comments', '#comments'], ['arrow-left', 'All guides', hi_page('news')]],
  'slides' => [[$cover, null, null]],
], ['saveData' => false]) ?>

<div class="wrap ev-grid">
  <div class="ev-main">
    <article class="ev-card bl-art" id="read">
      <div class="bl-meta">
        <span><i class="fa-solid fa-newspaper"></i> <?= esc($type) ?></span>
        <?php if ($pub): ?><span><i class="fa-regular fa-calendar"></i> <?= esc($pub) ?></span><?php endif; ?>
        <span><i class="fa-regular fa-clock"></i> <?= $mins ?> min read</span>
      </div>
      <div class="prose bl-prose"><?= $body !== '' ? $body : '<p>' . esc($title) . '</p>' ?></div>
      <div class="bl-foot">
        <a href="<?= hi_page('news') ?>"><i class="fa-solid fa-arrow-left"></i> Back to all guides</a>
        <button type="button" class="bl-share" id="evShare"><i class="fa-solid fa-share-nodes"></i> Share</button>
      </div>
    </article>

    <section class="ev-card" id="comments">
      <h2 class="ev-h"><i class="ev-ic fa-solid fa-comments"></i>
        <?= $nCom ? $nCom . ' comment' . ($nCom === 1 ? '' : 's') : 'Comments' ?></h2>

      <?php if (! empty($flash)): ?>
        <div class="bl-note ok"><i class="fa-solid fa-circle-check"></i> <?= esc($flash) ?></div>
      <?php endif; ?>
      <?php if (! empty($cerr)): ?>
        <div class="bl-note err"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($cerr) ?></div>
      <?php endif; ?>

      <?php foreach (($comments ?? []) as $cm): $cn = trim((string) ($cm['user_name'] ?: $cm['name'])) ?: 'Reader'; ?>
        <div class="rev bl-rev">
          <div class="rev-av" style="background:<?= esc(hi_avatar_bg($cn), 'attr') ?>"><?= esc(hi_initial($cn)) ?></div>
          <div class="rev-bd">
            <div class="rev-top">
              <b><?php if (! empty($cm['user_id'])): ?><a href="<?= base_url('u/' . (int) $cm['user_id']) ?>" style="color:inherit"><?= esc($cn) ?></a><?php else: ?><?= esc($cn) ?><?php endif; ?></b>
              <?php $_ct = hi_valid_ts($cm['created_at'] ?? ''); if ($_ct): ?><span><?= esc(date('d M Y', $_ct)) ?></span><?php endif; ?>
            </div>
            <p><?= nl2br(esc($cm['comment'] ?? '')) ?></p>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (! $nCom): ?>
        <p class="bl-empty"><i class="fa-regular fa-comment-dots"></i> <?= esc(hi_c('blog_detail.p_nothing_here_yet_be_the_first_to_say_somethi', 'Nothing here yet — be the first to say something.')) ?></p>
      <?php endif; ?>

      <form method="post" action="<?= base_url('news/post/' . (int) $post['id'] . '/comment') ?>" class="bl-form">
        <b>Leave a comment</b>
        <?php if (! session()->get('uid')): ?>
          <div class="bl-two">
            <input class="authinp" name="name" placeholder="<?= esc(hi_c('blog_detail.ph_your_name', 'Your name'), 'attr') ?>" required>
            <input class="authinp" name="email" type="email" placeholder="<?= esc(hi_c('blog_detail.ph_email_not_published', 'Email (not published)'), 'attr') ?>">
          </div>
        <?php endif; ?>
        <textarea class="authinp" name="comment" rows="4" placeholder="What did you think?" required></textarea>
        <?php /* honeypot — hidden from people, tempting to bots */ ?>
        <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <div class="bl-send">
          <button class="authbtn"><?= esc(hi_c('blog_detail.btn_post_comment', 'Post comment')) ?></button>
          <?php if (! session()->get('uid')): ?>
            <small>Visitor comments are checked first. <a href="<?= hi_page('login') ?>">Log in</a> to post straight away.</small>
          <?php endif; ?>
        </div>
      </form>
    </section>
  </div>

  <aside class="ev-side">
    <div class="ev-ticket">
      <div class="ev-date bl-badge"><i class="fa-solid fa-book-open"></i><span class="m"><?= $mins ?> min read</span></div>
      <div class="ev-tk-bd">
        <h3>About this <?= esc(strtolower($type)) ?></h3>
        <p><i class="fa-solid fa-newspaper"></i> <?= esc($type) ?> · HiNoida</p>
        <?php if ($pub): ?><p><i class="fa-regular fa-calendar"></i> Published <?= esc($pub) ?></p><?php endif; ?>
        <p><i class="fa-regular fa-comments"></i> <?= $nCom ?> comment<?= $nCom === 1 ? '' : 's' ?></p>
      </div>
      <div class="ev-btns">
        <a class="ev-btn primary" href="#comments"><i class="fa-solid fa-pen"></i> Write a comment</a>
        <button type="button" class="ev-btn" id="blShare2"><i class="fa-solid fa-share-nodes"></i> Share</button>
        <a class="ev-btn" href="<?= hi_page('news') ?>"><i class="fa-solid fa-arrow-left"></i> All guides</a>
      </div>
    </div>
  </aside>
</div>
<script>
(function(){
  ['evShare','blShare2'].forEach(function(id){ var b=document.getElementById(id); if(!b) return; var back=b.innerHTML;
    b.addEventListener('click',function(){ var d={title:document.title,url:location.href};
      if(navigator.share){ navigator.share(d).catch(function(){}); }
      else if(navigator.clipboard){ navigator.clipboard.writeText(location.href).then(function(){ b.innerHTML='<i class="fa-solid fa-check"></i> Link copied'; setTimeout(function(){ b.innerHTML=back; },2000); }); } }); });
})();
</script>

<?php if (! empty($more)): ?>
<section class="sec" style="background:var(--bg)"><div class="wrap">
  <div class="sec-head"><h2><?= esc(hi_c('blog_detail.more_from_hinoida', 'More from HiNoida')) ?></h2></div>
  <div class="lgrid">
    <?php foreach ($more as $p): $_t = hi_dec($p['title'] ?? ''); $_d = hi_valid_ts($p['added_date'] ?? '');
          $img = ! empty($p['blog_thumbnail']) ? hi_media($p['blog_thumbnail'], 'blog') : hi_topic_img('blog', (int) $p['id'], 440, 300, $_t); ?>
      <a class="lcard" href="<?= hi_news_url($p) ?>">
        <span class="ph"><img src="<?= esc($img) ?>" alt="<?= esc($_t) ?>" loading="lazy"></span>
        <span class="bd"><b><?= esc($_t) ?></b><?php if ($_d): ?><span class="loc"><i class="fa-regular fa-calendar"></i> <?= esc(date('d M Y', $_d)) ?></span><?php endif; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php endif; ?>
<script>
/* remember this read for "For you" on Noida Today (kept on this device only) */
(function(){ try{ var K='hinoida_reads', m=JSON.parse(localStorage.getItem(K)||'{}')||{}; m.t=m.t||{}; m.r=m.r||[];
  var id='<?= (int) $post['id'] ?>', cs='<?= esc(implode(',', array_filter(array_map('intval', preg_split('/[,\s]+/', (string) ($post['category_id'] ?? '')) ?: []))), 'js') ?>'.split(',').filter(Boolean);
  if(m.r.indexOf(id)<0){ cs.forEach(function(c){ m.t[c]=(m.t[c]||0)+1; }); m.r.push(id); m.r=m.r.slice(-200); localStorage.setItem(K,JSON.stringify(m)); }
}catch(e){} })();
</script>
<?= $this->endSection() ?>
