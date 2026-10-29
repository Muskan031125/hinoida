<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
/* Calm areas page: one search box, the 8 busiest areas, then each sector
   range folded into a single row. Duplicate areas ("sector 4" / "Sector 4")
   are merged into one, keeping the link that has more listings. */
$bands = [
  'b1'  => ['Sectors 1–50',    1,   50],
  'b2'  => ['Sectors 51–100',  51,  100],
  'b3'  => ['Sectors 101–150', 101, 150],
  'b4'  => ['Sectors 151 and above', 151, 99999],
  'oth' => ['Other places',    null, null],
];
$merged = [];
foreach ($areas as $a) {
    $name = trim(preg_replace('/\s+/', ' ', html_entity_decode((string) $a['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $num  = preg_match('/^sector[\s\-]*(\d+)\s*$/i', $name, $m) ? (int) $m[1] : null;
    $name = $num !== null ? 'Sector ' . $num : ucwords($name);
    $key  = strtolower($name);
    $n    = (int) $a['listing_count'];
    if (isset($merged[$key])) {
        if ($n > $merged[$key]['best']) { $merged[$key]['url'] = hi_locality_url(esc($a['slug'], 'url')); $merged[$key]['best'] = $n; }
        $merged[$key]['n'] += $n;
        continue;
    }
    $merged[$key] = ['name' => $name, 'num' => $num, 'n' => $n, 'best' => $n, 'url' => hi_locality_url(esc($a['slug'], 'url'))];
}
$groups = array_fill_keys(array_keys($bands), []);
foreach ($merged as $a) {
    $k = 'oth';
    if ($a['num'] !== null) { foreach ($bands as $bk => $b) { if ($b[1] !== null && $a['num'] >= $b[1] && $a['num'] <= $b[2]) { $k = $bk; break; } } }
    $groups[$k][] = $a;
}
// inside a range: in number order (easy to find), places A–Z; empty ones last
foreach ($groups as &$g) { usort($g, static fn ($x, $y) => [$x['n'] === 0, $x['num'] ?? 0, $x['name']] <=> [$y['n'] === 0, $y['num'] ?? 0, $y['name']]); }
unset($g);
$groups  = array_filter($groups);
$popular = array_values($merged);
usort($popular, static fn ($x, $y) => $y['n'] <=> $x['n']);
$popular = array_slice(array_filter($popular, static fn ($a) => $a['n'] > 0), 0, 8);
?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Areas', null]],
  'title'  => esc(hi_c('areas.search_by_area', 'Search by area')),
  'sub'    => 'Pick a sector or locality to see every business, property and place around it.',
  'facts'  => [['location-dot', 'Areas', (string) count($merged)], ['city', 'City', 'Noida'], ['map', 'Coverage', 'All sectors']],
  'actions'=> [['magnifying-glass', 'Search Noida', hi_page('search'), 'primary'], ['folder-open', 'Categories', hi_page('categories')]],
  'links'  => [['building', 'Property', base_url('property')], ['calendar', 'Events', hi_page('events')], ['square-plus', 'List your business', base_url('list')]],
], ['saveData' => false]) ?>

<section class="sec ar-sec"><div class="wrap"><div class="ar-box">
  <label class="ar-find"><i class="fa-solid fa-magnifying-glass"></i>
    <input type="search" id="arFind" placeholder="Find an area — try 62, Sector 18 or Greater Noida" autocomplete="off"></label>

  <div id="arPopWrap">
    <span class="ar-lbl">Popular areas</span>
    <div class="ar-pop">
      <?php foreach ($popular as $a): ?>
        <a class="ar-pc" href="<?= $a['url'] ?>"><i class="fa-solid fa-location-dot"></i>
          <span><b><?= esc($a['name']) ?></b><small><?= number_format($a['n']) ?> listings</small></span></a>
      <?php endforeach; ?>
    </div>
  </div>

  <span class="ar-lbl">All areas</span>
  <div class="ar-list">
    <?php foreach ($groups as $k => $g): ?>
      <details class="ar-row">
        <summary><b><?= esc($bands[$k][0]) ?></b><small><?= count($g) ?> areas</small><i class="ar-chev fa-solid fa-chevron-down"></i></summary>
        <div class="ar-links">
          <?php foreach ($g as $a): ?>
            <a href="<?= $a['url'] ?>" class="<?= $a['n'] ? '' : 'zero' ?>" data-q="<?= esc(strtolower($a['name']), 'attr') ?>" data-num="<?= $a['num'] ?? '' ?>">
              <?= esc($a['name']) ?><span><?= $a['n'] ?: '—' ?></span></a>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endforeach; ?>
    <p class="ar-none" id="arNone" hidden>No area matches that. Try just the number, like “62”.</p>
  </div>
</div></div></section>
<script>
(function(){
  var f=document.getElementById('arFind'), none=document.getElementById('arNone'), pop=document.getElementById('arPopWrap'); if(!f) return;
  var rows=[].slice.call(document.querySelectorAll('.ar-row'));
  f.addEventListener('input',function(){
    var q=f.value.trim().toLowerCase().replace(/^sec(tor)?\s*/,''), isNum=/^\d+$/.test(q), any=false;
    pop.hidden=q!=='';
    rows.forEach(function(r){ var hit=0;
      r.querySelectorAll('.ar-links a').forEach(function(a){
        var ok=q===''||(isNum?a.dataset.num.indexOf(q)===0:a.dataset.q.indexOf(q)>-1);
        a.classList.toggle('hide',!ok); if(ok) hit++; });
      r.classList.toggle('hide',q!==''&&!hit);
      if(q!=='') r.open=hit>0; if(hit&&q!=='') any=true; });
    if(q==='') rows.forEach(function(r){ r.open=false; });
    none.hidden=q===''||any;
  });
})();
</script>
<?= $this->endSection() ?>
