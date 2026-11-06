<?php
/**
 * A property card — price first, like the big property sites.
 *
 * Same outer shape as _card.php (.lcard .ph .bd) so it sits in any grid, but
 * the body is a home's: the price on top, the facts as chips, who posted it
 * and how fresh it is. A heart saves it to the visitor's shortlist (kept in
 * their own browser).
 *
 * Expects: $l (listing row, ideally with locality_name)
 */
helper(['image', 'property']);

$lid  = (int) $l['id'];
$p    = hi_property_of($lid);
$loc  = $l['locality_name'] ?? ($l['locality'] ?? '');
if ($loc === '' && ! empty($l['locality_id'])) {
    // some rows arrive without the sector name; look it up once per sector
    static $_locNames = [];
    $_lid = (int) $l['locality_id'];
    if (! array_key_exists($_lid, $_locNames)) {
        $_r = \Config\Database::connect()->table('locality')->select('name')->where('id', $_lid)->get()->getRowArray();
        $_locNames[$_lid] = (string) ($_r['name'] ?? '');
    }
    $loc = $_locNames[$_lid];
}
if ($loc === '') { $loc = 'Noida'; }

$deal      = strtolower((string) ($p['deal'] ?? ''));
$dealLabel = ['sale' => 'For sale', 'rent' => 'For rent', 'pg' => 'PG'][$deal] ?? '';
$amt       = hi_inr($p['price'] ?? 0);
$per       = ['month' => '/month', 'year' => '/year'][$p['price_period'] ?? ''] ?? '';
$deposit   = hi_inr($p['deposit'] ?? 0);
$facts     = hi_property_facts($p);
$project   = trim((string) ($p['project'] ?? ''));
$verified  = ! empty($l['is_verified']);
$poster    = hi_listing_poster($l);
$ago       = hi_posted_ago($l);
$ready     = ($p['possession'] ?? '') === 'ready';
$src       = hi_cover($l);
$url       = hi_listing_url($l);
$save      = json_encode([
    'id' => $lid, 'n' => (string) $l['name'], 'u' => $url,
    'i' => str_starts_with($src, 'data:') ? '' : $src,
    's' => $amt !== '' ? '₹' . $amt . $per : (string) $loc,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<?php
  /* Same as every other card on the site: photo, one small label, the name.
     Everything else (deposit, size, furnishing, who posted it) is on the
     listing page itself. The label carries the deal and the price. */
  $_title = preg_replace('/^Sample\s*·\s*/u', '', (string) $l['name']);
  // one line a renter can read at a glance: price · BHK · sector
  $_bits = [];
  $_bits[] = $amt !== '' ? '₹' . $amt . (['month' => '/mo', 'year' => '/yr'][$p['price_period'] ?? ''] ?? '') : 'Price on request';
  if (! empty($p['bedrooms'])) { $_bits[] = (int) $p['bedrooms'] . ' BHK'; }
  elseif (! empty($facts[0][1])) { $_bits[] = $facts[0][1]; }
  if ($loc !== '') { $_bits[] = $loc; }
  $_pill = implode(' · ', $_bits);
?>
<a class="lcard rich pcard" href="<?= $url ?>">
  <span class="ph">
    <img src="<?= esc($src) ?>" alt="<?= esc($l['name']) ?>"<?= str_starts_with($src, 'data:') ? '' : ' loading="lazy"' ?>>
    <span class="lc-ic" aria-hidden="true"><i class="fa-solid fa-<?= esc(hi_card_icon($deal !== '' ? $deal : 'sale')) ?>"></i></span>
    <span class="hi-save" role="button" tabindex="0" aria-label="Save to shortlist" title="Save" data-save="<?= esc($save, 'attr') ?>"><i class="fa-regular fa-heart"></i></span>
  </span>
  <div class="bd">
    <?php if ($_pill !== ''): ?><span class="lc-cat"><?= esc($_pill) ?></span><?php endif; ?>
    <h3><?= esc($_title) ?><?php if ($verified): ?> <i class="fa-solid fa-circle-check v-ok" title="Verified by HiNoida" aria-label="Verified"></i><?php endif; ?></h3>
    <span class="lc-explore">View <i class="fa-solid fa-arrow-right"></i></span>
  </div>
</a>
