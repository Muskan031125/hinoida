<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="acc-head"><div class="wrap">
  <div class="det-crumb" style="margin-bottom:18px"><a href="<?= base_url('/') ?>">Home</a> &rsaquo; <span><?= esc($name) ?></span></div>
  <div class="acc-id">
    <span class="acc-avatar" style="background:<?= esc(hi_avatar_bg($name), 'attr') ?>"><?= esc(hi_initial($name)) ?></span>
    <div>
      <h1 class="acc-hello" style="margin:0"><?= esc($name) ?></h1>
      <p class="acc-sub">
        <?= $isBiz ? 'Business owner on HiNoida' : 'HiNoida member' ?>
        <?php if (! empty($u['is_verified'])): ?> &middot; <span style="color:#0b8a3d;font-weight:700">Verified</span><?php endif; ?>
      </p>
    </div>
  </div>
</div></section>

<section class="sec" style="padding-top:26px"><div class="wrap">
  <div class="stats" style="margin-bottom:26px">
    <?php if ($isBiz): ?>
      <div class="stat"><i class="fa-solid fa-store"></i><b><?= count($listings) ?></b><span>Listed places</span></div>
    <?php endif; ?>
    <div class="stat"><i class="fa-solid fa-star"></i><b><?= number_format($reviewCount) ?></b><span>Reviews written</span></div>
  </div>

  <?php if ($social): ?>
    <div class="sc-social" style="margin-bottom:26px">
      <?php foreach ($social as $net => $url):
        $icon = ['facebook'=>'facebook-f','instagram'=>'instagram','twitter'=>'x-twitter','x'=>'x-twitter',
                 'youtube'=>'youtube','linkedin'=>'linkedin-in'][$net] ?? 'globe'; ?>
        <a href="<?= esc($url) ?>" target="_blank" rel="noopener nofollow" title="<?= esc(ucfirst($net)) ?>"><i class="fa-brands fa-<?= $icon ?>"></i></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($listings): ?>
    <div class="sec-head" style="text-align:left"><h2>Places by <?= esc($name) ?></h2></div>
    <div class="lgrid" style="margin-bottom:30px">
      <?php foreach ($listings as $l): ?>
        <?= view('frontend/_card', ['l' => $l]) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($reviews): ?>
    <div class="sec-head" style="text-align:left"><h2><?= esc(hi_c('profile.recent_reviews', 'Recent reviews')) ?></h2></div>
    <?php foreach ($reviews as $rv): $rt = (int) ($rv['review_rating'] ?? 0); ?>
      <div class="acc-panel" style="padding:16px 20px;margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
          <b><a href="<?= hi_listing_url((int) $rv['listing_id']) ?>"><?= esc($rv['listing_name'] ?: 'A place in Noida') ?></a></b>
          <span class="stars"><?php for ($i = 1; $i <= 5; $i++) { echo $i <= $rt ? '★' : '☆'; } ?></span>
        </div>
        <p style="margin:6px 0 0;color:var(--body)"><?= esc($rv['review_comment'] ?? '') ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (! $listings && ! $reviews): ?>
    <div class="acc-empty">
      <i class="fa-solid fa-user"></i>
      <p><?= esc($name) ?> hasn't listed a business or written a review yet.</p>
      <a class="nav btn" style="display:inline-block;padding:11px 22px;border-radius:9px" href="<?= base_url('/') ?>">Explore HiNoida</a>
    </div>
  <?php endif; ?>
</div></section>
<?= $this->endSection() ?>
