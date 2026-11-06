<?= $this->extend('layouts/main') ?>
<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/category.css') ?>?v=1">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$img = static fn (array $l): string => hi_cover($l);
// only surface sub-categories that actually have listings — a wall of
// "0 places" cards is worse than showing nothing
$children = array_values(array_filter($children, static fn ($c) => (int) ($c['listing_count'] ?? 0) > 0));
?>
<?php
  $fc  = ($facet ?? []) + ['area'=>[],'rating'=>['4'=>0,'3'=>0],'open'=>0,'sub'=>[],'amen'=>[]];
  $tot = array_sum($fc['area']);
  $ss  = static fn ($v) => ($sort ?? '') === $v ? 'selected' : '';
  $sel = static fn ($a,$bb) => (string)$a === (string)$bb ? 'selected' : '';
  $matched  = $pg['total'] ?? count($listings);
  $_areasN  = count(array_filter($fc['area'], static fn ($n) => (int) $n > 0));
  $_heroImg = ! empty($listings) ? $img($listings[0]) : base_url('assets/img/noida.jpg');
?>
<?php
  $_slides = [];
  foreach (array_slice($listings, 0, 6) as $_x) { $_slides[] = [hi_cover($_x), hi_listing_url($_x), $_x['name']]; }
  ob_start(); ?>

    <?php if ($children): ?>
      <div class="ph-types">
        <?php foreach ($children as $ch): ?>
          <a href="<?= hi_category_url(esc($ch['slug'], 'url')) ?>"><?= esc($ch['name']) ?> <em><?= (int) ($ch['listing_count'] ?? 0) ?></em></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
<?php $_extra = ob_get_clean(); ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [[$cat['name'], null]],
  'title'  => esc($cat['name']) . ' <span>in Noida</span>',
  'sub'    => 'Compare ' . number_format($total ?? count($listings)) . ' ' . strtolower($cat['name']) . ' businesses in Noida — ratings, timings, photos and direct contact.',
  'extra'  => $_extra,
  'facts'  => [],   // Businesses / Areas covered / Open now box removed from the banner
  'links'  => [['folder-open', 'All categories', hi_page('categories')], ['map', 'Browse by area', hi_page('areas')], ['square-plus', 'List your business', base_url('list')]],
  'slides' => $_slides,
], ['saveData' => false]) ?>

<section class="cp-body">
  <div class="wrap">
    <div class="cp-toolbar sx">
      <div class="cp-tb-head">
        <h2>All <?= esc($cat['name']) ?> <span><?= number_format($matched) ?><?= ($total ?? 0) > $matched ? ' of ' . number_format($total) : '' ?> results</span></h2>
        <?php if ($listings): ?><?= view('frontend/_viewtoggle') ?><?php endif; ?>
      </div>
      <form method="get" id="catfilter" class="filterbar" data-sxq="Search in <?= esc($cat['name'], 'attr') ?>…" data-sxqv="<?= esc($f['q'] ?? '', 'attr') ?>" data-sx="open:1:clock:Open now|rating:4:star:4★ &amp; up">
        <select name="area" onchange="document.getElementById('catfilter').submit()">
          <option value="">All areas (<?= $tot ?>)</option>
          <?php /* tidy names ("Sector - 66" -> "Sector 66"), skip areas with nothing in them */
            $_an = static function ($n) { $n = trim(preg_replace('/\s+/', ' ', html_entity_decode((string) $n, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
              return preg_match('/^sector\s*-?\s*(\d+)\s*$/i', $n, $m) ? 'Sector ' . $m[1] : ucwords($n); }; ?>
          <?php foreach ($areas as $a): $n = (int) ($fc['area'][(int)$a['id']] ?? 0); if (! $n && (string) $f['area'] !== (string) $a['id']) continue; ?>
            <option value="<?= (int)$a['id'] ?>" <?= $sel($f['area'],$a['id']) ?>><?= esc($_an($a['name'])) ?> (<?= $n ?>)</option>
          <?php endforeach; ?>
        </select>
    <?php /* every listing shows (0) when nothing has been reviewed —
               a filter that can only ever return nothing is worse than none */ ?>
<?php if (array_sum($fc['rating'] ?? []) > 0): ?>
    <select name="rating" onchange="document.getElementById('catfilter').submit()">
          <option value="">Any rating</option>
          <?php foreach (['4'=>'4★ &amp; up', '3'=>'3★ &amp; up'] as $rk => $rlabel): $rn = (int) ($fc['rating'][$rk] ?? 0); ?>
            <option value="<?= $rk ?>" <?= $sel($f['rating'],$rk) ?><?= $rn ? '' : ' disabled' ?>><?= $rlabel ?> (<?= $rn ?>)</option>
          <?php endforeach; ?>
        </select>
<?php endif; ?>
        <?php if (! empty($subs)): ?>
        <select name="sub" onchange="document.getElementById('catfilter').submit()">
          <option value="">All <?= esc($cat['name']) ?></option>
          <?php foreach ($subs as $sc): ?>
            <option value="<?= (int)$sc['id'] ?>" <?= $sel($f['sub'] ?? 0, $sc['id']) ?>><?= esc($sc['name']) ?> (<?= (int)($fc['sub'][(int)$sc['id']] ?? 0) ?>)</option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if (! empty($amens)): ?>
        <select name="amenity[]" multiple onchange="document.getElementById('catfilter').submit()">
          <option value="" disabled hidden>Any facility</option>
          <?php foreach ($amens as $am): ?>
            <option value="<?= (int)$am['id'] ?>" <?= in_array((int) $am['id'], (array) ($f['amenity'] ?? []), true) ? 'selected' : '' ?>><?= esc($am['name']) ?> (<?= (int)($fc['amen'][(int)$am['id']] ?? 0) ?>)</option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select name="sort" onchange="document.getElementById('catfilter').submit()">
          <option value="" <?= $ss('') ?>>Sort: Recommended</option><?php if (array_sum($fc['rating'] ?? []) > 0): ?><option value="rating" <?= $ss('rating') ?>>Top rated</option><?php endif; ?>
          <?php if (array_sum($fc['rating'] ?? []) > 0): ?><option value="reviews" <?= $ss('reviews') ?>>Most reviewed</option><?php endif; ?><option value="featured" <?= $ss('featured') ?>>Featured</option>
          <option value="name" <?= $ss('name') ?>>Name (A–Z)</option></select>
        <label class="fb-check"><input type="checkbox" name="open" value="1" <?= $f['open']?'checked':'' ?> onchange="document.getElementById('catfilter').submit()"> Open now<?= $fc['open'] ? ' (' . $fc['open'] . ')' : '' ?></label>
        <?php if (array_filter($f)): ?>
          <a class="fb-clear" href="<?= hi_category_url(esc($cat['slug'], 'url')) ?>">Clear filters</a>
        <?php endif; ?>
      </form>
      <?php
        /* Popular here: the busiest types in this category, then the busiest
           sectors for it — one tap each, a quick way in when unsure */
        $_pop = [];
        $_kids = $children; usort($_kids, static fn ($a, $b) => (int) $b['listing_count'] <=> (int) $a['listing_count']);
        foreach (array_slice($_kids, 0, 4) as $_c) { $_pop[] = [hi_category_url(esc($_c['slug'], 'url')), html_entity_decode((string) $_c['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), (int) $_c['listing_count'], 'tag']; }
        foreach (array_slice($areas, 0, 8 - count($_pop)) as $_a) {
          $_n = (int) ($fc['area'][(int) $_a['id']] ?? 0); if (! $_n) continue;
          $_pop[] = [hi_category_url(esc($cat['slug'], 'url')) . '?area=' . (int) $_a['id'], $_an($_a['name']), $_n, 'location-dot'];
        }
      ?>
      <?php if ($_pop): ?>
        <div class="sx-trend sx-pop">
          <span class="sx-onl"><i class="fa-solid fa-fire"></i> Popular in <?= esc(html_entity_decode((string) $cat['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>:</span>
          <?php foreach ($_pop as $_p): ?><a href="<?= esc($_p[0], 'attr') ?>"><i class="fa-solid fa-<?= $_p[3] ?>"></i> <?= esc($_p[1]) ?> <em><?= $_p[2] ?></em></a><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php if (! $listings): ?>
      <?php if (array_filter($f)): ?>
        <div style="text-align:center;padding:44px 20px">
          <i class="fa-solid fa-filter-circle-xmark" style="font-size:28px;color:#c7d2e4;display:block;margin-bottom:14px"></i>
          <p style="color:var(--body);font-size:15.5px;margin:0 0 6px">No <?= esc(strtolower($cat['name'])) ?> match these filters.</p>
          <p style="color:var(--muted);font-size:13.5px;margin:0 0 18px">There are <?= number_format($total ?? 0) ?> in this category — try removing one.</p>
          <a class="btn" style="display:inline-block" href="<?= hi_category_url(esc($cat['slug'], 'url')) ?>">Clear filters</a>
        </div>
      <?php else: ?>
        <p style="color:var(--muted)"><?= esc(hi_c('category.p_no_listings_here_yet', 'No listings here yet.')) ?></p>
      <?php endif; ?>
    <?php else: ?>
    <?= view('frontend/_map', ['listings' => $listings]) ?>
    <?php
      /* One grid. A property sits among the businesses like any other listing —
         _card hands it to the property card, which leads with the price instead
         of a star rating, but it is the same row of results. */
      helper('property');
      $_props = count(array_filter($listings, static fn ($x) => hi_is_property($x)));
    ?>
    <?php /* On anything in the Property tree, offer the way property is
             actually searched — by deal, not by name. Same five the mockup
             puts on its real-estate category, plus PG. */ ?>
    <?php if (! empty($listings) && hi_shows_nearby($listings[0])): ?>
      <div class="cquick">
        <?php foreach (['sale' => 'Buy', 'rent' => 'Rent', 'pg' => 'PG',
                        'commercial' => 'Commercial', 'plots' => 'Plots',
                        'new' => 'New projects'] as $_qk => $_ql): ?>
          <a class="cq" href="<?= base_url('property') ?>?for=<?= $_qk ?>"><?= esc($_ql) ?></a>
        <?php endforeach; ?>
      </div>
      <style>
      .cquick{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 16px}
      .cq{border:1px solid var(--line);border-radius:20px;padding:8px 16px;font-size:13px;
        font-weight:600;color:var(--body);background:#fff;text-decoration:none}
      .cq:hover{border-color:#b9cdf0;background:#f8fbff;color:var(--blue)}
      </style>
    <?php endif; ?>
    <?php if ($_props): ?>
      <p style="margin:0 0 14px;font-size:13.5px;color:var(--muted)">
        <i class="fa-solid fa-building" style="color:var(--blue);margin-right:6px"></i>
        <b style="color:var(--ink)"><?= $_props ?></b> of these <?= $_props === 1 ? 'is a property' : 'are properties' ?> &mdash;
        <a href="<?= base_url('property') ?>" style="font-weight:600">search by budget and BHK &rsaquo;</a>
      </p>
    <?php endif; ?>
    <div class="lgrid">
      <?php foreach ($listings as $l): ?><?= view('frontend/_card', ['l' => $l]) ?><?php endforeach; ?>
    </div>
    <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
    <?php endif; ?>
  </div>
</section>

<?php if (! empty($faqs)): ?>
<section class="sec cp-faq">
  <div class="wrap" style="max-width:900px">
    <div class="sec-head"><h2><?= esc(hi_c('category.frequently_asked_questions', 'Frequently asked questions')) ?></h2></div>
    <?php foreach ($faqs as $f): ?>
      <details style="border:1px solid var(--line);border-radius:12px;padding:0 18px;margin-bottom:10px;background:#fff">
        <summary style="cursor:pointer;padding:15px 0;font-weight:700;color:var(--ink);list-style:none"><?= esc($f[0]) ?></summary>
        <p style="margin:0 0 16px;color:var(--body);line-height:1.6"><?= esc($f[1]) ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?= $this->endSection() ?>
