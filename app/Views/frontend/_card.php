<?php
/**
 * Rich listing card — used across category / area / search / home.
 * Expects: $l (listing row, ideally with _ravg/_rcount and locality_name)
 */
helper(['image', 'property']);

// A property leads with its price, not a star rating — nobody rates a flat.
if (hi_is_property($l)) {
    echo view('frontend/_propertycard', ['l' => $l, 'badge' => $badge ?? null]);
    return;
}

$lid   = (int) $l['id'];
$loc   = $l['locality_name'] ?? ($l['locality'] ?? 'Noida');
$rc    = (int) ($l['_rcount'] ?? 0);
$ra    = $l['_ravg'] ?? 0;
$phone = trim((string) ($l['phone'] ?? ''));
$addr  = trim((string) ($l['address'] ?? ''));

// open / closed — evaluated in IST against today's hours, handles past-midnight closing
$today = hi_hours($l)[hi_today_key()] ?? null;
$openState = null; $hoursTxt = ''; $openLabel = '';
if ($today) {
    // first and last range of the day drive the "opens/closes" wording
    $om = $today[0][0];
    $cm = $today[count($today) - 1][1];
    $now = (int) date('G') * 60 + (int) date('i');
    $openState = hi_is_open($l, $now);
    $hoursTxt  = hi_hours_label($today);

    if ($openState) {
        $minsLeft = ($cm > $now) ? $cm - $now : (1440 - $now) + $cm;
        $openLabel = $minsLeft <= 60 ? 'Closes soon' : 'Open now';
    } else {
        $minsTo = ($om > $now) ? $om - $now : (1440 - $now) + $om;
        $openLabel = $minsTo <= 90 ? 'Opens ' . date('g:i A', mktime(0, $om, 0)) : 'Closed';
    }
}
?>
<a class="lcard rich" href="<?= hi_listing_url($l) ?>">
  <span class="ph">
    <?php $_src = hi_cover($l); ?>
    <img src="<?= esc($_src) ?>" alt="<?= esc($l['name']) ?>"<?= str_starts_with($_src, 'data:') ? '' : ' loading="lazy"' ?>>
    <?php if (! empty($l['is_featured'])): ?><span class="lc-feat"><i class="fa-solid fa-crown"></i> Featured</span><?php endif; ?>
    <?php /* an optional caller-supplied badge — view counts on the trending row */ ?>
    <?php if (! empty($badge)): ?><span class="lc-badge"><i class="fa-solid fa-arrow-trend-up"></i> <?= esc($badge) ?></span><?php endif; ?>
    <?php if ($rc): $_q = hi_rating_quality((float) $ra, (int) $rc); ?><span class="rate"><i class="fa-solid fa-star"></i> <?= esc($ra) ?> <small><?= $_q !== '' ? esc($_q) . ' &middot; ' : '' ?>(<?= $rc ?>)</small></span><?php endif; ?>
    <?php if ($openState !== null): ?>
      <span class="lc-open <?= $openState ? ($openLabel === 'Closes soon' ? 'is-soon' : 'is-open') : 'is-shut' ?>"><?= esc($openLabel) ?></span>
    <?php endif; ?>
  </span>
  <div class="bd">
    <?php /* a heading, not bold text — a card title is a real heading and
           screen readers and search engines both rely on that */ ?>
    <?php $_cat = trim((string) ($l['category_name'] ?? $l['cat_name'] ?? $l['_cat_name'] ?? '')); if (preg_match('/^propert/i', $_cat) && trim((string) ($l['_sub_name'] ?? '')) !== '') { $_cat = trim($l['_sub_name']); } foreach (['pg/'=>'PG & Hostel','hostel'=>'PG & Hostel','cowork'=>'Coworking','commercial propert'=>'Commercial Space','property dealer'=>'Property Dealer'] as $_k => $_v) { if (stripos($_cat, $_k) !== false) { $_cat = $_v; break; } } if ($_cat !== ''): ?><span class="lc-cat"><?= esc($_cat) ?></span><?php endif; ?>
    <h3><?= esc($l['name']) ?></h3>
    <span class="loc"><i class="fa-solid fa-location-dot"></i> <?= esc($addr ? mb_strimwidth($addr, 0, 34, '…') . ', ' : '') ?><?= esc($loc) ?></span>
    <span class="lc-meta">
      <?php if ($hoursTxt): ?><span><i class="fa-regular fa-clock"></i> <?= esc($hoursTxt) ?></span><?php endif; ?>
      <?php if ($rc): ?><span><i class="fa-regular fa-comment"></i> <?= $rc ?> review<?= $rc > 1 ? 's' : '' ?></span><?php endif; ?>
    </span>
    <?php
      /* One button, the same on every card. 182 listings have no phone, so a
         Call/WhatsApp row appeared on some cards and not others and the grid
         read as broken. Calling and WhatsApp stay on the listing page itself,
         where the number is actually shown. */
    ?>
    <span class="lc-actions">
      <span class="lc-btn lc-view"><i class="fa-solid fa-arrow-right"></i> View details</span>
    </span>
    <?php /* grid cards: one tidy footer row — open state + today's hours, and an arrow */ ?>
    <span class="lc-foot">
      <?php if ($openState !== null): ?>
        <span class="lc-st <?= $openState ? ($openLabel === 'Closes soon' ? 'is-soon' : 'is-open') : 'is-shut' ?>"><i></i><b><?= esc($openLabel) ?></b><?php if ($hoursTxt): ?><em><?= esc($hoursTxt) ?></em><?php endif; ?></span>
      <?php elseif ($rc): ?>
        <span class="lc-st"><b><?= $rc ?> review<?= $rc > 1 ? 's' : '' ?></b></span>
      <?php else: ?>
        <span class="lc-st"><b>View details</b></span>
      <?php endif; ?>
      <span class="lc-go" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
    </span>
  </div>
</a>
