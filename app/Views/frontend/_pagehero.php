<?php
/**
 * Shared two-column page banner.
 *
 * Left:  breadcrumb, title, description, badges, quick facts, buttons, links,
 *        and an optional extra block (a search form, type chips…).
 * Right: a photo slider that moves on its own every 3 seconds.
 *
 * Every value is optional except the title.
 *
 *   crumbs  [[label, url|null], ...]          last one is the current page
 *   title   string
 *   sub     string                            one or two lines under the title
 *   chips   [[icon, text, class?], ...]       small pills (class: 'hot', 'ok', 'blue')
 *   facts   [[icon, label, value, wide?], ...] value is escaped; wide spans the row
 *   actions [[icon, label, href, class?], ...] class: 'primary', 'wa'
 *   links   [[icon, label, href], ...]
 *   extra   string                            raw HTML placed after the badges
 *   slides  [[src, href|null, caption|null], ...]
 */
$crumbs  = $crumbs  ?? [];
$sub     = trim((string) ($sub ?? ''));
$chips   = $chips   ?? [];
$facts   = array_values(array_filter($facts ?? [], static fn ($f) => trim((string) ($f[2] ?? '')) !== ''));
$actions = $actions ?? [];
$links   = [];   // the small link row under the buttons is switched off everywhere — it only repeated the menu
$extra   = $extra   ?? '';
$slides  = array_values(array_filter($slides ?? [], static fn ($s) => ! empty($s[0])));

// pages with no photos of their own still get a moving picture of the city
// List pages (stock pictures, or slides that link to items) get the light,
// compact header; detail pages with their own photo keep the dark banner.
$_light = ($tone ?? '') === 'light';
$_noslide = ! empty($noslider);   // no photo card on the right — just a background photo
$_stock = ! $slides;   // no photos of its own — stock city pictures (hidden on phones)
if (! $slides) {
    foreach (['food-1' => 'Food & dining', 'shopping-1' => 'Shopping', 'hotels-1' => 'Hotels & stays',
              'medical-1' => 'Health & medical', 'education-1' => 'Education', 'realestate-1' => 'Property'] as $_f => $_c) {
        $slides[] = [base_url('assets/img/cat/' . $_f . '.jpg'), null, $_c];
    }
}
$slides = array_slice($slides, 0, 6);
$_n     = count($slides);
$_bg    = ! empty($bg) ? $bg : $slides[0][0];
?>
<?php if ($_light): ?>
<section class="ph-lite phl-center">
  <div class="wrap">
    <?php if ($crumbs): ?>
      <div class="phl-crumb">
        <a href="<?= base_url('/') ?>">Home</a>
        <?php foreach ($crumbs as $_i => $_c): ?>
          <i class="fa-solid fa-chevron-right"></i><?php if (! empty($_c[1]) && $_i < count($crumbs) - 1): ?><a href="<?= esc($_c[1], 'attr') ?>"><?= esc($_c[0]) ?></a><?php else: ?><span><?= esc($_c[0]) ?></span><?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="phl-row">
      <div class="phl-txt">
        <?php if (! empty($eyebrow)): ?><span class="phl-eb"><i class="fa-solid fa-<?= esc($eyebrow[0], 'attr') ?>"></i> <?= esc($eyebrow[1]) ?></span><?php endif; ?>
        <h1 class="phl-title"><?= $title ?></h1>
        <?php if ($sub !== ''): ?><p class="phl-sub"><?= esc($sub) ?></p><?php endif; ?>
        <?php if ($chips || $facts): ?>
          <div class="phl-pills">
            <?php foreach ($chips as $_c): ?>
              <span class="phl-pill<?= ! empty($_c[2]) ? ' is-' . esc($_c[2], 'attr') : '' ?>"><?php if (! empty($_c[0])): ?><i class="fa-solid fa-<?= esc($_c[0], 'attr') ?>"></i><?php endif; ?><?= esc($_c[1]) ?></span>
            <?php endforeach; ?>
            <?php foreach ($facts as $_f): ?>
              <span class="phl-pill" title="<?= esc($_f[1], 'attr') ?>"><i class="fa-solid fa-<?= esc($_f[0], 'attr') ?>"></i><b><?= esc((string) $_f[2]) ?></b> <?= esc($_f[1]) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php if ($actions || $links): ?>
        <div class="phl-act">
          <?php foreach ($actions as $_a): ?>
            <a class="phl-btn<?= ! empty($_a[3]) && str_contains((string) $_a[3], 'primary') ? ' is-primary' : '' ?>" href="<?= esc($_a[2], 'attr') ?>"<?= str_starts_with((string) $_a[2], 'http') ? ' target="_blank" rel="noopener"' : '' ?>><i class="fa-solid fa-<?= esc($_a[0], 'attr') ?>"></i> <?= esc($_a[1]) ?></a>
          <?php endforeach; ?>
          <?php foreach ($links as $_k): ?>
            <a class="phl-btn is-ghost" href="<?= esc($_k[2], 'attr') ?>"><i class="fa-regular fa-<?= esc($_k[0], 'attr') ?>"></i> <?= esc($_k[1]) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php if ($extra !== ''): ?><div class="phl-extra"><?= $extra ?></div><?php endif; ?>
  </div>
</section>
<?php return; endif; ?>
<section class="det-hero ph-hero<?= $_noslide ? ' ph-noslide' : '' ?>" style="--dethero:url('<?= esc($_bg, 'attr') ?>')">
  <div class="wrap">
   <div class="lp-hero-grid">
    <div class="lp-hero-main">
      <?php if ($crumbs): ?>
        <div class="det-crumb">
          <a href="<?= base_url('/') ?>">Home</a>
          <?php foreach ($crumbs as $_i => $_c): ?>
            &rsaquo; <?php if (! empty($_c[1]) && $_i < count($crumbs) - 1): ?><a href="<?= esc($_c[1], 'attr') ?>"><?= esc($_c[0]) ?></a><?php else: ?><span><?= esc($_c[0]) ?></span><?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h1 class="det-name"><?= $title ?></h1>
      <?php if ($sub !== ''): ?><p class="det-tagline"><?= esc($sub) ?></p><?php endif; ?>

      <?php if ($chips): ?>
        <div class="det-meta">
          <?php foreach ($chips as $_c): ?>
            <span class="dm-loc ph-chip<?= ! empty($_c[2]) ? ' ph-' . esc($_c[2], 'attr') : '' ?>"><?php if (! empty($_c[0])): ?><i class="fa-solid fa-<?= esc($_c[0], 'attr') ?>"></i><?php endif; ?> <?= esc($_c[1]) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?= $extra ?>

      <?php if ($facts): ?>
        <?php $_short = count(array_filter($facts, static fn ($f) => empty($f[3]))); ?>
        <div class="lp-facts s<?= min(3, max(1, $_short)) ?>">
          <?php foreach ($facts as $_f): ?>
            <div class="lp-fact<?= ! empty($_f[3]) ? ' lp-fact-wide' : '' ?>"><i class="fa-solid fa-<?= esc($_f[0], 'attr') ?>"></i>
              <div><small><?= esc($_f[1]) ?></small><b title="<?= esc((string) $_f[2], 'attr') ?>"><?= esc((string) $_f[2]) ?></b></div></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($actions): ?>
        <div class="lp-actions">
          <?php foreach ($actions as $_a): ?>
            <a class="lp-act<?= ! empty($_a[3]) ? ' ' . esc($_a[3], 'attr') : '' ?>" href="<?= esc($_a[2], 'attr') ?>"<?= str_starts_with((string) $_a[2], 'http') ? ' target="_blank" rel="noopener"' : '' ?>><i class="fa-solid fa-<?= esc($_a[0], 'attr') ?>"></i> <?= esc($_a[1]) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($links): ?>
        <div class="lp-links">
          <?php foreach ($links as $_k): ?>
            <a href="<?= esc($_k[2], 'attr') ?>"><i class="fa-regular fa-<?= esc($_k[0], 'attr') ?>"></i> <?= esc($_k[1]) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if (! $_noslide): ?>
    <aside class="lp-slider ph-slider<?= $_stock ? ' ph-stock' : '' ?>" data-count="<?= $_n ?>">
      <div class="lp-slides">
        <?php foreach ($slides as $_i => $_s): $_tag = ! empty($_s[1]) ? 'a' : 'div'; ?>
          <<?= $_tag ?> class="ph-slide"<?= $_tag === 'a' ? ' href="' . esc($_s[1], 'attr') . '"' : '' ?>>
            <img src="<?= esc($_s[0], 'attr') ?>" alt="<?= esc((string) ($_s[2] ?? 'Noida'), 'attr') ?>"<?= $_i ? ' loading="lazy"' : '' ?>>
            <?php if (! empty($_s[2])): ?><span class="ph-cap"><?= esc($_s[2]) ?><?php if ($_tag === 'a'): ?> <i class="fa-solid fa-arrow-right"></i><?php endif; ?></span><?php endif; ?>
          </<?= $_tag ?>>
        <?php endforeach; ?>
      </div>
      <?php if ($_n > 1): ?>
        <button type="button" class="lp-sl-btn lp-sl-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="lp-sl-btn lp-sl-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="lp-sl-foot">
          <div class="lp-dots"><?php for ($_i = 0; $_i < $_n; $_i++): ?><button type="button" aria-label="Slide <?= $_i + 1 ?>"<?= $_i === 0 ? ' class="on"' : '' ?>></button><?php endfor; ?></div>
          <span class="lp-sl-count"><i class="fa-regular fa-images"></i> <b>1</b> / <?= $_n ?></span>
        </div>
        <div class="lp-sl-bar"><i></i></div>
      <?php endif; ?>
    </aside>
    <?php endif; ?>
   </div>
  </div>
</section>
<script>
/* banner slider: every 3s, pauses on hover / hidden tab, arrows, dots, swipe */
(function(){
  document.querySelectorAll('.ph-slider:not([data-on])').forEach(function(box){
    box.setAttribute('data-on','1');
    var track=box.querySelector('.lp-slides'), n=track.children.length; if(n<2) return;
    var dots=box.querySelectorAll('.lp-dots button'), num=box.querySelector('.lp-sl-count b'),
        bar=box.querySelector('.lp-sl-bar i'), i=0, timer=null, DELAY=3000,
        still=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
    function go(k){ i=(k+n)%n; track.style.transform='translateX('+(-100*i)+'%)';
      dots.forEach(function(d,j){ d.classList.toggle('on',j===i); }); if(num) num.textContent=i+1;
      if(bar){ bar.style.transition='none'; bar.style.width='0'; void bar.offsetWidth;
        if(timer){ bar.style.transition='width '+DELAY+'ms linear'; bar.style.width='100%'; } } }
    function stop(){ if(timer){ clearInterval(timer); timer=null; } if(bar){ bar.style.transition='none'; bar.style.width='0'; } }
    function play(){ if(still) return; stop(); timer=setInterval(function(){ go(i+1); },DELAY); go(i); }
    box.querySelector('.lp-sl-next').addEventListener('click',function(){ go(i+1); play(); });
    box.querySelector('.lp-sl-prev').addEventListener('click',function(){ go(i-1); play(); });
    dots.forEach(function(d,j){ d.addEventListener('click',function(){ go(j); play(); }); });
    box.addEventListener('mouseenter',stop); box.addEventListener('mouseleave',play);
    document.addEventListener('visibilitychange',function(){ document.hidden?stop():play(); });
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
  });
})();
</script>
