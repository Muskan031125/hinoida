<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
/* ---------------------------------------------------------------------
   Offers work like events: many are typed as free text, so the code,
   discount and expiry are read from the description when the fields
   are empty. Nothing shows 1970, and empty boxes are left out.
   --------------------------------------------------------------------- */
require_once __DIR__ . '/_posthelp.php';

$raw   = hi_fix_mojibake(hi_rich($o['description'] ?? ''));
$lines = hi_post_lines($raw);
$all   = implode("\n", $lines);
$title = hi_dec($o['title'] ?? '');

// discount
$disc = trim(hi_dec($o['discount'] ?? ''));
if ($disc === '' && preg_match('/\b(?:flat\s+)?(?:upto\s+|up to\s+)?(?:\d{1,2}\s?%|(?:rs\.?|₹|inr)\s?\d[\d,]*)\s*off\b|\bbuy\s+\d+\s+get\s+\d+(?:\s+free)?\b/iu', $all, $m)) { $disc = trim($m[0]); }

if ($disc === '' && preg_match('/\bfree\b/i', $title . ' ' . $all)) { $disc = 'FREE'; }

// coupon code
$code = trim((string) ($o['code'] ?? ''));
if ($code === '' && preg_match('/\b(?:code|coupon)\s*[:\-–]?\s*["\']?([A-Z0-9]{4,16})\b/u', $all, $m)) { $code = $m[1]; }

// valid till
$till = hi_valid_ts($o['valid_till'] ?? '');
if (! $till && preg_match('/\b(?:valid|offer ends?|ends|till|until|upto)\b[^\n]{0,30}/i', $all, $m)) { $till = hi_text_date(substr($all, strpos($all, $m[0]), 60)); }
$tillTxt = $till ? date('d M Y', $till) : '';
$expired = $till && $till < strtotime('today');

$biz  = trim(hi_dec($o['business'] ?? ''));
$area = trim(hi_dec($o['locality'] ?? ''));
$cat  = trim(hi_dec($o['category'] ?? ''));
$grab = hi_post_link($raw);
$dir  = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim(($biz !== '' ? $biz : $title) . ' ' . $area) . ' Noida');
$img  = hi_topic_img('offer', (int) $o['id'], 1000, 640, $title . ' ' . $biz);

// text: skip lines that only repeat the code / date already shown
$skip = [];
foreach ($lines as $ln) {
    if (mb_strlen($ln) < 60 && (($code !== '' && stripos($ln, $code) !== false) || preg_match('/^(valid|offer ends?|till|until)\b/i', $ln))) { $skip[] = $ln; }
}
[$intro, $points] = hi_split_points($lines, $skip);
$sub = $intro ? mb_strimwidth($intro[0], 0, 150, '…') : '';

$chips = [];
if ($disc !== '') $chips[] = ['tag', $disc, 'hot'];
if ($tillTxt)     $chips[] = $expired ? ['circle-check', 'Offer ended ' . $tillTxt] : ['calendar', 'Valid till ' . $tillTxt, 'ok'];
$acts = [];
if ($grab) $acts[] = ['bag-shopping', 'Grab deal', $grab, 'primary'];
$acts[] = ['diamond-turn-right', 'Directions', $dir, $grab ? '' : 'primary'];
$acts[] = ['arrow-left', 'All offers', hi_page('offers')];
?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Offers', hi_page('offers')], [$title, null]],
  'title'  => esc($title),
  'sub'    => $sub,
  'chips'  => $chips,
  'facts'  => [['ticket', 'Code', $code], ['calendar', 'Valid till', $tillTxt],
               ['shop', 'Business', implode(', ', array_filter([$biz, $area])), true]],
  'actions'=> $acts,
  'slides' => [[$img, null, null]],
], ['saveData' => false]) ?>

<div class="wrap ev-grid">
  <div class="ev-main">
    <section class="ev-card">
      <h2 class="ev-h"><i class="ev-ic fa-solid fa-circle-info"></i> <?= esc(hi_c('offer_detail.about_this_offer', 'About this offer')) ?></h2>
      <?php if ($intro): ?>
        <div class="ev-text"><?php foreach ($intro as $p): ?><p><?= esc($p) ?></p><?php endforeach; ?></div>
      <?php elseif (! $points): ?>
        <div class="ev-text prose"><?= $raw ?: '<p>' . esc($title) . '</p>' ?></div>
      <?php endif; ?>
      <?php if ($points): ?>
        <div class="ev-pts"><span class="ev-pts-l">What you get</span>
          <?php foreach ($points as $p): ?><span class="ev-chip"><i class="fa-solid fa-check"></i> <?= esc($p) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php
      $det = array_values(array_filter([
        ['tag', 'Discount', $disc], ['ticket', 'Coupon code', $code], ['calendar', 'Valid till', $tillTxt],
        ['shop', 'Business', $biz], ['map-pin', 'Area', $area], ['layer-group', 'Category', $cat],
      ], static fn ($r) => trim((string) $r[2]) !== ''));
    ?>
    <?php if (count($det) > 1): ?>
    <section class="ev-card">
      <h2 class="ev-h"><i class="ev-ic fa-solid fa-receipt"></i> <?= esc(hi_c('offer_detail.offer_details', 'Offer details')) ?></h2>
      <div class="ev-det">
        <?php foreach ($det as $d): ?>
          <div class="ev-di"><i class="fa-solid fa-<?= $d[0] ?>"></i><div><small><?= esc($d[1]) ?></small><b><?= esc($d[2]) ?></b></div></div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (! $expired): ?>
    <section class="ev-card of-how">
      <h2 class="ev-h"><i class="ev-ic fa-solid fa-list-ol"></i> How to use it</h2>
      <ol class="of-steps">
        <?php if ($code !== ''): ?><li><b>Copy the code</b><span>Tap “Copy” on the code box.</span></li><?php endif; ?>
        <li><b>Visit <?= esc($biz !== '' ? $biz : 'the store') ?></b><span><?= $grab ? 'Open the deal link or walk in.' : 'Walk in or call ahead.' ?></span></li>
        <li><b><?= $code !== '' ? 'Show or enter the code' : 'Mention this offer' ?></b><span>Tell them you saw it on HiNoida.</span></li>
      </ol>
    </section>
    <?php endif; ?>
  </div>

  <aside class="ev-side">
    <div class="ev-ticket">
      <?php if ($disc !== ''): ?><div class="ev-date of-disc"><span class="d"><?= esc($disc) ?></span></div><?php endif; ?>
      <div class="ev-tk-bd">
        <h3><?= esc(hi_c('offer_detail.grab_this_deal', 'Grab this deal')) ?></h3>
        <?php if ($code !== ''): ?>
          <div class="of-code"><div><small>Coupon code</small><b id="ofCode"><?= esc($code) ?></b></div>
            <button type="button" id="ofCopy"><i class="fa-regular fa-copy"></i> Copy</button></div>
        <?php else: ?>
          <div class="of-code of-nocode"><div><small>Coupon code</small><b>None listed</b></div><i class="fa-solid fa-circle-check"></i></div>
          <p class="of-hint"><i class="fa-solid fa-circle-info"></i> Just tell them you saw this offer on HiNoida.</p>
        <?php endif; ?>
        <?php if ($tillTxt): ?><p><i class="fa-regular fa-calendar"></i> <?= $expired ? 'Ended on ' : 'Valid till ' ?><?= esc($tillTxt) ?></p><?php endif; ?>
        <?php if ($biz !== '' || $area !== ''): ?><p><i class="fa-solid fa-shop"></i> <?= esc(implode(', ', array_filter([$biz, $area]))) ?></p><?php endif; ?>
        <?php if ($expired): ?><p class="ev-ended"><i class="fa-solid fa-circle-info"></i> This offer has ended — check the store for current deals</p><?php endif; ?>
      </div>
      <div class="ev-btns">
        <?php if ($grab): ?><a class="ev-btn primary" href="<?= esc($grab, 'attr') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-bag-shopping"></i> Grab deal</a><?php endif; ?>
        <a class="ev-btn<?= $grab ? '' : ' primary' ?>" href="<?= esc($dir, 'attr') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-diamond-turn-right"></i> Get directions</a>
        <button type="button" class="ev-btn" id="evShare"><i class="fa-solid fa-share-nodes"></i> Share</button>
      </div>
    </div>
  </aside>
</div>
<script>
(function(){
  function flash(b,html,back){ b.innerHTML=html; setTimeout(function(){ b.innerHTML=back; },2000); }
  var c=document.getElementById('ofCopy');
  if(c) c.addEventListener('click',function(){ var t=document.getElementById('ofCode').textContent;
    if(navigator.clipboard) navigator.clipboard.writeText(t).then(function(){ c.classList.add('ok'); flash(c,'<i class="fa-solid fa-check"></i> Copied','<i class="fa-regular fa-copy"></i> Copy'); setTimeout(function(){ c.classList.remove('ok'); },2000); }); });
  var b=document.getElementById('evShare'); if(!b) return;
  b.addEventListener('click',function(){ var d={title:document.title,url:location.href};
    if(navigator.share){ navigator.share(d).catch(function(){}); }
    else if(navigator.clipboard){ navigator.clipboard.writeText(location.href).then(function(){ flash(b,'<i class="fa-solid fa-check"></i> Link copied','<i class="fa-solid fa-share-nodes"></i> Share'); }); } });
})();
</script>

<?php if (! empty($more)): ?>
<section class="sec" style="background:var(--bg)"><div class="wrap">
  <div class="sec-head"><h2><?= esc(hi_c('offer_detail.more_deals_in_noida', 'More deals in Noida')) ?></h2></div>
  <div class="lgrid">
    <?php foreach ($more as $m): $_mt = hi_dec($m['title'] ?? ''); $_mb = trim(hi_dec($m['business'] ?? '')); $_md = trim(hi_dec($m['discount'] ?? '')); ?>
      <a class="lcard" href="<?= hi_offer_url($m) ?>">
        <span class="ph"><img src="<?= esc(hi_topic_img('offer', (int) $m['id'], 440, 300, $_mt . ' ' . $_mb)) ?>" alt="<?= esc($_mt) ?>" loading="lazy"><?php if ($_md !== ''): ?><span class="rate" style="background:#e0446b"><?= esc($_md) ?></span><?php endif; ?></span>
        <span class="bd"><b><?= esc($_mt) ?></b><?php if ($_mb !== ''): ?><span class="loc"><i class="fa-solid fa-shop"></i> <?= esc($_mb) ?></span><?php endif; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php endif; ?>
<?= $this->endSection() ?>
