<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section style="background:linear-gradient(120deg,#0a2a6b,#123a8f);color:#fff;padding:44px 20px"><div class="wrap"><h1 style="color:#fff;margin:0"><?= esc(hi_c('inbox.your_inbox', 'Your inbox')) ?></h1><p style="opacity:.9;margin:6px 0 0"><?= esc(hi_c('inbox.p_booking_requests_for_your_businesses', 'Booking requests for your businesses.')) ?></p></div></section>
<section class="sec"><div class="wrap">
  <?php if(!$bookings): ?><p style="color:var(--muted)"><?= esc(hi_c('inbox.p_no_booking_requests_yet', 'No booking requests yet.')) ?></p>
  <?php else: foreach($bookings as $bk): ?>
    <div style="border:1px solid var(--line);border-radius:14px;padding:16px 18px;margin-bottom:12px;background:#fff">
      <b><?= esc($bk['name']??'') ?></b> — <?= esc($bk['listing_name']??'') ?>
      <div style="color:var(--muted);font-size:13px;margin-top:4px"><?= esc($bk['phone']??'') ?> · <?= esc($bk['booking_date']??'') ?></div>
    </div>
  <?php endforeach; endif; ?>
</div></section>
<?= $this->endSection() ?>
