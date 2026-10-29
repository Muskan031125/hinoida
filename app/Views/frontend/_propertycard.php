<?php
/**
 * A property, drawn as an ordinary listing card.
 *
 * This used to be a bespoke price-led card with its own stylesheet carried
 * inline. Two cards of different shapes in one grid read as a broken page, so
 * it now uses exactly the same markup and classes as _card.php — .lcard, .ph,
 * .bd, .rate, .lc-meta, .lc-actions. What differs is only what goes in those
 * slots: a flat has a price, a size and a furnishing state where a restaurant
 * has opening hours.
 *
 * Expects: $l (listing row, ideally with _ravg/_rcount and locality_name)
 */
helper(['image', 'property']);

$lid  = (int) $l['id'];
$p    = hi_property_of($lid);
$loc  = $l['locality_name'] ?? ($l['locality'] ?? 'Noida');
$rc   = (int) ($l['_rcount'] ?? 0);
$ra   = $l['_ravg'] ?? 0;

$deal      = strtolower((string) ($p['deal'] ?? ''));
$dealLabel = ['sale' => 'For sale', 'rent' => 'For rent', 'pg' => 'PG'][$deal] ?? '';
$price     = hi_property_price($p);
$summary   = hi_property_summary($p);
$badges    = hi_property_badges($p, $l);
$project   = trim((string) ($p['project'] ?? ''));
?>
<a class="lcard rich" href="<?= hi_listing_url($l) ?>">
  <span class="ph">
    <?php $_src = hi_cover($l); ?>
    <img src="<?= esc($_src) ?>" alt="<?= esc($l['name']) ?>"<?= str_starts_with($_src, 'data:') ? '' : ' loading="lazy"' ?>>
    <?php if (! empty($l['is_featured'])): ?><span class="lc-feat"><i class="fa-solid fa-crown"></i> Featured</span><?php endif; ?>
    <?php if (! empty($badge)): ?><span class="lc-badge"><i class="fa-solid fa-arrow-trend-up"></i> <?= esc($badge) ?></span><?php endif; ?>
    <?php if ($rc): $_q = hi_rating_quality((float) $ra, (int) $rc); ?>
      <span class="rate"><i class="fa-solid fa-star"></i> <?= esc($ra) ?> <small><?= $_q !== '' ? esc($_q) . ' &middot; ' : '' ?>(<?= $rc ?>)</small></span>
    <?php endif; ?>
    <?php /* where a restaurant says Open now, a flat says what it is for */ ?>
    <?php if ($dealLabel !== ''): ?>
      <span class="lc-open <?= $deal === 'sale' ? 'is-open' : 'is-soon' ?>"><?= esc($dealLabel) ?></span>
    <?php endif; ?>
  </span>
  <div class="bd">
    <h3><?= esc($l['name']) ?></h3>
    <span class="loc"><i class="fa-solid fa-location-dot"></i>
      <?= esc($project !== '' ? mb_strimwidth($project, 0, 34, '…') . ', ' : '') ?><?= esc($loc) ?></span>
    <span class="lc-meta">
      <?php if ($price !== ''): ?><span><i class="fa-solid fa-indian-rupee-sign"></i> <?= esc(ltrim($price, '₹')) ?></span><?php endif; ?>
      <?php if ($summary !== ''): ?><span><i class="fa-solid fa-ruler-combined"></i> <?= esc($summary) ?></span><?php endif; ?>
      <?php foreach (array_slice($badges, 0, 1) as [$_bl, $_bi, $_bk]): ?>
        <span><i class="fa-solid fa-<?= esc($_bi) ?>"></i> <?= esc($_bl) ?></span>
      <?php endforeach; ?>
    </span>
    <span class="lc-actions">
      <span class="lc-btn lc-view"><i class="fa-solid fa-arrow-right"></i> <?= esc(hi_c('_propertycard.view_details', 'View details')) ?></span>
    </span>
  </div>
</a>
