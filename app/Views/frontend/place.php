<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $_slides = []; foreach (array_slice($listings, 0, 6) as $_x) { $_slides[] = [hi_cover($_x), hi_listing_url($_x), $_x['name']]; }
  $_cr = [['Areas', hi_page('areas')]]; if ($loc) { $_cr[] = [$loc['name'], hi_locality_url(esc($loc['slug'], 'url'))]; } $_cr[] = [$area['name'], null]; ?>
<?= view('frontend/_pagehero', [
  'crumbs' => $_cr,
  'title'  => esc($area['name']),
  'sub'    => number_format($total) . ' business' . ($total === 1 ? '' : 'es') . ($loc ? ' in ' . $loc['name'] : '') . ', Noida.',
  'facts'  => [['store', 'Businesses', number_format($total)], ['location-dot', 'Locality', $loc ? $loc['name'] : 'Noida'], ['city', 'City', 'Noida']],
  'links'  => [['map', 'All areas', hi_page('areas')], ['folder-open', 'Categories', hi_page('categories')], ['magnifying-glass', 'Search', hi_page('search')]],
  'slides' => $_slides,
], ['saveData' => false]) ?>

<section class="sec"><div class="wrap">
  <?php if (! empty($area['description'])): ?>
    <p style="color:var(--body);line-height:1.7;max-width:820px;margin:0 0 22px"><?= esc($area['description']) ?></p>
  <?php endif; ?>

  <?php if (! $listings): ?>
    <div style="text-align:center;padding:44px 20px">
      <i class="fa-solid fa-store-slash" style="font-size:28px;color:#c7d2e4;display:block;margin-bottom:14px"></i>
      <p style="color:var(--body);font-size:15.5px;margin:0 0 6px"><?= esc(hi_c('place.p_no_businesses_listed_here_yet', 'No businesses listed here yet.')) ?></p>
      <?php if ($loc): ?>
        <a class="btn" style="display:inline-block;margin-top:10px" href="<?= hi_locality_url(esc($loc['slug'], 'url')) ?>">
          Browse all of <?= esc($loc['name']) ?>
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php $ss = static fn ($v) => ($sort ?? '') === $v ? 'selected' : ''; ?>
    <form method="get" id="placesort" class="filterbar sx sx-card" data-sxq="Search here…" data-sxqv="<?= esc($_GET['q'] ?? '', 'attr') ?>" data-sx="">
      <select name="sort" onchange="document.getElementById('placesort').submit()">
        <option value="" <?= $ss('') ?>>Sort: Recommended</option>
        <option value="rating" <?= $ss('rating') ?>>Top rated</option>
        <option value="reviews" <?= $ss('reviews') ?>>Most reviewed</option>
        <option value="name" <?= $ss('name') ?>>Name (A–Z)</option>
      </select>
        <?php if (! empty($placeKinds)): ?>
      <div class="sx-trend sx-pop">
        <span class="sx-onl"><i class="fa-solid fa-fire"></i> Popular in <?= esc($area['name']) ?>:</span>
        <?php foreach ($placeKinds as $_k => $_n): ?><a href="?q=<?= urlencode($_k) ?>"><i class="fa-solid fa-tag"></i> <?= esc($_k) ?> <em><?= (int) $_n ?></em></a><?php endforeach; ?>
      </div>
    <?php endif; ?>
    </form>
    <?= view('frontend/_map', ['listings' => $listings]) ?>
    <div class="resbar"><?= view('frontend/_viewtoggle') ?></div>
    <div class="lgrid">
      <?php foreach ($listings as $l): ?>
        <?= view('frontend/_card', ['l' => $l]) ?>
      <?php endforeach; ?>
    </div>
    <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
  <?php endif; ?>
</div></section>
<?= $this->endSection() ?>
