<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $sel = static fn ($a, $b) => (string) $a === (string) $b ? 'selected' : ''; ?>
<?php $_slides = []; foreach (array_slice($results ?? [], 0, 6) as $_x) { $_slides[] = [hi_cover($_x), hi_listing_url($_x), $_x['name']]; } ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [[$q !== '' ? 'Search' : 'Listings', null]],
  'title'  => $q !== '' ? 'Results for <span>“' . esc($q) . '”</span>' : 'All listings <span>in Noida</span>',
  'sub'    => 'Every business and property on HiNoida — filter by area, type, rating or opening hours.',
  'facts'  => [['list', 'Results', number_format($pg['total'] ?? count($results ?? []))], ['location-dot', 'Areas', (string) count($areas ?? [])], ['filter', 'Search', $q !== '' ? $q : 'Everything']],
  'links'  => [['folder-open', 'All categories', hi_page('categories')], ['map', 'Browse by area', hi_page('areas')], ['building', 'Property', base_url('property')]],
  'slides' => $_slides,
], ['saveData' => false]) ?>

<section class="sec sx-sec"><div class="wrap">
  <?php
    $_sortNames = ['relevance'=>'Relevance','rating'=>'Top rated','reviews'=>'Most reviewed','featured'=>'Featured','newest'=>'Newest first','name'=>'Name (A–Z)'];
    $_areaName = ''; foreach ($areas as $a) { if ((string) $a['id'] === (string) $f['area']) { $_areaName = $a['name']; break; } }
    // what is switched on right now — shown as removable chips
    $_on = [];
    if ($q !== '')                    $_on[] = ['q', '', 'magnifying-glass', '“' . $q . '”'];
    if ($f['area'])                   $_on[] = ['area', '', 'location-dot', $_areaName ?: 'Area'];
    if (($f['show'] ?? '') !== '')    $_on[] = ['show', '', 'layer-group', $f['show'] === 'property' ? 'Property only' : 'Businesses only'];
    if ($f['rating'])                 $_on[] = ['rating', '', 'star', (int) $f['rating'] . '★ & up'];
    if ($f['open'])                   $_on[] = ['open', '', 'clock', 'Open now'];
    if ($f['sort'] !== 'relevance')   $_on[] = ['sort', 'relevance', 'arrow-down-wide-short', $_sortNames[$f['sort']] ?? $f['sort']];
    // one-tap shortcuts: [field, value, icon, label]
    $_quick = [['open','1','clock','Open now'], ['rating','4','star','4★ & up']];
    $_isOn = static function ($k, $v) use ($f) { return $k === 'open' ? (bool) $f['open'] : (string) ($f[$k] ?? '') === (string) $v; };
  ?>
  <form method="get" action="<?= hi_page('search') ?>" id="searchForm" class="sx">
    <div class="sx-top">
      <label class="sx-q">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" id="sxQ" value="<?= esc($q) ?>" autocomplete="off"
               placeholder="<?= esc(hi_c('search.ph_search_businesses_services_or_areas_in_noida', 'Search businesses, services or areas in Noida...'), 'attr') ?>"
               data-hints="<?= esc(implode('|', array_map(static fn ($p) => 'Try “' . $p . '”', $popular)), 'attr') ?>">
        <button type="button" class="sx-x" id="sxX" aria-label="Clear search"<?= $q === '' ? ' hidden' : '' ?>><i class="fa-solid fa-xmark"></i></button>
        <kbd class="sx-kbd">/</kbd>
      </label>
      <button type="submit" class="sx-go"><i class="fa-solid fa-magnifying-glass"></i><span>Search</span></button>
    </div>

    <div class="sx-quick" role="group" aria-label="Quick filters">
      <?php foreach ($_quick as $_c): $_a = $_isOn($_c[0], $_c[1]); ?>
        <button type="button" class="sx-chip<?= $_a ? ' on' : '' ?>" data-k="<?= $_c[0] ?>" data-v="<?= esc($_c[1], 'attr') ?>" aria-pressed="<?= $_a ? 'true' : 'false' ?>"><i class="fa-solid fa-<?= $_c[2] ?>"></i> <?= esc($_c[3]) ?><?php if ($_a): ?> <i class="fa-solid fa-xmark sx-cx"></i><?php endif; ?></button>
      <?php endforeach; ?>
      <?php if ($_on): ?><a class="sx-clear" href="<?= hi_page('search') ?>"><i class="fa-solid fa-rotate-left"></i> Clear all</a><?php endif; ?>
    </div>

    <div class="filterbar sx-filters" id="sxFilters">
      <div class="sx-f"><span class="sx-l"><i class="fa-solid fa-location-dot"></i> Area</span>
      <select name="area" onchange="this.form.requestSubmit()">
        <option value="">All areas</option>
        <?php foreach ($areas as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $sel($f['area'], $a['id']) ?>><?= esc($a['name']) ?><?= isset($a['n']) ? ' (' . (int) $a['n'] . ')' : '' ?></option><?php endforeach; ?>
      </select></div>
      <?php /* "show" and "rating" are set by the chips above — kept here so the form remembers them */ ?>
      <input type="hidden" name="show" value="<?= esc($f['show'] ?? '', 'attr') ?>">
      <input type="hidden" name="rating" value="<?= $f['rating'] ? (int) $f['rating'] : '' ?>">
      <div class="sx-f"><span class="sx-l"><i class="fa-solid fa-arrow-down-wide-short"></i> Sort by</span>
      <select name="sort" onchange="this.form.requestSubmit()">
        <?php foreach ($_sortNames as $_k => $_v): ?><option value="<?= $_k ?>" <?= $sel($f['sort'], $_k) ?>><?= esc($_v) ?></option><?php endforeach; ?>
      </select></div>
      <input type="checkbox" name="open" value="1" id="sxOpen" <?= $f['open'] ? 'checked' : '' ?> hidden>
    </div>

    <?php if (true): ?>
      <div class="sx-trend sx-pop">
        <span class="sx-onl"><i class="fa-solid fa-fire"></i> Popular:</span>
        <?php foreach ($popular as $p): ?><a href="<?= hi_page('search') . '?q=' . urlencode($p) ?>"><?= esc($p) ?></a><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </form>

  <script>
  (function(){
    var form=document.getElementById('searchForm'); if(!form) return;
    var q=document.getElementById('sxQ'), x=document.getElementById('sxX');
    function go(){ form.classList.add('is-busy'); var r=document.querySelector('.sx-sec .lgrid'); if(r) r.classList.add('is-busy');
      var b=form.querySelector('.sx-go'); if(b) b.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i><span>Searching</span>'; }
    form.addEventListener('submit',function(e){ if(e.submitter) go(); });   // chips only pick; Search loads
    // set one field and search again
    function setField(k,v){
      if(k==='open'){ document.getElementById('sxOpen').checked = v==='1'; }
      else { var el=form.elements[k]; if(el) el.value=v; }
      if(form.requestSubmit) form.requestSubmit(); else { go(); form.submit(); }
    }
    form.querySelectorAll('.sx-chip').forEach(function(c){ c.addEventListener('click',function(){
      var on=c.classList.contains('on'), k=c.dataset.k;
      setField(k, on ? (k==='sort' ? 'relevance' : '') : c.dataset.v); }); });
    form.querySelectorAll('.sx-tag').forEach(function(t){ t.addEventListener('click',function(){ setField(t.dataset.k,t.dataset.v); }); });
    // clear button + "/" to focus
    if(q&&x){ q.addEventListener('input',function(){ x.hidden=!q.value; });
      x.addEventListener('click',function(){ q.value=''; x.hidden=true; q.focus(); }); }
    document.addEventListener('keydown',function(e){ if(e.key==='/' && document.activeElement!==q && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)){ e.preventDefault(); q.focus(); } });
    // gentle rotating hint in the empty box
    var hints=(q&&q.dataset.hints||'').split('|').filter(Boolean), base=q?q.placeholder:'', i=0;
    if(q&&hints.length&&!q.value){ setInterval(function(){ if(document.activeElement===q||q.value) return; i=(i+1)%(hints.length+1); q.placeholder = i===0 ? base : hints[i-1]; },2600); }
    // phones: filters fold away behind one button
    var m=document.getElementById('sxMore'), fl=document.getElementById('sxFilters');
    if(m&&fl){ var mq=window.matchMedia('(max-width:640px)');
      function fit(){ var open=!mq.matches; fl.classList.toggle('is-shut',!open); m.setAttribute('aria-expanded',open?'true':'false'); m.classList.toggle('on',false); }
      fit(); if(mq.addEventListener) mq.addEventListener('change',fit);
      m.addEventListener('click',function(){ var shut=fl.classList.toggle('is-shut'); m.setAttribute('aria-expanded',shut?'false':'true'); m.classList.toggle('on',!shut); }); }
  })();
  </script>

  <?php /* Property is listed here too. This points at the dedicated page, where
           budget, BHK and furnishing filters live. */ ?>
  <?php if (! empty($propMatches) && ($f['show'] ?? '') !== 'business'): ?>
    <a href="<?= base_url('property') ?><?= ($propQ ?? '') !== '' ? '?q=' . urlencode($propQ) : '' ?>"
       style="display:flex;align-items:center;gap:10px;margin:18px 0 0;padding:12px 15px;border:1px solid #dbe6fb;
              background:#f2f6fd;border-radius:11px;font-size:13.5px;color:var(--ink);font-weight:600">
      <i class="fa-solid fa-building" style="color:var(--blue)"></i>
      <b><?= number_format($propMatches) ?></b> propert<?= $propMatches === 1 ? 'y' : 'ies' ?>
      <?= ($propQ ?? '') !== '' ? 'matching &ldquo;' . esc($propQ) . '&rdquo;' : 'in this list' ?> &mdash;
      search by budget, BHK and furnishing
      <span style="margin-left:auto;color:var(--blue)">Property search &rsaquo;</span>
    </a>
  <?php endif; ?>

  <?php if (! $results): ?>
    <div style="text-align:center;padding:40px 20px">
      <i class="fa-solid fa-magnifying-glass" style="font-size:32px;color:#c7d2e4;display:block;margin-bottom:14px"></i>
      <p style="color:var(--body);font-size:15.5px;margin:0 0 6px">No matches<?= $q!==''?' for &ldquo;'.esc($q).'&rdquo;':'' ?>.</p>
      <p style="color:var(--muted);font-size:13.5px;margin:0 0 20px"><?= esc(hi_c('search.p_try_a_broader_term_remove_a_filter_or_browse', 'Try a broader term, remove a filter, or browse instead:')) ?></p>
      <div class="pills" style="justify-content:center">
        <?php foreach ($popular as $p): ?><a href="<?= hi_page('search') . '?q=' . urlencode($p) ?>" style="background:#eef3fc;border-color:#dbe6fb;color:var(--blue)"><?= esc($p) ?></a><?php endforeach; ?>
      </div>
      <a href="<?= hi_page('areas') ?>" style="display:inline-block;margin-top:18px;font-weight:700;color:var(--blue)">Browse by area instead &rsaquo;</a>
    </div>
  <?php else: ?>
    <?php $_n = $matched ?? count($results); $_noun = $q !== '' ? 'result' : 'listing'; ?>
    <p class="sx-count"><b><?= number_format($_n) ?></b> <?= $_noun ?><?= $_n > 1 ? 's' : '' ?><?= $q !== '' ? ' for &ldquo;' . esc($q) . '&rdquo;' : '' ?><?= $_areaName !== '' ? ' in ' . esc($_areaName) : '' ?></p>
    <?= view('frontend/_map', ['listings' => $results]) ?>
    <div class="resbar"><?= view('frontend/_viewtoggle') ?></div>
    <div class="lgrid">
      <?php foreach ($results as $l): ?>
        <?= view('frontend/_card', ['l' => $l]) ?>
      <?php endforeach; ?>
    </div>
    <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
  <?php endif; ?>
</div></section>
<?= $this->endSection() ?>
