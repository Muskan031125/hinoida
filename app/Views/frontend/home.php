<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $img = static fn (array $l): string => hi_cover($l); ?>
<script>try{if('scrollRestoration' in history)history.scrollRestoration='manual';window.scrollTo(0,0);}catch(e){}</script>
<section class="hero">
  <div class="wrap">
    <?php
      /* Headline and strapline come from the live hero_settings blob when our
         own settings are blank, so the site opens with the copy that was
         already written rather than placeholder text. */
      /* The original leads with a short promise and keeps the long
         "Noida Business Directory…" line for the block further down, where it
         has room. Using it as the H1 made the top of the page a paragraph. */
      /* banner_title in the database reads "Hi noida business directory
         listing", which the original does not display either — its hero is a
         short promise and the long directory line belongs to the block below.
         Only an explicitly set hero_title overrides this. */
      $_hero = hi_hero();
      $_h1   = hi_setting('hero_title') ?: 'Find the best of Noida, with details you can trust.';
      $hsub  = hi_setting('hero_subtitle')
             ?: 'Verified local businesses — address, timings, photos and real reviews, all in one place.';
    ?>
    <?php
      /* Property first: one search box with tabs, like the big portals.
         Rent / Buy / Commercial / Plots search the property listings; PG goes
         to the 12 real PGs; "Local places" searches every business. */
      $_tabs = [
        ['rent',   'key',               'Rent',          base_url('property'), 'rent'],
        ['sale',   'house',             'Buy',           base_url('property'), 'sale'],
        ['pg',     'bed',               'PG / Co-living', hi_category_url('pg-hostel-accommodations'), ''],
        ['comm',   'building',          'Commercial',    base_url('property'), 'commercial'],
        ['plots',  'map',               'Plots',         base_url('property'), 'plots'],
        ['places', 'store',             'Local places',  hi_page('search'), ''],
      ];
      $_hs = $heroStats ?? []; $_st = $stays['kinds'] ?? [];
      $_pgN = 0; foreach ($_st as $_k) { if (stripos($_k['name'], 'pg') !== false || stripos($_k['name'], 'hostel') !== false) { $_pgN = (int) $_k['n']; } }
      $_dealers = 0; foreach ($_st as $_k) { if (stripos($_k['name'], 'dealer') !== false) { $_dealers = (int) $_k['n']; } }
    ?>
    <h1 class="hp-h1">Find your next home in Noida</h1>

    <div class="hp-search">
      <div class="hp-tabs" role="tablist" aria-label="What are you looking for?">
        <?php foreach ($_tabs as $_i => $_t): ?>
          <button type="button" role="tab" class="hp-tab<?= $_i === 0 ? ' on' : '' ?>" aria-selected="<?= $_i === 0 ? 'true' : 'false' ?>"
                  data-action="<?= esc($_t[3], 'attr') ?>" data-for="<?= esc($_t[4], 'attr') ?>" data-key="<?= $_t[0] ?>"><i class="fa-solid fa-<?= $_t[1] ?>"></i> <?= esc($_t[2]) ?></button>
        <?php endforeach; ?>
        <span class="hp-ink" id="hpInk" aria-hidden="true"></span>
      </div>
      <?php /* suggestions appear in #hsDrop while typing — see assets/js/hisearch.js */ ?>
      <div class="hs-wrap" id="hpWrap" data-mode="prop">
      <form class="searchbar hp-form" id="hpForm" method="get" action="<?= base_url('property') ?>" data-hisearch="<?= base_url('ajax/search-index') ?>">
        <input type="hidden" name="for" id="hpFor" value="rent">
        <i class="fa-solid fa-location-dot hp-pin"></i>
        <input type="text" name="q" id="hpQ" autocomplete="off" placeholder="Search sector, society or project…">
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
      </form>
      <div class="hs-drop" id="hsDrop" role="listbox" hidden></div>
      </div>

      <?php
        /* Once a tab is picked, everything under the search box follows it —
           quick picks for that kind of property, and the localities open the
           same search in that sector. */
        $_locs = ['Sector 62', 'Sector 75', 'Sector 137', 'Sector 150', 'Sector 18', 'Sector 76'];
      ?>
      <div class="hp-below" id="hpBelow">
        <div class="hero-pop hp-locs" id="hpLocs">
          <span class="hero-pop-l"><i class="fa-solid fa-location-dot"></i> Popular localities:</span>
          <?php foreach ($_locs as $_l): $_slug = strtolower(str_replace(' ', '-', $_l)); ?>
            <a href="<?= base_url('property') ?>?for=rent&amp;q=<?= rawurlencode($_l) ?>" data-page="<?= base_url($_slug) ?>" data-name="<?= esc($_l, 'attr') ?>"><?= esc($_l) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  <script>
  (function(){
    var form=document.getElementById('hpForm'), forI=document.getElementById('hpFor'), q=document.getElementById('hpQ'), wrap=document.getElementById('hpWrap');
    var hints={rent:'Search sector, society or project…',sale:'Search sector, society or project…',pg:'Try “girls PG in Sector 62”',comm:'Search offices, shops or coworking…',plots:'Search sector or area…',places:'Search hospitals, restaurants, salons…'};
    // one blue line that glides to the chosen tab
    var ink=document.getElementById('hpInk'), bar=ink.parentNode;
    function slide(t, instant){ if(!t) return; var pad=t.offsetWidth>90?14:10;
      if(instant) ink.style.transition='none';
      ink.style.width=(t.offsetWidth-pad*2)+'px'; ink.style.transform='translateX('+(t.offsetLeft+pad)+'px)';
      if(instant){ void ink.offsetWidth; ink.style.transition=''; }
      // keep the chosen tab in view on phones, where the row scrolls
      var l=t.offsetLeft-bar.scrollLeft, r=l+t.offsetWidth; if(l<0||r>bar.clientWidth) bar.scrollTo({left:t.offsetLeft-24,behavior:'smooth'}); }
    slide(document.querySelector('.hp-tab.on'), true);
    window.addEventListener('resize',function(){ slide(document.querySelector('.hp-tab.on'), true); });
    if(document.fonts&&document.fonts.ready) document.fonts.ready.then(function(){ slide(document.querySelector('.hp-tab.on'), true); });
    document.querySelectorAll('.hp-tab').forEach(function(t){ t.addEventListener('click',function(){
      document.querySelectorAll('.hp-tab').forEach(function(x){ var on=x===t; x.classList.toggle('on',on); x.setAttribute('aria-selected',on?'true':'false'); });
      form.setAttribute('action',t.dataset.action);
      if(t.dataset.for){ forI.value=t.dataset.for; forI.disabled=false; } else { forI.disabled=true; }
      // the smart suggestions list places, so they show only for PG and Local places
      wrap.dataset.mode=(t.dataset.key==='pg'||t.dataset.key==='places')?'places':'prop';
      slide(t);
      document.querySelectorAll('#mtSec .mt-panel').forEach(function(p){ var on=p.dataset.for===t.dataset.key; if(on&&p.hidden){ p.hidden=false; p.classList.remove('mt-in'); void p.offsetWidth; p.classList.add('mt-in'); } else if(!on){ p.hidden=true; } });
      var k=t.dataset.key;
      document.querySelectorAll('#hpLocs a').forEach(function(a){
        a.href = t.dataset.for ? '<?= base_url('property') ?>?for='+encodeURIComponent(t.dataset.for)+'&q='+encodeURIComponent(a.dataset.name) : a.dataset.page; });
      q.placeholder=hints[t.dataset.key]||q.placeholder; q.focus({preventScroll:true});
    }); });
  })();
  </script>
  </div>
</section>

<?php
  /* Stay & work: the Property family first on the page — PGs, coworking,
     commercial space and dealers — with "moving in?" help underneath. */
  $_st = $stays['kinds'] ?? [];
  // shorter, friendlier names for the four kinds
  $_kNm = static function (string $n): string { foreach (['pg'=>'PG & Hostels','hostel'=>'PG & Hostels','cowork'=>'Coworking','commercial'=>'Commercial Spaces','dealer'=>'Property Dealers'] as $k => $v) { if (stripos($n, $k) !== false) return $v; } return $n; };
  $_kIc = static function (string $n): string { foreach (['pg'=>'bed','hostel'=>'bed','cowork'=>'laptop','commercial'=>'building','dealer'=>'handshake'] as $k => $v) { if (stripos($n, $k) !== false) return $v; } return 'house'; };
?>
<?php
  /* "Find your space": four tabs, in this order —
     PG & Hostels · Residential (Buy / Rent inside) · Commercial Spaces · Property Dealers.
     Coworking counts as commercial space, so it sits under Commercial. */
  $_byKind = [];
  foreach ($_st as $_t) {
      $_n = strtolower($_t['name']);
      $_key = (str_contains($_n, 'pg') || str_contains($_n, 'hostel')) ? 'pg'
            : ((str_contains($_n, 'commercial') || str_contains($_n, 'cowork')) ? 'com'
            : (str_contains($_n, 'dealer') ? 'dealer' : null));
      if ($_key === null) { continue; }
      if (! isset($_byKind[$_key])) { $_byKind[$_key] = ['rows' => [], 'n' => 0, 'slug' => $_t['slug']]; }
      $_byKind[$_key]['n'] += (int) $_t['n'];
      foreach ($_t['rows'] as $_r) { $_byKind[$_key]['rows'][(int) $_r['id']] = $_r; }
      if ($_key === 'com' && str_contains($_n, 'commercial')) { $_byKind[$_key]['slug'] = $_t['slug']; }
  }
  // homes for sale / for rent come from the property records (flats, villas, builder floors)
  $_homes = ['sale' => [], 'rent' => []];
  $_hdb = \Config\Database::connect();
  if ($_hdb->tableExists('listing_property')) {
      foreach (['sale', 'rent'] as $_deal) {
          $_hb = $_hdb->table('listing li')->select('li.*, l.name AS locality_name')
              ->join('locality l', 'l.id = li.locality_id', 'left')
              ->join('listing_property lp', 'lp.listing_id = li.id')
              ->where('li.status', 'active')->where('lp.deal', $_deal)
              ->whereIn('lp.kind', ['flat', 'villa', 'floor'])
              ->orderBy('li.is_featured', 'DESC')->orderBy('li.id', 'DESC')->limit(6);
          $_homes[$_deal] = $_hb->get()->getResultArray();
      }
  }
  $_tabs = [
      ['pg',     'PG & Hostels',      'bed'],
      ['home',   'Residential',       'house'],
      ['com',    'Commercial Spaces', 'building'],
      ['dealer', 'Property Dealers',  'handshake'],
  ];
?>
<?php
  /* ---- Right under the search: listings for the tab picked in the hero.
     Pick Buy and the homes for sale show first; Rent shows homes for rent, and
     so on. Switching tabs swaps the cards without reloading. ---- */
  helper('property');
  $_more = ['commercial' => [], 'plots' => []];
  if ($_hdb->tableExists('listing_property')) {
      foreach (['commercial' => ['office', 'shop', 'warehouse', 'coworking'], 'plots' => ['plot']] as $_mk => $_kinds) {
          $_more[$_mk] = $_hdb->table('listing li')->select('li.*, l.name AS locality_name')
              ->join('locality l', 'l.id = li.locality_id', 'left')
              ->join('listing_property lp', 'lp.listing_id = li.id')
              ->where('li.status', 'active')->whereIn('lp.kind', $_kinds)
              ->orderBy('li.is_featured', 'DESC')->orderBy('li.id', 'DESC')->limit(6)->get()->getResultArray();
      }
  }
  $_comRows = array_slice(array_merge($_more['commercial'], array_values($_byKind['com']['rows'] ?? [])), 0, 6);
  $_placeRows = array_slice(array_values(array_filter($trending ?? [], static fn ($r) => ! hi_is_property($r))), 0, 6);
  $_match = [
      'rent'   => ['Homes for rent in Noida',   'Flats, villas and builder floors you can rent', $_homes['rent'] ?? [], base_url('property') . '?for=rent',  'See all homes for rent'],
      'sale'   => ['Homes for sale in Noida',   'Flats, villas and builder floors you can buy',  $_homes['sale'] ?? [], base_url('property') . '?for=sale',  'See all homes for sale'],
      'pg'     => ['PGs & hostels in Noida',    'Beds and rooms for students and working people', array_slice(array_values($_byKind['pg']['rows'] ?? []), 0, 6), hi_category_url('pg-hostel-accommodations'), 'See all PGs & hostels'],
      'comm'   => ['Commercial spaces in Noida','Offices, shops and coworking',                  $_comRows, base_url('property') . '?for=commercial', 'See all commercial'],
      'plots'  => ['Plots & land in Noida',     'Residential and commercial plots',              $_more['plots'], base_url('property') . '?for=plots', 'See all plots'],
      'places' => ['Popular local places',      'What Noida is looking at this month',            $_placeRows, hi_page('search'), 'Browse all local places'],
  ];
?>


<section class="sec mt-sec" id="mtSec">
  <div class="wrap">
    <?php foreach ($_match as $_k => [$_h, $_p, $_rows, $_href, $_lbl]): ?>
      <div class="mt-panel" data-for="<?= $_k ?>"<?= $_k === 'rent' ? '' : ' hidden' ?>>
        <div class="sec-head mt-head"><div><h2><?= esc($_h) ?></h2><p><?= esc($_p) ?></p></div>
          <a class="mt-all" href="<?= $_href ?>"><?= esc($_lbl) ?> <i class="fa-solid fa-arrow-right"></i></a></div>
        <?php if ($_rows): ?>
          <div class="lgrid mscroll">
            <?php foreach ($_rows as $l): ?><?= view('frontend/_card', ['l' => $l, 'badge' => null], ['saveData' => false]) ?><?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="mt-none"><i class="fa-solid fa-house-circle-exclamation"></i>
            <span>Nothing listed here yet. <a href="<?= base_url('list') ?>">Post one free</a> or <a href="<?= $_href ?>">look again later</a>.</span></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<style>
.mt-sec{padding-top:44px!important}
.np-subs{display:flex;flex-wrap:wrap;gap:8px;margin:-4px 0 18px}
.np-sub{display:inline-flex;align-items:center;gap:7px;height:36px;padding:0 14px;border-radius:999px;border:1px solid #e7e2d8;background:#fff;
  font:inherit;font-size:13.5px;font-weight:600;color:#33405a;cursor:pointer;transition:all .2s}
.np-sub i{color:#014dda;font-size:12px}.np-sub em{font-style:normal;font-size:11.5px;font-weight:700;color:#7c8aa3;background:#f3f5f9;border-radius:999px;padding:1px 7px}
.np-sub:hover{border-color:#bcd0f5}
.np-sub.on{background:#eaf1ff;border-color:#014dda;color:#014dda}
.np-grid[hidden],.np-more[hidden]{display:none!important}
@media(max-width:600px){.np-subs{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none;margin-right:-16px;padding-right:16px}.np-sub{flex:none}}
.mt-head{display:flex!important;align-items:flex-end;justify-content:space-between;gap:16px;text-align:left!important}
.mt-head h2{margin:0}.mt-head h2::after{display:none!important}.mt-head p{margin:4px 0 0}
.mt-all{flex:none;font-weight:700;font-size:14px;color:#014dda;text-decoration:none;white-space:nowrap}
.mt-all i{font-size:12px;transition:transform .2s}.mt-all:hover i{transform:translateX(3px)}
.mt-panel[hidden]{display:none!important}
.mt-panel.mt-in{animation:mtIn .3s ease}
@keyframes mtIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.mt-none{display:flex;align-items:center;gap:12px;padding:20px;border:1px dashed #d9e0eb;border-radius:16px;background:#fff;color:#5f6b82;font-size:14.5px}
.mt-none i{font-size:22px;color:#9fb0cc}.mt-none a{color:#014dda;font-weight:700}
@media(max-width:560px){.mt-head{flex-direction:column;align-items:flex-start;gap:6px}}
</style>

<?php
  /* ---- Strip: what homes cost here + a quick EMI check (breaks up the card rows) ---- */
  helper('property');
  $_gRent = hi_price_guide('rent', 'home');
  $_gSale = hi_price_guide('sale', 'home');
  /* the EMI slider follows real prices: it starts at the average home price
     and runs from the cheapest to the dearest home for sale on HiNoida */
  $_pr = ['lo' => 2500000, 'hi' => 50000000, 'avg' => 10000000];
  try {
      $_r = \Config\Database::connect()->table('listing_property lp')->select('MIN(lp.price) lo, MAX(lp.price) hi, AVG(lp.price) av', false)
          ->join('listing li', 'li.id = lp.listing_id')->where('li.status', 'active')->where('lp.deal', 'sale')->where('lp.price >', 0)->get()->getRowArray();
      if (! empty($_r['av'])) {
          $_step = 500000;
          $_pr['lo']  = max(1000000, (int) (floor(((float) $_r['lo']) * 0.5 / $_step) * $_step));
          $_pr['hi']  = max($_pr['lo'] + 5000000, (int) (ceil(((float) $_r['hi']) * 1.5 / $_step) * $_step));
          $_pr['avg'] = (int) (round(((float) $_r['av']) / $_step) * $_step);
      }
  } catch (\Throwable $e) {}
?>

<?php if (! empty($localities)): ?>
<section class="sec loc-sec">
  <div class="wrap">
    <div class="sec-head"><h2>Popular localities</h2><p>The busiest sectors on HiNoida</p></div>
    <div class="lgrid loc-tiles">
      <?php foreach ($localities as $_i => $_lo): ?>
        <?php $_c = ! empty($_lo['cover']) ? hi_cover($_lo['cover']) : ''; $_hn = (int) $_lo['homes']; ?>
        <a class="lcard rich loc-tile" href="<?= hi_locality_url($_lo['slug']) ?>">
          <span class="ph"><?php if ($_c !== ''): ?><img src="<?= esc($_c) ?>" alt="<?= esc(ucwords($_lo['name'])) ?>" loading="lazy"><?php endif; ?>
            <span class="lc-ic" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
          </span>
          <div class="bd">
            <h3><?= esc(ucwords($_lo['name'])) ?></h3>
            <span class="lc-cat"><?= number_format((int) $_lo['n']) ?> places<?= $_hn ? ' · ' . $_hn . ($_hn === 1 ? ' property' : ' properties') : '' ?></span>
            <span class="lc-explore">Explore <i class="fa-solid fa-arrow-right"></i></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php /* Featured, then popular, then new — the order the original uses, so
         the page reads as a shortlist before it becomes a browse. */ ?>
<?php
  /* Featured listings: property only (PG, flats, rentals, dealers…). Only
     listings really marked Featured; if there are none, the section is left out. */
  $_feat = [];
  $_fr = \Config\Database::connect()->table('listing li')
      ->select('li.*, l.name AS locality_name')->join('locality l', 'l.id = li.locality_id', 'left')
      ->where('li.status', 'active')->where('li.is_featured', 1)
      ->orderBy('li.id', 'DESC')->limit(200)->get()->getResultArray();
  foreach ($_fr as $_r) { if (hi_shows_nearby($_r)) { $_feat[] = $_r; } if (count($_feat) >= 8) { break; } }
  if ($_feat) { $_feat = (new \App\Models\ListingModel())->attachRatings($_feat); }
?>
<?php if ($_feat): ?>
<section class="sec">
  <div class="wrap">
    <div class="sec-head"><h2><?= esc(hi_c('home.featured_listings', 'Featured listings')) ?></h2>
      <p><?= esc(hi_c('home.p_businesses_that_have_paid_to_stand_out', 'Businesses that have paid to stand out')) ?></p></div>
    <div class="lgrid mscroll lg-simple">
      <?php foreach ($_feat as $l): ?>
        <?= view('frontend/_card', ['l' => $l, 'badge' => null, 'simple' => true], ['saveData' => false]) ?>
      <?php endforeach; ?>
    </div>
    <div class="secmore"><a class="seeall" href="<?= hi_page('search') . '?sort=featured' ?>">See all featured businesses <i class="fa-solid fa-arrow-right"></i></a></div>
  </div>
</section>
<?php endif; ?>



<?php /* ---- List your property: the owner's way in ---- */ ?>
<section class="sec lp-band-sec">
  <div class="wrap">
    <div class="lp-band">
      <div class="lp-band-txt">
        <h2>Have a flat, PG or shop in Noida?</h2>
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
  </div>
</section>


<section class="sec st-sec">
  <div class="wrap">
    <div class="sec-head"><h2>Find your space in Noida</h2></div>
    <?php
      /* four cards, one per kind of space; each opens that section.
         The photo is the first listing of that kind, else a stock property photo. */
      $_pool = hi_local_pool('realestate');
      $_photo = static function (array $rows, int $i) use ($_pool): string {
          $r = array_values($rows)[0] ?? null;
          if ($r) { return hi_cover($r); }
          return $_pool ? base_url($_pool[$i % count($_pool)]) : '';
      };
      $_homesN = count($_homes['sale']) + count($_homes['rent']);
    ?>
    <div class="st-cards">
      <?php foreach ($_tabs as $_k => [$_key, $_lbl, $_ic]): ?>
        <?php
          $_t   = $_byKind[$_key] ?? ['rows' => [], 'n' => 0, 'slug' => ''];
          $_n   = $_key === 'home' ? $_homesN : (int) $_t['n'];
          $_img = $_key === 'home' ? $_photo(array_merge($_homes['sale'], $_homes['rent']), 1) : $_photo($_t['rows'], $_k);
          $_bg  = "linear-gradient(to top,rgba(6,14,30,.9) 0%,rgba(6,14,30,.45) 50%,rgba(6,14,30,.1) 100%)" . ($_img !== '' ? ",url('" . $_img . "')" : '');
          $_sub = ['pg' => 'Beds and rooms, boys and girls', 'home' => 'Flats, villas and builder floors',
                   'com' => 'Offices, shops and coworking', 'dealer' => 'Agents who know each sector'][$_key];
        ?>
        <?php if ($_key === 'home'): ?>
          <div class="st-card">
            <span class="st-ph" style="background-image:<?= esc($_bg, 'attr') ?>"></span>
            <span class="st-ic"><i class="fa-solid fa-<?= $_ic ?>"></i></span>
            <span class="st-bd">
              <b><?= esc($_lbl) ?></b>
              <small><?= esc($_sub) ?></small>
              <span class="st-btns">
                <a href="<?= base_url('property') ?>?deal=sale"><i class="fa-solid fa-key"></i> Buy</a>
                <a href="<?= base_url('property') ?>?deal=rent"><i class="fa-solid fa-house-chimney"></i> Rent</a>
              </span>
            </span>
          </div>
        <?php else: ?>
          <a class="st-card" href="<?= $_t['slug'] !== '' ? hi_category_url(esc($_t['slug'], 'url')) : base_url('property') ?>">
            <span class="st-ph" style="background-image:<?= esc($_bg, 'attr') ?>"></span>
            <span class="st-ic"><i class="fa-solid fa-<?= $_ic ?>"></i></span>
            <span class="st-bd">
              <b><?= esc($_lbl) ?></b>
              <small><?= esc($_sub) ?><?= $_n ? ' · ' . $_n . ' listed' : '' ?></small>
              <span class="st-go">Explore <i class="fa-solid fa-arrow-right"></i></span>
            </span>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<style>
.st-sec .sec-head h2::after{display:none!important}
.st-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
.st-card{position:relative;display:flex;flex-direction:column;justify-content:flex-end;height:260px;border-radius:20px;overflow:hidden;
  padding:20px;color:#fff;text-decoration:none;background:#0d1830;isolation:isolate;
  box-shadow:0 2px 4px rgba(16,26,44,.06),0 18px 34px -22px rgba(23,65,166,.55);transition:transform .25s,box-shadow .25s}
.st-card:hover{transform:translateY(-5px);box-shadow:0 2px 4px rgba(16,26,44,.06),0 28px 44px -22px rgba(23,65,166,.6)}
.st-ph{position:absolute;inset:0;z-index:-1;background-size:cover;background-position:center;transition:transform .5s}
.st-card:hover .st-ph{transform:scale(1.06)}
.st-ic{position:absolute;top:18px;left:18px;width:46px;height:46px;border-radius:14px;background:#fff;color:#1741a6;
  display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 6px 16px rgba(0,0,0,.18)}
.st-bd{display:flex;flex-direction:column;gap:4px}
.st-bd b{font-size:20px;font-weight:800;letter-spacing:-.01em;line-height:1.2}
.st-bd small{font-size:13px;color:rgba(255,255,255,.82)}
.st-go{margin-top:8px;font-size:13.5px;font-weight:700;color:#fff;display:inline-flex;align-items:center;gap:6px}
.st-go i{font-size:12px;transition:transform .2s}
.st-card:hover .st-go i{transform:translateX(3px)}
.st-btns{display:flex;gap:8px;margin-top:10px}
.st-btns a{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 10px;border-radius:10px;
  background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);color:#fff;font-weight:700;font-size:13.5px;text-decoration:none;
  backdrop-filter:blur(6px);transition:background .2s,color .2s}
.st-btns a:hover{background:#fff;color:#1741a6}
@media(max-width:1000px){.st-cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}}
@media(max-width:560px){.st-cards{gap:10px}.st-card{height:210px;padding:14px;border-radius:16px}.st-ic{top:12px;left:12px;width:38px;height:38px;font-size:15px}
  .st-bd b{font-size:16px}.st-bd small{font-size:11.5px}.st-btns{gap:6px}.st-btns a{padding:7px 6px;font-size:12.5px}}
</style>


<?php if (! empty($moveIn)): ?>
<section class="sec mv-sec">
  <div class="wrap">
    <div class="sec-head"><h2>Moving in? Get help</h2><p>Packers, interiors, furniture and repairs near you</p></div>
    <div class="mv-row">
      <?php
        /* always end with "all home services", and keep rows full (6 a row) */
        $_mv = array_slice($moveIn, 0, 11);
        $_tot = count($_mv) + 1;
        if ($_tot > 6 && $_tot % 6) { $_mv = array_slice($_mv, 0, $_tot - $_tot % 6 - 1); }
        $_mv[] = ['name' => 'All home services', 'slug' => 'home-services', 'n' => 0, 'icon' => 'arrow-right'];
      ?>
      <?php foreach ($_mv as $_m): ?>
        <a class="mv-tile" href="<?= hi_category_url(esc($_m['slug'], 'url')) ?>">
          <span class="mv-ic"><i class="fa-solid fa-<?= esc($_m['icon']) ?>"></i></span>
          <span><b><?= esc($_m['name']) ?></b><?php if ($_m['n']): ?><small><?= number_format($_m['n']) ?> listed</small><?php endif; ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (! empty($trending)): ?>
<section class="sec">
  <div class="wrap">
    <div class="sec-head"><h2><?= esc(hi_c('home.popular_listings', 'Popular listings')) ?></h2>
      <p><?= esc(hi_c('home.p_the_places_noida_has_looked_at_most_this_mon', 'The places Noida has looked at most this month')) ?></p></div>
    <?php
      $_tabs = $trendingByCat ?? [];
      /* Property is the tab that opens first; "All" comes next */
      $_pk = null;
      foreach ($_tabs as $_k => $_t) { if (stripos((string) $_t['name'], 'propert') === 0 && ! empty($_t['rows'])) { $_pk = $_k; break; } }
      $_order = $_pk !== null ? array_merge([$_pk], array_values(array_diff(array_keys($_tabs), [$_pk]))) : array_keys($_tabs);
    ?>
    <?php /* property only: when there are popular homes, no category tabs */ ?>
    <?php if ($_tabs && $_pk === null): ?>
      <div class="ptabs" role="tablist" aria-label="Popular listings by category">
        <?php if ($_pk !== null): ?>
          <button type="button" class="ptab on" role="tab" aria-selected="true" data-pt="c<?= $_pk ?>"><i class="fa-solid fa-house"></i> <?= esc($_tabs[$_pk]['name']) ?></button>
        <?php endif; ?>
        <button type="button" class="ptab<?= $_pk === null ? ' on' : '' ?>" role="tab" aria-selected="<?= $_pk === null ? 'true' : 'false' ?>" data-pt="all"><i class="fa-solid fa-fire"></i> All</button>
        <?php foreach ($_order as $_k): if ($_k === $_pk) { continue; } $_t = $_tabs[$_k]; ?>
          <button type="button" class="ptab" role="tab" aria-selected="false" data-pt="c<?= $_k ?>"><?= esc($_t['name']) ?></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php
      /* property leads: the most-viewed property listing goes first in "All" */
      $_allPop = $trending;
      foreach ($_tabs as $_t) {
        if (stripos((string) $_t['name'], 'propert') !== 0 || empty($_t['rows'])) { continue; }
        $_ids = array_map(static fn ($x) => (int) $x['id'], $_allPop);
        $_p = $_t['rows'][0];
        $_allPop = array_values(array_filter($_allPop, static fn ($x) => (int) $x['id'] !== (int) $_p['id']));
        array_unshift($_allPop, $_p);
        $_allPop = array_slice($_allPop, 0, max(6, count($trending)));
        break;
      }
    ?>
    <?php
      /* "All" should never be a half-empty row: top it up from the category tabs */
      if (count($_allPop) < 6) {
          $_seen = array_flip(array_map(static fn ($x) => (int) $x['id'], $_allPop));
          foreach ($_tabs as $_t) { foreach (($_t['rows'] ?? []) as $_r) {
              if (count($_allPop) >= 6) { break 2; }
              if (! isset($_seen[(int) $_r['id']])) { $_allPop[] = $_r; $_seen[(int) $_r['id']] = 1; }
          } }
      }
    ?>
    <div class="lgrid mscroll ptpanel" data-pt="all"<?= $_pk !== null ? ' hidden' : '' ?>>
      <?php foreach ($_allPop as $l): ?>
        <?= view('frontend/_card', ['l' => $l, 'badge' => number_format((int) ($l['_views'] ?? 0)) . ' views']) ?>
      <?php endforeach; ?>
    </div>
    <?php foreach ($_tabs as $_k => $_t): ?>
      <div class="lgrid mscroll ptpanel" data-pt="c<?= $_k ?>"<?= $_k === $_pk ? '' : ' hidden' ?>>
        <?php foreach ($_t['rows'] as $l): ?>
          <?= view('frontend/_card', ['l' => $l, 'badge' => number_format((int) ($l['_views'] ?? 0)) . ' views']) ?>
        <?php endforeach; ?>
      </div>
      <p class="ptmore" data-pt="c<?= $_k ?>"<?= $_k === $_pk ? '' : ' hidden' ?>><a href="<?= hi_category_url(esc($_t['slug'], 'url')) ?>">See all in <?= esc($_t['name']) ?> <i class="fa-solid fa-arrow-right"></i></a></p>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (! empty($newest)): ?>
<section class="sec">
  <div class="wrap">
    <div class="sec-head"><h2><?= esc(hi_c('home.new_on_hinoida', 'New on HiNoida')) ?></h2>
      <p><?= esc(hi_c('home.p_the_most_recent_businesses_to_join', 'The most recent businesses to join')) ?></p></div>
    <?php
      $_ntabs = array_values(array_filter($newestByCat ?? [], static fn ($t) => stripos((string) $t['name'], 'propert') !== 0));
      /* "Property" opens first: newest homes, PGs, coworking, commercial
         spaces and dealers, with a small filter for each kind */
      $_psubs = [];
      $_hm = array_merge($_homes['rent'] ?? [], $_homes['sale'] ?? []);
      usort($_hm, static fn ($a, $b) => (int) $b['id'] <=> (int) $a['id']);
      if ($_hm) { $_psubs[] = ['Homes', 'house', count($_hm), array_slice($_hm, 0, 6), base_url('property')]; }
      foreach (($stays['kinds'] ?? []) as $_t) {
        $_nm = $_t['name']; $_ic = 'building';
        foreach (['pg' => ['PG & Hostels', 'bed'], 'hostel' => ['PG & Hostels', 'bed'], 'cowork' => ['Coworking', 'laptop'], 'commercial' => ['Commercial', 'building'], 'dealer' => ['Dealers', 'handshake']] as $_kk => [$_lb, $_ii]) {
          if (stripos($_nm, $_kk) !== false) { $_nm = $_lb; $_ic = $_ii; break; }
        }
        $_psubs[] = [$_nm, $_ic, (int) $_t['n'], $_t['rows'], hi_category_url($_t['slug'])];
      }
      $_pall = [];
      foreach ($_psubs as $_ps) { foreach ($_ps[3] as $_r) { $_pall[(int) $_r['id']] = $_r; } }
      krsort($_pall); $_pall = array_slice(array_values($_pall), 0, 6);
    ?>
    <?php /* property only here: no category tabs while there are new property listings */ ?>
    <?php if ($_ntabs && ! $_pall): ?>
      <div class="ptabs" role="tablist" aria-label="New listings by category">
        <?php if ($_pall): ?><button type="button" class="ptab on" role="tab" aria-selected="true" data-pt="nprop"><i class="fa-solid fa-house"></i> Property</button><?php endif; ?>
        <button type="button" class="ptab<?= $_pall ? '' : ' on' ?>" role="tab" aria-selected="<?= $_pall ? 'false' : 'true' ?>" data-pt="all"><i class="fa-solid fa-wand-magic-sparkles"></i> All</button>
        <?php foreach ($_ntabs as $_k => $_t): ?>
          <button type="button" class="ptab" role="tab" aria-selected="false" data-pt="n<?= $_k ?>"><?= esc($_t['name']) ?></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($_pall): ?>
      <div class="ptpanel np-wrap" data-pt="nprop">
        <div class="np-subs" role="group" aria-label="Kind of property">
          <button type="button" class="np-sub on" data-sub="all"><i class="fa-solid fa-border-all"></i> All property</button>
          <?php foreach ($_psubs as $_i => [$_lb, $_ic, $_n]): ?>
            <button type="button" class="np-sub" data-sub="s<?= $_i ?>"><i class="fa-solid fa-<?= esc($_ic) ?>"></i> <?= esc($_lb) ?> <em><?= $_n ?></em></button>
          <?php endforeach; ?>
        </div>
        <div class="lgrid mscroll np-grid" data-sub="all">
          <?php foreach ($_pall as $l): ?><?= view('frontend/_card', ['l' => $l, 'badge' => null]) ?><?php endforeach; ?>
        </div>
        <?php foreach ($_psubs as $_i => [$_lb, $_ic, $_n, $_rows, $_href]): ?>
          <div class="lgrid mscroll np-grid" data-sub="s<?= $_i ?>" hidden>
            <?php foreach ($_rows as $l): ?><?= view('frontend/_card', ['l' => $l, 'badge' => null]) ?><?php endforeach; ?>
          </div>
          <p class="ptmore np-more" data-sub="s<?= $_i ?>" hidden><a href="<?= $_href ?>">See all <?= esc(strtolower($_lb)) ?> <i class="fa-solid fa-arrow-right"></i></a></p>
        <?php endforeach; ?>
      </div>
      <script>
      (function(){ var w=document.currentScript.previousElementSibling;
        w.querySelectorAll('.np-sub').forEach(function(b){ b.addEventListener('click',function(){
          var k=b.dataset.sub; w.querySelectorAll('.np-sub').forEach(function(x){ x.classList.toggle('on',x===b); });
          w.querySelectorAll('.np-grid,.np-more').forEach(function(g){ g.hidden=g.dataset.sub!==k; }); }); }); })();
      </script>
    <?php endif; ?>
    <?php if (! $_pall): ?>
    <div class="lgrid mscroll ptpanel" data-pt="all"<?= $_pall ? ' hidden' : '' ?>>
      <?php
        /* property leads: the newest property/space listing goes first */
        $_newAll = $newest; $_np = null;
        foreach (($stays['kinds'] ?? []) as $_t) { foreach ($_t['rows'] as $_r) { if (! $_np || (int) $_r['id'] > (int) $_np['id']) { $_np = $_r; } } }
        if ($_np) {
          $_newAll = array_values(array_filter($_newAll, static fn ($x) => (int) $x['id'] !== (int) $_np['id']));
          array_unshift($_newAll, $_np);
          $_newAll = array_slice($_newAll, 0, max(4, count($newest)));
        }
      ?>
      <?php foreach ($_newAll as $l): ?>
        <?= view('frontend/_card', ['l' => $l, 'badge' => null]) ?>
      <?php endforeach; ?>
    </div>
    <?php foreach ($_ntabs as $_k => $_t): ?>
      <div class="lgrid mscroll ptpanel" data-pt="n<?= $_k ?>" hidden>
        <?php foreach ($_t['rows'] as $l): ?>
          <?= view('frontend/_card', ['l' => $l, 'badge' => null]) ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <div class="secmore"><a class="seeall" href="<?= hi_page('search') . '?sort=newest' ?>">See the newest listings <i class="fa-solid fa-arrow-right"></i></a></div>
  </div>
</section>
<?php endif; ?>

<?php /* ---- Strip: didn't find it? leave a number ---- */ ?>
<?php if ($_gRent || $_gSale): ?>
<section class="sec price-sec">
  <div class="wrap">
    <div class="sec-head"><h2>Noida prices on HiNoida</h2></div>
    <div class="pstrip">
      <?php if ($_gRent): ?>
        <a class="ps-it" href="<?= base_url('property') ?>?for=rent"><i class="fa-solid fa-key"></i><span><small>Average rent</small><b>₹<?= esc(hi_inr($_gRent['avg'])) ?><em>/month</em></b></span><span class="ps-go">See homes for rent <i class="fa-solid fa-arrow-right"></i></span></a>
      <?php endif; ?>
      <?php if ($_gSale): ?>
        <a class="ps-it" href="<?= base_url('property') ?>?for=sale"><i class="fa-solid fa-house"></i><span><small>Average home price</small><b>₹<?= esc(hi_inr($_gSale['avg'])) ?></b></span><span class="ps-go">See homes for sale <i class="fa-solid fa-arrow-right"></i></span></a>
      <?php endif; ?>
      <a class="ps-it" href="<?= base_url('city') ?>"><i class="fa-solid fa-map-location-dot"></i><span><small>Prices by sector</small><b>Compare sectors</b></span><span class="ps-go">Explore sectors <i class="fa-solid fa-arrow-right"></i></span></a>
    </div>
  </div>
</section>
<?php endif; ?>



<section class="sec" id="browse">
  <div class="wrap">
    <?php /* The flat grid the original leads with: icon, name, count. Busiest
             categories first, so the page opens with where the listings are. */ ?>
    <?php if (! empty($topCats)): ?>
      <div class="sec-head">
        <h2><?= esc(hi_c('home.explore_categories', 'Explore categories')) ?></h2>
        <p><?= esc(hi_c('home.p_discover_local_businesses_across_every_categ', 'Discover local businesses across every category in Noida')) ?></p>
      </div>
      <?php /* Twelve categories in a four-wide grid — three full rows, so the
               section never ends on a half-empty line. The repository already
               ships 56 category photographs and hi_cat_bg() picks one; the grid
               was drawing flat icon tiles and ignoring every last one of them. */ ?>
      <div class="hcatgrid">
        <?php foreach ($topCats as $c): ?>
          <a class="hcat" href="<?= hi_category_url(esc($c['slug'], 'url')) ?>"
             <?php /* hi_cat_bg() prefers category.thumbnail, and 12 of the 13
                      top-level rows hold clip-art PNGs with transparent
                      backgrounds — as full-bleed tiles they rendered as
                      checkerboards and broken-image glyphs. Dropping the
                      thumbnail lets it fall through to the photographs in
                      assets/img/cat/, which is what they are there for. */ ?>
             >
            <?php /* the photo is its own layer so it can zoom on hover */ ?>
            <span class="hcat-ph" style="background-image:<?= esc(hi_cat_bg(array_diff_key($c, ['thumbnail' => 0])), 'attr') ?>"></span>
            <span class="hcat-ic"><i class="fa-solid fa-<?= esc(hi_cat_icon($c['name'])) ?>"></i></span>
            <span class="hcat-go"><i class="fa-solid fa-arrow-right"></i></span>
            <span class="hcat-bd">
              <?php /* names are stored HTML-encoded ("Hotels &amp; Banquets") — decode before escaping */ ?>
              <b><?= esc(html_entity_decode((string) $c['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b>
              <span class="hcat-n"><?= number_format((int) $c['listing_count']) ?> businesses</span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php
      /* Two category sections competing on one page read as clutter — the
         grid above already answers "what is here". The themed rows now live
         on /categories, which is the page for browsing the whole tree. */
    ?>
    <div style="text-align:center;margin-top:6px">
      <a class="seeall" href="<?= hi_page('categories') ?>">See all <?= number_format($stats['categories']) ?> categories <i class="fa-solid fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<section class="sec emi-sec">
  <div class="wrap">
    <div class="sec-head"><h2>EMI calculator</h2><a class="seeall" href="<?= base_url('property') ?>?for=sale">See homes for sale <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="emi-box" id="hEmi" data-lo="<?= (int) $_pr['lo'] ?>" data-hi="<?= (int) $_pr['hi'] ?>" data-avg="<?= (int) $_pr['avg'] ?>">
      <div class="emi-in">
        <div class="emi-f">
          <label for="hEmiP"><span><i class="fa-solid fa-house"></i> Home price</span> <b data-o="p"></b></label>
          <input type="range" id="hEmiP" min="<?= (int) $_pr['lo'] ?>" max="<?= (int) $_pr['hi'] ?>" step="100000" value="<?= (int) $_pr['avg'] ?>" autocomplete="off">
          <div class="emi-ends"><span data-o="lo"></span><em data-o="avg"></em><span data-o="hi"></span></div>
        </div>
        <div class="emi-f">
          <label for="hEmiD"><span><i class="fa-solid fa-wallet"></i> Down payment</span> <b data-o="d"></b></label>
          <input type="range" id="hEmiD" min="10" max="50" step="5" value="20" autocomplete="off">
          <div class="emi-ends"><span>10%</span><span>50%</span></div>
        </div>
        <div class="emi-f">
          <label for="hEmiR"><span><i class="fa-solid fa-percent"></i> Interest rate</span> <b data-o="r"></b></label>
          <input type="range" id="hEmiR" min="6" max="14" step="0.05" value="8.5" autocomplete="off">
          <div class="emi-ends"><span>6%</span><span>14%</span></div>
        </div>
        <div class="emi-f">
          <label for="hEmiN"><span><i class="fa-regular fa-calendar"></i> Loan for</span> <b data-o="n"></b></label>
          <input type="range" id="hEmiN" min="5" max="30" step="1" value="20" autocomplete="off">
          <div class="emi-ends"><span>5 years</span><span>30 years</span></div>
        </div>
      </div>
      <div class="emi-out">
        <small>Your monthly EMI</small>
        <strong data-o="emi"></strong>
        <div class="emi-bar"><span data-o="bar"></span></div>
        <div class="emi-legend"><span><i class="k1"></i>Loan</span><span><i class="k2"></i>Interest</span></div>
        <dl>
          <div><dt>Loan amount</dt><dd data-o="loan"></dd></div>
          <div><dt>Total interest</dt><dd data-o="int"></dd></div>
          <div><dt>Total you pay</dt><dd data-o="tot"></dd></div>
        </dl>
        <a class="emi-go" data-o="go" href="<?= base_url('property') ?>?for=sale">See homes in this budget <i class="fa-solid fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</section>
<script>
(function(){ var box=document.getElementById('hEmi'); if(!box) return;
  var P=document.getElementById('hEmiP'), D=document.getElementById('hEmiD'), R=document.getElementById('hEmiR'), N=document.getElementById('hEmiN');
  function o(k){ return box.querySelector('[data-o="'+k+'"]'); }
  function inr(n){ if(n>=1e7) return '₹'+(n/1e7).toFixed(2).replace(/\.?0+$/,'')+' Cr'; if(n>=1e5) return '₹'+(n/1e5).toFixed(1).replace(/\.0$/,'')+' Lakh'; return '₹'+Math.round(n).toLocaleString('en-IN'); }
  function fill(r){ r.style.setProperty('--p',((r.value-r.min)/(r.max-r.min)*100)+'%'); }
  function go(){
    var price=+P.value, dp=+D.value, rate=+R.value, yrs=+N.value, loan=price*(1-dp/100), m=rate/1200, n=yrs*12;
    var emi=m? loan*m*Math.pow(1+m,n)/(Math.pow(1+m,n)-1) : loan/n, tot=emi*n, intr=tot-loan;
    o('p').textContent=inr(price); o('d').textContent=dp+'% · '+inr(price*dp/100); o('r').textContent=rate.toFixed(2).replace(/0$/,'')+'%'; o('n').textContent=yrs+' years';
    o('emi').textContent='₹'+Math.round(emi).toLocaleString('en-IN'); o('loan').textContent=inr(loan); o('int').textContent=inr(intr); o('tot').textContent=inr(tot);
    o('bar').style.width=(loan/tot*100)+'%';
    o('avg').textContent=Math.abs(price-(+box.dataset.avg))<150000?'average home on HiNoida':'';
    o('go').href=o('go').href.split('?')[0]+'?for=sale&budget=0-'+Math.round(price*1.1);
    [P,D,R,N].forEach(fill);
  }
  o('lo').textContent=inr(+P.min); o('hi').textContent=inr(+P.max);
  [P,D,R,N].forEach(function(r){ r.addEventListener('input',go); });
  box.querySelectorAll('.emi-chip').forEach(function(c){ c.addEventListener('click',function(){ P.value=c.dataset.v; go(); }); });
  var _go=go; go=function(){ _go(); box.querySelectorAll('.emi-chip').forEach(function(c){ c.classList.toggle('on', +c.dataset.v===+P.value); }); };
  [P].forEach(function(r){ r.addEventListener('input',go); }); go();
})();
</script>

<?php if (! empty($newsLatest)): ?>
<section class="sec nb-sec"><div class="wrap">
  <?= view('frontend/_newsbrief', ['latest' => $newsLatest, 'cats' => $newsCats ?? [], 'propOnly' => true], ['saveData' => false]) ?>
</div></section>
<?php endif; ?>

<script>
/* category tabs — each section switches only its own cards */
(function(){
  document.querySelectorAll('.ptabs').forEach(function(bar){
    var sec=bar.closest('section'), tabs=bar.querySelectorAll('.ptab');
    tabs.forEach(function(t){ t.addEventListener('click',function(){
      var k=t.dataset.pt;
      tabs.forEach(function(x){ var on=x===t; x.classList.toggle('on',on); x.setAttribute('aria-selected',on?'true':'false'); });
      sec.querySelectorAll('.ptpanel,.ptmore').forEach(function(p){ p.hidden=p.dataset.pt!==k; });
    }); });
  });
})();
</script>

<?php
  /* "How it works" — three icon blocks under the hero.
     Ported from the original home.php, which renders
     hero_settings.topic_one / icon_picker_one / sub_topic_one (×3). */
  // falls back to the live hero_settings blob, which already has all three
  $hboxes = hi_hero_boxes();
?>
<?php if ($hboxes): ?>
<section class="sec howsec">
  <div class="wrap">
    <?php $htitle = hi_setting('hero_boxes_title'); ?>
    <div class="sec-head"><h2><?= esc($htitle !== '' ? $htitle : 'Why people use HiNoida') ?></h2></div>
    <div class="howgrid">
      <?php foreach ($hboxes as $hb): ?>
        <div class="howbox">
          <i class="<?= esc($hb['icon'], 'attr') ?>"></i>
          <h3><?= esc($hb['topic']) ?></h3>
          <?php if ($hb['sub'] !== ''): ?><p><?= esc($hb['sub']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php /* the original closes this block with a single call to action */ ?>
    <div class="howsec-go">
      <a class="freelist" href="<?= base_url('list') ?>"><i class="fa-solid fa-plus"></i> List your property or business free</a>
    </div>
  </div>
</section>
<?php endif; ?>

<style>
/* breathing room: filter rows sit clear of the cards, and the cards clear of each other */
.ptabs{margin-bottom:34px!important}
.nt-chips{margin-bottom:22px!important}
.lgrid.mscroll{gap:24px!important}
.hcatgrid{gap:22px!important}
@media(max-width:760px){.ptabs{margin-bottom:20px!important}.lgrid.mscroll{gap:14px!important}.hcatgrid{gap:12px!important}}
</style>
<script>
/* Category tile photos: if one fails to load (the local dev server sometimes
   drops a few of many requests at once), try it again, up to 3 times. */
(function(){
  document.querySelectorAll('.hcat-ph').forEach(function(ph){
    var m=(ph.getAttribute('style')||'').match(/url\(['"]?([^'")]+)['"]?\)/); if(!m) return;
    var url=m[1], tries=0;
    (function check(){
      var i=new Image();
      i.onerror=function(){ if(++tries>3) return; setTimeout(function(){
        var u=url+(url.indexOf('?')>-1?'&':'?')+'r='+tries;
        ph.style.backgroundImage=ph.style.backgroundImage.replace(/url\([^)]*\)/, 'url("'+u+'")'); url=u; check(); }, 600*tries); };
      i.src=url;
    })();
  });
})();
</script>
<style>
/* search suggestions dropdown */
.hs-wrap{position:relative;z-index:30}
.hs-drop{position:fixed;z-index:1000;background:#fff;border-radius:14px;text-align:left;
  box-shadow:0 18px 40px -12px rgba(10,20,45,.45),0 0 0 1px rgba(10,20,45,.06);padding:6px;max-height:min(420px,60vh);overflow-y:auto}
.hs-it{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:10px;color:#14213d;text-decoration:none}
.hs-it>i{flex:none;width:30px;height:30px;border-radius:9px;background:#eef3fc;color:#1741a6;display:flex;align-items:center;justify-content:center;font-size:13px}
.hs-it:first-child>i.fa-magnifying-glass{background:#1741a6;color:#fff}
.hs-tx{display:flex;flex-direction:column;min-width:0;line-height:1.3}
.hs-tx>span{font-size:14.5px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hs-tx b{font-weight:700}
.hs-tx small{font-size:12px;color:#6b7686;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hs-it:hover,.hs-it.on{background:#f2f6fd}
.hs-none{padding:12px 14px;font-size:14px;color:#6b7686}
@media(max-width:640px){.hs-it{padding:8px 10px}.hs-tx>span{font-size:14px}}
</style>
<script src="<?= base_url('assets/js/hisearch.js') ?>?v=2" defer></script>
<?php /* ---- Pick up where you left off: recently viewed + shortlist (this browser only) ---- */ ?>
<section class="sec mine-sec" id="hiMine" hidden>
  <div class="wrap">
    <div class="sec-head"><h2>Pick up where you left off</h2></div>
    <div class="ptabs" role="tablist" aria-label="Your homes">
      <button type="button" class="ptab on" role="tab" aria-selected="true" data-pt="rv"><i class="fa-solid fa-clock-rotate-left"></i> Recently viewed <em class="ptab-n" id="mineRvN"></em></button>
      <button type="button" class="ptab" role="tab" aria-selected="false" data-pt="sv" id="mineSvTab"><i class="fa-solid fa-heart"></i> Shortlist <em class="ptab-n" id="mineSvN"></em></button>
    </div>
    <div class="mine-row ptpanel" data-pt="rv" id="mineRv"></div>
    <div class="mine-row ptpanel" data-pt="sv" id="mineSv" hidden></div>
  </div>
</section>
<script>
(function(){
  var sec=document.getElementById('hiMine'); if(!sec) return;
  function get(k){ try{ var v=JSON.parse(localStorage.getItem(k)||'[]'); return Array.isArray(v)?v:[]; }catch(e){ return []; } }
  function dec(t){ var a=document.createElement('textarea'); a.innerHTML=t==null?'':String(t); return a.value; }
  function esc(t){ var d=document.createElement('div'); d.textContent=dec(t); return d.innerHTML; }
  function safe(u){ u=String(u||''); return /^(https?:)?\/\//i.test(u)||u.charAt(0)==='/' ? u : ''; }
  function card(x,list){
    var img=safe(x.i||x.img), url=safe(x.u||x.url)||'#', nm=x.n||x.name, sub=x.s||x.loc||'Noida';
    return '<a class="mine-card" href="'+esc(url)+'"><span class="mine-ph"'+(img?' style="background-image:url(\''+esc(img).replace(/'/g,'%27')+'\')"':'')+'></span>'
      +'<span class="mine-bd"><b>'+esc(nm)+'</b><small>'+esc(sub)+'</small></span>'
      +'<span class="mine-x" role="button" tabindex="0" data-list="'+list+'" data-id="'+(+x.id||0)+'" title="Remove" aria-label="Remove '+esc(nm)+'"><i class="fa-solid fa-xmark"></i></span></a>';
  }
  function fill(el,list,empty,key){ el.innerHTML=list.length?list.slice(0,8).map(function(x){ return card(x,key); }).join(''):'<p class="mine-empty">'+empty+'</p>'; }
  function draw(){
    var rv=get('hi_recent'), sv=get('hi_saved'), want=location.hash==='#hiMine';
    fill(document.getElementById('mineRv'),rv,'Places you open will show up here.','hi_recent');
    fill(document.getElementById('mineSv'),sv,'Tap the <i class="fa-regular fa-heart"></i> on any home to save it here.','hi_saved');
    document.getElementById('mineRvN').textContent=rv.length||'';
    document.getElementById('mineSvN').textContent=sv.length||'';
    // everyone who has looked at something sees this; the Shortlist tab only shows once something is saved
    sec.hidden=!(rv.length||sv.length||want);
    document.getElementById('mineSvTab').hidden=!(sv.length||want);
    return {rv:rv.length,sv:sv.length,want:want};
  }
  var s=draw();
  if(s.want) setTimeout(function(){ sec.scrollIntoView({behavior:'smooth',block:'start'}); },60);
  function pick(k){ sec.querySelectorAll('.ptab').forEach(function(b){ var on=b.dataset.pt===k; b.classList.toggle('on',on); b.setAttribute('aria-selected',on?'true':'false'); });
    sec.querySelectorAll('.ptpanel').forEach(function(p){ p.hidden=p.dataset.pt!==k; }); }
  sec.querySelectorAll('.ptab').forEach(function(b){ b.addEventListener('click',function(){ pick(b.dataset.pt); }); });
  if(s.want || (!s.rv && s.sv)) pick('sv');
  document.addEventListener('hi:mine',draw);
  /* the small x removes one place from that list */
  sec.addEventListener('click',function(e){
    var x=e.target.closest&&e.target.closest('.mine-x'); if(!x) return; e.preventDefault(); e.stopPropagation();
    var key=x.dataset.list, id=+x.dataset.id;
    try{ var l=JSON.parse(localStorage.getItem(key)||'[]').filter(function(i){ return +i.id!==id; }); localStorage.setItem(key,JSON.stringify(l)); }catch(err){}
    draw(); if(window.hiSaveMark) window.hiSaveMark(document);
  },true);
  window.addEventListener('hashchange',function(){ var t=draw(); if(t.want){ pick('sv'); sec.scrollIntoView({behavior:'smooth'}); } });
})();
</script>


<script>
/* "See all" links sit as small text in each section heading, not as buttons
   under the cards. The link follows the tab or filter that is showing. */
(function(){
  function sync(sec){
    var head=sec.querySelector('.sec-head'); if(!head) return;
    var src=[].filter.call(sec.querySelectorAll('.ptmore a, .secmore a, a.seeall'), function(a){
      return !a.closest('.sec-head') && !a.closest('[hidden]') && !a.closest('.mt-panel[hidden]');
    })[0];
    var link=head.querySelector('.sec-more-link');
    if(!src){ if(link) link.remove(); return; }
    if(!link){ link=document.createElement('a'); link.className='sec-more-link'; head.appendChild(link); }
    link.href=src.getAttribute('href'); link.innerHTML=src.innerHTML;
    var box=src.closest('.ptmore, .secmore')||src.parentElement; if(box && box!==head) box.classList.add('moved-up');
  }
  var secs=document.querySelectorAll('body.is-home section.sec');
  [].forEach.call(secs, sync);
  document.addEventListener('click', function(e){
    if(e.target.closest('.ptab, .np-sub, .nt-chip')){ setTimeout(function(){ [].forEach.call(secs, sync); }, 30); }
  });
})();
</script>
<?= $this->endSection() ?>
