<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php helper('property'); $anyRows = (int) \Config\Database::connect()->table('listing_property')->countAllResults() > 0; $sel = static fn ($a, $b) => (string) $a === (string) $b ? 'selected' : ''; ?>
<style>
/* The property filter bar. Kept with the page so the module never depends
   on a separate stylesheet upload landing. Cards use .lcard, like the rest
   of the site. */
.pfilter{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:20px}
.pfilter select,.pfilter input{width:100%;border:1px solid var(--line);border-radius:9px;padding:11px 12px;
  font-size:14px;font-family:inherit;background:#fff}
.pnone{text-align:center;padding:52px 20px;color:var(--muted)}
.pnone i{font-size:32px;color:#c7d2e4;display:block;margin-bottom:14px}
.pquick{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.pq{border:1px solid var(--line);border-radius:20px;padding:8px 16px;font-size:13px;font-weight:600;
  color:var(--body);background:#fff;text-decoration:none}
.pq:hover{border-color:#b9cdf0;background:#f8fbff}
.pq.on{background:var(--blue);border-color:var(--blue);color:#fff}
/* one compact search box: chips, search and filters together */
.pp-toolbar{background:#fff;border:1px solid var(--line);border-radius:18px;padding:16px 18px;margin-bottom:18px;
  box-shadow:0 1px 2px rgba(16,26,44,.04),0 12px 30px -20px rgba(16,26,44,.3)}
.pp-toolbar .pquick{margin-bottom:12px;gap:6px}
.pp-toolbar .pq{padding:7px 14px;font-size:12.5px;background:#f4f6fa;border-color:transparent}
.pp-toolbar .pq.on{background:var(--blue);color:#fff}
.pp-toolbar .searchbar{max-width:none!important;width:100%;margin:0 0 12px!important;box-shadow:none!important;
  border:1px solid var(--line);border-radius:12px;overflow:hidden}
.pp-toolbar .searchbar input{padding:12px 16px!important;font-size:14.5px!important}
.pp-toolbar .searchbar button{padding:0 22px!important;font-size:14px!important;border-radius:0!important}
.pp-toolbar .pfilter{grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;margin:0}
.pp-toolbar .pfilter select{height:42px;padding:0 32px 0 12px;border-radius:10px;background-color:#f7f9fc;font-size:13.5px;font-weight:600;
  color:var(--ink);cursor:pointer;appearance:none;-webkit-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237c8aa3' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 11px center}
.pp-toolbar .pfilter select:hover{border-color:#c6d5f0;background-color:#fff}
.pnone{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px 20px!important}
.pnone i{font-size:26px!important;margin-bottom:10px!important}
@media(min-width:1100px){ .pp-toolbar .pfilter{grid-template-columns:repeat(7,minmax(0,1fr))} }
@media(min-width:721px) and (max-width:1099px){ .pp-toolbar .pfilter{grid-template-columns:repeat(4,minmax(0,1fr))} }
@media(max-width:720px){
  .pp-toolbar{padding:12px}
  .pp-toolbar .pquick{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none}
  .pp-toolbar .pq{flex:none}
  .pp-toolbar .pfilter{grid-template-columns:1fr 1fr}
}
/* The site's filter script moves the Rent/Buy chips and the drop-downs inside
   .pfilter. As a grid it squeezed them into narrow columns, so here it is a
   plain block: chips in one row, drop-downs in one horizontal row below. */
.pp-toolbar .pfilter{display:block!important;margin:0}
.pp-toolbar .sx-quick{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 0}
.pp-toolbar .sx-filters{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(150px,1fr))!important;gap:10px!important;margin:12px 0 0!important}
@media(max-width:640px){.pp-toolbar .sx-filters{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
/* Rent / Buy groups on the property page */
.pgrp+.pgrp{margin-top:34px}
.pgrp-h{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 14px}
.pgrp-h h2{margin:0;font-size:21px;display:flex;align-items:center;gap:10px}
.pgrp-h h2 i{color:var(--blue);font-size:17px}
.pgrp-h h2 span{font-size:12.5px;font-weight:700;color:var(--blue);background:#eaf1fd;border-radius:20px;padding:3px 10px}
.pgrp-h a{font-weight:700;font-size:14px;color:var(--blue);text-decoration:none;white-space:nowrap}
.pgrp-h a i{font-size:12px}
</style>
<?php
  $_slides = []; foreach (array_slice($rows ?? [], 0, 6) as $_x) { $_slides[] = [hi_cover($_x), hi_listing_url($_x), $_x['name']]; }
  // which quick filter is on — the hero buttons and title follow it
  $_now   = ($f['for'] ?? '') !== '' ? $f['for'] : ($f['deal'] ?? '');
  $_names = ['rent' => 'Flats & homes for rent', 'sale' => 'Flats & homes for sale', 'pg' => 'PG accommodation',
             'commercial' => 'Commercial property', 'plots' => 'Plots & land', 'new' => 'New projects'];
  $_title = ($f['bhk'] ?? 0) && in_array($_now, ['', 'rent', 'sale'], true)
          ? esc(ucfirst($heading)) : esc($_names[$_now] ?? ucfirst($heading));
  $_btn   = static fn ($key, $icon, $label) => [$icon, $label, base_url('property') . '?for=' . $key . '#results', $_now === $key ? 'primary' : ''];
?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Property', $_now !== '' ? base_url('property') : null], ...($_now !== '' ? [[$_names[$_now] ?? 'Results', null]] : [])],
  'title'  => $_title . ' <span>in Noida</span>',
  'sub'    => ['home' => 'Flats, villas and builder floors across Noida — filter by budget, BHK and furnishing.', 'commercial' => 'Offices, shops, warehouses and coworking across Noida.', 'plots' => 'Plots and land across Noida — filter by budget and locality.', 'pg' => 'PG beds and rooms across Noida — filter by budget and locality.'][$f['mode'] ?? ''] ?? 'Flats, plots, PGs and commercial space across Noida — filter by deal, budget and BHK.',
  'facts'  => [['building', $_now !== '' ? 'Matching' : 'Listings', number_format($total)], ['handshake', 'Deals', 'Buy · Rent · PG'], ['location-dot', 'City', 'Noida']],
  'actions'=> [],   // Rent / Buy / PG buttons removed — the filters below already have them
  'links'  => [['map', 'Browse by area', hi_page('areas')], ['square-plus', 'List a property', base_url('list')]],
  'slides' => $_slides,
], ['saveData' => false]) ?>

<section class="sec" id="results" style="scroll-margin-top:70px"><div class="wrap">
  <div class="pp-toolbar sx">
  <?php /* the mockup's quick filters, above the detailed ones */ ?>
  <div class="pquick">
    <?php foreach (['' => 'All', 'sale' => 'Buy', 'rent' => 'Rent', 'pg' => 'PG',
                    'commercial' => 'Commercial', 'plots' => 'Plots', 'new' => 'New projects'] as $qk => $ql): ?>
      <a class="pq<?= ($f['for'] ?? '') === $qk ? ' on' : '' ?>"
         href="<?= base_url('property') ?><?= $qk === '' ? '' : '?for=' . $qk ?>#results"><?= esc($ql) ?></a>
    <?php endforeach; ?>
  </div>
  <?php
    /* the one-tap chips: Rent / Buy are for HOMES only. Commercial gets its
       space types instead; Plots and PG need no chips. */
    $_chips = ['home' => 'deal:rent:key:Rent|deal:sale:house:Buy', 'all' => 'deal:rent:key:Rent a home|deal:sale:house:Buy a home',
               'commercial' => 'kind:office:briefcase:Office|kind:shop:store:Shop|kind:warehouse:warehouse:Warehouse|kind:coworking:laptop:Coworking'][$f['mode'] ?? 'all'] ?? '';
  ?>
  <form method="get" action="<?= base_url('property') ?>" id="pForm" data-sx="<?= esc($_chips, 'attr') ?>">
    <?php
      /* Rent / Buy / PG is ONE choice. A link like ?for=sale used to stay in a
         hidden field, so picking Rent then searched "sale AND rent" (0 results,
         both chips lit). Now ?for=sale/rent/pg simply becomes the deal choice. */
      $_dealNow = ($f['deal'] ?? '') !== '' ? $f['deal']
                : (in_array($f['for'] ?? '', ['sale', 'rent', 'pg'], true) ? $f['for'] : '');
    ?>
    <?php if (($f['for'] ?? '') !== '' && ! in_array($f['for'], ['sale', 'rent', 'pg'], true)): ?><input type="hidden" name="for" value="<?= esc($f['for'], 'attr') ?>"><?php endif; ?>
    <div class="sx-top">
      <label class="sx-q"><i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" value="<?= esc($f['q']) ?>" autocomplete="off" placeholder="<?= esc(hi_c('property.ph_society_project_or_area_gardenia_sector_76', 'Society, project or area — Gardenia, Sector 76…'), 'attr') ?>"></label>
      <button type="submit" class="sx-go"><i class="fa-solid fa-magnifying-glass"></i><span>Search</span></button>
    </div>
    <?php
      /* Each kind of property only gets the filters that fit it:
         Buy / Rent = homes (flat, villa, builder floor) with BHK and furnishing;
         Commercial = offices, shops, warehouses, coworking (no BHK);
         Plots = land (no BHK, no furnishing); PG = beds (no BHK, no buy/rent). */
      $_mode  = $f['mode'] ?? 'all';
      $_kinds = array_intersect_key(hi_property_kinds(), array_flip($f['kinds'] ?? array_keys(hi_property_kinds())));
      $_deals = $_mode === 'all' ? hi_property_deals() : ($_mode === 'commercial' ? ['sale' => 'For sale', 'rent' => 'For lease'] : ['sale' => 'For sale', 'rent' => 'For rent']);
      $_showDeal = in_array($_mode, ['all', 'home', 'commercial'], true);
      $_showBhk  = in_array($_mode, ['all', 'home'], true);
      $_showFurn = $_mode !== 'plots';
      $_rentB = ['0-15000'=>'Under ₹15,000 / month','15000-30000'=>'₹15,000 – ₹30,000 / month','30000-60000'=>'₹30,000 – ₹60,000 / month','60000-0'=>'₹60,000+ / month'];
      $_saleB = ['0-5000000'=>'Under ₹50 Lakh','5000000-10000000'=>'₹50 Lakh – ₹1 Cr','10000000-20000000'=>'₹1 Cr – ₹2 Cr','20000000-0'=>'₹2 Cr+'];
      $_budgets = ($_dealNow === 'rent' || $_mode === 'pg') ? $_rentB : (($_dealNow === 'sale' || $_mode === 'plots') ? $_saleB : $_rentB + $_saleB);
      $_clear = base_url('property') . (($f['for'] ?? '') !== '' ? '?for=' . rawurlencode($f['for']) : '') . '#results';
    ?>
    <a class="fb-clear" href="<?= esc($_clear, 'attr') ?>" hidden></a>
    <?php if ($_mode === 'pg'): ?><input type="hidden" name="for" value="pg"><?php endif; ?>
    <div class="pfilter">
      <?php if ($_showDeal): ?>
      <select name="deal" onchange="pForm.submit()"<?= $_mode === 'commercial' ? ' data-sxl="Buy or lease"' : '' ?>>
        <option value=""><?= $_mode === 'home' ? 'Buy or rent' : ($_mode === 'commercial' ? 'Buy or lease' : 'Buy, rent or PG') ?></option>
        <?php foreach ($_deals as $k => $v): ?><option value="<?= $k ?>" <?= $sel($_dealNow, $k) ?>><?= esc($v) ?></option><?php endforeach; ?>
      </select>
      <?php endif; ?>
      <?php if (count($_kinds) > 1): ?>
      <select name="kind" onchange="pForm.submit()">
        <option value=""><?= $_mode === 'home' ? 'Any home type' : ($_mode === 'commercial' ? 'Any space type' : 'Any type') ?></option>
        <?php foreach ($_kinds as $k => $v): ?><option value="<?= $k ?>" <?= $sel($f['kind'], $k) ?>><?= esc($v[0]) ?></option><?php endforeach; ?>
      </select>
      <?php endif; ?>
      <?php if ($_showBhk): ?>
      <select name="bhk" onchange="pForm.submit()">
        <option value="">Any BHK</option>
        <?php foreach ([1,2,3,4] as $b): ?><option value="<?= $b ?>" <?= $sel($f['bhk'], $b) ?>><?= $b ?><?= $b === 4 ? '+' : '' ?> BHK</option><?php endforeach; ?>
      </select>
      <?php endif; ?>
      <select name="budget" onchange="pForm.submit()">
        <option value="">Any budget</option>
        <?php foreach ($_budgets as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $sel($f['budget'], $k) ?>><?= esc($v) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($_showFurn): ?>
      <select name="furnishing" onchange="pForm.submit()">
        <option value="">Any furnishing</option>
        <?php foreach (['unfurnished'=>'Unfurnished','semi'=>'Semi-furnished','full'=>'Fully furnished'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $sel($f['furnishing'], $k) ?>><?= esc($v) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <select name="locality" onchange="pForm.submit()">
        <option value="">Any locality</option>
        <?php foreach ($localities as $l): ?><option value="<?= (int) $l['id'] ?>" <?= $sel($f['locality'], $l['id']) ?>><?= esc($l['name']) ?></option><?php endforeach; ?>
      </select>
      <select name="sort" onchange="pForm.submit()">
        <option value="newest" <?= $sel($f['sort'],'newest') ?>>Newest first</option>
        <option value="low" <?= $sel($f['sort'],'low') ?>>Price: low to high</option>
        <option value="high" <?= $sel($f['sort'],'high') ?>>Price: high to low</option>
        <option value="area" <?= $sel($f['sort'],'area') ?>>Largest area</option>
      </select>
    </div>
  </form>
  <?php $_stays = \Config\Services::cache()->get('home_stays_v4'); $_kinds = is_array($_stays) ? ($_stays['kinds'] ?? []) : []; ?>
  <?php if ($_kinds): ?>
    <div class="sx-trend sx-pop">
      <span class="sx-onl"><i class="fa-solid fa-fire"></i> Popular:</span>
      <?php foreach ($_kinds as $_k): ?><a href="<?= hi_category_url(esc($_k['slug'], 'url')) ?>"><i class="fa-solid fa-tag"></i> <?= esc($_k['name']) ?> <em><?= (int) $_k['n'] ?></em></a><?php endforeach; ?>
    </div>
  <?php endif; ?>
  </div>

  <?php
    /* price check for what is being looked at: Rent / Buy homes, PG */
    $_gDeal = ($f['mode'] ?? '') === 'pg' ? 'pg' : ($f['deal'] ?? '');
    $_gLoc  = (int) ($f['locality'] ?? 0); $_gName = '';
    if ($_gLoc) { foreach (($localities ?? []) as $_lc) { if ((int) $_lc['id'] === $_gLoc) { $_gName = $_lc['name']; break; } } }
    $_guide = in_array(($f['mode'] ?? ''), ['home', 'pg'], true) ? hi_price_guide($_gDeal, ($f['mode'] ?? '') === 'pg' ? 'pg' : 'home', $_gLoc, $_gName, (int) ($f['bhk'] ?? 0)) : [];
  ?>
  <?php if ($_guide): ?>
    <div class="pg-guide pg-guide-sm">
      <div class="pg-ic"><i class="fa-solid fa-scale-balanced"></i></div>
      <p>Average asking <?= $_gDeal === 'sale' ? 'price' : 'rent' ?> for <?= ! empty($_guide['bhk']) ? (int) $_guide['bhk'] . ' BHK ' : '' ?><?= $_gDeal === 'pg' ? 'a PG bed' : 'homes' ?> in <?= esc($_guide['where']) ?>:
        <strong>₹<?= esc(hi_inr($_guide['avg'])) ?><?= esc($_guide['per']) ?></strong> <span>(<?= (int) $_guide['n'] ?> listings on HiNoida)</span></p>
    </div>
  <?php endif; ?>

  <?php if (! $rows && ! $anyRows): ?>
    <div class="pnone">
      <i class="fa-solid fa-house-circle-xmark"></i>
      <p style="margin:0 0 6px;color:var(--body);font-size:15.5px">No property has been listed yet.</p>
      <p style="margin:0 0 18px;font-size:13.5px">Be the first — add a flat, PG or office in a few minutes.</p>
      <a class="btn" style="display:inline-block" href="<?= base_url('list') ?>"><i class="fa-solid fa-square-plus"></i> List a property</a>
    </div>
  <?php elseif (! $rows): ?>
    <div class="pnone">
      <i class="fa-solid fa-building"></i>
      <p style="margin:0 0 6px;color:var(--body);font-size:15.5px"><?= ($f['q'] ?? '') !== '' ? 'Nothing listed for “' . esc($f['q']) . '” yet.' : esc(hi_c('property.p_no_property_matches_those_filters', 'No property matches those filters.')) ?></p>
      <p style="margin:0 0 18px;font-size:13.5px"><?= esc(hi_c('property.p_try_a_wider_budget_or_clear_a_filter', 'Try a wider budget, or clear a filter.')) ?></p>
      <?php if (($f['mode'] ?? '') === 'pg'): ?>
        <a class="btn" style="display:inline-block" href="<?= hi_category_url('pg-hostel-accommodations') ?>">See PGs &amp; hostels listed in Noida</a>
      <?php elseif (($f['mode'] ?? '') === 'commercial'): ?>
        <a class="btn" style="display:inline-block" href="<?= hi_category_url('coworking-spaces') ?>">See coworking spaces</a>
        <a class="btn" style="display:inline-block;margin-left:8px" href="<?= hi_category_url('commercial-properties') ?>">See commercial properties</a>
  <?php else: ?>
        <a class="btn" style="display:inline-block" href="<?= base_url('property') ?>">Show all property</a>
      <?php endif; ?>
    </div>
    <?php if (! empty($suggest)): ?>
      <div class="pgrp" style="margin-top:26px">
        <div class="pgrp-h"><h2><i class="fa-solid fa-house"></i> <?= esc(ucfirst($heading)) ?> in Noida <span><?= count($suggest) ?></span></h2>
          <a href="<?= base_url('property') ?><?php $_sf = ($f['for'] ?? '') !== '' ? $f['for'] : ($f['deal'] ?? ''); ?><?= $_sf !== '' ? '?for=' . esc($_sf, 'url') : '' ?>#results">See all <i class="fa-solid fa-arrow-right"></i></a></div>
        <div class="lgrid"><?php foreach ($suggest as $l): ?><?= view('frontend/_propertycard', ['l' => $l]) ?><?php endforeach; ?></div>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <?php /* where a place is matters more for a flat than for anything else */ ?>
    <?php $_ssUrl = '/' . ltrim(service('uri')->getPath(), '/') . (($_qs = (string) service('request')->getServer('QUERY_STRING')) !== '' ? '?' . $_qs : ''); ?>
    <div class="ss-bar">
      <?php if ($_ssMsg = session()->getFlashdata('saved_search')): ?><span class="ds-badge ok"><i class="fa-solid fa-check"></i> <?= esc($_ssMsg) ?></span><?php endif; ?>
      <form method="post" action="<?= base_url('search/save') ?>">
        <input type="hidden" name="url" value="<?= esc($_ssUrl, 'attr') ?>">
        <input type="hidden" name="label" value="<?= esc(strip_tags(ucfirst($heading ?? 'Property')) . (! empty($_gName) && is_string($_gName) ? ' in ' . $_gName : ' in Noida'), 'attr') ?>">
        <button class="ds-btn ghost" type="submit"><i class="fa-regular fa-bookmark"></i> Save this search</button>
      </form>
    </div>
    <?= view('frontend/_map', ['listings' => $rows]) ?>
    <?php
      /* With no Rent/Buy filter picked, show the homes in groups — For rent,
         For sale, then PG and anything else — each with its own "See all". */
      $_dealPicked = ($f['mode'] ?? 'all') !== 'all';
      $_groups = [];
      if (! $_dealPicked) {
          foreach ($rows as $_r) {
              $_pp = hi_property_of((int) $_r['id']); $_gk = hi_property_group((string) ($_pp['kind'] ?? ''));
              $_d  = $_gk === 'home' ? ((string) ($_pp['deal'] ?? '') === 'sale' ? 'sale' : 'rent')
                   : ['pg' => 'pg', 'commercial' => 'commercial', 'land' => 'plots'][$_gk] ?? 'rent';
              if (($_pp['deal'] ?? '') === 'pg') { $_d = 'pg'; }
              $_groups[$_d][] = $_r;
          }
      }
      $_gName = ['rent' => ['key', 'Homes for rent'], 'sale' => ['house', 'Homes for sale'], 'pg' => ['bed', 'PG / hostel'],
                 'commercial' => ['building', 'Commercial'], 'plots' => ['map', 'Plots &amp; land']];
    ?>
    <?php if ($_groups && count($_groups) > 1): ?>
      <?php foreach (['rent', 'sale', 'pg', 'commercial', 'plots'] as $_d): if (empty($_groups[$_d])) { continue; } ?>
        <div class="pgrp">
          <div class="pgrp-h">
            <h2><i class="fa-solid fa-<?= $_gName[$_d][0] ?>"></i> <?= $_gName[$_d][1] ?> <span><?= count($_groups[$_d]) ?></span></h2>
            <a href="<?= base_url('property') ?>?for=<?= $_d ?>#results">See all <i class="fa-solid fa-arrow-right"></i></a>
          </div>
          <div class="lgrid">
            <?php foreach ($_groups[$_d] as $l): ?><?= view('frontend/_propertycard', ['l' => $l]) ?><?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
    <div class="lgrid">
      <?php foreach ($rows as $l): ?><?= hi_property_of((int) $l['id']) ? view('frontend/_propertycard', ['l' => $l]) : view('frontend/_card', ['l' => $l, 'badge' => null]) ?><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
  <?php endif; ?>
  <?php if (in_array(($f['mode'] ?? ''), ['home', 'pg', 'commercial', 'plots'], true)): ?>
    <div class="lp-band lp-band-inline">
      <div class="lp-band-txt">
        <h2>Own a property in Noida?</h2>
        <p>Put it in front of people looking in your sector.</p>
        <ul>
          <li><i class="fa-solid fa-circle-check"></i> Free to post</li>
          <li><i class="fa-solid fa-circle-check"></i> Takes about 5 minutes</li>
          <li><i class="fa-solid fa-circle-check"></i> Change it any time</li>
        </ul>
      </div>
      <div class="lp-band-go">
        <a class="lp-band-btn" href="<?= base_url('list') ?>"><i class="fa-solid fa-house-circle-check"></i> Post property FREE</a>
      </div>
    </div>
  <?php endif; ?>
</div></section>
<script>
/* Rent / Buy apply at once. Elsewhere a chip only marks a choice until
   Search is pressed; here Rent / Buy is the main choice, so it runs straight
   away (only one of them can be on). Added once, even after the results are
   swapped in without a page reload. */
(function(){
  if(window.__hiDealChips) return; window.__hiDealChips=true;
  document.addEventListener('click',function(e){
    var c=e.target.closest && e.target.closest('#pForm .sx-chip[data-k="deal"]'); if(!c) return;
    setTimeout(function(){
      var f=document.getElementById('pForm'); if(!f) return;
      var go=f.querySelector('.sx-go,button[type=submit]');
      if(f.requestSubmit && go) f.requestSubmit(go); else if(go) go.click();
    },0);
  });
})();
</script>
<?= $this->endSection() ?>
