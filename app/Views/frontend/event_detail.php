<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
/* ---------------------------------------------------------------------
   Many live events are blog posts: the date, time and venue fields are
   empty and the real details are typed inside the description. Read
   them from there when the fields are blank, never show 1970, and only
   show boxes that have something in them.
   --------------------------------------------------------------------- */
require_once __DIR__ . '/_posthelp.php';
$validDate = static function ($d) { $t = strtotime((string) $d); return ($t && $t > strtotime('2000-01-01')) ? $t : null; };

$raw  = hi_rich($e['description'] ?? '');
$flat = preg_replace('~<\s*(br|/p|/div|/li|/h[1-6])\s*/?>~i', "\n", $raw);
$txt  = html_entity_decode(strip_tags($flat), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$lines = [];
foreach (preg_split('/\R/u', $txt) as $ln) {
    $ln = hi_fix_mojibake(trim($ln));                                                          // "ÔÇô" -> "–", "ðŸŽ‰" -> "🎉"
    $ln = trim(preg_replace('/\s+/u', ' ', $ln));
    if ($ln !== '') { $lines[] = $ln; }
}
$all = implode("\n", $lines);

// date
$ts = $validDate($e['event_date'] ?? '');
if (! $ts && preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?,?\s+(\d{4})\b/i', $all, $m)) { $ts = $validDate("$m[1] $m[2] $m[3]"); }
if (! $ts && preg_match('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+(\d{4})\b/i', $all, $m)) { $ts = $validDate("$m[2] $m[1] $m[3]"); }
$dateTxt = $ts ? date('D, d M Y', $ts) : '';

// time
$timeTxt = trim((string) ($e['event_time'] ?? ''));
if ($timeTxt === '' && preg_match('/\b\d{1,2}(?::\d{2})?\s*(?:AM|PM)\b(?:\s*(?:onwards|–|-|to)\s*(?:\d{1,2}(?::\d{2})?\s*(?:AM|PM))?)?/i', $all, $m)) { $timeTxt = trim($m[0]); }

// venue
$venue = implode(', ', array_filter([trim((string) ($e['venue'] ?? '')), trim((string) ($e['locality'] ?? ''))]));
$venueLine = '';
if ($venue === '') {
    foreach ($lines as $ln) {
        if (mb_strlen($ln) < 110 && preg_match('/\b(Sector|Mall|Noida|Road|Marg|Complex|Plaza|Hall)\b/i', $ln) && ! preg_match('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+\d{4}|\b(AM|PM)\b/i', $ln) && substr_count($ln, ',') >= 1) {
            $venue = $venueLine = $ln; break;
        }
    }
}

// booking link
$book = '';
if (preg_match('~href=["\'](https?://[^"\']+)["\']~i', $raw, $m)) { $book = $m[1]; }

// intro paragraphs + short "what to expect" points
$intro = []; $points = [];
foreach ($lines as $ln) {
    if ($ln === $venueLine) continue;
    if (preg_match('/^(about the event|event details?)$/i', $ln)) continue;
    if (stripos($ln, 'book now') !== false || stripos($ln, 'book your') === 0) { continue; }
    $isDateLine = preg_match('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+\d{4}\b|\b\d{4}\b.*\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)/i', $ln) && mb_strlen($ln) < 50;
    $isTimeLine = preg_match('/^\d{1,2}(?::\d{2})?\s*(AM|PM)/i', $ln);
    if ($isDateLine || $isTimeLine) continue;
    if (mb_strlen($ln) <= 45) { $points[] = $ln; } else { $intro[] = $ln; }
}
$sub = $intro ? mb_strimwidth($intro[0], 0, 150, '…') : '';
$price = trim((string) ($e['price'] ?? ''));
$cat   = trim((string) ($e['category'] ?? ''));
$area  = trim((string) ($e['locality'] ?? ''));
$img   = ! empty($e['blog_thumbnail']) ? hi_media($e['blog_thumbnail'], 'blog') : hi_topic_img('event', (int) $e['id'], 1000, 640, $e['title'] ?? '');
$dir   = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(($venue !== '' ? $venue : $e['title']) . ' Noida');
$gcal  = $ts ? 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . rawurlencode($e['title']) . '&dates=' . date('Ymd', $ts) . '/' . date('Ymd', $ts + 86400) . '&location=' . rawurlencode($venue) : '';
$upcoming = $ts && $ts >= strtotime('today');
$tags = class_exists(\App\Controllers\Events::class) ? \App\Controllers\Events::tags(($e['title'] ?? '') . ' ' . ($e['description'] ?? '')) : [];

$chips = [];
if ($dateTxt) $chips[] = ['calendar', $dateTxt, 'blue'];
if ($timeTxt !== '') $chips[] = ['clock', $timeTxt, 'ok'];
if ($ts && ! $upcoming) $chips[] = ['circle-check', 'This event has ended'];
$acts = [];
if ($book) $acts[] = ['ticket', 'Book now', $book, 'primary'];
$acts[] = ['diamond-turn-right', 'Directions', $dir, $book ? '' : 'primary'];
$acts[] = ['arrow-left', 'All events', hi_page('events')];
?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Events', hi_page('events')], [$e['title'], null]],
  'title'  => esc($e['title']),
  'sub'    => $sub,
  'chips'  => $chips,
  'facts'  => [['calendar', 'Date', $dateTxt], ['clock', 'Time', $timeTxt], ['ticket', 'Entry', $price], ['location-dot', 'Venue', $venue, true]],
  'actions'=> $acts,
  'slides' => [[$img, null, null]],
], ['saveData' => false]) ?>

<div class="wrap ev-grid">
  <div class="ev-main">
    <section class="ev-card">
      <h2 class="ev-h"><i class="ev-ic fa-solid fa-circle-info"></i> <?= esc(hi_c('event_detail.about_this_event', 'About this event')) ?></h2>
      <?php if ($tags): ?><div class="ev-tags"><?php foreach ($tags as $t): ?><span><?= esc($t) ?></span><?php endforeach; ?></div><?php endif; ?>
      <?php if ($intro): ?>
        <div class="ev-text"><?php foreach ($intro as $p): ?><p><?= esc($p) ?></p><?php endforeach; ?></div>
      <?php elseif (! $points): ?>
        <div class="ev-text prose"><?= $raw ?></div>
      <?php endif; ?>
      <?php if ($points): ?>
        <div class="ev-pts"><span class="ev-pts-l">What to expect</span>
          <?php foreach ($points as $p): ?><span class="ev-chip"><i class="fa-solid fa-star"></i> <?= esc($p) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php
      $det = array_values(array_filter([
        ['calendar', 'Date', $dateTxt], ['clock', 'Time', $timeTxt], ['location-dot', 'Venue', $venue],
        ['ticket', 'Entry', $price], ['tag', 'Category', $cat], ['map-pin', 'Area', $area],
      ], static fn ($r) => trim((string) $r[2]) !== ''));
    ?>
    <?php if ($det): ?>
    <section class="ev-card">
      <h2 class="ev-h"><i class="ev-ic fa-solid fa-calendar-check"></i> <?= esc(hi_c('event_detail.event_details', 'Event details')) ?></h2>
      <div class="ev-det">
        <?php foreach ($det as $d): ?>
          <div class="ev-di<?= $d[1] === 'Venue' ? ' wide' : '' ?>"><i class="fa-solid fa-<?= $d[0] ?>"></i><div><small><?= esc($d[1]) ?></small><b><?= esc($d[2]) ?></b></div></div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <aside class="ev-side">
    <div class="ev-ticket">
      <?php if ($ts): ?>
        <div class="ev-date"><span class="m"><?= date('M', $ts) ?></span><span class="d"><?= date('d', $ts) ?></span><span class="y"><?= date('Y', $ts) ?></span></div>
      <?php else: ?>
        <div class="ev-date ev-nodate"><i class="fa-solid fa-champagne-glasses"></i><span><?= esc($tags[0] ?? 'Event') ?><small>Date in the details below</small></span></div>
      <?php endif; ?>
      <div class="ev-tk-bd">
        <h3><?= esc(hi_c('event_detail.attend_this_event', 'Attend this event')) ?></h3>
        <?php if ($ts): ?><p><i class="fa-regular fa-calendar"></i> <?= esc(date('l, d F Y', $ts)) ?></p><?php endif; ?>
        <?php if ($timeTxt !== ''): ?><p><i class="fa-regular fa-clock"></i> <?= esc($timeTxt) ?></p><?php endif; ?>
        <?php if ($venue !== ''): ?><p><i class="fa-solid fa-location-dot"></i> <?= esc($venue) ?></p><?php endif; ?>
        <?php if ($price !== ''): ?><p><i class="fa-solid fa-ticket"></i> <?= esc($price) ?></p><?php endif; ?>
        <?php if ($venue === ''): ?><p><i class="fa-solid fa-location-dot"></i> <?= esc($area !== '' ? $area . ', Noida' : 'Noida') ?></p><?php endif; ?>
        <?php if ($tags): ?><p><i class="fa-solid fa-tags"></i> <?= esc(implode(' · ', array_slice($tags, 0, 3))) ?></p><?php endif; ?>
        <?php if ($ts && ! $upcoming): ?><p class="ev-ended"><i class="fa-solid fa-circle-check"></i> This event has ended</p><?php endif; ?>
      </div>
      <div class="ev-btns">
        <?php if ($book): ?><a class="ev-btn primary" href="<?= esc($book, 'attr') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-ticket"></i> Book now</a><?php endif; ?>
        <a class="ev-btn<?= $book ? '' : ' primary' ?>" href="<?= esc($dir, 'attr') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-diamond-turn-right"></i> Get directions</a>
        <?php if ($gcal && $upcoming): ?><a class="ev-btn" href="<?= esc($gcal, 'attr') ?>" target="_blank" rel="noopener"><i class="fa-regular fa-calendar-plus"></i> Add to calendar</a><?php endif; ?>
        <button type="button" class="ev-btn" id="evShare"><i class="fa-solid fa-share-nodes"></i> Share</button>
      </div>
    </div>
  </aside>
</div>
<script>
(function(){ var b=document.getElementById('evShare'); if(!b) return;
  b.addEventListener('click',function(){ var d={title:document.title,url:location.href};
    if(navigator.share){ navigator.share(d).catch(function(){}); }
    else if(navigator.clipboard){ navigator.clipboard.writeText(location.href).then(function(){ b.innerHTML='<i class="fa-solid fa-check"></i> Link copied'; setTimeout(function(){ b.innerHTML='<i class="fa-solid fa-share-nodes"></i> Share'; },2000); }); } });
})();
</script>

<?php if (! empty($more)): ?>
<section class="sec" style="background:var(--bg)"><div class="wrap">
  <div class="sec-head"><h2><?= esc(hi_c('event_detail.more_events_in_noida', 'More events in Noida')) ?></h2></div>
  <div class="lgrid">
    <?php foreach ($more as $m): $_mt = $validDate($m['event_date'] ?? ''); ?>
      <a class="lcard" href="<?= hi_event_url($m) ?>">
        <span class="ph"><img src="<?= esc(hi_topic_img('event', (int) $m['id'], 440, 300, $m['title'] ?? '')) ?>" alt="<?= esc($m['title']) ?>"></span>
        <span class="bd"><b><?= esc($m['title']) ?></b><?php if ($_mt): ?><span class="loc"><i class="fa-regular fa-calendar"></i> <?= esc(date('d M Y', $_mt)) ?></span><?php endif; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php endif; ?>
<?= $this->endSection() ?>
