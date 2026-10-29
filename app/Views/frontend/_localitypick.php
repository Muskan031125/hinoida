<?php
/**
 * Header locality filter — the original's "All localities" dropdown.
 *
 * Remembered for the session, so choosing one narrows browsing everywhere
 * until it is cleared. Only localities that actually have live listings are
 * offered, with their count, so the list never leads anywhere empty.
 */
$db = \Config\Database::connect();

/** @var list<array{id:string,name:string,slug:string,n:string}> $locs */
$locs = $db->table('locality l')
    ->select('l.id, l.name, l.slug, (SELECT COUNT(*) FROM listing li WHERE li.locality_id = l.id AND li.status = "active") AS n')
    ->orderBy('n', 'DESC')->get()->getResultArray();
$locs = array_values(array_filter($locs, static fn ($x) => (int) $x['n'] > 0));

$curId   = (int) session()->get('loc_id');
$curName = (string) (session()->get('loc_name') ?: '');
$back    = urlencode(current_url() . ($_SERVER['QUERY_STRING'] ?? '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
?>
<div class="locpick" id="locPick">
  <button type="button" class="locbtn" id="locBtn" aria-expanded="false" aria-haspopup="listbox">
    <i class="fa-solid fa-location-dot"></i>
    <span><?= esc($curName !== '' ? $curName : 'All localities') ?></span>
    <i class="fa-solid fa-chevron-down" style="font-size:9px"></i>
  </button>
  <div class="locdrop" id="locDrop" hidden role="listbox">
    <input type="search" class="locsearch" id="locSearch" placeholder="<?= esc(hi_c('_localitypick.ph_find_a_locality', 'Find a locality…'), 'attr') ?>" autocomplete="off">
    <div class="loclist" id="locList">
      <a href="<?= base_url('locality/clear') ?>?r=<?= $back ?>" class="locitem<?= $curId ? '' : ' on' ?>" data-name="all localities">
        All localities
      </a>
      <?php foreach ($locs as $l): ?>
        <a href="<?= base_url('locality/set/' . (int) $l['id']) ?>?r=<?= $back ?>"
           class="locitem<?= $curId === (int) $l['id'] ? ' on' : '' ?>"
           data-name="<?= esc(mb_strtolower($l['name']), 'attr') ?>">
          <?= esc($l['name']) ?> <span class="locn"><?= number_format((int) $l['n']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <p class="locnone" id="locNone" hidden><?= esc(hi_c('_localitypick.p_no_locality_by_that_name', 'No locality by that name.')) ?></p>
  </div>
</div>
<script>
(function(){
  var wrap = document.getElementById('locPick'), btn = document.getElementById('locBtn'),
      drop = document.getElementById('locDrop'), search = document.getElementById('locSearch'),
      none = document.getElementById('locNone');
  if (!wrap) { return; }

  function open(yes){
    drop.hidden = !yes;
    btn.setAttribute('aria-expanded', yes ? 'true' : 'false');
    if (yes) { search.value = ''; filter(); search.focus(); }
  }
  function filter(){
    var q = search.value.trim().toLowerCase(), shown = 0;
    drop.querySelectorAll('.locitem').forEach(function(a){
      var hit = !q || (a.dataset.name || '').indexOf(q) !== -1;
      a.style.display = hit ? '' : 'none';
      if (hit) { shown++; }
    });
    none.hidden = shown > 0;
  }
  btn.addEventListener('click', function(e){ e.stopPropagation(); open(drop.hidden); });
  search.addEventListener('input', filter);
  // a click anywhere else closes it, and Esc does too
  document.addEventListener('click', function(e){ if (!wrap.contains(e.target)) { open(false); } });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !drop.hidden) { open(false); btn.focus(); } });
})();
</script>
