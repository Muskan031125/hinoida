<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $img = static fn (array $l): string => hi_cover($l); ?>
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
    <h1><?= esc($_h1) ?></h1>
    <?php if ($hsub !== ''): ?><p class="hero-sub"><?= esc($hsub) ?></p><?php endif; ?>
    <form class="searchbar" method="get" action="<?= hi_page('search') ?>">
      <input type="text" name="q" placeholder="<?= esc(hi_setting('hero_placeholder', 'Search hospitals, restaurants, salons, shops...'), 'attr') ?>">
      <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>
    <?php /* quick ideas under the search box — one tap runs the search */
      $_pop = [['bed', 'PG'], ['laptop', 'Coworking'], ['building', 'Property dealers'], ['utensils', 'Restaurants'],
               ['hospital', 'Hospitals'], ['mug-hot', 'Cafes'], ['dumbbell', 'Gyms'], ['school', 'Schools']]; ?>
    <div class="hero-pop">
      <span class="hero-pop-l"><i class="fa-solid fa-fire"></i> Popular:</span>
      <?php foreach ($_pop as $_p): ?>
        <a href="<?= hi_page('search') . '?q=' . urlencode($_p[1]) ?>"><i class="fa-solid fa-<?= $_p[0] ?>"></i> <?= esc($_p[1]) ?></a>
      <?php endforeach; ?>
    </div>
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
<?php if ($_st): ?>
<section class="sec st-sec">
  <div class="wrap">
    <div class="sec-head"><h2>Find your space in Noida</h2>
      <p>PGs, hostels, coworking desks and commercial space — with the dealers who know each sector</p></div>
    <div class="ptabs" role="tablist" aria-label="Spaces by type">
      <?php foreach ($_st as $_k => $_t): ?>
        <button type="button" class="ptab<?= $_k === 0 ? ' on' : '' ?>" role="tab" aria-selected="<?= $_k === 0 ? 'true' : 'false' ?>" data-pt="s<?= $_k ?>"><i class="fa-solid fa-<?= $_kIc($_t['name']) ?>"></i> <?= esc($_kNm($_t['name'])) ?> <em class="ptab-n"><?= (int) $_t['n'] ?></em></button>
      <?php endforeach; ?>
    </div>
    <?php foreach ($_st as $_k => $_t): ?>
      <div class="lgrid mscroll ptpanel" data-pt="s<?= $_k ?>"<?= $_k === 0 ? '' : ' hidden' ?>>
        <?php foreach ($_t['rows'] as $l): ?>
          <?= view('frontend/_card', ['l' => $l, 'badge' => null], ['saveData' => false]) ?>
        <?php endforeach; ?>
      </div>
      <p class="ptmore" data-pt="s<?= $_k ?>"<?= $_k === 0 ? '' : ' hidden' ?>><a href="<?= hi_category_url(esc($_t['slug'], 'url')) ?>">See all <?= (int) $_t['n'] ?> <?= esc($_kNm($_t['name'])) ?> <i class="fa-solid fa-arrow-right"></i></a></p>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (! empty($newsLatest)): ?>
<section class="sec nb-sec"><div class="wrap">
  <?= view('frontend/_newsbrief', ['latest' => $newsLatest, 'cats' => $newsCats ?? []], ['saveData' => false]) ?>
</div></section>
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

<?php /* Featured, then popular, then new — the order the original uses, so
         the page reads as a shortlist before it becomes a browse. */ ?>
<section class="sec" style="background:var(--bg)">
  <div class="wrap">
    <div class="sec-head"><h2><?= esc(hi_c('home.featured_listings', 'Featured listings')) ?></h2>
      <p><?= esc(hi_c('home.p_businesses_that_have_paid_to_stand_out', 'Businesses that have paid to stand out')) ?></p></div>
    <div class="lgrid mscroll lg-simple">
      <?php
        /* property leads here too: a featured property if there is one,
           else the top PG/space, goes first */
        $_feat = $featured; $_pp = null;
        foreach (($stays['kinds'] ?? []) as $_t) { foreach ($_t['rows'] as $_r) { if (! empty($_r['is_featured'])) { $_pp = $_r; break 2; } } }
        if (! $_pp && ! empty($stays['kinds'][0]['rows'][0])) { $_pp = $stays['kinds'][0]['rows'][0]; }
        if ($_pp) {
          $_feat = array_values(array_filter($_feat, static fn ($x) => (int) $x['id'] !== (int) $_pp['id']));
          array_unshift($_feat, $_pp);
          $_feat = array_slice($_feat, 0, max(4, count($featured)));
        }
      ?>
      <?php foreach ($_feat as $l): ?>
        <?= view('frontend/_card', ['l' => $l, 'badge' => null, 'simple' => true], ['saveData' => false]) ?>
      <?php endforeach; ?>
    </div>
    <div class="secmore"><a class="seeall" href="<?= hi_page('search') . '?sort=featured' ?>">See all featured businesses <i class="fa-solid fa-arrow-right"></i></a></div>
  </div>
</section>

<?php if (! empty($trending)): ?>
<section class="sec">
  <div class="wrap">
    <div class="sec-head"><h2><?= esc(hi_c('home.popular_listings', 'Popular listings')) ?></h2>
      <p><?= esc(hi_c('home.p_the_places_noida_has_looked_at_most_this_mon', 'The places Noida has looked at most this month')) ?></p></div>
    <?php $_tabs = $trendingByCat ?? []; ?>
    <?php if ($_tabs): ?>
      <div class="ptabs" role="tablist" aria-label="Popular listings by category">
        <button type="button" class="ptab on" role="tab" aria-selected="true" data-pt="all"><i class="fa-solid fa-fire"></i> All</button>
        <?php foreach ($_tabs as $_k => $_t): ?>
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
        $_allPop = array_slice($_allPop, 0, max(4, count($trending)));
        break;
      }
    ?>
    <div class="lgrid mscroll ptpanel" data-pt="all">
      <?php foreach ($_allPop as $l): ?>
        <?= view('frontend/_card', ['l' => $l, 'badge' => number_format((int) ($l['_views'] ?? 0)) . ' views']) ?>
      <?php endforeach; ?>
    </div>
    <?php foreach ($_tabs as $_k => $_t): ?>
      <div class="lgrid mscroll ptpanel" data-pt="c<?= $_k ?>" hidden>
        <?php foreach ($_t['rows'] as $l): ?>
          <?= view('frontend/_card', ['l' => $l, 'badge' => number_format((int) ($l['_views'] ?? 0)) . ' views']) ?>
        <?php endforeach; ?>
      </div>
      <p class="ptmore" data-pt="c<?= $_k ?>" hidden><a href="<?= hi_category_url(esc($_t['slug'], 'url')) ?>">See all in <?= esc($_t['name']) ?> <i class="fa-solid fa-arrow-right"></i></a></p>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (! empty($newest)): ?>
<section class="sec" style="background:var(--bg)">
  <div class="wrap">
    <div class="sec-head"><h2><?= esc(hi_c('home.new_on_hinoida', 'New on HiNoida')) ?></h2>
      <p><?= esc(hi_c('home.p_the_most_recent_businesses_to_join', 'The most recent businesses to join')) ?></p></div>
    <?php $_ntabs = $newestByCat ?? []; ?>
    <?php if ($_ntabs): ?>
      <div class="ptabs" role="tablist" aria-label="New listings by category">
        <button type="button" class="ptab on" role="tab" aria-selected="true" data-pt="all"><i class="fa-solid fa-wand-magic-sparkles"></i> All</button>
        <?php foreach ($_ntabs as $_k => $_t): ?>
          <button type="button" class="ptab" role="tab" aria-selected="false" data-pt="n<?= $_k ?>"><?= esc($_t['name']) ?></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="lgrid mscroll ptpanel" data-pt="all">
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
    <div class="secmore"><a class="seeall" href="<?= hi_page('search') . '?sort=newest' ?>">See the newest listings <i class="fa-solid fa-arrow-right"></i></a></div>
  </div>
</section>
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
    <?php if ($htitle !== ''): ?><div class="sec-head"><h2><?= esc($htitle) ?></h2></div><?php endif; ?>
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
    <div style="text-align:center;margin-top:26px">
      <a class="freelist" href="<?= base_url('list') ?>">Free listing</a>
    </div>
  </div>
</section>
<?php endif; ?>

<?= $this->endSection() ?>
