<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Categories', null]],
  'title'  => 'Categories' . ($locName !== '' ? ' <span>in ' . esc($locName) . '</span>' : ' <span>in Noida</span>'),
  'sub'    => 'Every business category across Noida — pick one to see the places, ratings and contact details.',
  'facts'  => [['layer-group', 'Categories', (string) count($tree)], ['location-dot', 'Localities', (string) count($locs)], ['filter', 'Showing', $locName !== '' ? $locName : 'All of Noida']],
  'actions'=> [['magnifying-glass', 'Search Noida', hi_page('search'), 'primary'], ['map', 'Browse by area', hi_page('areas')]],
  'links'  => [['calendar', 'Events', hi_page('events')], ['star', 'Offers', hi_page('offers')], ['square-plus', 'List your business', base_url('list')]],
], ['saveData' => false]) ?>

<section class="sec"><div class="wrap">
  <?php
    $dec = static fn ($x) => html_entity_decode((string) $x, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $low = static fn ($x) => mb_strtolower(html_entity_decode((string) $x, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
  ?>
  <?php /* one toolbar: find a category instantly + filter by locality */ ?>
  <div class="ct-tools">
    <label class="ct-find"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" id="ctFind" placeholder="Find a category — e.g. plumber, cafe, PG…" autocomplete="off"></label>
    <form method="get" class="ct-loc">
      <i class="fa-solid fa-location-dot"></i>
      <select name="locality" onchange="this.form.submit()">
        <option value="">All localities</option>
        <?php foreach ($locs as $l): ?>
          <option value="<?= (int) $l['id'] ?>" <?= $loc === (int) $l['id'] ? 'selected' : '' ?>><?= esc($l['name']) ?> (<?= number_format((int) $l['n']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <?php if ($loc): ?><a class="fb-clear" href="<?= hi_page('categories') ?>">Clear</a><?php endif; ?>
    </form>
  </div>
  <?php if ($tree): ?>
    <nav class="ct-jump" aria-label="Jump to a category">
      <?php foreach ($tree as $t): $c = $t['cat']; ?>
        <a href="#cat-<?= esc($c['slug'], 'attr') ?>"><i class="fa-solid fa-<?= esc(hi_cat_icon($c['name'])) ?>"></i> <?= esc($dec($c['name'])) ?></a>
      <?php endforeach; ?>
    </nav>
    <p class="ct-none" id="ctNone" hidden><i class="fa-regular fa-face-frown"></i> No category matches “<b></b>”. Try another word, or <a href="<?= hi_page('search') ?>">search all listings</a>.</p>
  <?php endif; ?>

  <?php if (! $tree): ?>
    <div style="text-align:center;padding:44px 20px">
      <i class="fa-solid fa-folder-open" style="font-size:28px;color:#c7d2e4;display:block;margin-bottom:14px"></i>
      <p style="color:var(--body);margin:0 0 6px">No categories have listings<?= $locName !== '' ? ' in ' . esc($locName) : '' ?> yet.</p>
      <?php if ($loc): ?>
        <a class="btn" style="display:inline-block;margin-top:10px" href="<?= hi_page('categories') ?>">Show all localities</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="ctgrid" id="ctGrid">
      <?php foreach ($tree as $t): $c = $t['cat'];
        $kids = $t['kids'] ?: [];
        usort($kids, static fn ($x, $y) => (int) $y['n'] <=> (int) $x['n']);   // biggest first
        $more = max(0, count($kids) - 5);
        $words = $low($c['name']) . ' ' . implode(' ', array_map(static fn ($k) => $low($k['name']), $kids)); ?>
        <div class="ctb" id="cat-<?= esc($c['slug'], 'attr') ?>" data-words="<?= esc($words, 'attr') ?>">
          <a class="ctb-head" href="<?= hi_category_url(esc($c['slug'], 'url')) ?>">
            <span class="ctb-ph" style="background-image:<?= esc(hi_cat_bg(array_diff_key($c, ['thumbnail' => 0])), 'attr') ?>"></span>
            <span class="ctb-ic"><i class="fa-solid fa-<?= esc(hi_cat_icon($c['name'])) ?>"></i></span>
            <span class="ctb-t"><b><?= esc($dec($c['name'])) ?></b>
              <em><?= number_format((int) $t['total']) ?> businesses<?= $kids ? ' · ' . count($kids) . ' types' : '' ?></em></span>
            <span class="ctb-go"><i class="fa-solid fa-arrow-right"></i></span>
          </a>
          <?php if ($kids): ?>
            <div class="ctb-list">
              <?php foreach ($kids as $i => $k): ?>
                <a class="ctb-row<?= $i >= 5 ? ' is-more' : '' ?>" href="<?= hi_category_url(esc($k['slug'], 'url')) ?>" data-n="<?= esc($low($k['name']), 'attr') ?>">
                  <span><?= esc($dec($k['name'])) ?></span><em><?= number_format((int) $k['n']) ?></em><i class="fa-solid fa-chevron-right"></i>
                </a>
              <?php endforeach; ?>
            </div>
            <?php if ($more): ?>
              <button type="button" class="ctb-more" data-more="<?= $more ?>"><span>+ <?= $more ?> more</span> <i class="fa-solid fa-chevron-down"></i></button>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <script>
    (function(){
      var grid=document.getElementById('ctGrid'), find=document.getElementById('ctFind'), none=document.getElementById('ctNone');
      if(!grid) return;
      /* "+ N more" opens the rest of a category's list */
      grid.addEventListener('click',function(e){
        var b=e.target.closest('.ctb-more'); if(!b) return;
        var box=b.closest('.ctb'), open=box.classList.toggle('open');
        b.querySelector('span').textContent = open ? 'Show less' : '+ '+b.dataset.more+' more';
      });
      /* type to filter categories and their sub-types */
      if(find) find.addEventListener('input',function(){
        var q=find.value.trim().toLowerCase(), shown=0;
        grid.querySelectorAll('.ctb').forEach(function(box){
          var rows=box.querySelectorAll('.ctb-row'), head=(box.querySelector('.ctb-t b').textContent||'').toLowerCase(), hit=0;
          if(!q){ box.hidden=false; box.classList.remove('searching'); rows.forEach(function(r){ r.hidden=false; }); shown++; return; }
          var headHit=head.indexOf(q)>-1;
          rows.forEach(function(r){ var ok=headHit||r.dataset.n.indexOf(q)>-1; r.hidden=!ok; if(ok) hit++; });
          box.classList.add('searching'); box.hidden=!(headHit||hit); if(!box.hidden) shown++;
        });
        none.hidden = shown>0; none.querySelector('b').textContent=find.value;
      });
    })();
    </script>
  <?php endif; ?>
</div></section>
<?= $this->endSection() ?>
