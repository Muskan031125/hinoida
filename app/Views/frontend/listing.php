<?= $this->extend('layouts/main') ?>
<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/listing.css') ?>?v=6">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
helper('image');
$loc   = $l['locality_name'] ?: 'Noida';
$phone = $l['phone'] ?? '';
$addr  = $l['address'] ?? '';
// some addresses already contain the locality — don't print it twice
$fullAddr = trim(implode(', ', array_filter([
    $addr,
    (stripos($addr, (string) $loc) === false ? $loc : ''),
    'Noida',
])));
$gallery = hi_photos($l, 5);
$cover = $gallery[0];
$inp = 'width:100%;border:1px solid var(--line);border-radius:9px;padding:10px 12px;font-size:14px;font-family:inherit;margin-bottom:8px';
$loggedIn = (bool) session()->get('uid');
$loginTo = static function (string $label, string $anchor): string {
    $u = urlencode(current_url() . '#' . $anchor);
    return '<div class="loginbox">
      <p><i class="fa-solid fa-lock"></i> Please log in to ' . $label . '.</p>
      <a href="' . hi_page('login') . '?redirect=' . $u . '" class="loginbtn">Log in / Sign up</a></div>';
};
?>
<section class="det-hero" style="--dethero:url('<?= esc($cover, 'attr') ?>')">
  <div class="wrap">
   <div class="lp-hero-grid">
    <div class="lp-hero-main">
    <div class="det-crumb"><a href="<?= base_url('/') ?>">Home</a> &rsaquo; <?php if (! empty($catNames[0])): ?><a href="<?= hi_category_url(esc($catNames[0]['slug'], 'url')) ?>"><?= esc($catNames[0]['name']) ?></a> &rsaquo; <?php endif; ?><span><?= esc($l['name']) ?></span></div>
    <h1 class="det-name"><?= esc($l['name']) ?>
      <?php if (! empty($l['is_featured'])): ?><span class="det-featured"><i class="fa-solid fa-crown"></i> Featured</span><?php endif; ?>
    </h1>
    <?php /* the original's head_point — one line of what the place is */ ?>
    <?php if (trim((string) ($l['head_point'] ?? '')) !== ''): ?>
      <p class="det-tagline"><?= esc(html_entity_decode((string) $l['head_point'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></p>
    <?php else: ?>
      <?php
        $_ex = trim(preg_replace('/\s+/', ' ', strip_tags(hi_rich($l['description'] ?? ''))));
        if (mb_strlen($_ex) > 150) { $_ex = rtrim(mb_substr($_ex, 0, 150), " ,.;") . '…'; }
      ?>
      <?php if ($_ex !== ''): ?><p class="det-tagline"><?= esc(html_entity_decode($_ex, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></p><?php endif; ?>
    <?php endif; ?>
    <?php /* one calm line: open status first, then the plain facts */ ?>
    <div class="lh-meta">
      <?php if (! empty($hours)): ?>
        <span class="lh-open <?= $hours['is_open'] ? 'is-open' : 'is-shut' ?>"><i></i><?= $hours['is_open'] ? 'Open now' : 'Closed' ?><?php if (! empty($hours['label'])): ?><em><?= esc($hours['label']) ?></em><?php endif; ?></span>
      <?php endif; ?>
      <?php /* category · sector · New on HiNoida · views line removed on every listing */ ?>
    </div>
    <?php
      /* Key facts as chips — only what is really stored for this listing:
         its most specific type, sector, rating (or "New listing") and its
         first three amenities. */
      $_chips = [];
      $_tp = $catNames ? html_entity_decode((string) $catNames[count($catNames) - 1]['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
      foreach (['pg/' => 'PG & Hostel', 'hostel' => 'PG & Hostel', 'rental listing' => 'For rent', 'commercial propert' => 'Commercial space'] as $_k => $_v) {
          if (stripos($_tp, $_k) !== false) { $_tp = $_v; break; }
      }
      if ($_tp !== '') { $_chips[] = ['tag', $_tp]; }
      $_chips[] = ['location-dot', $loc];
      $_chips[] = $rating['count'] ? ['star', $rating['avg'] . ' (' . (int) $rating['count'] . ' reviews)'] : ['wand-magic-sparkles', 'New listing'];
      foreach (array_slice($amenities ?? [], 0, 3) as $_a) {
          $_ic = preg_replace('/^(fas|far|fab|fa-solid|fa-regular|fa-brands)\s+fa-/', '', trim((string) ($_a['icon'] ?? '')));
          $_nm = html_entity_decode((string) $_a['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
          $_nm = strtoupper($_nm) === $_nm ? ucwords(strtolower($_nm)) : $_nm;   // "WIFI" → "Wifi"
          $_chips[] = [preg_match('/^[a-z0-9-]+$/', $_ic) ? $_ic : 'circle-check', $_nm];
      }
    ?>
    <div class="lh-chips">
      <?php foreach ($_chips as [$_ci, $_ct]): ?>
        <span class="lh-chip<?= $_ci === 'star' ? ' is-rate' : '' ?>"><i class="fa-solid fa-<?= esc($_ci) ?>"></i> <?= esc($_ct) ?></span>
      <?php endforeach; ?>
    </div>
    <?php
      $_dirUrl = (! empty($l['latitude']) && ! empty($l['longitude']))
        ? 'https://www.google.com/maps/dir/?api=1&destination=' . (float) $l['latitude'] . ',' . (float) $l['longitude']
        : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($l['name'] . ', ' . $fullAddr);
    ?>
    <?php /* contact info as two tidy rows instead of four boxes */ ?>
    <?php /* phone + address box removed on every listing (address is in Location below) */ ?>
    <div class="lp-actions lh-actions">
      <?php if ($phone): ?>
        <a class="lp-act primary" data-track="phone" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="fa-solid fa-phone"></i> Call now</a>
        <a class="lp-act wa" data-track="whatsapp" target="_blank" rel="noopener" href="https://wa.me/<?= esc(preg_replace('/[^0-9]/', '', $phone)) ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
      <?php endif; ?>
      <?php /* only Call now + WhatsApp in the banner, on every listing */ ?>
    </div>
    </div>

    <?php /* photo slider — moves on its own every 3 seconds, click opens full view */ ?>
    <?php $_gal = array_slice($gallery, 0, 6); $_gn = count($_gal); ?>
    <aside class="lp-slider" id="lpSlider" data-count="<?= $_gn ?>">
      <div class="gallery-grid lp-slides">
        <?php foreach ($_gal as $gi => $gsrc): ?>
          <a href="<?= esc($gsrc) ?>" data-lb><img src="<?= esc($gsrc) ?>" alt="<?= esc($l['name']) ?> photo <?= $gi + 1 ?>"<?= $gi === 0 || str_starts_with($gsrc, 'data:') ? '' : ' loading="lazy"' ?>></a>
        <?php endforeach; ?>
      </div>
      <?php if ($_gn > 1): ?>
        <button type="button" class="lp-sl-btn lp-sl-prev" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="lp-sl-btn lp-sl-next" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="lp-sl-foot">
          <div class="lp-dots"><?php for ($i = 0; $i < $_gn; $i++): ?><button type="button" aria-label="Photo <?= $i + 1 ?>"<?= $i === 0 ? ' class="on"' : '' ?>></button><?php endfor; ?></div>
          <span class="lp-sl-count"><i class="fa-regular fa-images"></i> <b>1</b> / <?= $_gn ?></span>
        </div>
        <div class="lp-sl-bar"><i></i></div>
      <?php endif; ?>
    </aside>
   </div>
  </div>
</section>

<nav class="lp-nav" id="lpNav" hidden><div class="wrap"></div></nav>

<div class="wrap det-grid lpage">
  <div class="det-main">
    <h2 style="margin:26px 0 8px">About <?= esc($l['name']) ?></h2>
    <?php
      /* Descriptions are stored double-encoded ("&lt;p&gt;…&amp;#39;s"), so
         escaping them printed the tags on the page. Decode first, then render
         the owner's paragraphs as markup rather than flattening them. */
      $_about = hi_rich($l['description'] ?? '');
    ?>
    <?php
      /* Many owners type their services as " -Bridal Mehandi -Arabic Mehandi …"
         inside the paragraph. Pull those out as chips so the text reads. */
      $_plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($_about), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
      $_parts = preg_split('/\s+[-–•]\s*(?=\p{Lu})/u', $_plain);
      $_svc   = [];
      if (count($_parts) >= 4) {
          $_intro = array_shift($_parts);
          // drop a trailing "…Provide Services" fragment — the chips say it
          $_cut = preg_split('/(?<=[.!?])\s+/u', $_intro);
          if (count($_cut) > 1 && preg_match('/servic|offer|provide/i', end($_cut))) { array_pop($_cut); $_intro = implode(' ', $_cut); }
          foreach ($_parts as $_x) { $_x = trim(rtrim($_x, ' .,;')); if ($_x !== '' && mb_strlen($_x) <= 40) { $_svc[] = preg_replace('/\s*etc$/i', '', $_x); } }
      }
      if (count($_svc) < 3) { $_svc = []; }
    ?>
    <?php if ($_about !== '' && $_svc): ?>
      <div class="prose lp-more-text" style="color:var(--body);line-height:1.7"><p><?= esc($_intro) ?></p></div>
      <div class="lp-svc"><span class="lp-svc-l">Services offered</span>
        <?php foreach ($_svc as $_x): ?><span class="lp-chip"><i class="fa-solid fa-check"></i> <?= esc($_x) ?></span><?php endforeach; ?>
      </div>
    <?php elseif ($_about !== ''): ?>
      <div class="prose lp-more-text" style="color:var(--body);line-height:1.7"><?= $_about ?></div>
    <?php else: ?>
      <p style="color:var(--body);line-height:1.7"><?= esc($l['name'] . ' is a ' . strtolower($catNames[0]['name'] ?? 'business') . ' located in ' . $loc . ', Noida. Find the full address, phone number, opening hours, photos, amenities, genuine customer reviews and directions on this page. You can also send an enquiry or booking request directly to ' . $l['name'] . ' from here.') ?></p>
    <?php endif; ?>

    <!-- AT A GLANCE — one dense, organised quick-facts panel -->
    <?php ob_start(); ?>
    <h2 style="margin:28px 0 12px"><?= esc(hi_c('listing.at_a_glance', 'At a glance')) ?></h2>
    <?php
      // ONLY facts that appear nowhere else on the page.
      // Rating / open-now / address / category are in the header; hours, amenities,
      // services and contact each have their own section — so they are not repeated here.
      /* Only facts the business or the site actually has. The old panel filled
         itself with generated values (capacity, prices, parking, lift…) that
         nobody had entered — customers could be misled by them. */
      $glance = [];
      $gkey = hi_group_key($l['_cat_name'] ?? ($catNames[0]['name'] ?? ''));
      if (! empty($catNames[0]['name'])) $glance[] = ['layer-group', 'Category', html_entity_decode((string) $catNames[0]['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')];
      $glance[] = ['location-dot', 'Area', $loc . ', Noida'];
      if (! empty($amenities)) $glance[] = ['list-check', 'Amenities', count($amenities) . ' listed'];
      if (! empty($catalog))   $glance[] = ['tags', 'Services listed', count($catalog) . ' with prices'];
      if (! empty($l['website'])) $glance[] = ['globe', 'Website', preg_replace('~^https?://(www\.)?~', '', rtrim((string) $l['website'], '/'))];
      $_yr = (int) preg_replace('/\D/', '', (string) ($established ?? ''));
      if ($_yr >= 1995 && $_yr <= (int) date('Y')) $glance[] = ['calendar-day', 'On HiNoida since', (string) $_yr];
      if (! empty($totalViews)) $glance[] = ['eye', 'Page views', number_format($totalViews)];
    ?>
    <div class="glance">
      <?php foreach ($glance as $s): ?>
        <div class="glance-i">
          <i class="fa-solid fa-<?= $s[0] ?>"></i>
          <div><span class="gl-l"><?= esc($s[1]) ?></span><span class="gl-v"><?= esc($s[2]) ?></span></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php $_glanceHtml = ob_get_clean(); if (count($glance) >= 2) echo $_glanceHtml; ?>

    <?php if (! empty($catalog)): ?>
      <h2 style="margin:30px 0 12px"><?= esc(hi_catalog_heading($gkey)) ?></h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px">
        <?php foreach ($catalog as $sv): ?>
          <div style="border:1px solid var(--line);border-radius:12px;padding:14px 16px;background:#fff;display:flex;gap:12px">
            <?php if (! empty($sv['image'])): ?><img src="<?= base_url($sv['image']) ?>" alt="<?= esc($sv['name']) ?>" style="width:52px;height:52px;border-radius:8px;object-fit:cover;flex:none"><?php endif; ?>
            <div style="flex:1;min-width:0">
              <div style="display:flex;justify-content:space-between;gap:8px;align-items:baseline"><b style="font-size:14.5px;color:var(--ink)"><?= esc($sv['name']) ?></b><?php if (! empty($sv['price'])): ?><span style="font-weight:800;color:var(--blue);white-space:nowrap"><?= esc($sv['price']) ?></span><?php endif; ?></div>
              <?php if (! empty($sv['description'])): ?><p style="margin:4px 0 0;font-size:13px;color:var(--muted)"><?= esc($sv['description']) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php
      // "Good to know" — derived, business-specific highlights (unique text = strong for SEO/AEO)
      $gtk = [];
      if ($rating['count'] >= 5 && (float) $rating['avg'] >= 4) $gtk[] = ['star', 'Highly rated', 'Rated ' . $rating['avg'] . '/5 by ' . $rating['count'] . ' customers in ' . $loc . '.'];
      if (! empty($verified)) $gtk[] = ['circle-check', 'Verified listing', 'Details confirmed by the HiNoida team.'];
      if ($hours) { $_td = hi_hours($l)[hi_today_key()];
        $_mins = 0; foreach ($_td as [$_o, $_c]) { $_mins += ($_c > $_o ? $_c - $_o : (1440 - $_o) + $_c); }
        $gtk[] = ['clock', 'Open ' . round($_mins / 60) . '+ hours today',
          'Serving customers from ' . $hours['open'] . ' to ' . $hours['close']
          . (empty($sameAllWeek) ? '. Hours vary by day — see the full week below.' : ', every day.')]; }
      if (! empty($l['phone'])) $gtk[] = ['phone', 'Instant contact', 'Call or WhatsApp directly — no waiting for a callback.'];
      if (! empty($catalog)) $gtk[] = ['tags', 'Transparent pricing', count($catalog) . ' services listed with clear prices.'];
      if (! empty($l['latitude'])) $gtk[] = ['location-dot', 'Easy to reach', 'Located in ' . $loc . ', Noida with map directions available.'];
      if (! empty($amenities)) $gtk[] = ['star', count($amenities) . ' amenities', 'Including ' . esc(implode(', ', array_slice(array_column($amenities, 'name'), 0, 3))) . '.'];
      // category-specific advice — unique per group, strong for AEO
      /* hi_group_highlights() added claims such as "Verified technicians" or
         "Parking on site" to every listing in a category — not shown any more. */
    ?>
    <?php if (! empty($gtk)): ?>
      <h2 style="margin:30px 0 12px"><?= esc(hi_c('listing.good_to_know', 'Good to know')) ?></h2>
      <div class="gtk-grid">
        <?php foreach (array_slice($gtk, 0, 8) as $g): ?>
          <div class="gtk-i">
            <i class="fa-solid fa-<?= $g[0] ?>"></i>
            <div><b><?= esc($g[1]) ?></b><span><?= $g[2] ?></span></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>


    <?php if ($amenities): ?>
      <h2 style="margin:30px 0 12px">Amenities &amp; features <span style="font-size:14px;color:var(--muted);font-weight:600">(<?= count($amenities) ?> available)</span></h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:7px">
        <?php foreach ($amenities as $a): ?>
          <span style="display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);color:var(--ink);border-radius:9px;padding:8px 12px;font-size:13px;font-weight:500"><i class="fa-solid fa-<?= esc($a['icon'] ?: 'circle-check') ?>" style="color:var(--blue);font-size:12px"></i> <?= esc($a['name']) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php
      /* Listing-type detail — the original's food_menu / hotel_room_specification
         / beauty_service / product_details, shown as one section whose heading
         and fields follow the type. */
      $_dt = hi_detail_table_for($l);
      $_dr = $_dt !== '' ? hi_details_for($l) : [];
    ?>
    <?php if ($_dr): $_dm = hi_detail_meta($_dt); ?>
      <h2 id="menu" style="margin:30px 0 12px"><?= esc($_dm['title']) ?> <span style="font-size:14px;color:var(--muted);font-weight:600">(<?= count($_dr) ?>)</span></h2>
      <div class="dlist">
        <?php foreach ($_dr as $_d): ?>
          <div class="ditem">
            <?php if (! empty($_d['photo'])): ?>
              <img class="ditem-ph" src="<?= esc(base_url($_d['photo'])) ?>" alt="<?= esc($_d['name']) ?>" loading="lazy">
            <?php endif; ?>
            <div class="ditem-bd">
              <div class="ditem-top">
                <b><?= esc($_d['name']) ?></b>
                <?php if (($_d['price'] ?? '') !== ''): ?><span class="ditem-pr"><?= esc(hi_money($_d['price'])) ?></span><?php endif; ?>
              </div>
              <?php
                // the descriptive column differs per type
                $_sub = trim((string) ($_d['items'] ?? $_d['description'] ?? $_d['variant'] ?? $_d['service_times'] ?? ''));
              ?>
              <?php if ($_sub !== ''): ?><p class="ditem-sub"><?= esc($_sub) ?></p><?php endif; ?>
              <?php if (! empty($_d['amenities'])): ?><p class="ditem-sub"><?= esc($_d['amenities']) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php /* video — ported from video_player.php, same package gate */ ?>
    <?php $_ve = hi_video_embed((string) ($l['video_url'] ?? '')); ?>
    <?php if ($_ve !== ''): ?>
      <h2 style="margin:30px 0 12px"><?= esc(hi_c('listing.video', 'Video')) ?></h2>
      <div style="position:relative;padding-bottom:56.25%;height:0;border-radius:14px;overflow:hidden;border:1px solid var(--line);background:#000">
        <iframe src="<?= esc($_ve) ?>" title="<?= esc($l['name']) ?> video" loading="lazy"
                style="position:absolute;inset:0;width:100%;height:100%;border:0"
                allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>
      </div>
    <?php endif; ?>
    <?php /* "What's nearby" — the everyday essentials around this place, with
             real measured distances. Anything in the Property tree gets it — a flat,
             a PG, a coworking desk, a shop — because what is within walking
             distance is half that decision, and means nothing on a restaurant. */ ?>
       <?php $_showNear = hi_is_residential($l); ?>
       
    <?php $_near = $_showNear ?   hi_nearby_venues($l, 5.0, 12, 120) : []; ?>
    <?php
      /* Places worth knowing about — metro, market, mall, park. Blocks, pockets
         and road names are in the same table but are not places anyone chooses a
         flat for, so only the recognised types appear. */
      $_marks  = []; // landmark chips (market, mall…) removed — the cards below cover it
      $_places = [];
    ?>
    <?php if ($_near || $_places || $_marks): ?>
      <h2 style="margin:30px 0 4px"><?= esc(hi_c('listing.what_s_nearby', 'What’s nearby')) ?></h2>
    <?php endif; ?>
    <?php /* landmarks first — malls, societies and blocks from the live area
             list, positioned by the listings tagged to them */ ?>
    <?php if ($_marks || $_places): ?>
      <div class="lmarks">
        <?php foreach ($_marks as $_m): ?>
          <?php $_u = $_m['slug'] !== '' ? base_url('a/' . $_m['slug']) : ''; ?>
          <<?= $_u ? 'a href="' . esc($_u, 'attr') . '"' : 'span' ?> class="lmark lmark-typed">
            <i class="fa-solid fa-<?= esc($_m['icon']) ?>"></i>
            <em><?= esc($_m['label']) ?></em>
            <?= esc($_m['name']) ?>
            <b><?= $_m['km'] < 1 ? round($_m['km'] * 1000 / 50) * 50 . ' m' : number_format($_m['km'], 1) . ' km' ?></b>
          </<?= $_u ? 'a' : 'span' ?>>
        <?php endforeach; ?>
        <?php foreach ($_places as $_pl): ?>
          <?php $_u = $_pl['slug'] !== '' ? base_url('a/' . $_pl['slug']) : ''; ?>
          <<?= $_u ? 'a href="' . esc($_u, 'attr') . '"' : 'span' ?> class="lmark">
            <i class="fa-solid fa-location-dot"></i>
            <?= esc($_pl['name']) ?>
            <b><?= $_pl['km'] < 1 ? round($_pl['km'] * 1000 / 50) * 50 . ' m' : number_format($_pl['km'], 1) . ' km' ?></b>
          </<?= $_u ? 'a' : 'span' ?>>
        <?php endforeach; ?>
      </div>
      <style>
      .lmarks{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 16px}
      .lmark{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;color:var(--body);
        border:1px solid var(--line);border-radius:20px;padding:7px 13px;background:#fff;text-decoration:none}
      a.lmark:hover{border-color:#b9cdf0;background:#f8fbff}
      .lmark i{color:var(--blue);font-size:11px}
      .lmark b{color:var(--blue);font-weight:700}
      .lmark-typed{border-color:#c9d8f2;background:#f7faff}
      .lmark em{font-style:normal;font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;
        color:var(--muted);margin-right:-2px}
      </style>
    <?php endif; ?>
    <?php if ($_near): ?>
      <?php /* What's nearby: the site's own listing cards (the same ones as on
               category and search pages), with a distance + type filter row and
               6 cards a page. Everything is in the page, so filtering and paging
               run in the browser with no reload. Class names use "nby-" because
               "nb-" is already taken by the news brief styles in site.css. */
        $_nt = [];
        foreach ($_near as $_n) { $_nt[$_n['label']] ??= $_n['icon']; }
        $_fmtKm = static fn ($km) => $km < 1 ? (round($km * 1000 / 50) * 50) . ' m' : number_format($km, 1) . ' km';
      ?>
      <div class="nby" id="nearbyBox">
        <div class="nby-filters">
          <div class="nby-row">
            <span class="nby-lbl">Distance</span>
            <?php foreach (['0.5' => '500 m', '1' => '1 km', '2' => '2 km', '5' => '5 km'] as $_v => $_t): ?>
              <button type="button" class="nby-chip nby-d<?= (string) $_v === '5' ? ' on' : '' ?>" data-km="<?= $_v ?>"><?= $_t ?></button>
            <?php endforeach; ?>
          </div>
          <div class="nby-row nby-types">
            <span class="nby-lbl">Type</span>
            <button type="button" class="nby-chip nby-t on" data-t="*">All <b>0</b></button>
            <?php foreach ($_nt as $_lb => $_ic): ?>
              <button type="button" class="nby-chip nby-t" data-t="<?= esc($_lb, 'attr') ?>"><i class="fa-solid fa-<?= esc($_ic) ?>"></i> <?= esc($_lb) ?> <b>0</b></button>
            <?php endforeach; ?>
          </div>
        </div>
        <p class="nby-info"></p>
        <div class="nby-grid">
          <?php foreach ($_near as $n): $np = $n['listing'];
            $_nm  = html_entity_decode((string) $np['name'], ENT_QUOTES | ENT_HTML5);
            // location: the sector/locality, or the start of the address
            $_loc = trim(html_entity_decode((string) ($np['locality_name'] ?? ''), ENT_QUOTES | ENT_HTML5));
            if ($_loc === '') { $_loc = mb_strimwidth(trim(html_entity_decode((string) ($np['address'] ?? ''), ENT_QUOTES | ENT_HTML5)), 0, 32, '…'); }
            $_cat = trim((string) ($np['_cat_name'] ?? '')) ?: $n['label'];
          ?>
            <?php
              $_tone = ['Hospital' => 'red', 'Pharmacy' => 'red', 'Diagnostics' => 'red',
                        'Restaurant' => 'orange', 'Cafe' => 'orange',
                        'School' => 'green', 'Gym' => 'teal', 'Salon' => 'pink',
                        'Shopping' => 'purple', 'Grocery' => 'purple', 'Hotel' => 'blue', 'Bank / ATM' => 'blue'][$n['label']] ?? 'blue';
            ?>
            <a class="nby-card" href="<?= hi_listing_url($np) ?>" data-t="<?= esc($n['label'], 'attr') ?>" data-km="<?= round($n['km'], 3) ?>">
              <span class="nby-ph">
                <img src="<?= esc(hi_cover($np)) ?>" alt="<?= esc($_nm) ?>" loading="lazy">
                <span class="nby-dist"><?= $_fmtKm($n['km']) ?></span>
              </span>
              <span class="nby-bd">
                <span class="nby-tag nby-<?= $_tone ?>"><i class="fa-solid fa-<?= esc($n['icon']) ?>"></i> <?= esc($n['label']) ?></span>
                <strong><?= esc($_nm) ?></strong>
                <?php if ($_loc !== ''): ?><span class="nby-loc"><i class="fa-solid fa-location-dot"></i> <?= esc($_loc) ?></span><?php endif; ?>
                <?php if (! empty($np['_rcount'])): ?>
                  <span class="nby-ft"><span class="nby-rt"><i class="fa-solid fa-star"></i> <?= esc($np['_ravg']) ?> <small>(<?= (int) $np['_rcount'] ?>)</small></span></span>
                <?php endif; ?>
              </span>
              <i class="fa-solid fa-chevron-right nby-go" aria-hidden="true"></i>
            </a>
          <?php endforeach; ?>
        </div>
        <p class="nby-empty" style="display:none">Nothing of this type within this distance. Try a wider distance.</p>
        <nav class="nby-pager" aria-label="Nearby pages"></nav>
      </div>
      <style>
      .nby-filters{display:flex;flex-direction:column;gap:10px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:#fbfcfe;margin:0 0 12px}
      .nby-row{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
      .nby-lbl{flex:0 0 70px;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:700}
      .nby-chip{display:inline-flex;align-items:center;gap:6px;font:600 13px/1 inherit;font-family:inherit;padding:8px 12px;
        border:1px solid var(--line);border-radius:20px;background:#fff;color:var(--body);cursor:pointer;white-space:nowrap}
      .nby-chip i{color:var(--blue);font-size:12px}
      .nby-chip b{font-size:11px;color:var(--muted);background:#eef1f6;border-radius:10px;padding:2px 7px}
      .nby-chip:hover{border-color:#b9cdf0;background:#f8fbff}
      .nby-chip.on{background:var(--blue);border-color:var(--blue);color:#fff}
      .nby-chip.on i{color:#fff}
      .nby-chip.on b{background:rgba(255,255,255,.22);color:#fff}
      .nby-chip.is-zero{opacity:.45}
      .nby-info{margin:0 0 10px;font-size:13px;color:var(--muted)}
      .nby-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
      .nby-card{display:flex;align-items:center;gap:13px;padding:10px;border:1px solid var(--line);border-radius:14px;background:#fff;
        text-decoration:none;color:inherit;min-width:0;box-shadow:0 1px 2px rgba(16,24,40,.04);
        transition:border-color .15s,box-shadow .15s,transform .15s}
      .nby-card:hover{border-color:#c9d8f2;box-shadow:0 8px 20px -8px rgba(20,40,80,.18);transform:translateY(-1px)}
      .nby-ph{position:relative;flex:0 0 84px;width:84px;height:84px;border-radius:11px;overflow:hidden;background:#eef2f7}
      .nby-ph img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s}
      .nby-card:hover .nby-ph img{transform:scale(1.06)}
      .nby-dist{position:absolute;left:6px;bottom:6px;background:rgba(255,255,255,.96);color:var(--blue);font-size:11px;font-weight:800;
        border-radius:20px;padding:2px 7px;box-shadow:0 1px 4px rgba(0,0,0,.18)}
      .nby-bd{flex:1;min-width:0;display:flex;flex-direction:column;align-items:flex-start;gap:4px}
      .nby-tag{display:inline-flex;align-items:center;gap:5px;max-width:100%;font-size:10.5px;font-weight:800;letter-spacing:.05em;
        text-transform:uppercase;border-radius:6px;padding:3px 7px;white-space:nowrap}
      .nby-tag i{font-size:10px}
      .nby-red{background:#fdecee;color:#c0283f}
      .nby-orange{background:#fff3e2;color:#b25d00}
      .nby-green{background:#e7f6ee;color:#16794a}
      .nby-teal{background:#e3f5f6;color:#0e7480}
      .nby-pink{background:#fdebf5;color:#b02a78}
      .nby-purple{background:#f0ebfd;color:#6232c4}
      .nby-blue{background:#eaf1fd;color:#1741a6}
      .nby-bd strong{max-width:100%;font-size:15px;line-height:1.25;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
      .nby-loc{max-width:100%;font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
      .nby-loc i{font-size:10px;margin-right:2px}
      .nby-ft{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted)}
      .nby-rt{font-weight:700;color:#1d2b45}
      .nby-rt i{color:#f5a623;font-size:11px}
      .nby-rt small{color:var(--muted);font-weight:500}
      .nby-dot{color:#c3ccdb}
      .nby-go{flex:none;color:#c3ccdb;font-size:12px;padding-right:4px;transition:color .15s,transform .15s}
      .nby-card:hover .nby-go{color:var(--blue);transform:translateX(2px)}
      .nby-empty{margin:4px 0 0;padding:22px;border:1px dashed var(--line);border-radius:12px;text-align:center;color:var(--muted);font-size:13.5px}
      .nby-pager{display:flex;justify-content:center;gap:6px;margin-top:16px}
      .nby-pager:empty{display:none}
      .nby-pg{min-width:34px;height:34px;padding:0 8px;font:600 13px/1 inherit;font-family:inherit;border:1px solid var(--line);
        border-radius:8px;background:#fff;color:var(--body);cursor:pointer}
      .nby-pg:hover:not(:disabled){border-color:#b9cdf0;color:var(--blue)}
      .nby-pg.on{background:var(--blue);border-color:var(--blue);color:#fff}
      .nby-pg:disabled{opacity:.4;cursor:default}
      .nby-gap{align-self:center;color:var(--muted);padding:0 2px}
      @media (max-width:760px){
        .nby-grid{grid-template-columns:minmax(0,1fr);gap:8px}
        .nby-ph{flex-basis:72px;width:72px;height:72px}
        .nby-bd strong{font-size:14px}
        .nby-lbl{flex-basis:100%}
        .nby-types{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none}
        .nby-types::-webkit-scrollbar{display:none}
        .nby-types .nby-lbl{display:none}
      }
      </style>
      <script>
      (function () {
        var box = document.getElementById('nearbyBox'); if (!box) return;
        var cells = [].slice.call(box.querySelectorAll('.nby-card')),
            types = [].slice.call(box.querySelectorAll('.nby-t')),
            dists = [].slice.call(box.querySelectorAll('.nby-d')),
            pager = box.querySelector('.nby-pager'), info = box.querySelector('.nby-info'),
            empty = box.querySelector('.nby-empty'),
            // 8 small cards (4 rows of 2) on a laptop, 5 on a phone
            PER = window.matchMedia('(max-width:760px)').matches ? 5 : 8, type = '*', km = 5, page = 1;

        function draw() {
          var inRange = cells.filter(function (c) { return +c.getAttribute('data-km') <= km; });
          types.forEach(function (b) {
            var t = b.getAttribute('data-t'),
                n = t === '*' ? inRange.length : inRange.filter(function (c) { return c.getAttribute('data-t') === t; }).length;
            b.querySelector('b').textContent = n;
            b.classList.toggle('is-zero', n === 0);
          });
          var shown = inRange.filter(function (c) { return type === '*' || c.getAttribute('data-t') === type; }),
              pages = Math.max(1, Math.ceil(shown.length / PER));
          if (page > pages) page = pages;
          var from = (page - 1) * PER;
          cells.forEach(function (c) { c.style.display = 'none'; });
          shown.slice(from, from + PER).forEach(function (c) { c.style.display = ''; });
          empty.style.display = shown.length ? 'none' : '';
          info.textContent = shown.length ? 'Showing ' + (from + 1) + '–' + Math.min(from + PER, shown.length) + ' of ' + shown.length + ' places' : '';
          pager.innerHTML = '';
          if (pages < 2) return;
          function btn(label, p, opts) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'nby-pg' + (opts && opts.on ? ' on' : ''); b.innerHTML = label;
            b.disabled = !!(opts && opts.off);
            b.addEventListener('click', function () { page = p; draw(); box.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
            pager.appendChild(b);
          }
          btn('&lsaquo;', page - 1, { off: page === 1 });
          var last = 0;
          for (var i = 1; i <= pages; i++) {
            if (i !== 1 && i !== pages && Math.abs(i - page) > 1) continue;
            if (last && i - last > 1) { var g = document.createElement('span'); g.className = 'nby-gap'; g.textContent = '…'; pager.appendChild(g); }
            btn(i, i, { on: i === page }); last = i;
          }
          btn('&rsaquo;', page + 1, { off: page === pages });
        }
        types.forEach(function (b) { b.addEventListener('click', function () {
          types.forEach(function (x) { x.classList.toggle('on', x === b); }); type = b.getAttribute('data-t'); page = 1; draw(); }); });
        dists.forEach(function (b) { b.addEventListener('click', function () {
          dists.forEach(function (x) { x.classList.toggle('on', x === b); }); km = +b.getAttribute('data-km'); page = 1; draw(); }); });
        draw();
      })();
      </script>
    <?php endif; ?>

    <?php /* Where the two sides meet: a dealer's own flats, on their page. */ ?>
    <?php if (! empty($theirProps)): ?>
      <h2 style="margin:30px 0 12px">Properties from <?= esc($l['name']) ?> <span style="color:var(--muted);font-weight:500">(<?= count($theirProps) ?>)</span></h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(212px,1fr));gap:14px">
        <?php foreach ($theirProps as $_tp): ?><?= view('frontend/_propertycard', ['l' => $_tp]) ?><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php /* A flat has no opening time — it has a floor, a rent and a size.
             Everything property-specific lives in this one block. */ ?>
    <?php if (hi_is_property($l)): $_p = hi_property_of((int) $l['id']); if ($_p): ?>
      <?php /* the mockup's trust markers — each one read from real data, so a
               listing with no RERA number simply does not carry the badge */ ?>
      <?php $_bd = hi_property_badges($_p, $l); ?>
      <?php if ($_bd): ?>
        <div class="pbadges">
          <?php foreach ($_bd as [$_bl, $_bi, $_bk]): ?>
            <span class="pbadge pbadge-<?= esc($_bk) ?>"><i class="fa-solid fa-<?= esc($_bi) ?>"></i> <?= esc($_bl) ?></span>
          <?php endforeach; ?>
        </div>
        <style>
        .pbadges{display:flex;gap:8px;flex-wrap:wrap;margin:22px 0 0}
        .pbadge{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;
          padding:6px 13px;border-radius:20px;border:1px solid #c4e8d1;background:#e9f7ee;color:#12653a}
        .pbadge-new{border-color:#f2ddb4;background:#fdf5e6;color:#8a6d1a}
        .pbadge i{font-size:11px}
        </style>
      <?php endif; ?>
      <h2 style="margin:22px 0 12px"><?= esc(hi_c('listing.property_details', 'Property details')) ?></h2>
      <div class="pspecs">
        <?php
          $_labs = hi_property_labels();
          $_show = array_merge(['deal' => 'Offered for', 'kind' => 'Type'], []);
          echo '<div class="pspec"><span>Offered for</span><b>'
             . esc(hi_property_deals()[$_p['deal'] ?? ''] ?? '—') . '</b></div>';
          echo '<div class="pspec"><span>Type</span><b>'
             . esc(hi_property_kinds()[$_p['kind'] ?? ''][0] ?? '—') . '</b></div>';
          echo '<div class="pspec"><span>Price</span><b>' . esc(hi_property_price($_p)) . '</b></div>';
          foreach (hi_property_fields(hi_property_group((string) ($_p['kind'] ?? ''))) as $_f) {
            // area_unit is already folded into the Area row above it
            if (in_array($_f, ['price', 'price_period', 'area_unit'], true)) { continue; }
            $_v = $_p[$_f] ?? null;
            if ($_v === null || $_v === '' || $_v === '0.00') { continue; }
            [$_lbl, $_type, $_opts] = $_labs[$_f];
            if ($_type === 'check') { $_v = $_v ? 'Yes' : 'No'; }
            elseif ($_type === 'select') { $_v = $_opts[$_v] ?? $_v; }
            elseif ($_f === 'carpet_area') { $_v = number_format((float) $_v, 0) . ' ' . ($_labs['area_unit'][2][$_p['area_unit'] ?? 'sqft'] ?? ''); }
            elseif ($_f === 'deposit') { if ((float) $_v <= 0) { continue; } $_v = '₹' . hi_inr($_v); }
            echo '<div class="pspec"><span>' . esc($_lbl) . '</span><b>' . esc((string) $_v) . '</b></div>';
          }
        ?>
      </div>
      <style>
      .pspecs{display:grid;grid-template-columns:repeat(auto-fit,minmax(168px,1fr));gap:1px;
        background:var(--line);border:1px solid var(--line);border-radius:12px;overflow:hidden}
      .pspec{background:#fff;padding:13px 15px}
      .pspec span{display:block;font-size:11.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted)}
      .pspec b{display:block;margin-top:3px;font-size:14.5px;color:var(--ink);font-weight:600}
      </style>
    <?php endif; ?>
    <?php elseif (! empty($week) && array_filter($week)): $tk = hi_today_key(); $di = 0; ?>
      <h2 style="margin:30px 0 12px"><?= esc(hi_c('listing.opening_hours', 'Opening hours')) ?></h2>
      <div style="border:1px solid var(--line);border-radius:14px;overflow:hidden;max-width:420px">
        <?php foreach (hi_days() as $dk => $day): $isToday = $dk === $tk; $shut = empty($week[$dk]); ?>
          <div style="display:flex;justify-content:space-between;padding:11px 16px;font-size:14px;<?= $isToday ? 'background:#eaf1ff;font-weight:700' : '' ?><?= $di < 6 ? ';border-bottom:1px solid var(--line)' : '' ?>">
            <span style="color:<?= $isToday ? 'var(--blue)' : 'var(--body)' ?>"><?= $day ?><?= $isToday ? ' (Today)' : '' ?></span>
            <span style="color:<?= $shut ? 'var(--muted)' : 'var(--ink)' ?>"><?= esc(hi_hours_label($week[$dk])) ?></span>
          </div>
        <?php $di++; endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (! empty($l['latitude']) && ! empty($l['longitude'])): ?>
      <h2 style="margin:30px 0 12px"><?= esc(hi_c('listing.location_directions', 'Location & directions')) ?></h2>
      <div style="border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:var(--shadow);background:#fff">
        <iframe title="Map of <?= esc($l['name']) ?>" width="100%" height="300" style="border:0;display:block" loading="lazy"
          src="https://www.openstreetmap.org/export/embed.html?bbox=<?= (float)$l['longitude']-0.01 ?>,<?= (float)$l['latitude']-0.007 ?>,<?= (float)$l['longitude']+0.01 ?>,<?= (float)$l['latitude']+0.007 ?>&layer=mapnik&marker=<?= (float)$l['latitude'] ?>,<?= (float)$l['longitude'] ?>"></iframe>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;flex-wrap:wrap">
          <span style="font-size:14px;color:var(--body)"><i class="fa-solid fa-location-dot" style="color:var(--blue)"></i> <?= esc($addr ? $addr . ', ' : '') ?><?= esc($loc) ?>, Noida</span>
          <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)$l['latitude'] ?>,<?= (float)$l['longitude'] ?>" target="_blank" rel="noopener" style="background:var(--blue);color:#fff;padding:9px 16px;border-radius:9px;font-weight:700;font-size:13.5px;white-space:nowrap"><i class="fa-solid fa-diamond-turn-right"></i> Get directions</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if (hi_addon('reviews')): ?>
    <h2 id="reviews" style="margin:30px 0 12px"><?= esc(hi_c('listing.ratings_reviews', 'Ratings & reviews')) ?></h2>
    <?php /* shown even at zero — the original does, and an empty breakdown
             tells a visitor there is nothing yet rather than looking broken */ ?>
    <?php if (true): ?>
      <div style="display:flex;gap:26px;align-items:center;border:1px solid var(--line);border-radius:14px;padding:20px;margin-bottom:16px;background:#fff;flex-wrap:wrap">
        <div style="text-align:center;min-width:110px">
          <div style="font-size:44px;font-weight:800;color:var(--ink);line-height:1"><?= esc($rating['avg']) ?></div>
          <div style="color:#f5a623;margin:4px 0"><?php for ($i=1;$i<=5;$i++) echo $i <= round($rating['avg']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>'; ?></div>
          <?php $_bq = hi_rating_quality((float) $rating['avg'], (int) $rating['count']); ?>
          <?php if ($_bq !== ''): ?><div style="font-weight:800;color:var(--ink);font-size:14px;margin-top:2px"><?= esc($_bq) ?></div><?php endif; ?>
          <div style="font-size:12.5px;color:var(--muted)"><?= $rating['count'] ?> review<?= $rating['count']>1?'s':'' ?></div>
        </div>
        <div style="flex:1;min-width:220px">
          <?php foreach ([5,4,3,2,1] as $star): $pct = $rating['count'] ? round(($ratingDist[$star] / $rating['count']) * 100) : 0; ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
              <span style="font-size:12.5px;color:var(--muted);width:34px"><?= $star ?> ★</span>
              <span style="flex:1;height:8px;background:#eef1f6;border-radius:6px;overflow:hidden"><span style="display:block;height:100%;width:<?= $pct ?>%;background:#f5a623"></span></span>
              <span style="font-size:12px;color:var(--muted);width:34px;text-align:right"><?= $ratingDist[$star] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($loggedIn): ?>
      <form method="post" action="<?= base_url('l/' . (int) $l['id'] . '/review') ?>" style="border:1px solid var(--line);border-radius:12px;padding:16px;margin-bottom:16px;background:#fff">
        <div style="margin-bottom:8px"><label style="font-size:13px;font-weight:600;color:#444;margin-right:8px"><?= esc(hi_c('listing.lbl_your_rating', 'Your rating')) ?></label>
          <select name="rating" style="border:1px solid var(--line);border-radius:8px;padding:6px 10px"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> ★</option><?php endfor; ?></select></div>
        <textarea name="comment" rows="3" required minlength="10" placeholder="Share your experience — what was good, what could be better? (min 10 characters)" class="sc-in"></textarea>
        <button type="submit" style="background:var(--blue);color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:600;cursor:pointer"><?= esc(hi_c('listing.btn_post_review', 'Post review')) ?></button>
      </form>
    <?php else: echo $loginTo('write a review', 'reviews'); endif; ?>
    <?php if (! $reviews): ?><p style="color:var(--muted)">No reviews yet — be the first to review <?= esc($l['name']) ?>.</p><?php else: ?>
      <div class="rev-list">
      <?php
        $palette = ['#1741a6','#0b8a3d','#b23a3a','#8a5a1a','#5b3a9a','#0a7a8a'];
        foreach ($reviews as $ri => $rv):
          $rt = (int) ($rv['review_rating'] ?? 0);
          $rn = trim((string) ($rv['reviewer_name'] ?? '')) ?: 'HiNoida User';
          $rc = $rv['review_comment'] ?? ($rv['comment'] ?? '');
          $col = $palette[$ri % count($palette)];
      ?>
        <div class="rev">
          <div class="rev-av" style="background:<?= $col ?>"><?= esc(strtoupper(substr($rn, 0, 1))) ?></div>
          <div class="rev-bd">
            <div class="rev-top"><b><?php if (! empty($rv['reviewer_id'])): ?><a href="<?= base_url('u/' . (int) $rv['reviewer_id']) ?>" style="color:inherit"><?= esc($rn) ?></a><?php else: ?><?= esc($rn) ?><?php endif; ?></b>
              <span class="rev-stars"><?php for ($i=1;$i<=5;$i++) echo $i<=$rt?'<i class="fa-solid fa-star"></i>':'<i class="fa-regular fa-star"></i>'; ?></span>
            </div>
            <?php if ($rc !== ''): ?><p><?= esc($rc) ?></p><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php endif; /* addon: reviews */ ?>

    <?php /* Report a listing — ported from Home::report_this_listing */ ?>
    <div style="margin-top:14px">
      <?php if ($loggedIn): ?>
        <button type="button" id="reportOpen" style="background:none;border:0;padding:0;color:var(--muted);font-size:13px;cursor:pointer;font-family:inherit;text-decoration:underline">
          <i class="fa-solid fa-flag"></i> Report this listing
        </button>
        <form method="post" action="<?= base_url('l/' . (int) $l['id'] . '/report') ?>" id="reportForm" hidden
              style="margin-top:12px;border:1px solid var(--line);border-radius:12px;padding:16px;background:#fff;max-width:520px">
          <b style="display:block;color:var(--ink);margin-bottom:4px">What's wrong with this listing?</b>
          <p style="margin:0 0 10px;font-size:12.5px;color:var(--muted)"><?= esc(hi_c('listing.p_wrong_details_permanently_closed_duplicate_o', 'Wrong details, permanently closed, duplicate, or something offensive.')) ?></p>
          <textarea name="report" rows="3" required minlength="10" class="sc-in" placeholder="<?= esc(hi_c('listing.ph_tell_us_what_we_should_check_min_10_characte', 'Tell us what we should check (min 10 characters)'), 'attr') ?>"></textarea>
          <button type="submit" class="abtn" style="background:#fdecec;color:#b3262f;border-color:#f5c6c6;margin-top:6px"><?= esc(hi_c('listing.btn_submit_report', 'Submit report')) ?></button>
          <button type="button" id="reportCancel" class="abtn" style="margin-top:6px"><?= esc(hi_c('listing.btn_cancel', 'Cancel')) ?></button>
        </form>
        <script>
        (function(){
          var o=document.getElementById('reportOpen'), f=document.getElementById('reportForm'), c=document.getElementById('reportCancel');
          o.addEventListener('click',function(){ f.hidden=false; o.hidden=true; });
          c.addEventListener('click',function(){ f.hidden=true; o.hidden=false; });
        })();
        </script>
      <?php else: ?>
        <a href="<?= hi_page('login') ?>?redirect=<?= urlencode(current_url()) ?>" style="color:var(--muted);font-size:13px;text-decoration:underline">
          <i class="fa-solid fa-flag"></i> Report this listing
        </a>
      <?php endif; ?>
    </div>

    <?php if (hi_addon('qa')): ?>
    <!-- Community Q&A -->
    <h2 id="qa" style="margin:30px 0 12px"><?= esc(hi_c('listing.questions_answers', 'Questions & answers')) ?></h2>
    <?php if ($loggedIn): ?>
      <form method="post" action="<?= base_url('l/' . (int) $l['id'] . '/ask') ?>" style="border:1px solid var(--line);border-radius:12px;padding:16px;margin-bottom:16px;background:#fff">
        <textarea name="question" rows="2" required minlength="10" placeholder="Ask <?= esc($l['name']) ?> a question..." class="sc-in"></textarea>
        <button type="submit" style="background:var(--blue);color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:600;cursor:pointer"><?= esc(hi_c('listing.btn_post_question', 'Post question')) ?></button>
      </form>
    <?php else: echo $loginTo('ask a question', 'qa'); endif; ?>
    <?php if (! $qa): ?><p style="color:var(--muted)"><?= esc(hi_c('listing.p_no_questions_yet_be_the_first_to_ask', 'No questions yet — be the first to ask.')) ?></p><?php else: ?>
      <?php foreach ($qa as $item): ?>
        <?php $qName = $item['name'] ?: 'HiNoida user'; ?>
        <div class="qa-item">
          <div class="qa-head">
            <span class="qa-av" style="background:<?= esc(hi_avatar_bg($qName), 'attr') ?>"><?= esc(hi_initial($qName)) ?></span>
            <div class="qa-body">
              <b class="qa-who"><?= esc($qName) ?></b>
              <p class="qa-q"><?= esc($item['question']) ?></p>
            </div>
          </div>
          <?php if (! empty($item['answer'])): ?>
            <div class="qa-answer">
              <span class="qa-av qa-av-biz"><i class="fa-solid fa-store"></i></span>
              <div><b><?= esc($l['name']) ?></b><p><?= esc($item['answer']) ?></p></div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; /* addon: qa */ ?>
  </div>

  <aside style="position:sticky;top:82px;align-self:start">
    <div class="sidecard">
      <h3 class="sc-h"><i class="fa-solid fa-headset"></i> Contact</h3>
      <?php if ($phone): ?>
        <a class="sc-btn sc-call" data-track="phone" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="fa-solid fa-phone"></i> <?= esc($phone) ?></a>
        <a class="sc-btn sc-wa" data-track="whatsapp" target="_blank" rel="noopener" href="https://wa.me/<?= esc(preg_replace('/[^0-9]/', '', $phone)) ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
      <?php endif; ?>
      <?php if (! empty($l['latitude']) && ! empty($l['longitude'])): ?>
        <a class="sc-btn sc-dir" data-track="direction" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= (float) $l['latitude'] ?>,<?= (float) $l['longitude'] ?>"><i class="fa-solid fa-diamond-turn-right"></i> Directions</a>
      <?php endif; ?>

      <?php if ($addr || ! empty($l['website']) || ! empty($l['email'])): ?>
        <div class="sc-meta">
          <?php if ($addr): ?><div class="sc-row"><i class="fa-solid fa-location-dot"></i><span><?= esc($fullAddr) ?></span></div><?php endif; ?>
          <?php if (! empty($l['website'])): ?><div class="sc-row"><i class="fa-solid fa-globe"></i><a href="<?= esc($l['website']) ?>" target="_blank" rel="noopener"><?= esc(preg_replace('~^https?://~', '', $l['website'])) ?></a></div><?php endif; ?>
          <?php if (! empty($l['email'])): ?><div class="sc-row"><i class="fa-solid fa-envelope"></i><a href="mailto:<?= esc($l['email']) ?>"><?= esc($l['email']) ?></a></div><?php endif; ?>
        </div>
      <?php endif; ?>

      <?php
        // The business's own social profiles, stored per listing. 469 listings
        // have real links here that nothing displayed. Blank entries are common,
        // so filter before deciding whether to show the row at all.
        $_ls = [];
        foreach ((array) ($social ?? []) as $_k => $_v) {
            $_v = trim((string) $_v);
            if ($_v === '' || ! preg_match('~^https?://~i', $_v)) { continue; }
            $_ls[$_k === 'twitter' ? 'x-twitter' : strtolower($_k)] = $_v;
        }
      ?>
      <?php if ($_ls): ?>
        <div style="display:flex;gap:9px;margin-top:12px;flex-wrap:wrap">
          <?php foreach ($_ls as $_ic => $_u): ?>
            <a href="<?= esc($_u, 'attr') ?>" target="_blank" rel="noopener noreferrer nofollow"
               aria-label="<?= esc(ucfirst(str_replace('-', ' ', $_ic))) ?>"
               style="width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:#eef3fc;color:var(--blue)">
              <i class="fa-brands fa-<?= esc($_ic) ?>"></i>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div style="margin-top:14px">
        <?php if (hi_addon('vcard')): ?><a class="sc-btn sc-ghost" href="<?= base_url('card/' . (int) $l['id']) ?>"><i class="fa-solid fa-qrcode"></i> Digital card &amp; QR</a><?php endif; ?>
        <a class="sc-btn sc-ghost<?= ! empty($isSaved) ? ' on' : '' ?>" href="<?= base_url('l/' . (int) $l['id'] . '/save') ?>"><i class="fa-<?= ! empty($isSaved) ? 'solid' : 'regular' ?> fa-heart"></i> <?= ! empty($isSaved) ? 'Saved' : 'Save this place' ?></a>
        <?php if (hi_addon('compare')): ?><button type="button" class="sc-btn sc-ghost" data-cmp-add="<?= (int) $l['id'] ?>"><i class="fa-solid fa-scale-balanced"></i> Add to compare</button><?php endif; ?>
      </div>

      <?php $share = urlencode(hi_listing_url($l)); $stitle = urlencode($l['name'] . ' — on HiNoida'); ?>
      <div style="margin-top:13px">
        <span class="sc-lbl">Share</span>
        <div class="sc-share">
          <a title="WhatsApp" target="_blank" rel="noopener" href="https://wa.me/?text=<?= $stitle ?>%20<?= $share ?>" style="background:#19b85a"><i class="fa-brands fa-whatsapp"></i></a>
          <a title="Facebook" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= $share ?>" style="background:#1877f2"><i class="fa-brands fa-facebook-f"></i></a>
          <a title="X" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url=<?= $share ?>&text=<?= $stitle ?>" style="background:#111"><i class="fa-brands fa-x-twitter"></i></a>
          <button type="button" title="Copy link" onclick="navigator.clipboard&amp;&amp;navigator.clipboard.writeText('<?= hi_listing_url($l) ?>');this.innerHTML='<i class=\'fa-solid fa-check\'></i>'" style="background:var(--blue)"><i class="fa-solid fa-link"></i></button>
        </div>
      </div>

      <?php if (! empty($social)): ?>
        <div class="sc-social">
          <?php foreach ($social as $net => $url): $icon = ['facebook'=>'facebook-f','instagram'=>'instagram','twitter'=>'x-twitter','x'=>'x-twitter','youtube'=>'youtube','linkedin'=>'linkedin-in','whatsapp'=>'whatsapp'][$net] ?? 'globe'; ?>
            <a href="<?= esc($url) ?>" target="_blank" rel="noopener" title="<?= esc(ucfirst($net)) ?>"><i class="fa-brands fa-<?= $icon ?>"></i></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if (hi_addon('claims') && \App\Controllers\Claim::claimable($l)): ?>
      <div class="sidecard claimcard">
        <b>Is this your business?</b>
        <p>Claim <?= esc($l['name']) ?> to edit its details, add photos and reply to enquiries.</p>
        <a class="sc-btn sc-ghost" href="<?= base_url('claim/' . (int) $l['id']) ?>"><i class="fa-solid fa-check-double"></i> Claim this business</a>
      </div>
    <?php endif; ?>

    <?php
      // Keyed off the category group. listing_type holds the literal string
      // "listing" for nearly every row, so keying off it meant every business
      // got the same generic form.
      [$flavour, $head, $cta, $dateLabel] = hi_booking_flavour($gkey);
      [$partyName, $partyType, $partyPh]  = hi_booking_party($flavour);
      // Practo-style time slots generated from opening hours
      $slots = [];
      // slots follow today's hours, not a single week-long pair
      $_sd = hi_hours($l)[hi_today_key()] ?? null;
      // a slot in every open range, so a lunch closing is not offered
      if ($_sd) { foreach ($_sd as [$_so, $_sc]) { $_end = $_sc > $_so ? $_sc : $_sc + 1440;
        for ($t = $_so; $t < $_end; $t += 30) { $slots[] = hi_hhmm($t); } } }
    ?>
    <?php if (hi_addon('bookings')): ?>
    <div id="enquiry" class="sidecard">
      <h3 class="sc-h"><i class="fa-solid fa-paper-plane"></i> <?= $head ?></h3>
      <?php /* No login wall. The form already asks for a name and a phone
               number, which is everything the owner needs to call back — and
               a visitor who arrived from a search will not create an account
               just to ask one question. */ ?>
      <?php if (! $loggedIn): ?>
        <p class="sc-guest">Send this straight to the owner. <a href="<?= esc(hi_page('login'), 'attr') ?>?redirect=<?= urlencode(current_url()) ?>">Sign in</a> to keep track of your enquiries.</p>
        <style>.sc-guest{font-size:12.5px;color:var(--muted);margin:0 0 12px;line-height:1.5}
        .sc-guest a{color:var(--blue);font-weight:600}</style>
      <?php endif; ?>
      <?php if (true): ?>
      <form method="post" action="<?= base_url('l/' . (int) $l['id'] . '/book') ?>">
        <input type="hidden" name="booking_type" value="<?= $flavour ?>">
        <input name="name" placeholder="<?= esc(hi_c('listing.ph_your_name', 'Your name'), 'attr') ?>" required class="sc-in">
        <?= view('frontend/_phone', ['name'=>'phone', 'required'=>true, 'ph'=>'Phone number']) ?>
        <?php $today = date('Y-m-d'); ?>
        <?php if ($flavour === 'hotel'): ?>
          <label class="sc-flab"><?= esc($dateLabel) ?></label>
          <input name="booking_date" type="date" min="<?= $today ?>" class="sc-in">
          <label class="sc-flab"><?= esc(hi_c('listing.lbl_check_out', 'Check-out')) ?></label>
          <input name="booking_time" type="date" min="<?= $today ?>" class="sc-in">
        <?php else: ?>
          <label class="sc-flab"><?= esc($dateLabel) ?></label>
          <input name="booking_date" type="date" min="<?= $today ?>" class="sc-in">
          <?php if ($slots): ?>
            <label class="sc-flab"><?= esc(hi_c('listing.lbl_preferred_time', 'Preferred time')) ?></label>
            <select name="booking_time" class="sc-in"><option value="">Any time</option><?php foreach ($slots as $sl): ?><option><?= esc($sl) ?></option><?php endforeach; ?></select>
          <?php elseif ($flavour !== 'enquiry'): ?>
            <label class="sc-flab">Preferred time</label>
            <input name="booking_time" type="time" class="sc-in">
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($partyName !== ''): ?>
          <input name="<?= $partyName ?>" type="<?= $partyType ?>"<?= $partyType === 'number' ? ' min="1"' : '' ?> placeholder="<?= esc($partyPh, 'attr') ?>" class="sc-in">
        <?php endif; ?>
        <textarea name="message" rows="3" required minlength="10" placeholder="<?= esc(hi_c('listing.ph_tell_them_what_you_need_min_10_characters', 'Tell them what you need (min 10 characters)'), 'attr') ?>" class="sc-in"></textarea>
        <button type="submit" class="sc-btn sc-call" style="margin-top:4px"><i class="fa-solid fa-paper-plane"></i> <?= esc($cta) ?></button>
      </form>
      <?php endif; ?>
    </div>
    <?php endif; /* addon: bookings */ ?>
  </aside>
</div>

<?php if (! empty($faqs)): ?>
<section class="sec">
  <div class="wrap" style="max-width:900px">
    <div class="sec-head"><h2><?= esc(hi_c('listing.frequently_asked_questions', 'Frequently asked questions')) ?></h2></div>
    <?php foreach ($faqs as $f): ?>
      <details style="border:1px solid var(--line);border-radius:12px;padding:0 18px;margin-bottom:10px;background:#fff">
        <summary style="cursor:pointer;padding:15px 0;font-weight:700;color:var(--ink);list-style:none"><?= esc($f[0]) ?></summary>
        <p style="margin:0 0 16px;color:var(--body);line-height:1.6"><?= esc($f[1]) ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($similar): ?>
<section class="sec" style="background:var(--bg)">
  <div class="wrap">
    <?php
      /* For a flat the category is the dealer's ("Property Dealers"), which
         made this read "Similar property dealers" above a row of flats. */
      if (hi_is_property($l)) {
        $_pk = (string) (hi_property_of((int) $l['id'])['kind'] ?? '');
        $_similarLabel = strtolower(hi_property_kinds()[$_pk][0] ?? 'property') . 's';
        $_similarLabel = str_replace(['flat / apartments', 'villa / independent houses',
                                      'pg / hostel beds', 'plot / lands', 'coworking seats'],
                                     ['flats', 'houses', 'PG rooms', 'plots', 'coworking spaces'], $_similarLabel);
      } else {
        $_similarLabel = strtolower($catNames[0]['name'] ?? 'places');
      }
    ?>
    <div class="sec-head"><h2>Similar <?= esc($_similarLabel) ?> in Noida</h2></div>
    <div class="lscroll">
      <?php foreach ($similar as $s): if ((int) $s['id'] === (int) $l['id']) { continue; } ?>
        <?= view('frontend/_card', ['l' => $s]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php /* "Also nearby in <sector>" removed — the What's nearby section covers it */ ?>

<?php
  // ---- JSON-LD structured data (SEO / AEO / GEO) ----
  /* A flat is not a LocalBusiness — it has no opening hours, no payment methods
     and nobody rates it. Google reads a listed home as an Accommodation with an
     Offer, which is what carries the price and the size into a rich result. */
  $_isProp = hi_is_property($l);
  if ($_isProp) {
    $_p    = hi_property_of((int) $l['id']);
    $_kind = (string) ($_p['kind'] ?? '');
    $_type = in_array($_kind, ['flat', 'floor'], true) ? 'Apartment'
           : ($_kind === 'villa' ? 'House'
           : ($_kind === 'pg' ? 'Room'
           : (in_array($_kind, ['office','shop','warehouse','coworking'], true) ? 'Place' : 'Accommodation')));
    $ld = [
      '@context' => 'https://schema.org',
      '@type'    => $_type,
      'name'     => $l['name'],
      'address'  => ['@type'=>'PostalAddress','streetAddress'=>$addr,'addressLocality'=>$loc,'addressRegion'=>'Uttar Pradesh','addressCountry'=>'IN'],
      'url'      => hi_listing_url($l),
    ];
    if (! empty($_p['bedrooms']))    { $ld['numberOfBedroomsTotal'] = (int) $_p['bedrooms']; }
    if (! empty($_p['bathrooms']))   { $ld['numberOfBathroomsTotal'] = (int) $_p['bathrooms']; }
    if (! empty($_p['carpet_area'])) {
      $_u = ['sqft'=>'FTK','sqyd'=>'YDK','sqm'=>'MTK','acre'=>'ACR'][$_p['area_unit'] ?? 'sqft'] ?? 'FTK';
      $ld['floorSize'] = ['@type'=>'QuantitativeValue','value'=>(float) $_p['carpet_area'],'unitCode'=>$_u];
    }
    if (! empty($_p['price'])) {
      $ld['offers'] = [
        '@type' => 'Offer',
        'price' => (float) $_p['price'],
        'priceCurrency' => 'INR',
        'availability' => 'https://schema.org/InStock',
        'url' => hi_listing_url($l),
      ];
      if (($_p['price_period'] ?? '') === 'month') { $ld['offers']['priceSpecification'] = [
        '@type' => 'UnitPriceSpecification', 'price' => (float) $_p['price'],
        'priceCurrency' => 'INR', 'unitCode' => 'MON',
      ]; }
    }
  } else {
  $ld = [
    '@context' => 'https://schema.org',
    '@type'    => 'LocalBusiness',
    'name'     => $l['name'],
    'address'  => ['@type'=>'PostalAddress','streetAddress'=>$addr,'addressLocality'=>$loc,'addressRegion'=>'Uttar Pradesh','addressCountry'=>'IN'],
    'url'      => hi_listing_url($l),
  ];
  }
  if (! empty($l['phone']))   $ld['telephone'] = $l['phone'];
  if (! empty($l['website'])) $ld['sameAs'] = array_values($social ?: []) + [$l['website']];
  if (! empty($l['email']))   $ld['email'] = $l['email'];
  if (! empty($l['latitude']) && ! empty($l['longitude'])) $ld['geo'] = ['@type'=>'GeoCoordinates','latitude'=>(float)$l['latitude'],'longitude'=>(float)$l['longitude']];
  // opening hours, price range and payment methods describe a shop, not a flat
  if (! $_isProp) {
    $_spec = hi_hours_schema($l);
    if ($_spec) $ld['openingHoursSpecification'] = $_spec;
    $ld['priceRange'] = '₹₹';
    $ld['paymentAccepted'] = 'Cash, UPI, Cards';
  }
  if ($rating['count']) $ld['aggregateRating'] = ['@type'=>'AggregateRating','ratingValue'=>$rating['avg'],'reviewCount'=>$rating['count'],'bestRating'=>5];
  // per-review schema → eligible for star rich snippets
  if (! empty($reviews)) {
    $ld['review'] = [];
    foreach (array_slice($reviews, 0, 5) as $rv) {
      $rt = (int) ($rv['review_rating'] ?? 0); if ($rt < 1) continue;
      $ld['review'][] = [
        '@type' => 'Review',
        'author' => ['@type' => 'Person', 'name' => $rv['reviewer_name'] ?? 'HiNoida user'],
        'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $rt, 'bestRating' => 5],
        'reviewBody' => (string) ($rv['review_comment'] ?? ($rv['comment'] ?? '')),
      ];
    }
  }
  $ld['image'] = hi_cover($l);

  $bc = ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>base_url('/')],
  ]];
  if (! empty($catNames[0])) $bc['itemListElement'][] = ['@type'=>'ListItem','position'=>2,'name'=>$catNames[0]['name'],'item'=>hi_category_url($catNames[0]['slug'])];
  $bc['itemListElement'][] = ['@type'=>'ListItem','position'=>count($bc['itemListElement'])+1,'name'=>$l['name'],'item'=>hi_listing_url($l)];

  $faqLd = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
  foreach ($faqs as $f) $faqLd['mainEntity'][] = ['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/ld+json"><?= json_encode($bc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php if (! empty($faqs)): ?><script type="application/ld+json"><?= json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script><?php endif; ?>

<section class="sec" id="recentSec" style="display:none"><div class="wrap">
  <div class="sec-head"><h2><?= esc(hi_c('listing.recently_viewed', 'Recently viewed')) ?></h2></div>
  <div class="lscroll" id="recentRow"></div>
</div></section>
<script>
(function(){
  try{
    var cur={id:<?= (int)$l['id'] ?>,name:<?= json_encode($l['name']) ?>,loc:<?= json_encode($loc) ?>,img:<?= json_encode($cover) ?>,url:<?= json_encode(hi_listing_url($l)) ?>};
    var key='hi_recent',list=JSON.parse(localStorage.getItem(key)||'[]');
    var others=list.filter(function(x){return x.id!==cur.id;});
    if(others.length){
      var row=document.getElementById('recentRow');
      others.slice(0,8).forEach(function(x){
        var a=document.createElement('a');a.className='lcard';a.href=x.url||('<?= base_url('l/') ?>'+x.id);   // entries saved before the URL change still resolve
        var hue=(x.id*37)%40+205, tile='linear-gradient('+(110+(x.id%5)*14)+'deg,hsl('+hue+',72%,24%),hsl('+hue+',64%,44%))';
        var ph = x.img
          ? '<span class="ph"><img src="'+String(x.img).replace(/"/g,'&quot;')+'" alt=""></span>'
          : '<span class="ph" style="background:'+tile+'"></span>';
        a.innerHTML=ph+'<span class="bd"><b>'+(x.name||'').replace(/</g,'')+'</b><span class="loc"><i class="fa-solid fa-location-dot"></i> '+(x.loc||'Noida')+'</span></span>';
        row.appendChild(a);
      });
      document.getElementById('recentSec').style.display='';
    }
    list=others;list.unshift(cur);localStorage.setItem(key,JSON.stringify(list.slice(0,12)));
  }catch(e){}
})();
</script>
<script>
/* Listing page layout: group every heading in the main column with the
   content under it into a card, give it an icon, and build the section bar. */
(function(){
  var main=document.querySelector('.lpage .det-main'); if(!main) return;
  var icons=[['about','circle-info'],['glance','bolt'],['good to know','lightbulb'],['amenit','list-check'],
    ['menu','utensils'],['room','bed'],['service','tags'],['price','tags'],['product','box-open'],['video','circle-play'],
    ['nearby','map-pin'],['propert','building'],['detail','clipboard-list'],['hour','clock'],['location','map-location-dot'],
    ['review','star'],['question','comments'],['faq','circle-question']];
  var nav=document.getElementById('lpNav'), bar=nav&&nav.querySelector('.wrap'), cards=[];
  Array.prototype.slice.call(main.children).forEach(function(h){
    if(h.tagName!=='H2') return;
    var card=document.createElement('section'); card.className='lp-card';
    var txt=(h.childNodes[0]&&h.childNodes[0].textContent||h.textContent).trim();
    var low=txt.toLowerCase(), ic='circle-dot';
    for(var i=0;i<icons.length;i++){ if(low.indexOf(icons[i][0])>-1){ ic=icons[i][1]; break; } }
    card.id=h.id?('s-'+h.id):('s-'+low.replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'').slice(0,40));
    h.parentNode.insertBefore(card,h);
    var n=h; while(n){ var nx=n.nextSibling; card.appendChild(n); n=nx; if(n&&n.nodeType===1&&n.tagName==='H2') break; }
    var b=document.createElement('i'); b.className='lp-ic fa-solid fa-'+ic; h.insertBefore(b,h.firstChild);
    if(card.textContent.replace(txt,'').trim()===''&&!card.querySelector('img,iframe,video,form')){ card.remove(); return; }
    cards.push(card);
    if(bar){ var a=document.createElement('a'); a.href='#'+card.id;
      var short=[['about','About'],['glance','Overview'],['good to know','Highlights'],['amenit','Amenities'],['menu','Menu'],
        ['nearby','Nearby'],['propert','Properties'],['detail','Details'],['hour','Hours'],['location','Location'],
        ['review','Reviews'],['question','Q&A'],['video','Video']], lab=txt;
      for(var k=0;k<short.length;k++){ if(low.indexOf(short[k][0])>-1){ lab=short[k][1]; break; } }
      a.innerHTML='<i class="fa-solid fa-'+ic+'"></i>'; a.appendChild(document.createTextNode(' '+lab)); a.title=txt;
      bar.appendChild(a); }
  });
  /* interactive sections: click a heading to fold it; long ones show the top
     part with a "Show more" button. Reviews, Q&A and the map stay fully open. */
  cards.forEach(function(c,ci){
    var h=c.querySelector('h2'); if(!h) return;
    var body=document.createElement('div'); body.className='lp-body';
    while(h.nextSibling){ body.appendChild(h.nextSibling); }
    c.appendChild(body);
    var chev=document.createElement('i'); chev.className='fa-solid fa-chevron-down lp-chev'; h.appendChild(chev);
    h.classList.add('lp-tog'); h.setAttribute('role','button'); h.tabIndex=0; h.setAttribute('aria-expanded','true');
    function toggle(){ var closed=c.classList.toggle('lp-closed'); h.setAttribute('aria-expanded',closed?'false':'true'); }
    h.addEventListener('click',function(e){ if(e.target.closest('a,button')) return; toggle(); });
    h.addEventListener('keydown',function(e){ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); toggle(); } });
    if(/review|qa|question|location|nearby/.test(c.id)) return;
    var lim = ci===0 ? 190 : 300;
    if(body.scrollHeight > lim+60){
      c.classList.add('lp-clamp'); body.style.maxHeight=lim+'px';
      var b=document.createElement('button'); b.type='button'; b.className='lp-morebtn';
      b.innerHTML='Show more <i class="fa-solid fa-chevron-down"></i>';
      b.addEventListener('click',function(){
        var open=c.classList.toggle('lp-open');
        body.style.maxHeight = open ? body.scrollHeight+'px' : lim+'px';
        b.innerHTML = open ? 'Show less <i class="fa-solid fa-chevron-up"></i>' : 'Show more <i class="fa-solid fa-chevron-down"></i>';
        if(!open) c.scrollIntoView({behavior:'smooth',block:'start'});
      });
      c.appendChild(b);
    }
  });
  /* jumping to a section (bar or "Write a review") always opens it */
  function openCard(id){ var c=document.getElementById(id); if(c){ c.classList.remove('lp-closed'); } }
  document.querySelectorAll('a[href^="#s-"]').forEach(function(a){ a.addEventListener('click',function(){ openCard(a.getAttribute('href').slice(1)); }); });
  if(bar&&cards.length>2){ nav.hidden=false;
    var links=bar.querySelectorAll('a');
    if('IntersectionObserver' in window){
      var io=new IntersectionObserver(function(es){ es.forEach(function(e){ if(!e.isIntersecting) return;
        links.forEach(function(a){ var on=a.getAttribute('href')==='#'+e.target.id; a.classList.toggle('on',on);
          if(on&&a.scrollIntoView){ bar.scrollTo({left:a.offsetLeft-bar.offsetWidth/2+a.offsetWidth/2,behavior:'smooth'}); } }); }); },
        {rootMargin:'-140px 0px -60% 0px'});
      cards.forEach(function(c){ io.observe(c); });
    }
  }
  /* "+N photos" badge on the last gallery tile */
  var g=main.querySelector('.gallery-grid'), ga=g?g.querySelectorAll('a'):[];
  if(ga.length>1){ var t=document.createElement('span'); t.className='lp-more';
    t.innerHTML='<i class="fa-regular fa-images"></i> '+ga.length+' photos'; ga[ga.length-1].appendChild(t); }
  /* share */
  var sh=document.getElementById('lpShare');
  if(sh) sh.addEventListener('click',function(){
    var d={title:document.title,url:location.href};
    if(navigator.share){ navigator.share(d).catch(function(){}); }
    else if(navigator.clipboard){ navigator.clipboard.writeText(location.href).then(function(){
      sh.innerHTML='<i class="fa-solid fa-check"></i>'; sh.title='Link copied'; setTimeout(function(){ sh.innerHTML='<i class="fa-solid fa-share-nodes"></i>'; sh.title='Share'; },2000); }); }
  });
})();
</script>
<script>
/* hero photo slider: auto every 3s, pauses on hover / hidden tab, arrows + dots */
(function(){
  var box=document.getElementById('lpSlider'); if(!box) return;
  var track=box.querySelector('.lp-slides'), n=track.children.length; if(n<2) return;
  var dots=box.querySelectorAll('.lp-dots button'), num=box.querySelector('.lp-sl-count b'),
      bar=box.querySelector('.lp-sl-bar i'), i=0, timer=null, DELAY=3000,
      still=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
  function go(k){ i=(k+n)%n; track.style.transform='translateX('+(-100*i)+'%)';
    dots.forEach(function(d,j){ d.classList.toggle('on',j===i); }); if(num) num.textContent=i+1;
    if(bar){ bar.style.transition='none'; bar.style.width='0'; void bar.offsetWidth;
      if(timer){ bar.style.transition='width '+DELAY+'ms linear'; bar.style.width='100%'; } } }
  function play(){ if(still) return; stop(); timer=setInterval(function(){ go(i+1); },DELAY); go(i); }
  function stop(){ if(timer){ clearInterval(timer); timer=null; } if(bar){ bar.style.transition='none'; bar.style.width='0'; } }
  box.querySelector('.lp-sl-next').addEventListener('click',function(){ go(i+1); play(); });
  box.querySelector('.lp-sl-prev').addEventListener('click',function(){ go(i-1); play(); });
  dots.forEach(function(d,j){ d.addEventListener('click',function(){ go(j); play(); }); });
  box.addEventListener('mouseenter',stop); box.addEventListener('mouseleave',play);
  document.addEventListener('visibilitychange',function(){ document.hidden?stop():play(); });
  /* swipe on phones */
  /* swipe with a finger OR drag with the mouse */
  var x0=null, lastX=0, moved=false;
    box.addEventListener('dragstart',function(e){ e.preventDefault(); });
  box.addEventListener('pointerdown',function(e){ if(e.target.closest('button')) return; x0=lastX=e.clientX; moved=false; stop(); box.classList.add('dragging'); });
  box.addEventListener('pointermove',function(e){ if(x0===null) return; lastX=e.clientX; var dx=lastX-x0; if(Math.abs(dx)>5) moved=true;
    track.style.transition='none'; track.style.transform='translateX(calc('+(-100*i)+'% + '+dx+'px))'; });
  function endDrag(e){ if(x0===null) return; if(e.type!=='pointercancel' && e.clientX) lastX=e.clientX; var dx=lastX-x0; x0=null; box.classList.remove('dragging'); track.style.transition='';
    if(Math.abs(dx)>50){ go(i+(dx<0?1:-1)); } else { go(i); } play(); }
  box.addEventListener('pointerup',endDrag); box.addEventListener('pointerleave',endDrag); box.addEventListener('pointercancel',endDrag);
  box.addEventListener('click',function(e){ if(moved){ e.preventDefault(); e.stopPropagation(); moved=false; } },true);
  play();
})();
</script>
<!-- sticky mobile action bar -->
<div class="det-stickybar">
  <?php if (! empty($l['phone'])): ?>
    <a class="sb-call" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $l['phone'])) ?>"><i class="fa-solid fa-phone"></i> Call</a>
    <a class="sb-wa" target="_blank" rel="noopener" href="https://wa.me/<?= esc(preg_replace('/[^0-9]/', '', $l['phone'])) ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
  <?php endif; ?>
  <a class="sb-enq" href="#enquiry"><i class="fa-solid fa-paper-plane"></i> Enquire</a>
</div>
<script>
(function(){
 var id=<?= (int) $l['id'] ?>;
 document.querySelectorAll('[data-track]').forEach(function(el){
   el.addEventListener('click',function(){
     try{ navigator.sendBeacon('<?= base_url('l/') ?>'+id+'/track', new URLSearchParams({type:el.dataset.track})); }catch(e){}
   });
 });
})();
</script>
<?= view('frontend/_lightbox') ?>
<?= $this->endSection() ?>
