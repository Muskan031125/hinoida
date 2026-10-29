<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section style="background:linear-gradient(120deg,#0a2a6b,#123a8f);color:#fff;padding:44px 20px"><div class="wrap"><h1 style="color:#fff;margin:0">Hi, <?= esc($name) ?></h1><p style="opacity:.9;margin:6px 0 0"><?= esc(hi_c('dashboard.p_manage_your_business_listings_on_hinoida', 'Manage your business listings on HiNoida.')) ?></p></div></section>
<section class="sec"><div class="wrap">
  <?php if (! empty($flash)): ?><div style="background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:12px;padding:13px 18px;margin-bottom:18px"><i class="fa-solid fa-circle-check"></i> <?= esc($flash) ?></div><?php endif; ?>

  <?php if (! $verified): ?>
    <div style="background:#fff8e6;color:#8a6d1a;border:1px solid #f0dca3;border-radius:12px;padding:15px 18px;margin-bottom:18px">
      <b><i class="fa-solid fa-envelope-circle-check"></i> Please confirm your email.</b> We sent a confirmation link to <?= esc($me['email'] ?? '') ?>. Click it to activate your account.
    </div>
  <?php elseif ($isBusiness && ! $approved): ?>
    <div style="background:#fff8e6;color:#8a6d1a;border:1px solid #f0dca3;border-radius:12px;padding:15px 18px;margin-bottom:18px">
      <b><i class="fa-solid fa-clock"></i> Your business account is pending admin approval.</b> You'll be emailed once approved — after that your listings can go live.
    </div>
  <?php elseif ($isBusiness && $approved): ?>
    <div style="background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:12px;padding:15px 18px;margin-bottom:18px">
      <b><i class="fa-solid fa-circle-check"></i> Your business is approved.</b> You can add and manage listings below.
    </div>
  <?php endif; ?>

  <div class="sec-head" style="display:flex;justify-content:space-between;align-items:center"><h2 style="margin:0">My Listings (<?= count($mine) ?>)</h2><a class="nav btn" href="<?= base_url('list') ?>" style="padding:10px 18px;border-radius:9px"><i class="fa-solid fa-plus"></i> List a business</a></div>
  <?php if(!$mine): ?><p style="color:var(--muted);margin-top:16px"><?= esc(hi_c('dashboard.p_you_haven_t_listed_a_business_yet', 'You haven\'t listed a business yet.')) ?></p>
  <?php else: ?><div class="lgrid" style="margin-top:18px">
    <?php foreach($mine as $l): ?>
      <a class="lcard" href="<?= hi_listing_url($l) ?>">
        <span class="ph"><img src="<?= esc(hi_cover($l)) ?>" alt=""></span>
        <span class="bd"><b><?= esc($l['name']) ?></b><span class="loc"><i class="fa-solid fa-circle" style="font-size:7px;color:<?= ($l['status']??'')==='active'?'#1a7a3d':'#b8770a' ?>"></i> <?= esc(ucfirst($l['status']??'pending')) ?></span></span>
      </a>
    <?php endforeach; ?>
  </div><?php endif; ?>
</div></section>
<?= $this->endSection() ?>
