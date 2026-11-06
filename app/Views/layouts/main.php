<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
/* property cards: For rent / For sale tag and the price line (see _propertycard.php) */
.lcard .pc-deal{align-self:flex-start;display:inline-block;font-size:10.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
  border-radius:6px;padding:3px 8px;color:#16794a;background:#e7f6ee}
.lcard .pc-deal.is-rent{color:#b25d00;background:#fff3e2}
.lcard .pc-deal.is-pg{color:#1741a6;background:#eaf1fd}
.lcard .pc-line{display:block;font-size:13px;color:#5f6878;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lcard .pc-line b{display:inline!important;color:#14213d;font-size:15px;font-weight:800;margin:0!important}
</style>
<script>
/* Broken photos: many listing photos are served from the live media server and
   some of those files are gone. Any image that fails is replaced by a tile drawn
   here, in the browser: the place's initials on a blue gradient. Nothing extra
   is sent with the page; the tile is only made when a photo actually fails. */
(function(){
  function tile(name){
    var w=(name||'').replace(/[^A-Za-z0-9 ]/g,' ').split(/\s+/).filter(function(x){return x&&!/^(the|a|an|of|and|new)$/i.test(x);});
    var ini=((w[0]||'')[0]||'H')+((w[1]||'')[0]||''), h=0;
    for(var i=0;i<name.length;i++) h=(h*31+name.charCodeAt(i))>>>0;
    var hue=205+h%30, a='hsl('+hue+',62%,30%)', b='hsl('+(hue+12)+',58%,48%)';
    return 'data:image/svg+xml;charset=utf-8,'+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="'+a+'"/><stop offset="1" stop-color="'+b+'"/></linearGradient></defs><rect width="400" height="300" fill="url(#g)"/><circle cx="320" cy="50" r="150" fill="#fff" opacity=".08"/><text x="200" y="150" dy=".35em" text-anchor="middle" font-family="Inter,Segoe UI,Arial,sans-serif" font-size="96" font-weight="800" fill="#fff" fill-opacity=".9">'+ini.toUpperCase()+'</text></svg>');
  }
  function fix(img){ if(img.dataset.fbDone) return; img.dataset.fbDone='1'; img.src=tile(img.getAttribute('alt')||''); }
  window.addEventListener('error',function(e){ var t=e.target; if(t && t.tagName==='IMG') fix(t); },true);
  document.addEventListener('DOMContentLoaded',function(){
    [].forEach.call(document.images,function(i){ if(i.complete && i.naturalWidth===0 && i.getAttribute('src')) fix(i); }); });
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php
  $mTitle = $title ?? hi_c('brand_name') . ' — ' . hi_c('brand_tagline');
  $mDesc  = $metaDescription ?? 'Discover every business in Noida — restaurants, hospitals, shopping, salons, real estate and more.';
  // fix characters that were saved with the wrong encoding ("ÔÇô" -> "–") on every page
  require_once APPPATH . 'Views/frontend/_posthelp.php';
  $_dec   = static fn ($t) => html_entity_decode(html_entity_decode((string) $t, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $mTitle = hi_repair_text($_dec($mTitle));
  $mTitle = preg_replace('/^Sample\s*·\s*/u', '', $mTitle);
  $mDesc  = hi_repair_text($_dec($mDesc));
  $mCanon = $canonical ?? base_url(hi_current_path());
  $mImg   = $ogImage ?? base_url('assets/img/og-default.jpg');
  $mRobots= $robots ?? 'index,follow';
  $mLogo  = trim((string) hi_setting('fe_logo'));
  $mLogo  = $mLogo !== '' ? (str_starts_with($mLogo, 'http') ? $mLogo : base_url($mLogo)) : '';
?>
<title><?= esc($mTitle) ?></title>
<meta name="description" content="<?= esc($mDesc) ?>">
<?php if (! empty($metaKeywords)): ?><meta name="keywords" content="<?= esc($metaKeywords, 'attr') ?>"><?php endif; ?>
<meta name="robots" content="<?= esc($mRobots) ?>">
<meta name="theme-color" content="#1741a6">
<link rel="icon" href="<?= base_url('assets/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= base_url('assets/img/apple-touch-icon.png') ?>">
<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">

<script>if(!(window.matchMedia&&matchMedia('(prefers-reduced-motion:reduce)').matches))document.documentElement.className+=' jsr';</script>
<link rel="canonical" href="<?= esc($mCanon) ?>">
<!-- Open Graph -->
<meta property="og:type" content="<?= esc($ogType ?? 'website') ?>">
<meta property="og:site_name" content="<?= esc(hi_c('brand_name'), 'attr') ?>">
<meta property="og:title" content="<?= esc($mTitle) ?>">
<meta property="og:description" content="<?= esc($mDesc) ?>">
<meta property="og:url" content="<?= esc($mCanon) ?>">
<meta property="og:image" content="<?= esc($mImg) ?>">
<meta property="og:locale" content="en_IN">
<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= esc($mTitle) ?>">
<meta name="twitter:description" content="<?= esc($mDesc) ?>">
<meta name="twitter:image" content="<?= esc($mImg) ?>">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/theme.css') ?>?v=90">
<?php
  /* Which page is this? The contrast layer is scoped to the homepage for now,
     so the body carries the class rather than the stylesheet being conditional. */
  $_isHome = hi_current_path() === '';
?>
<link rel="stylesheet" href="<?= base_url('assets/css/contrast.css') ?>?v=19">
<link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>?v=129">
<?= $this->renderSection('head') ?>
<?php if (! empty($jsonld)) { foreach ((array) $jsonld as $schema) { ?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php } } ?>
<?php
  // Analytics / verification — configured in Admin -> SEO, never hardcoded.
  $_ga  = hi_setting('seo_ga4_id');
  $_gsc = hi_setting('seo_gsc_token');
  $_head= hi_setting('seo_head_code');
?>
<?php if ($_gsc !== ''): ?><meta name="google-site-verification" content="<?= esc($_gsc, 'attr') ?>"><?php endif; ?>
<?php if ($_ga !== '' && ENVIRONMENT === 'production'): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($_ga, 'attr') ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',<?= json_encode($_ga) ?>);</script>
<?php endif; ?>
<?php if ($_head !== ''): ?><?= $_head ?><?php endif; ?>
<?php $_px = hi_setting('pixel_id'); ?>
<?php if ($_px !== '' && ENVIRONMENT === 'production'): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;
s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init',<?= json_encode($_px) ?>);fbq('track','PageView');</script>
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('assets/css/ds.css') ?>?v=18">
</head>
<body<?= $_isHome ? ' class="is-home"' : '' ?>>

<header class="hdr hdr-live">
  <div class="topstrip">Noida's home for renting, buying &amp; living</div>
  <div class="wrap hdr-in">
    <a class="logo" href="<?= base_url('/') ?>"><?php if ($mLogo !== ''): ?><img src="<?= esc($mLogo, 'attr') ?>" alt="<?= esc(hi_c('brand_name'), 'attr') ?>"><?php else: ?><b><?= esc(hi_c('brand_mark_1')) ?></b> <?= esc(hi_c('brand_mark_2')) ?><?php endif; ?></a>
    <?php
      // highlight the section we are actually in
      $_seg = explode('/', hi_current_path())[0] ?? '';
      $_here = static function (array $match) use ($_seg): string {
          return in_array($_seg, $match, true) ? ' class="on"' : '';
      };
    ?>
    <?php /* the original's "All localities" picker, remembered per session */ ?>
    <?php /* locality picker hidden in the bar, as on the live site */ ?>
    <button type="button" class="navtoggle" id="navToggle" aria-label="Menu" aria-expanded="false" aria-controls="mainNav">
      <i class="fa-solid fa-bars"></i>
    </button>
    <nav class="nav" id="mainNav">
      <?php
        /* Property-first menu: what people come for, then the local guide.
           News, events, offers and contact sit under "More". */
        $_for  = (string) (service('request')->getGet('for') ?? service('request')->getGet('deal') ?? '');
        $_path = hi_current_path();
        $_on   = static fn (bool $c): string => $c ? ' class="on"' : '';
        $_isProp = $_seg === 'property';
        $_isPg   = str_contains($_path, 'pg-hostel');
      ?>
      <a href="<?= base_url('property') ?>?for=sale"<?= $_on($_isProp && $_for === 'sale') ?>>Buy</a>
      <a href="<?= base_url('property') ?>?for=rent"<?= $_on($_isProp && $_for === 'rent') ?>>Rent</a>
      <a href="<?= hi_category_url('pg-hostel-accommodations') ?>"<?= $_on($_isPg || ($_isProp && $_for === 'pg')) ?>>PG / Co-living</a>
      <?php
        /* the busiest local categories for the "Local places" menu — counted once an hour */
        $_navCats = [];
        try {
            $_nc = \Config\Services::cache();
            $_navCats = $_nc->get('nav_cats_v1');
            if (! is_array($_navCats)) {
                $_all = (new \App\Models\CategoryModel())->topLevel(300);
                $_all = array_values(array_filter($_all, static fn ($c) => (int) $c['listing_count'] > 0 && stripos((string) $c['name'], 'propert') !== 0));
                usort($_all, static fn ($a, $b) => (int) $b['listing_count'] <=> (int) $a['listing_count']);
                $_navCats = array_map(static fn ($c) => ['name' => html_entity_decode((string) $c['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'slug' => (string) $c['slug'], 'n' => (int) $c['listing_count']], array_slice($_all, 0, 8));
                $_nc->save('nav_cats_v1', $_navCats, 3600);
            }
        } catch (\Throwable $e) { $_navCats = []; }
        $_navIc = static function (string $n): string {
            if (function_exists('hi_cat_icon')) { return hi_cat_icon($n); }
            foreach (['food' => 'utensils', 'restaur' => 'utensils', 'hospital' => 'hospital', 'health' => 'heart-pulse', 'school' => 'school', 'educat' => 'graduation-cap',
                      'shop' => 'bag-shopping', 'beauty' => 'spa', 'salon' => 'spa', 'hotel' => 'hotel', 'auto' => 'car', 'fitness' => 'dumbbell', 'gym' => 'dumbbell'] as $k => $v) {
                if (stripos($n, $k) !== false) { return $v; }
            }
            return 'store';
        };
        $_isCom   = $_isProp && $_for === 'commercial' || preg_match('~(coworking-spaces|commercial-properties|property-dealers)~', $_path);
        $_isLocal = ! $_isPg && ! $_isCom && in_array($_seg, ['listings', 'search', 'general', 'restaurant', 'category', 'c', 'g', 'categories'], true);
      ?>
      <div class="nav-more nav-dd">
        <a class="nav-more-btn" href="<?= base_url('property') ?>?for=commercial" aria-haspopup="true"<?= $_isCom ? ' data-on="1"' : '' ?>>Commercial <i class="fa-solid fa-chevron-down"></i></a>
        <div class="nav-more-pan nav-dd-pan" role="menu">
          <span class="nav-dd-h">Spaces for work</span>
          <a href="<?= hi_category_url('coworking-spaces') ?>" role="menuitem"><i class="fa-solid fa-laptop"></i> Coworking spaces</a>
          <a href="<?= hi_category_url('commercial-properties') ?>" role="menuitem"><i class="fa-solid fa-building"></i> Commercial properties</a>
          <a href="<?= base_url('property') ?>?for=commercial&amp;kind=office" role="menuitem"><i class="fa-solid fa-briefcase"></i> Office space</a>
          <a href="<?= base_url('property') ?>?for=commercial&amp;kind=shop" role="menuitem"><i class="fa-solid fa-store"></i> Shops &amp; showrooms</a>
          <a href="<?= base_url('property') ?>?for=commercial&amp;kind=warehouse" role="menuitem"><i class="fa-solid fa-warehouse"></i> Warehouses</a>
          <span class="nav-dd-h">Get help</span>
          <a href="<?= hi_category_url('property-dealers') ?>" role="menuitem"><i class="fa-solid fa-handshake"></i> Property dealers</a>
          <a class="nav-dd-all" href="<?= base_url('property') ?>?for=commercial" role="menuitem">See all commercial <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </div>
      <div class="nav-more nav-dd">
        <a class="nav-more-btn" href="<?= base_url('listings') ?>" aria-haspopup="true"<?= $_isLocal ? ' data-on="1"' : '' ?>>Local places <i class="fa-solid fa-chevron-down"></i></a>
        <div class="nav-more-pan nav-dd-pan nav-dd-2" role="menu">
          <span class="nav-dd-h">Popular in Noida</span>
          <?php foreach ($_navCats as $_c): ?>
            <a href="<?= hi_category_url(esc($_c['slug'], 'url')) ?>" role="menuitem"><i class="fa-solid fa-<?= esc($_navIc($_c['name'])) ?>"></i> <span><?= esc($_c['name']) ?></span><small><?= number_format($_c['n']) ?></small></a>
          <?php endforeach; ?>
          <a class="nav-dd-all" href="<?= hi_page('categories') ?>" role="menuitem">See all categories <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </div>
      <div class="nav-more" id="navMore">
        <button type="button" class="nav-more-btn" aria-expanded="false" aria-haspopup="true"<?= in_array($_seg, ['news', 'blog', 'event', 'events', 'offer', 'offers', 'contact', 'city', 'areas'], true) ? ' data-on="1"' : '' ?>>More <i class="fa-solid fa-chevron-down"></i></button>
        <div class="nav-more-pan" role="menu">
          <a href="<?= hi_page('categories') ?>" role="menuitem"><i class="fa-solid fa-layer-group"></i> All categories</a>
          <a href="<?= base_url('city') ?>" role="menuitem"><i class="fa-solid fa-map-location-dot"></i> Areas &amp; sectors</a>
          <a href="<?= hi_page('news') ?>" role="menuitem"><i class="fa-solid fa-newspaper"></i> News</a>
          <a href="<?= hi_page('events') ?>" role="menuitem"><i class="fa-solid fa-champagne-glasses"></i> Events</a>
          <a href="<?= hi_page('offers') ?>" role="menuitem"><i class="fa-solid fa-tags"></i> Offers</a>
          <a href="<?= hi_page('contact') ?>" role="menuitem"><i class="fa-solid fa-headset"></i> Contact</a>
        </div>
      </div>
      <?php /* every property portal keeps this one button in the top bar */ ?>
      <a class="btn nav-post" href="<?= base_url('list') ?>"><i class="fa-solid fa-house-circle-check"></i> Post property <span>FREE</span></a>
      <?php if (session()->get('uid')): ?>
        <?php if ((int) session()->get('role') === 1): ?><a href="<?= base_url('admin') ?>"><?= esc(hi_t('admin')) ?></a><?php endif; ?>
        <div class="profmenu" id="profMenu">
          <button type="button" class="profbtn" id="profBtn">
            <?php $_un = (string) session()->get('uname'); $_ue = (string) session()->get('uemail'); ?>
            <span class="profav" style="background:<?= esc(hi_avatar_bg($_un ?: $_ue), 'attr') ?>"><?= esc(hi_initial($_un, $_ue)) ?></span>
            <span class="profname"><?= esc(session()->get('uname')) ?></span>
            <i class="fa-solid fa-chevron-down" style="font-size:10px"></i>
          </button>
          <div class="profdrop" id="profDrop" hidden>
            <?php if ((int) session()->get('role') === 2): ?>
              <a href="<?= base_url('dashboard') ?>"><i class="fa-solid fa-gauge-high"></i> Business dashboard</a>
              <a href="<?= base_url('dashboard/listings') ?>"><i class="fa-solid fa-store"></i> My listings</a>
              <a href="<?= base_url('dashboard/leads') ?>"><i class="fa-solid fa-inbox"></i> Leads</a>
              <a href="<?= base_url('dashboard/insights') ?>"><i class="fa-solid fa-chart-line"></i> Insights</a>
              <a href="<?= base_url('dashboard/reviews') ?>"><i class="fa-solid fa-star"></i> Reviews</a>
            <?php else: ?>
              <a href="<?= base_url('dashboard/bookings') ?>"><i class="fa-solid fa-calendar-check"></i> My bookings</a>
              <a href="<?= base_url('dashboard/enquiries') ?>"><i class="fa-solid fa-paper-plane"></i> My enquiries</a>
              <a href="<?= base_url('dashboard/myreviews') ?>"><i class="fa-solid fa-star"></i> My reviews</a>
              <a href="<?= base_url('dashboard/saved') ?>"><i class="fa-solid fa-heart"></i> Saved places</a>
            <?php endif; ?>
            <a href="<?= base_url('dashboard/profile') ?>"><i class="fa-solid fa-user"></i> Profile settings</a>
            <div class="profdiv"></div>
            <a href="<?= base_url('logout') ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a class="btn nav-login" href="<?= hi_page('login') ?>">Login/Signup</a>
      <?php endif; ?>
    </nav>
    <?php /* shortlist, always in the top bar (like the big property sites) */ ?>
    <div class="hdr-sl" id="hdrSl">
      <button type="button" class="hdr-sl-btn" id="hdrSlBtn" aria-label="Your shortlist" aria-expanded="false" aria-controls="hdrSlPan">
        <i class="fa-regular fa-heart"></i><em id="hdrSlN" hidden>0</em><span class="hdr-sl-t">Shortlist</span>
      </button>
      <div class="hdr-sl-pan" id="hdrSlPan" hidden>
        <div class="hdr-sl-h"><b>Your shortlist</b><a href="<?= base_url('/') ?>#hiMine">See all</a></div>
        <div class="hdr-sl-list" id="hdrSlList"></div>
      </div>
    </div>
  </div>
</header>


<?php
  // Global confirmations. Actions redirect to an anchor further down the page,
  // so a banner at the top would be scrolled out of view — use a fixed toast.
  $_ok  = $flash ?? session()->getFlashdata('flash') ?? session()->getFlashdata('saved');
  $_bad = $err   ?? session()->getFlashdata('err');
?>
<?php if ($_ok || $_bad): ?>
<div class="toastwrap" id="toastWrap">
  <?php if ($_ok): ?>
    <div class="toast ok"><i class="fa-solid fa-circle-check ti"></i><span class="tx"><?= esc($_ok) ?></span><button type="button" aria-label="Dismiss"><?= esc(hi_c('main.btn_', '×')) ?></button></div>
  <?php endif; ?>
  <?php if ($_bad): ?>
    <div class="toast bad"><i class="fa-solid fa-circle-exclamation ti"></i><span class="tx"><?= esc($_bad) ?></span><button type="button" aria-label="Dismiss">&times;</button></div>
  <?php endif; ?>
</div>
<script>
(function(){
  var w=document.getElementById('toastWrap'); if(!w) return;
  function close(t){ t.classList.add('hide'); setTimeout(function(){ t.remove(); },300); }
  w.querySelectorAll('.toast').forEach(function(t){
    t.querySelector('button').addEventListener('click',function(){ close(t); });
    setTimeout(function(){ if(t.isConnected) close(t); }, 6000);
  });
})();
</script>
<?php endif; ?>

<main>
<?= hi_fix_entities(hi_repair_text($this->renderSection('content'))) ?>
</main>

<?php
  // App-style bottom bar on phones. Items depend on who is signed in.
  $_role = (int) session()->get('role');
  $_uid  = (int) session()->get('uid');
  if ($_uid === 0) {
      $_tabs = [
          ['', 'compass', 'Discover'],
          ['search', 'magnifying-glass', 'Search'],
          ['areas', 'location-dot', 'Areas'],
          ['login', 'right-to-bracket', 'Login'],
      ];
  } elseif ($_role === 1) {
      $_tabs = [
          ['', 'compass', 'Discover'],
          ['search', 'magnifying-glass', 'Search'],
          ['admin/listings', 'store', 'Listings'],
          ['admin/users', 'users', 'Members'],
          ['admin', 'gauge-high', 'Admin'],
      ];
  } elseif ($_role === 2) {
      $_tabs = [
          ['', 'compass', 'Discover'],
          ['search', 'magnifying-glass', 'Search'],
          ['dashboard/listings', 'store', 'Listings'],
          ['dashboard/leads', 'inbox', 'Leads'],
          ['dashboard', 'user', 'Account'],
      ];
  } else {
      $_tabs = [
          ['', 'compass', 'Discover'],
          ['search', 'magnifying-glass', 'Search'],
          ['dashboard/saved', 'heart', 'Saved'],
          ['dashboard/bookings', 'calendar-check', 'Bookings'],
          ['dashboard', 'user', 'Account'],
      ];
  }
  $_path = hi_current_path();
  // pick ONE active tab — the longest matching prefix, so /dashboard/saved
  // lights up "Saved" and not also "Account"
  $_active = -1; $_bestLen = -1;
  // the tab key is our own short name ("search"); the path it actually points
  // at is the live one ("listings"), so match on that
  $_tabPath = static fn (string $key): string => trim(str_replace(base_url(), '', hi_page($key)), '/');
  foreach ($_tabs as $i => $t) {
      $target = $t[0] === '' ? '' : $_tabPath($t[0]);
      $match  = $target === ''
          ? ($_path === '')
          : ($_path === $target || str_starts_with($_path, $target . '/'));
      if ($match && strlen($target) > $_bestLen) { $_active = $i; $_bestLen = strlen($target); }
  }
?>
<nav class="tabbar-m" aria-label="Quick navigation">
  <?php foreach ($_tabs as $i => $t): $on = ($i === $_active); ?>
    <a href="<?= hi_page($t[0]) ?>"<?= $on ? ' class="on"' : '' ?>>
      <i class="fa-solid fa-<?= $t[1] ?>"></i><span><?= esc($t[2]) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<script>
(function(){
  var btn=document.getElementById('profBtn'), drop=document.getElementById('profDrop'), wrap=document.getElementById('profMenu');
  if(!btn||!drop) return;
  btn.addEventListener('click',function(e){ e.stopPropagation(); drop.hidden=!drop.hidden; });
  document.addEventListener('click',function(e){ if(wrap && !wrap.contains(e.target)) drop.hidden=true; });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') drop.hidden=true; });
})();
</script>
<?php if (hi_addon('compare')): ?>
<div id="cmpBar" style="display:none;position:fixed;left:50%;transform:translateX(-50%);bottom:18px;z-index:60;background:#0b1a33;color:#fff;border-radius:14px;box-shadow:0 12px 34px rgba(0,0,0,.3);padding:12px 16px;align-items:center;gap:14px;font-size:14px">
  <span><i class="fa-solid fa-scale-balanced" style="color:#7fa2ff"></i> <b id="cmpCount">0</b> selected to compare</span>
  <a href="<?= base_url('compare') ?>" id="cmpGo" style="background:#1741a6;color:#fff;padding:8px 16px;border-radius:9px;font-weight:700">Compare</a>
  <button id="cmpClear" style="background:none;border:0;color:#9ab0d4;cursor:pointer;font-size:13px"><?= esc(hi_c('main.btn_clear', 'Clear')) ?></button>
</div>
<?php endif; ?>
<script>
(function(){
  var KEY='hi_compare';
  function get(){try{return JSON.parse(localStorage.getItem(KEY)||'[]');}catch(e){return[];}}
  // The compare page groups picks by category, so collecting across a few
  // categories is fine — only the table is one category at a time.
  var MAX=12;
  function set(v){localStorage.setItem(KEY,JSON.stringify(v.slice(0,MAX)));render();}
  var bar=document.getElementById('cmpBar');
  function render(){var ids=get();if(bar){bar.style.display=ids.length?'flex':'none';var c=document.getElementById('cmpCount');if(c)c.textContent=ids.length;var g=document.getElementById('cmpGo');if(g)g.href='<?= base_url('compare') ?>?ids='+ids.join(',');}
    document.querySelectorAll('[data-cmp-add]').forEach(function(b){var on=ids.indexOf(+b.dataset.cmpAdd)>-1;b.classList.toggle('on',on);b.innerHTML=on?'<i class="fa-solid fa-check"></i> Added to compare':'<i class="fa-solid fa-scale-balanced"></i> Add to compare';});}
  document.querySelectorAll('[data-cmp-add]').forEach(function(b){b.addEventListener('click',function(e){e.preventDefault();var id=+b.dataset.cmpAdd;var ids=get();var i=ids.indexOf(id);if(i>-1)ids.splice(i,1);else{if(ids.length>=MAX){alert('You can keep up to '+MAX+' places to compare.');return;}ids.push(id);}set(ids);});});
  var cl=document.getElementById('cmpClear');if(cl)cl.addEventListener('click',function(){set([]);});
  var cla=document.getElementById('cmpClearAll');if(cla)cla.addEventListener('click',function(){set([]);location.href='<?= base_url('compare') ?>';});
  render();
})();
// quick Call / WhatsApp buttons on rich listing cards
(function(){
  document.addEventListener('click',function(e){
    var call=e.target.closest('.lc-call'), wa=e.target.closest('.lc-wa');
    if(call){ e.preventDefault(); e.stopPropagation(); window.location.href='tel:'+call.dataset.tel; }
    else if(wa){ e.preventDefault(); e.stopPropagation(); var n=wa.dataset.wa; window.open('https://wa.me/'+(n.length===10?'91'+n:n),'_blank'); }
  });
})();
// (the old heart on every card is gone: only homes and property places can be shortlisted — see _card / _propertycard)
</script>

<?php /* phrase links the original carries site-wide */ ?>

<?php /* every page, as the original has it in footer.php */ ?>

<footer class="ftr">
  <?php
    /* Contacts. The address, email and phone were already in settings and
       nothing displayed them, so the footer gave no way to reach anyone. */
    $_addr  = trim((string) hi_setting('address'));
    $_mail  = trim((string) hi_setting('system_email'));
    $_phone = trim((string) hi_setting('phone'));
    // social links come from admin > Frontend; an empty one is simply not shown
    $_soc   = hi_fe_social();
  ?>
  <div class="wrap ftr-top">

    <?php /* The footer opened straight onto four link columns with no sense of
             whose site it was. The mark, one line about it, and the social row
             belong together at the head of it. */ ?>
    <div class="ftr-brand">
      <a class="logo" href="<?= base_url('/') ?>"><?php if ($mLogo !== ''): ?><img src="<?= esc($mLogo, 'attr') ?>" alt="<?= esc(hi_c('brand_name'), 'attr') ?>"><?php else: ?><b><?= esc(hi_c('brand_mark_1')) ?></b> <?= esc(hi_c('brand_mark_2')) ?><?php endif; ?></a>
      <p><?= esc(hi_c('footer_blurb')) ?></p>
      <?php if ($_soc): ?>
        <div class="ftr-social">
          <?php foreach ($_soc as $_ic => $_url): ?>
            <a href="<?= esc($_url, 'attr') ?>" target="_blank" rel="noopener noreferrer nofollow"
               aria-label="<?= esc(ucfirst(str_replace('-', ' ', $_ic))) ?>">
              <i class="fa-brands fa-<?= esc($_ic) ?>"></i>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="ftr-cols">
      <div><b>Property in Noida</b>
        <a href="<?= base_url('property') ?>?for=rent">Flats for rent in Noida</a>
        <a href="<?= base_url('property') ?>?for=sale">Flats for sale in Noida</a>
        <a href="<?= hi_category_url('pg-hostel-accommodations') ?>">PG &amp; hostels in Noida</a>
        <a href="<?= base_url('property') ?>?for=commercial">Offices &amp; shops</a>
        <a href="<?= base_url('property') ?>?for=plots">Plots &amp; land</a>
        <a href="<?= base_url('list') ?>">Post your property free</a>
      </div>
      <div><b><?= esc(hi_c('footer_col1')) ?></b>
        <a href="<?= base_url('/') ?>"><?= esc(hi_c('flink_categories')) ?></a>
        <a href="<?= base_url('property') ?>"><?= esc(hi_c('flink_property')) ?></a>
        <a href="<?= hi_page('areas') ?>"><?= esc(hi_c('flink_areas')) ?></a>
        <a href="<?= hi_page('events') ?>"><?= esc(hi_t('events')) ?></a>
        <a href="<?= hi_page('offers') ?>"><?= esc(hi_c('flink_offers')) ?></a>
        <a href="<?= hi_page('news') ?>"><?= esc(hi_c('flink_news')) ?></a>
      </div>
      <div><b><?= esc(hi_c('footer_col2')) ?></b>
        <a href="<?= hi_page('about') ?>"><?= esc(hi_c('flink_about')) ?></a>
        <a href="<?= hi_page('contact') ?>"><?= esc(hi_c('flink_contact')) ?></a>
        <a href="<?= hi_page('pricing') ?>"><?= esc(hi_c('flink_pricing')) ?></a>
        <a href="<?= hi_page('faq') ?>"><?= esc(hi_c('flink_faq')) ?></a>
        <a href="<?= base_url('advertise') ?>"><?= esc(hi_c('flink_advertise')) ?></a>
      </div>
      <div><b><?= esc(hi_c('footer_col3')) ?></b>
        <a href="<?= base_url('list') ?>"><?= esc(hi_c('flink_list')) ?></a>
        <a href="<?= hi_page('login') ?>"><?= esc(hi_c('flink_login')) ?></a>
        <a href="<?= base_url('dashboard') ?>"><?= esc(hi_c('flink_dashboard')) ?></a>
      </div>
      <div><b><?= esc(hi_c('footer_col4')) ?></b>
        <a href="<?= hi_page('privacy') ?>"><?= esc(hi_c('flink_privacy')) ?></a>
        <a href="<?= base_url('credits') ?>"><?= esc(hi_c('flink_credits')) ?></a>
        <a href="<?= hi_page('terms') ?>"><?= esc(hi_c('flink_terms')) ?></a>
        <a href="<?= base_url('cookie') ?>"><?= esc(hi_c('flink_cookie')) ?></a>
      </div>
      <?php if ($_addr !== '' || $_mail !== '' || $_phone !== ''): ?>
        <div class="ftr-contact"><b><?= esc(hi_c('footer_col5')) ?></b>
          <?php if ($_addr !== ''): ?>
            <span><i class="fa-solid fa-location-dot"></i><?= esc($_addr) ?></span>
          <?php endif; ?>
          <?php if ($_phone !== ''): ?>
            <a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $_phone), 'attr') ?>"><i class="fa-solid fa-phone"></i><?= esc($_phone) ?></a>
          <?php endif; ?>
          <?php if ($_mail !== ''): ?>
            <a href="mailto:<?= esc($_mail, 'attr') ?>"><i class="fa-solid fa-envelope"></i><?= esc($_mail) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="ftr-bottom">
    <div class="wrap">
      <span>&copy; <?= date('Y') ?> <?= esc(hi_c('footer_copy')) ?></span>
      <span class="ftr-bottom-links">
        <a href="<?= hi_page('privacy') ?>">Privacy</a>
        <a href="<?= hi_page('terms') ?>">Terms</a>
        <a href="<?= hi_page('contact') ?>"><?= esc(hi_c('flink_contact')) ?></a>
      </span>
    </div>
  </div>
<script>
/* phones: footer link groups open and close when their heading is tapped */
document.querySelectorAll('.ftr-cols>div:not(.ftr-contact)>b').forEach(function(b){
  b.setAttribute('role','button'); b.setAttribute('tabindex','0');
  function t(){ b.parentElement.classList.toggle('open'); }
  b.addEventListener('click',t); b.addEventListener('keydown',function(e){ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); t(); } });
});
</script>
<script>
/* Filter panels site-wide: labels over each drop-down, one-tap chips,
   a "Showing:" row you can remove things from. Runs before the drop-down
   builder below, so the selects are already in place when it styles them. */
(function(){
  var L={area:['location-dot','Area'],locality:['location-dot','Locality'],cat:['layer-group','Category'],rating:['star','Rating'],
    sub:['tags','Type'],amenity:['circle-check','Facility'],'amenity[]':['circle-check','Facilities'],sort:['arrow-down-wide-short','Sort by'],deal:['handshake','Buy or rent'],
    kind:['building','Property type'],bhk:['bed','BHK'],budget:['indian-rupee-sign','Budget'],furnishing:['couch','Furnishing'],show:['layer-group','Show']};
  function el(t,c,h){ var e=document.createElement(t); if(c) e.className=c; if(h!=null) e.innerHTML=h; return e; }
  function clean(t){ return t.replace(/\s*\(\d[\d,]*\)\s*$/,'').replace(/^Sort:\s*/,'').trim(); }
  function esc(t){ return t.replace(/[&<>"]/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];}); }
  function hiSxInit(root){ (root||document).querySelectorAll('form[data-sx]').forEach(function(form){
    if(form.querySelector('.sx-filters')) return;
    var card=form.closest('.sx')||form; card.classList.add('sx');
    var busy=function(){ document.body.classList.add('sx-busy'); };
    function send(){ form.submit(); }
    form.addEventListener('submit',function(e){ if(e.submitter) busy(); });
    var sels=[].slice.call(form.querySelectorAll('select'));
    var box=el('div','filterbar sx-filters');
    if(sels.length){ var first=sels[0]; first.parentNode.insertBefore(box,first); }
    sels.forEach(function(s){ var m=L[s.name]||['sliders',s.name]; if(s.dataset.sxl) m=[m[0],s.dataset.sxl]; var f=el('div','sx-f','<span class="sx-l"><i class="fa-solid fa-'+m[0]+'"></i> '+m[1]+'</span>'); f.appendChild(s); box.appendChild(f); });
    form.classList.remove('filterbar');
    form.querySelectorAll('.pfilter').forEach(function(p){ if(!p.querySelector('select')) p.remove(); });
    var chk=form.querySelector('input[type=checkbox][name=open]'), lab=chk&&chk.closest('label');
    if(lab){ lab.parentNode.insertBefore(chk,lab); chk.hidden=true; lab.remove(); }
    var fc=form.querySelector('.fb-clear'), clearHref=fc?fc.getAttribute('href'):(form.getAttribute('action')||location.pathname); if(fc) fc.remove();
    // one-tap chips
    var spec=(form.dataset.sx||'').split('|').filter(Boolean), quick=el('div','sx-quick');
    spec.forEach(function(s){ var p=s.split(':'), k=p[0], v=p[1], on=false;
      if(k==='open'){ if(!chk) return; on=chk.checked; }
      else { var se=form.elements[k]; if(!se||!se.options) return; var o=[].filter.call(se.options,function(x){return x.value===v;})[0]; if(!o||o.disabled) return; on=se.value===v; }
      var b=el('button','sx-chip'+(on?' on':''),'<i class="fa-solid fa-'+p[2]+'"></i> '+esc(p[3])+(on?' <i class="fa-solid fa-xmark sx-cx"></i>':''));
      b.type='button'; b.setAttribute('aria-pressed',on?'true':'false');
      b.dataset.k=k; b.dataset.v=v;
      b.addEventListener('click',function(){ var now=b.classList.contains('on'); if(k==='open'){ chk.checked=!now; } else { var se=form.elements[k]; se.value = now ? se.options[0].value : v; } send(); });
      quick.appendChild(b); });
    var more=null;
    if(sels.length>1){ more=el('button','sx-more','<i class="fa-solid fa-sliders"></i> Filters'); more.type='button'; quick.appendChild(more);
      var mq=window.matchMedia('(max-width:640px)'); var fit=function(){ box.classList.toggle('is-shut',mq.matches); more.classList.remove('on'); }; fit();
      if(mq.addEventListener) mq.addEventListener('change',fit);
      more.addEventListener('click',function(){ var shut=box.classList.toggle('is-shut'); more.classList.toggle('on',!shut); }); }
    // a field that has a one-tap chip doesn't also need a drop-down (sort keeps its drop-down)
    spec.forEach(function(s){ var k=s.split(':')[0]; if(k==='open'||k==='sort') return; var se=form.elements[k]; if(se&&se.options){ var w=se.closest('.sx-f'); if(w) w.hidden=true; } });
    if(more){ var shown=[].filter.call(box.querySelectorAll('.sx-f'),function(f){return !f.hidden;}).length; if(shown<2){ more.remove(); more=null; box.classList.remove('is-shut'); } }
    if(quick.children.length) box.parentNode.insertBefore(quick,box);
    // nothing loads until the visitor presses this (or Search) — chips and drop-downs only pick
    // the same top row on every page: a search box and one Search button
    if(!form.querySelector('button[type=submit],input[type=submit]')){
      var top=el('div','sx-top','<label class="sx-q"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" autocomplete="off"></label><button type="submit" class="sx-go"><i class="fa-solid fa-magnifying-glass"></i><span>Search</span></button>');
      var qi=top.querySelector('input'); qi.value=form.dataset.sxqv||''; qi.placeholder=form.dataset.sxq||'Search in this list…';
      form.insertBefore(top, form.firstChild); }
    // anything switched on? one small "Clear all" — no second row repeating the same things
    var any=sels.some(function(s){ return s.multiple ? [].some.call(s.options,function(o){return o.selected&&o.value;}) : (s.options.length && s.selectedIndex>0); }) || (chk&&chk.checked);
    if(any){ var ca=el('a','sx-clear','<i class="fa-solid fa-rotate-left"></i> Clear all'); ca.href=clearHref;
      (quick.parentNode?quick:box).appendChild(ca); }
  }); }
  window.hiSxInit=hiSxInit; hiSxInit(document);
})();
</script>
<script>
/* Nicer drop-downs for the filter bars: the same <select> underneath (so the
   form still submits exactly as before), with a clean panel on top — search
   for long lists, counts as pills, a tick on the chosen one. */
(function(){
  var open=null;
  function close(){ if(!open) return; open.box.classList.remove('dd-open'); open.btn.setAttribute('aria-expanded','false'); open=null; document.body.classList.remove('dd-lock'); }
  function tidy(t){ t=t.replace(/\s+/g,' ').trim(); var s=t.match(/^sector\s*-?\s*(\d+)$/i); return s?'Sector '+s[1]:t.charAt(0).toUpperCase()+t.slice(1); }
  function split(t){ var m=t.match(/^(.*?)\s*\((\d+)\)\s*$/); return m?[tidy(m[1]),m[2]]:[tidy(t),null]; }
  function build(sel){
    if(sel.dataset.dd) return; sel.dataset.dd='1'; var multi=sel.multiple;
    var box=document.createElement('div'); box.className='dd'+(multi?' dd-multi':'');
    sel.parentNode.insertBefore(box,sel); box.appendChild(sel); sel.classList.add('dd-native');
    var btn=document.createElement('button'); btn.type='button'; btn.className='dd-btn'; btn.setAttribute('aria-haspopup','listbox'); btn.setAttribute('aria-expanded','false');
    var pan=document.createElement('div'); pan.className='dd-pan'; pan.setAttribute('role','listbox');
    box.appendChild(btn); box.appendChild(pan);
    var many=sel.options.length>10, q=null;
    if(many){ var w=document.createElement('label'); w.className='dd-q'; w.innerHTML='<i class="fa-solid fa-magnifying-glass"></i>';
      q=document.createElement('input'); q.type='search'; q.placeholder='Search…'; w.appendChild(q); pan.appendChild(w); }
    var list=document.createElement('div'); list.className='dd-list'; pan.appendChild(list);
    var MAX=3; if(multi){ var nt=document.createElement('p'); nt.className='dd-note'; nt.innerHTML='<i class="fa-solid fa-circle-info"></i> Pick up to '+MAX; pan.appendChild(nt); }
    function label(){
      var t;
      if(multi){ var on=[].filter.call(sel.options,function(o){return o.selected&&o.value;}).map(function(o){return split(o.text)[0];});
        t=on.length?on[0]+(on.length>1?' +'+(on.length-1):''):split(sel.options[0]?sel.options[0].text:'Any')[0];
        box.classList.toggle('dd-set',on.length>0);
        [].forEach.call(list.children,function(it){ var o=sel.options[+it.dataset.i]; if(o){ it.classList.toggle('on',o.value?o.selected:!on.length); it.classList.toggle('dd-lock',!!o.value&&!o.selected&&on.length>=3); } });
      } else { var o=sel.options[sel.selectedIndex]; t=split(o?o.text:'')[0]; box.classList.toggle('dd-set',!!sel.value); }
      btn.innerHTML='<span>'+t.replace(/</g,'&lt;')+'</span><i class="fa-solid fa-chevron-down"></i>'; }
    [].forEach.call(sel.options,function(o,i){
      var p=split(o.text), it=document.createElement('button'); it.type='button'; it.className='dd-it'+(o.disabled?' dis':'')+(i===sel.selectedIndex?' on':'');
      it.setAttribute('role','option'); it.dataset.i=i; it.dataset.q=p[0].toLowerCase();
      it.innerHTML='<i class="fa-solid fa-check dd-tick"></i><span class="dd-t"></span>'+(p[1]!==null?'<em>'+p[1]+'</em>':'');
      it.querySelector('.dd-t').textContent=p[0]; if(o.disabled&&!(multi&&!o.value)) it.disabled=true; if(multi&&!o.value){ it.classList.remove('dis'); }
      it.addEventListener('click',function(e){
        if(multi){ e.stopPropagation();
          if(!o.value){ [].forEach.call(sel.options,function(x){x.selected=false;}); }
          else if(!o.selected && [].filter.call(sel.options,function(x){return x.selected&&x.value;}).length>=MAX){ pan.classList.add('dd-full'); setTimeout(function(){ pan.classList.remove('dd-full'); },1600); return; }
          else { o.selected=!o.selected; }
          label(); sel.dispatchEvent(new Event('change',{bubbles:true})); return; }
        sel.selectedIndex=i; label(); close(); sel.dispatchEvent(new Event('change',{bubbles:true})); });
      list.appendChild(it);
    });
    if(q) q.addEventListener('input',function(){ var v=q.value.trim().toLowerCase(); [].forEach.call(list.children,function(it){ it.hidden=v!==''&&it.dataset.q.indexOf(v)<0; }); });
    btn.addEventListener('click',function(e){ e.stopPropagation(); var was=open&&open.box===box; close(); if(was) return;
      box.classList.add('dd-open'); btn.setAttribute('aria-expanded','true'); open={box:box,btn:btn};
      if(window.innerWidth<=720) document.body.classList.add('dd-lock');
      var on=list.querySelector('.on'); if(on) on.scrollIntoView({block:'nearest'}); if(q&&window.innerWidth>720){ q.value=''; q.dispatchEvent(new Event('input')); q.focus(); } });
    pan.addEventListener('click',function(e){ e.stopPropagation(); if(e.target===pan&&window.innerWidth<=720) close(); });
    label();
  }
  window.hiDdInit=function(root){ (root||document).querySelectorAll('.filterbar select, .cp-toolbar select, #searchForm .filterbar select, .pfilter select').forEach(build); };
  window.hiDdInit(document);
  document.addEventListener('click',close);
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') close(); });
})();
</script>
<script>
/* Filters without a full page reload. A chip, a drop-down or Search fetches
   the new results and swaps only that part of the page; the address bar
   still updates, so Back, sharing and refresh all keep working. */
(function(){
  if(!window.fetch||!window.DOMParser||!history.pushState||!window.URL) return;
  function idx(sec){ return [].indexOf.call(document.querySelectorAll('section'),sec); }
  function urlOf(form){
    var u=new URL(form.getAttribute('action')||location.pathname, location.href), p=new URLSearchParams();
    new FormData(form).forEach(function(v,k){ if(v!=='') p.append(k,v); });
    u.search=p.toString(); return u.href;
  }
  function go(url, from, push){
    var u=new URL(url, location.href), sec=from&&from.closest('section');
    if(!sec||u.pathname!==location.pathname){ location.href=url; return; }   // another page: normal visit
    var i=idx(sec), fid=from.tagName==='FORM'?from.id:((from.closest('form')||{}).id||'');
    document.body.classList.add('sx-busy');
    fetch(u.href,{headers:{'X-Requested-With':'fetch'}}).then(function(r){ if(!r.ok) throw new Error(r.status); return r.text(); }).then(function(h){
      var d=new DOMParser().parseFromString(h,'text/html'), ns=null;
      if(fid){ var f=d.getElementById(fid); ns=f&&f.closest('section'); }
      if(!ns) ns=d.querySelectorAll('section')[i];
      if(!ns){ location.href=u.href; return; }
      var imp=document.importNode(ns,true); sec.replaceWith(imp);
      imp.querySelectorAll('script').forEach(function(old){ var n=document.createElement('script'); if(old.src) n.src=old.src; else n.textContent=old.textContent; old.replaceWith(n); });
      if(window.hiSxInit) window.hiSxInit(imp);
      if(window.hiDdInit) window.hiDdInit(imp);
      if(window.hiSaveMark) window.hiSaveMark(imp);
      bind(imp);
      document.title=d.title;
      if(push) history.pushState({sx:1},'',u.href);
      var top=imp.getBoundingClientRect().top; if(top<0) window.scrollTo({top:window.scrollY+top-90,behavior:'smooth'});
    }).catch(function(){ location.href=u.href; }).then(function(){ document.body.classList.remove('sx-busy'); });
  }
  function bind(root){
    (root||document).querySelectorAll('#searchForm, form[data-sx], .sx form').forEach(function(f){
      if(f.dataset.soft) return; f.dataset.soft='1';
      f.submit=function(){ pending(f); };   // the drop-downs' onchange="…submit()" now only marks a change
    });
  }
  // a chip or drop-down only changes the choice; the button shows something is waiting
  function pending(f){
    var card=f.closest('.sx')||f;
    card.querySelectorAll('.sx-chip[data-k]').forEach(function(c){
      var k=c.dataset.k, v=c.dataset.v, el=f.elements[k], on;
      if(k==='open'){ var cb=f.querySelector('input[name=open]'); on=!!(cb&&cb.checked); } else { on=!!el&&String(el.value)===String(v); }
      c.classList.toggle('on',on); c.setAttribute('aria-pressed',on?'true':'false');
      var x=c.querySelector('.sx-cx'); if(on&&!x){ c.insertAdjacentHTML('beforeend',' <i class="fa-solid fa-xmark sx-cx"></i>'); } else if(!on&&x){ x.remove(); }
    });
    card.querySelectorAll('.sx-go,.sx-apply').forEach(function(b){ b.classList.add('sx-pending'); });
    f.querySelectorAll('select').forEach(function(s){ var box=s.closest('.dd'); if(box) box.classList.toggle('dd-set',!!s.value); });
  }
  document.addEventListener('submit',function(e){
    var f=e.target; if(!f.dataset||!f.dataset.soft||(f.getAttribute('method')||'get').toLowerCase()!=='get') return;
    e.preventDefault();
    if(!e.submitter){ pending(f); return; }      // picked a chip / option: wait for Search
    go(urlOf(f), f, true);
  });
  document.addEventListener('click',function(e){
    var a=e.target.closest&&e.target.closest('.sx a[href], .pager a[href]');
    if(!a||e.defaultPrevented||e.button!==0||e.ctrlKey||e.metaKey||e.shiftKey||a.target==='_blank') return;
    var u=new URL(a.href, location.href); if(u.origin!==location.origin||u.pathname!==location.pathname) return;
    e.preventDefault(); go(u.href, a, true);
  });
  window.addEventListener('popstate',function(){ var f=document.querySelector('form[data-soft]'); if(f) go(location.href, f, false); else location.reload(); });
  bind(document);
})();
</script>
</footer>

<script>
(function(){
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion:reduce)').matches) return;
  // auto-tag content sections as scroll-reveal targets
  var sel = '.sec-head, .grouprow, .sec .lgrid, .det-main > h2, .det-main table, .glance, .det-main details, .rev, .grpgrid';
  var els = Array.prototype.slice.call(document.querySelectorAll(sel));
  els.forEach(function(el){ if(!el.classList.contains('reveal')) el.classList.add('reveal'); });
  var revealAll = function(){ els.forEach(function(el){ el.classList.add('in'); }); document.documentElement.classList.remove('jsr'); };
  if (!('IntersectionObserver' in window)) { revealAll(); return; }
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.05, rootMargin: '0px 0px -30px 0px' });
  els.forEach(function(el){ io.observe(el); });
  // reveal whatever is already on-screen immediately
  requestAnimationFrame(function(){
    els.forEach(function(el){ var r=el.getBoundingClientRect(); if(r.top < (innerHeight||document.documentElement.clientHeight)){ el.classList.add('in'); io.unobserve(el); } });
  });
  // failsafe: never leave content hidden
  setTimeout(revealAll, 2500);
  window.addEventListener('load', function(){ setTimeout(revealAll, 400); });
})();
</script>
<script>
(function(){var t=document.getElementById('navToggle'),n=document.getElementById('mainNav');
if(!t||!n)return;
t.addEventListener('click',function(e){e.stopPropagation();var o=n.classList.toggle('open');t.setAttribute('aria-expanded',o?'true':'false');t.innerHTML=o?'<i class=\"fa-solid fa-xmark\"></i>':'<i class=\"fa-solid fa-bars\"></i>';});
document.addEventListener('click',function(e){if(n.classList.contains('open')&&!n.contains(e.target)&&e.target!==t){n.classList.remove('open');t.setAttribute('aria-expanded','false');t.innerHTML='<i class=\"fa-solid fa-bars\"></i>';}});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){n.classList.remove('open');t.setAttribute('aria-expanded','false');t.innerHTML='<i class=\"fa-solid fa-bars\"></i>';}});
})();
</script>
<script>
(function(){
  // the header gets more opaque once content is actually passing under it —
  // near-transparent at rest reads as a plain white bar without this
  var h = document.querySelector('.hdr');
  if (!h) return;
  var set = function(){ h.classList.toggle('is-scrolled', window.scrollY > 8); };
  set();
  window.addEventListener('scroll', set, { passive: true });
})();
</script>
<script src="<?= base_url('assets/js/phone.js') ?>?v=1" defer></script>
<?php $_body = hi_setting('seo_body_code'); if ($_body !== '') { echo $_body; } ?>
<?= view('frontend/_sitechrome') ?>
<script>
/* the "More" menu: opens on click (and on hover on laptops), closes on outside click / Esc */
(function(){
  var all=document.querySelectorAll('.nav-more'); if(!all.length) return;
  all.forEach(function(m){
    var b=m.querySelector('.nav-more-btn'); if(!b) return;
    function set(o){ all.forEach(function(x){ if(x!==m) x.classList.remove('open'); }); m.classList.toggle('open',o); b.setAttribute('aria-expanded',o?'true':'false'); }
    /* "More" is only a button; Commercial / Local places are links — a tap opens the menu first on touch screens, a second tap follows the link */
    b.addEventListener('click',function(e){
      if(b.tagName==='A'){ if(!matchMedia('(hover: none)').matches||m.classList.contains('open')) return; e.preventDefault(); }
      e.stopPropagation(); set(!m.classList.contains('open'));
    });
    document.addEventListener('click',function(e){ if(!m.contains(e.target)) set(false); });
    document.addEventListener('keydown',function(e){ if(e.key==='Escape') set(false); });
  });
})();
</script>
<script>
/* Shortlist (heart) and Recently viewed — kept in this visitor's own browser,
   and, for someone signed in, saved to their account too so it follows them
   to any phone or laptop. Storage can be blocked (private mode), so every
   read and write is guarded. */
(function(){
  var UID=<?= (int) session()->get('uid') ?>, BASE=<?= json_encode(base_url('/')) ?>, OWN='hi_saved_owner';
  function post(path,data){ try{ return fetch(BASE.replace(/\/$/,'')+'/'+path,{method:'POST',credentials:'same-origin',
      headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(data)}).then(function(r){ return r.json(); }); }catch(e){ return Promise.reject(e); } }
  function owner(){ try{ return localStorage.getItem(OWN)||''; }catch(e){ return ''; } }
  function setOwner(v){ try{ v?localStorage.setItem(OWN,v):localStorage.removeItem(OWN); }catch(e){} }
  function get(k){ try{ var v=JSON.parse(localStorage.getItem(k)||'[]'); return Array.isArray(v)?v:[]; }catch(e){ return []; } }
  function put(k,v){ try{ localStorage.setItem(k,JSON.stringify(v)); }catch(e){} }
  function item(el){ try{ return JSON.parse(el.getAttribute('data-save')||'{}'); }catch(e){ return {}; } }
  function has(id){ return get('hi_saved').some(function(x){ return +x.id===+id; }); }
  function mark(root){ (root||document).querySelectorAll('.hi-save').forEach(function(b){ var on=has(item(b).id); b.classList.toggle('on',on); b.setAttribute('aria-pressed',on?'true':'false'); b.title=on?'Saved':'Save'; }); }
  function toast(t){ var d=document.getElementById('hiToast'); if(!d){ d=document.createElement('div'); d.id='hiToast'; d.className='hi-toast'; document.body.appendChild(d); }
    d.textContent=t; d.classList.add('on'); clearTimeout(d._t); d._t=setTimeout(function(){ d.classList.remove('on'); },1800); }
  function toggle(b){ var it=item(b); if(!it.id) return; var list=get('hi_saved'), on=has(it.id);
    list=list.filter(function(x){ return +x.id!==+it.id; }); if(!on){ list.unshift(it); list=list.slice(0,30); }
    put('hi_saved',list); if(UID){ post('shortlist/set',{id:+it.id,on:!on}).catch(function(){}); } mark(document); b.classList.add('pop'); setTimeout(function(){ b.classList.remove('pop'); },400);
    toast(on?'Removed from your shortlist':'Saved to your shortlist'); document.dispatchEvent(new CustomEvent('hi:mine')); }
  document.addEventListener('click',function(e){ var b=e.target.closest&&e.target.closest('.hi-save'); if(!b) return; e.preventDefault(); e.stopPropagation(); toggle(b); },true);
  document.addEventListener('keydown',function(e){ var b=e.target.closest&&e.target.closest('.hi-save'); if(b&&(e.key==='Enter'||e.key===' ')){ e.preventDefault(); e.stopPropagation(); toggle(b); } },true);
  window.hiSaveMark=mark; window.hiMine={get:get,put:put};
  /* signed in: a list left by someone else on this browser is dropped, then
     this browser's saves are merged into the account and the account list
     comes back. Signed out after using an account: the browser copy is
     cleared (it is safe in the account) so the next person can't see it. */
  if(UID){
    if(owner() && owner()!==String(UID)) put('hi_saved',[]);
    post('shortlist/sync',{ids:get('hi_saved').map(function(x){ return +x.id; })}).then(function(d){
      if(d&&d.ok&&Array.isArray(d.items)){ put('hi_saved',d.items); setOwner(String(UID)); mark(document); document.dispatchEvent(new CustomEvent('hi:mine')); }
    }).catch(function(){});
  } else if(owner()){ put('hi_saved',[]); setOwner(''); }
  mark(document);
})();
</script>
<script>
/* the heart in the top bar: count + a small list of saved homes */
(function(){
  var wrap=document.getElementById('hdrSl'); if(!wrap) return;
  var btn=document.getElementById('hdrSlBtn'), pan=document.getElementById('hdrSlPan'), list=document.getElementById('hdrSlList'), n=document.getElementById('hdrSlN');
  function get(){ try{ var v=JSON.parse(localStorage.getItem('hi_saved')||'[]'); return Array.isArray(v)?v:[]; }catch(e){ return []; } }
  function esc(t){ var a=document.createElement('textarea'); a.innerHTML=t==null?'':String(t); var d=document.createElement('div'); d.textContent=a.value; return d.innerHTML; }
  function safe(u){ u=String(u||''); return /^(https?:)?\/\//i.test(u)||u.charAt(0)==='/'?u:''; }
  function draw(){
    var l=get(); n.textContent=l.length; n.hidden=!l.length; btn.classList.toggle('on',l.length>0);
    btn.querySelector('i').className=(l.length?'fa-solid':'fa-regular')+' fa-heart';
    list.innerHTML=l.length? l.slice(0,6).map(function(x){ var img=safe(x.i||x.img);
      return '<a class="hdr-sl-it" href="'+esc(safe(x.u||x.url)||'#')+'"><span class="hdr-sl-ph"'+(img?' style="background-image:url(\''+esc(img).replace(/'/g,'%27')+'\')"':'')+'></span>'
        +'<span class="hdr-sl-bd"><b>'+esc(x.n||x.name)+'</b><small>'+esc(x.s||x.loc||'Noida')+'</small></span>'
        +'<span class="hdr-sl-x" role="button" tabindex="0" data-id="'+(+x.id||0)+'" title="Remove"><i class="fa-solid fa-xmark"></i></span></a>'; }).join('')
      : '<p class="hdr-sl-empty"><i class="fa-regular fa-heart"></i> Tap the heart on any home to save it here.</p>';
  }
  function open(o){ pan.hidden=!o; btn.setAttribute('aria-expanded',o?'true':'false'); }
  btn.addEventListener('click',function(e){ e.stopPropagation(); open(pan.hidden); });
  document.addEventListener('click',function(e){ if(!wrap.contains(e.target)) open(false); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') open(false); });
  list.addEventListener('click',function(e){ var x=e.target.closest('.hdr-sl-x'); if(!x) return; e.preventDefault(); e.stopPropagation();
    var id=+x.dataset.id; try{ localStorage.setItem('hi_saved',JSON.stringify(get().filter(function(i){ return +i.id!==id; }))); }catch(err){}
    var uid=<?= (int) session()->get('uid') ?>; if(uid){ try{ fetch(<?= json_encode(rtrim(base_url('/'), '/') . '/shortlist/set') ?>,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({id:id,on:false})}); }catch(err){} }
    draw(); if(window.hiSaveMark) window.hiSaveMark(document); document.dispatchEvent(new CustomEvent('hi:mine')); });
  document.addEventListener('hi:mine',draw); window.addEventListener('storage',draw);
  draw();
})();
</script>
<script>
/* A row with fewer than 4 cards gets an action card in the gap:
   "See all …" (where that section has a see-all link), and on property rows
   with only 1–2 cards also "Post your property FREE". */
(function(){
  var POST=<?= json_encode(base_url('list')) ?>, here=location.pathname+location.search;
  function linkFor(g){
    var a=null, sec=g.closest('section')||document;
    var mt=g.closest('.mt-panel'); if(mt) a=mt.querySelector('.mt-all');
    if(!a && g.dataset.pt){ var p=sec.querySelector('.ptmore[data-pt="'+g.dataset.pt+'"] a'); if(p) a=p; }
    if(!a && g.dataset.sub){ var q=(g.closest('.np-wrap')||sec).querySelector('.np-more[data-sub="'+g.dataset.sub+'"] a'); if(q) a=q; }
    if(!a && !g.dataset.pt && !g.dataset.sub) a=sec.querySelector('.mt-all,.seeall,.secmore a');
    if(!a) return null;
    var href=a.getAttribute('href')||''; try{ var u=new URL(href,location.href); if(u.pathname+u.search===here) return null; }catch(e){}
    return {href:href, text:(a.textContent||'').replace(/\s+/g,' ').replace(/[→›»]/g,'').trim()};
  }
  function card(cls,href,icon,title,sub,go){
    var a=document.createElement('a'); a.className='lcard rich act-card '+cls; a.href=href;
    a.innerHTML='<span class="lc-ic" aria-hidden="true"><i class="fa-solid fa-'+icon+'"></i></span><div class="bd"><h3></h3><span class="lc-cat"></span><span class="lc-explore"></span></div>';
    a.querySelector('h3').textContent=title; a.querySelector('.lc-cat').textContent=sub;
    a.querySelector('.lc-explore').innerHTML=go+' <i class="fa-solid fa-arrow-right"></i>'; return a;
  }
  /* "See all" / "Post property" filler boxes are switched off: rows show real listings only */
  document.querySelectorAll('.lgrid.hi-fill:not(.aslist)').forEach(function(g){
    if(g.classList.contains('loc-tiles')) return;
    var max=document.body.classList.contains('is-home')?(innerWidth>=1500?6:(innerWidth>=1100?5:4)):4;
    var kids=[].filter.call(g.children,function(c){ return c.classList&&c.classList.contains('lcard'); });
    var n=kids.filter(function(c){ return !c.classList.contains('act-card'); }).length, slots=kids.length;
    if(!n || slots>=max) return;
    var prop=!!g.querySelector('.pcard,.hi-save'), l=linkFor(g);
    if(l && !g.querySelector('.act-all')){ g.appendChild(card('act-all',l.href,'arrow-right',l.text||'See all','More listings, filters and prices','View all')); slots++; }
    if(prop && slots<max && !g.querySelector('.act-post') && (n<=max-2 || !l)){ g.appendChild(card('act-post',POST,'house-circle-check','Own a property here?','List it FREE in about 5 minutes','Post property')); slots++; }
    /* still short of a full row: one more same-size box, never a stretched one */
    if(prop && slots<max && document.body.classList.contains('is-home')){ g.appendChild(card('act-area','<?= base_url('city') ?>','map-location-dot','Browse by sector','Homes, prices and places in each sector','Explore sectors')); slots++; }
  });
})();
</script>
</body>
</html>
