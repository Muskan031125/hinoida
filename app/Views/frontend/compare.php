<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php /* Kept in the page rather than the stylesheet: this block is the whole
         category switcher, and it must not depend on a separate CSS upload
         landing for the page to look right. */ ?>
<style>
.cmpsw{border:1px solid var(--line);border-radius:16px;background:#fff;
  padding:16px 18px 18px;margin:0 0 22px;box-shadow:0 1px 3px rgba(15,28,51,.05)}
.cmpsw-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;
  flex-wrap:wrap;margin-bottom:15px}
.cmpsw-say{display:flex;gap:11px;align-items:flex-start;font-size:13.5px;line-height:1.6;
  color:var(--body);max-width:72ch}
.cmpsw-say i{color:var(--blue);font-size:15px;margin-top:2px;flex:0 0 auto}
.cmpsw-say b{color:var(--ink);font-weight:700}
.cmpsw-clear{flex:0 0 auto;background:none;border:1px solid var(--line);border-radius:9px;
  padding:8px 14px;font:inherit;font-size:12.5px;color:var(--muted);cursor:pointer}
.cmpsw-clear:hover{border-color:#e6b9b9;color:#b3262f;background:#fdf7f7}

.cmpsw-tabs{display:flex;gap:10px;overflow-x:auto;padding:2px 2px 10px;margin:0 -2px -10px}
.cmpsw-tab{display:flex;align-items:center;gap:12px;flex:0 0 auto;text-decoration:none;
  border:1.5px solid var(--line);border-radius:13px;padding:10px 16px 10px 11px;background:#fff}
.cmpsw-tab:hover{border-color:#b9cdf0;background:#f8fbff}
.cmpsw-tab.on{border-color:var(--blue);background:var(--blue)}
.cmpsw-ph{display:flex;flex:0 0 auto}
.cmpsw-ph img{width:34px;height:34px;border-radius:50%;object-fit:cover;
  border:2px solid #fff;box-shadow:0 1px 4px rgba(15,28,51,.22)}
.cmpsw-ph img+img{margin-left:-13px}
.cmpsw-tab.on .cmpsw-ph img{border-color:rgba(255,255,255,.9)}
.cmpsw-tx{display:flex;flex-direction:column;line-height:1.3;gap:1px}
.cmpsw-tx b{font-size:14px;font-weight:700;color:var(--ink);white-space:nowrap}
.cmpsw-tx em{font-size:11.5px;font-style:normal;color:var(--muted)}
.cmpsw-tab.on .cmpsw-tx b{color:#fff}
.cmpsw-tab.on .cmpsw-tx em{color:rgba(255,255,255,.8)}
.cmpsw-ok{color:#fff;font-size:11px;background:rgba(255,255,255,.22);
  width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center}
@media(max-width:560px){
  .cmpsw{padding:14px 14px 16px;border-radius:14px}
  .cmpsw-say{font-size:13px}
  .cmpsw-clear{padding:7px 12px}
  .cmpsw-ph img{width:30px;height:30px}
  .cmpsw-tx b{font-size:13.5px}
}
</style>
<?php
helper(['image', 'facts']);

$hours = static function ($l) {
    $om = (int) ($l['opened_minutes'] ?? 0); $cm = (int) ($l['closed_minutes'] ?? 0);
    if ($cm === $om) { return '—'; }
    return date('g:i A', mktime(0, $om, 0)) . ' – ' . date('g:i A', mktime(0, $cm, 0));
};
$isOpen = static function ($l) {
    $om = (int) ($l['opened_minutes'] ?? 0); $cm = (int) ($l['closed_minutes'] ?? 0);
    if ($cm === $om) { return null; }
    $now = (int) date('G') * 60 + (int) date('i');
    return $cm > $om ? ($now >= $om && $now < $cm) : ($now >= $om || $now < $cm);
};

// Best-in-column highlighting: what "wins" for each comparable row
$bestRating = 0; $bestReviews = 0; $longestHours = 0;
foreach ($rows as $l) {
    $bestRating  = max($bestRating, (float) ($l['_ravg'] ?? 0));
    $bestReviews = max($bestReviews, (int) ($l['_rcount'] ?? 0));
    $span = (int) ($l['closed_minutes'] ?? 0) - (int) ($l['opened_minutes'] ?? 0);
    $longestHours = max($longestHours, $span);
}
?>
<section class="det-hero" style="--dethero:none">
  <div class="wrap">
    <div class="det-crumb"><a href="<?= base_url('/') ?>">Home</a> &rsaquo; <span>Compare</span></div>
    <h1 class="det-name"><?= esc(hi_c('compare.compare', 'Compare')) ?></h1>
    <?php $groups = $groups ?? []; $active = $active ?? 0; $allIds = $allIds ?? []; ?>
    <?php if ($rows): ?><div class="det-meta"><span class="dm-loc"><?= count($rows) ?> businesses side by side<?php
      if (count($groups) > 1) { echo ' &nbsp;·&nbsp; ' . count($groups) . ' categories picked'; } ?></span></div><?php endif; ?>
  </div>
</section>

<section class="sec"><div class="wrap">
  <?php if (! $rows): ?>
    <div class="cmp-empty">
      <i class="fa-solid fa-scale-balanced"></i>
      <p>Nothing to compare yet. Open any listing and tap <b>Add to compare</b> — pick as many as you like, and we line them up a category at a time.</p>
      <a class="btn" href="<?= base_url('/') ?>">Browse HiNoida</a>
    </div>
  <?php else: ?>
  <div class="cmp-layout">
    <div class="cmp-main">
    <?php /* Comparing across categories is meaningless — a hotel has no rent and
             a PG has no room tariff — so the table holds one category at a time
             and the other picks stay here as tabs rather than being dropped. */ ?>
    <?php if (count($groups) > 1): ?>
      <div class="cmpsw">
        <div class="cmpsw-top">
          <div class="cmpsw-say">
            <i class="fa-solid fa-layer-group"></i>
            <span><b><?= count($allIds) ?></b> places in <b><?= count($groups) ?></b> categories &mdash;
              a P.G. and a hotel have nothing worth lining up, so pick one category at a time.</span>
          </div>
          <button type="button" class="cmpsw-clear" id="cmpClearAll"><?= esc(hi_c('compare.btn_clear_all', 'Clear all')) ?></button>
        </div>
        <div class="cmpsw-tabs" role="tablist">
          <?php /* keys are ints for categories, "p:home" for property sets */ ?>
          <?php foreach ($groups as $gkey => $g): $on = (string) $gkey === (string) $active; ?>
            <a class="cmpsw-tab<?= $on ? ' on' : '' ?>" role="tab" aria-selected="<?= $on ? 'true' : 'false' ?>"
               href="<?= base_url('compare') ?>?ids=<?= esc(implode(',', $allIds), 'url') ?>&amp;cat=<?= esc((string) $gkey, 'url') ?>">
              <span class="cmpsw-ph">
                <?php foreach (array_slice($g['rows'], 0, 3) as $tr): ?>
                  <img src="<?= esc(hi_cover($tr)) ?>" alt="" loading="lazy">
                <?php endforeach; ?>
              </span>
              <span class="cmpsw-tx">
                <b><?= esc($g['name']) ?></b>
                <em><?= count($g['rows']) ?> place<?= count($g['rows']) === 1 ? '' : 's' ?></em>
              </span>
              <?php if ($on): ?><i class="fa-solid fa-check cmpsw-ok"></i><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    <!-- the picked businesses as cards -->
    <div class="cmp-picked">
      <?php foreach ($rows as $l): $op = $isOpen($l); ?>
        <div style="position:relative">
          <a class="cmp-pick" href="<?= hi_listing_url($l) ?>">
            <img src="<?= esc(hi_cover($l)) ?>" alt="<?= esc($l['name']) ?>">
            <span class="pk">
              <b><?= esc($l['name']) ?></b>
              <span><i class="fa-solid fa-location-dot"></i> <?= esc($l['locality_name'] ?: 'Noida') ?><?php
                if ($l['_rcount']) { echo ' &nbsp;·&nbsp; ★ ' . esc($l['_ravg']); } ?></span>
            </span>
          </a>
          <button type="button" class="cmp-rm" data-cmp-remove="<?= (int) $l['id'] ?>" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($rows) > 1): ?>
      <div class="cmp-go"><button type="button" id="cmpShow"><i class="fa-solid fa-scale-balanced"></i> Compare these <?= count($rows) ?></button></div>
    <?php endif; ?>

    <div class="cmp-scroll" id="cmpTable"<?= count($rows) > 1 ? ' hidden' : '' ?>>
      <table class="cmp">
        <tbody>
          <?php
          /* A flat has no opening hours and nobody reviews it — comparing on
             rating and "Featured" tells a buyer nothing. When the active group
             is property, the rows become the ones that decide a let or a sale. */
          if (! empty($activeIsProp)):
            helper('property');
            $P = [];
            foreach ($rows as $_r) { $P[(int) $_r['id']] = hi_property_of((int) $_r['id']); }
            $labs = hi_property_labels();
            // what "wins" a row: the cheapest, the biggest, the most bedrooms
            $cheapest = null; $biggest = 0; $mostBeds = 0;
            foreach ($P as $_p) {
              $_pr = (float) ($_p['price'] ?? 0);
              if ($_pr > 0 && ($cheapest === null || $_pr < $cheapest)) { $cheapest = $_pr; }
              $biggest  = max($biggest, (float) ($_p['carpet_area'] ?? 0));
              $mostBeds = max($mostBeds, (int) ($_p['bedrooms'] ?? 0));
            }
            $val = static function ($l, $k) use ($P, $labs) {
              $p = $P[(int) $l['id']] ?? [];
              $v = $p[$k] ?? null;
              if ($v === null || $v === '' || $v === '0.00') { return null; }
              $type = $labs[$k][1] ?? 'text';
              if ($type === 'check')  { return $v ? 'Yes' : 'No'; }
              if ($type === 'select') { return $labs[$k][2][$v] ?? $v; }
              return $v;
            };
            $none = '<span class="cmp-none">—</span>';
            $defs = [
              ['Price', 'indian-rupee-sign', static function ($l) use ($P, $cheapest) {
                  $p = $P[(int) $l['id']] ?? [];
                  $pr = (float) ($p['price'] ?? 0);
                  return ['<b>' . esc(hi_property_price($p)) . '</b>', $pr > 0 && $cheapest !== null && $pr <= $cheapest];
              }],
              ['Offered for', 'tag', static function ($l) use ($P, $none) {
                  $p = $P[(int) $l['id']] ?? [];
                  return [esc(hi_property_deals()[$p['deal'] ?? ''] ?? '—') ?: $none, false];
              }],
              ['Type', 'building', static function ($l) use ($P, $none) {
                  $p = $P[(int) $l['id']] ?? [];
                  return [esc(hi_property_kinds()[$p['kind'] ?? ''][0] ?? '—') ?: $none, false];
              }],
              ['Bedrooms', 'bed', static function ($l) use ($P, $mostBeds, $none) {
                  $b = (int) ($P[(int) $l['id']]['bedrooms'] ?? 0);
                  return [$b ? $b . ' BHK' : $none, $b > 0 && $b >= $mostBeds];
              }],
              ['Bathrooms', 'shower', static fn ($l) => [$val($l, 'bathrooms') ?? $none, false]],
              ['Area', 'ruler-combined', static function ($l) use ($P, $labs, $biggest, $none) {
                  $p = $P[(int) $l['id']] ?? [];
                  $a = (float) ($p['carpet_area'] ?? 0);
                  if ($a <= 0) { return [$none, false]; }
                  $u = $labs['area_unit'][2][$p['area_unit'] ?? 'sqft'] ?? '';
                  return [number_format($a, 0) . ' ' . esc($u), $a >= $biggest];
              }],
              ['Furnishing', 'couch', static fn ($l) => [esc((string) ($val($l, 'furnishing') ?? '')) ?: $none, false]],
              ['Floor', 'stairs', static function ($l) use ($P, $none) {
                  $p = $P[(int) $l['id']] ?? [];
                  if (empty($p['floor_no'])) { return [$none, false]; }
                  return [(int) $p['floor_no'] . (! empty($p['total_floors']) ? ' of ' . (int) $p['total_floors'] : ''), false];
              }],
              ['Sharing', 'users', static fn ($l) => [esc((string) ($val($l, 'sharing') ?? '')) ?: $none, false]],
              ['Facing', 'compass', static fn ($l) => [esc((string) ($val($l, 'facing') ?? '')) ?: $none, false]],
              ['Possession', 'key', static fn ($l) => [esc((string) ($val($l, 'possession') ?? '')) ?: $none, false]],
              ['Deposit', 'wallet', static function ($l) use ($P, $none) {
                  $d = (float) ($P[(int) $l['id']]['deposit'] ?? 0);
                  return [$d > 0 ? '₹' . esc(hi_inr($d)) : $none, false];
              }],
              ['Project', 'city', static fn ($l) => [esc((string) ($val($l, 'project') ?? '')) ?: $none, false]],
              ['Area of Noida', 'location-dot', static fn ($l) => [esc(($l['locality_name'] ?: 'Noida') . ', Noida'), false]],
            ];
            // a row every property leaves blank is noise — drop it
            $defs = array_values(array_filter($defs, static function ($d) use ($rows, $none) {
              foreach ($rows as $r) { [$v] = $d[2]($r); if ($v !== $none && trim(strip_tags((string) $v)) !== '') { return true; } }
              return false;
            }));
          else:
          $defs = [
            ['Rating', 'star', static function ($l) use ($bestRating) {
                if (! $l['_rcount']) { return ['<span class="cmp-none">Not rated</span>', false]; }
                $win = $bestRating > 0 && (float) $l['_ravg'] >= $bestRating;
                return ['<b>' . esc($l['_ravg']) . '</b> <span class="cmp-stars">★</span>', $win];
            }],
            ['Reviews', 'comment', static function ($l) use ($bestReviews) {
                $n = (int) ($l['_rcount'] ?? 0);
                return [$n ? $n . ' review' . ($n > 1 ? 's' : '') : '<span class="cmp-none">None yet</span>',
                        $n > 0 && $n >= $bestReviews];
            }],
            ['Category', 'tag', static fn ($l) => [esc($l['_cat'] ?: ($l['_cat_name'] ?? '—')), false]],
            ['Area', 'location-dot', static fn ($l) => [esc(($l['locality_name'] ?: 'Noida') . ', Noida'), false]],
            ['Address', 'map-pin', static fn ($l) => [esc($l['address'] ?: '—'), false]],
            ['Hours', 'clock', static function ($l) use ($hours, $longestHours) {
                $span = (int) ($l['closed_minutes'] ?? 0) - (int) ($l['opened_minutes'] ?? 0);
                return [esc($hours($l)), $span > 0 && $span >= $longestHours];
            }],
            ['Phone', 'phone', static fn ($l) => [$l['phone']
                ? '<a href="tel:' . esc(preg_replace('/[^0-9+]/', '', $l['phone']), 'attr') . '">' . esc($l['phone']) . '</a>'
                : '<span class="cmp-none">—</span>', false]],
            ['Website', 'globe', static fn ($l) => [$l['website']
                ? '<a href="' . esc($l['website'], 'attr') . '" target="_blank" rel="noopener">Visit</a>'
                : '<span class="cmp-none">—</span>', false]],
            ['Featured', 'crown', static fn ($l) => [! empty($l['is_featured'])
                ? '<span class="cmp-yes"><i class="fa-solid fa-check"></i> Yes</span>'
                : '<span class="cmp-none">No</span>', ! empty($l['is_featured'])]],
          ];
          endif;
          foreach ($defs as $d): ?>
            <tr>
              <th class="cmp-lbl"><i class="fa-solid fa-<?= $d[1] ?>"></i> <?= esc($d[0]) ?></th>
              <?php foreach ($rows as $l): [$val, $win] = $d[2]($l); ?>
                <td class="cmp-cell<?= $win && count($rows) > 1 ? ' cmp-win' : '' ?>"><?= $val ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          <tr>
            <th class="cmp-lbl"></th>
            <?php foreach ($rows as $l): ?>
              <td class="cmp-cell cmp-actions">
                <a class="cmp-open-btn" href="<?= hi_listing_url($l) ?>">View listing</a>
                <?php if ($l['phone']): ?>
                  <a class="cmp-call-btn" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $l['phone']), 'attr') ?>"><i class="fa-solid fa-phone"></i> Call</a>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>
    <p class="cmp-hint"><i class="fa-solid fa-circle-info"></i> Highlighted cells show which option leads on that row.</p>
    </div><!-- /cmp-main -->
  </div><!-- /cmp-layout -->
  <?php endif; ?>
</div></section>

<script>
(function(){var b=document.getElementById('cmpShow'),t=document.getElementById('cmpTable');
 if(!b||!t)return;
 b.addEventListener('click',function(){ t.hidden=false; b.parentNode.style.display='none';
   t.scrollIntoView({behavior:'smooth',block:'start'}); });
})();
</script>
<script>
document.querySelectorAll('[data-cmp-remove]').forEach(function (b) {
  b.addEventListener('click', function () {
    try {
      var ids = JSON.parse(localStorage.getItem('hi_compare') || '[]')
        .filter(function (x) { return x != +b.dataset.cmpRemove; });
      localStorage.setItem('hi_compare', JSON.stringify(ids));
      location.href = '<?= base_url('compare') ?>' + (ids.length ? '?ids=' + ids.join(',') : '');
    } catch (e) { location.reload(); }
  });
});
</script>
<?= $this->endSection() ?>
