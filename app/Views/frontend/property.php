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
</style>
<?php
  $_slides = []; foreach (array_slice($rows ?? [], 0, 6) as $_x) { $_slides[] = [hi_cover($_x), hi_listing_url($_x), $_x['name']]; }
  // which quick filter is on — the hero buttons and title follow it
  $_now   = ($f['for'] ?? '') !== '' ? $f['for'] : ($f['deal'] ?? '');
  $_names = ['rent' => 'Property for rent', 'sale' => 'Property for sale', 'pg' => 'PG accommodation',
             'commercial' => 'Commercial property', 'plots' => 'Plots', 'new' => 'New projects'];
  $_title = ($f['bhk'] ?? 0) && in_array($_now, ['', 'rent', 'sale'], true)
          ? esc(ucfirst($heading)) : esc($_names[$_now] ?? ucfirst($heading));
  $_btn   = static fn ($key, $icon, $label) => [$icon, $label, base_url('property') . '?for=' . $key . '#results', $_now === $key ? 'primary' : ''];
?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Property', $_now !== '' ? base_url('property') : null], ...($_now !== '' ? [[$_names[$_now] ?? 'Results', null]] : [])],
  'title'  => $_title . ' <span>in Noida</span>',
  'sub'    => 'Flats, plots, PGs and commercial space across Noida — filter by deal, budget and BHK.',
  'facts'  => [['building', $_now !== '' ? 'Matching' : 'Listings', number_format($total)], ['handshake', 'Deals', 'Buy · Rent · PG'], ['location-dot', 'City', 'Noida']],
  'actions'=> [$_btn('rent', 'key', 'Rent'), $_btn('sale', 'house', 'Buy'), $_btn('pg', 'bed', 'PG')],
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
  <form method="get" action="<?= base_url('property') ?>" id="pForm" data-sx="deal:rent:key:Rent|deal:sale:house:Buy">
    <?php if (($f['for'] ?? '') !== ''): ?><input type="hidden" name="for" value="<?= esc($f['for'], 'attr') ?>"><?php endif; ?>
    <div class="sx-top">
      <label class="sx-q"><i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" value="<?= esc($f['q']) ?>" autocomplete="off" placeholder="<?= esc(hi_c('property.ph_society_project_or_area_gardenia_sector_76', 'Society, project or area — Gardenia, Sector 76…'), 'attr') ?>"></label>
      <button type="submit" class="sx-go"><i class="fa-solid fa-magnifying-glass"></i><span>Search</span></button>
    </div>
    <div class="pfilter">
      <select name="deal" onchange="pForm.submit()">
        <option value="">Buy or rent</option>
        <?php foreach (hi_property_deals() as $k => $v): ?><option value="<?= $k ?>" <?= $sel($f['deal'], $k) ?>><?= esc($v) ?></option><?php endforeach; ?>
      </select>
      <select name="kind" onchange="pForm.submit()">
        <option value="">Any type</option>
        <?php foreach (hi_property_kinds() as $k => $v): ?><option value="<?= $k ?>" <?= $sel($f['kind'], $k) ?>><?= esc($v[0]) ?></option><?php endforeach; ?>
      </select>
      <select name="bhk" onchange="pForm.submit()">
        <option value="">Any BHK</option>
        <?php foreach ([1,2,3,4] as $b): ?><option value="<?= $b ?>" <?= $sel($f['bhk'], $b) ?>><?= $b ?><?= $b === 4 ? '+' : '' ?> BHK</option><?php endforeach; ?>
      </select>
      <select name="budget" onchange="pForm.submit()">
        <option value="">Any budget</option>
        <?php foreach (['0-15000'=>'Under ₹15,000','15000-30000'=>'₹15,000 – ₹30,000','30000-60000'=>'₹30,000 – ₹60,000','60000-0'=>'₹60,000+','0-5000000'=>'Under ₹50 Lakh','5000000-10000000'=>'₹50 L – ₹1 Cr','10000000-0'=>'₹1 Cr+'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $sel($f['budget'], $k) ?>><?= esc($v) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="furnishing" onchange="pForm.submit()">
        <option value="">Any furnishing</option>
        <?php foreach (['unfurnished'=>'Unfurnished','semi'=>'Semi-furnished','full'=>'Fully furnished'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $sel($f['furnishing'], $k) ?>><?= esc($v) ?></option>
        <?php endforeach; ?>
      </select>
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
      <p style="margin:0 0 6px;color:var(--body);font-size:15.5px"><?= esc(hi_c('property.p_no_property_matches_those_filters', 'No property matches those filters.')) ?></p>
      <p style="margin:0 0 18px;font-size:13.5px"><?= esc(hi_c('property.p_try_a_wider_budget_or_clear_a_filter', 'Try a wider budget, or clear a filter.')) ?></p>
      <a class="btn" style="display:inline-block" href="<?= base_url('property') ?>">Show all property</a>
    </div>
  <?php else: ?>
    <?php /* where a place is matters more for a flat than for anything else */ ?>
    <?= view('frontend/_map', ['listings' => $rows]) ?>
    <div class="lgrid">
      <?php foreach ($rows as $l): ?><?= view('frontend/_propertycard', ['l' => $l]) ?><?php endforeach; ?>
    </div>
    <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
  <?php endif; ?>
</div></section>
<?= $this->endSection() ?>
