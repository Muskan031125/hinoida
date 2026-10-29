<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $_slides = []; foreach (array_slice($listings, 0, 6) as $_x) { $_slides[] = [hi_cover($_x), hi_listing_url($_x), $_x['name']]; } ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Areas', hi_page('areas')], [$area['name'], null]],
  'title'  => esc($area['name']) . ' <span>, Noida</span>',
  'sub'    => 'Businesses, services and property in ' . $area['name'] . ' — with ratings, timings and directions.',
  'facts'  => [['store', 'Listings', number_format($total ?? count($listings))], ['building', 'Properties', ! empty($propHere) ? (string) (int) $propHere : ''], ['location-dot', 'Area', $area['name']]],
  'actions'=> ! empty($propHere) ? [['building', 'See property here', base_url('property') . '?locality=' . (int) $area['id'], 'primary']] : [],
  'links'  => [['map', 'All areas', hi_page('areas')], ['folder-open', 'Categories', hi_page('categories')], ['magnifying-glass', 'Search', hi_page('search')]],
  'slides' => $_slides,
], ['saveData' => false]) ?>

<section class="sec"><div class="wrap">
  <div class="sec-head" style="text-align:left">
    <h2>Businesses in <?= esc($area['name']) ?>
      <span style="font-size:15px;color:var(--muted);font-weight:600">(<?= number_format($shown ?? count($listings)) ?><?= ($total ?? 0) > ($shown ?? 0) ? ' of ' . number_format($total) : '' ?>)</span>
    </h2>
  </div>
  <?php
    $ss  = static fn ($v) => ($sort ?? '') === $v ? 'selected' : '';
    $sel = static fn ($a, $bb) => (string) $a === (string) $bb ? 'selected' : '';
    $fc  = ($facet ?? []) + ['cat'=>[], 'rating'=>['4'=>0,'3'=>0], 'open'=>0, 'amen'=>[]];
  ?>
  <?php if (! empty($places)): ?>
    <div style="margin:-4px 0 20px">
      <div style="font-size:13px;color:var(--muted);font-weight:600;margin-bottom:9px">Places in <?= esc($area['name']) ?></div>
      <div class="typechips">
        <?php foreach ($places as $p): ?>
          <a href="<?= base_url('a/' . esc($p['slug'], 'url')) ?>"><?= esc($p['name']) ?> <span style="opacity:.6">(<?= (int) $p['n'] ?>)</span></a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
  <div class="sx sx-card">
  <form method="get" id="areafilter" class="filterbar" data-sxq="Search in <?= esc($area['name'], 'attr') ?>…" data-sxqv="<?= esc($f['q'] ?? '', 'attr') ?>" data-sx="open:1:clock:Open now|rating:4:star:4★ &amp; up">
    <select name="cat" onchange="document.getElementById('areafilter').submit()">
      <option value="">All categories (<?= number_format($total ?? 0) ?>)</option>
      <?php foreach (($facetCats ?? []) as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $sel($f['cat'], $c['id']) ?>><?= esc($c['name']) ?> (<?= (int) $c['n'] ?>)</option>
      <?php endforeach; ?>
    </select>
<?php /* every listing shows (0) when nothing has been reviewed —
               a filter that can only ever return nothing is worse than none */ ?>
<?php if (array_sum($fc['rating'] ?? []) > 0): ?>
    <select name="rating" onchange="document.getElementById('areafilter').submit()">
      <option value="">Any rating</option>
      <?php foreach (['4'=>'4★ &amp; up', '3'=>'3★ &amp; up'] as $rk => $rlabel): $rn = (int) ($fc['rating'][$rk] ?? 0); ?>
        <option value="<?= $rk ?>" <?= $sel($f['rating'],$rk) ?><?= $rn ? '' : ' disabled' ?>><?= $rlabel ?> (<?= $rn ?>)</option>
      <?php endforeach; ?>
    </select>
<?php endif; ?>
    <?php if (! empty($amens)): ?>
    <select name="amenity[]" multiple onchange="document.getElementById('areafilter').submit()">
      <option value="" disabled hidden>Any facility</option>
      <?php foreach ($amens as $am): ?>
        <option value="<?= (int)$am['id'] ?>" <?= in_array((int) $am['id'], (array) ($f['amenity'] ?? []), true) ? 'selected' : '' ?>><?= esc($am['name']) ?> (<?= (int)($fc['amen'][(int)$am['id']] ?? 0) ?>)</option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <select name="sort" onchange="document.getElementById('areafilter').submit()">
      <option value="" <?= $ss('') ?>>Sort: Recommended</option>
      <?php if (array_sum($fc['rating'] ?? []) > 0): ?><option value="rating" <?= $ss('rating') ?>>Top rated</option><?php endif; ?>
      <?php if (array_sum($fc['rating'] ?? []) > 0): ?><option value="reviews" <?= $ss('reviews') ?>>Most reviewed</option><?php endif; ?>
      <option value="featured" <?= $ss('featured') ?>>Featured</option>
      <option value="name" <?= $ss('name') ?>>Name (A–Z)</option>
    </select>
    <label class="fb-check"><input type="checkbox" name="open" value="1" <?= ! empty($f['open']) ? 'checked' : '' ?> onchange="document.getElementById('areafilter').submit()"> Open now<?= $fc['open'] ? ' (' . $fc['open'] . ')' : '' ?></label>
    <?php if (array_filter($f)): ?>
      <a class="fb-clear" href="<?= hi_locality_url(esc($area['slug'], 'url')) ?>">Clear filters</a>
    <?php endif; ?>
  </form>
  <?php
    /* Popular here: what this area has most of — one tap each */
    $_ac = $facetCats ?? []; usort($_ac, static fn ($a, $b) => (int) $b['n'] <=> (int) $a['n']);
  ?>
  <?php if ($_ac): ?>
    <div class="sx-trend sx-pop">
      <span class="sx-onl"><i class="fa-solid fa-fire"></i> Popular in <?= esc($area['name']) ?>:</span>
      <?php foreach (array_slice($_ac, 0, 8) as $_c): ?><a href="<?= hi_locality_url(esc($area['slug'], 'url')) ?>?cat=<?= (int) $_c['id'] ?>"><i class="fa-solid fa-tag"></i> <?= esc(html_entity_decode((string) $_c['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?> <em><?= (int) $_c['n'] ?></em></a><?php endforeach; ?>
    </div>
  <?php endif; ?>
  </div>
  <?php if (! $listings): ?>
    <div style="text-align:center;padding:44px 20px">
      <i class="fa-solid fa-filter-circle-xmark" style="font-size:28px;color:#c7d2e4;display:block;margin-bottom:14px"></i>
      <p style="color:var(--body);font-size:15.5px;margin:0 0 6px">Nothing in <?= esc($area['name']) ?> matches these filters.</p>
      <p style="color:var(--muted);font-size:13.5px;margin:0 0 18px">There are <?= number_format($total ?? 0) ?> businesses here — try removing one.</p>
      <a class="btn" style="display:inline-block" href="<?= hi_locality_url(esc($area['slug'], 'url')) ?>">Clear filters</a>
    </div>
  <?php endif; ?>
  <?= view('frontend/_map', ['listings' => $listings]) ?>
    <div class="resbar"><?= view('frontend/_viewtoggle') ?></div>
  <div class="lgrid">
  <?php foreach ($listings as $l): ?>
    <?= view('frontend/_card', ['l' => $l]) ?>
  <?php endforeach; ?>
  </div>
  <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
</div></section>

<?php if (! empty($faqs)): ?>
<section class="sec"><div class="wrap" style="max-width:900px">
  <div class="sec-head"><h2><?= esc(hi_c('area.frequently_asked_questions', 'Frequently asked questions')) ?></h2></div>
  <?php foreach ($faqs as $f): ?>
    <details style="border:1px solid var(--line);border-radius:12px;padding:0 18px;margin-bottom:10px;background:#fff">
      <summary style="cursor:pointer;padding:15px 0;font-weight:700;color:var(--ink);list-style:none"><?= esc($f[0]) ?></summary>
      <p style="margin:0 0 16px;color:var(--body);line-height:1.6"><?= esc($f[1]) ?></p>
    </details>
  <?php endforeach; ?>
</div></section>
<?php endif; ?>
<?= $this->endSection() ?>
