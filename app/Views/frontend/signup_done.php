<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="sec"><div class="wrap" style="max-width:520px">
  <div style="border:1px solid var(--line);border-radius:20px;padding:40px 30px;box-shadow:var(--shadow);background:#fff;text-align:center">
    <div style="width:84px;height:84px;border-radius:50%;background:#e6f6ec;color:#0b8a3d;display:flex;align-items:center;justify-content:center;font-size:40px;margin:0 auto 18px;animation:hiPop .5s cubic-bezier(.2,.7,.3,1)"><i class="fa-solid fa-circle-check"></i></div>
    <h1 style="margin:0 0 8px;font-size:30px">You're all set, <?= esc($name) ?>! 🎉</h1>
    <p style="color:var(--body);font-size:16px;margin:0 0 6px"><?= esc(hi_c('signup_done.p_your_email_is_verified_and_your_hinoida_acco', 'Your email is verified and your HiNoida account is confirmed.')) ?></p>
    <?php if ($biz): ?>
      <p style="background:#fff8e6;color:#8a6d1a;border:1px solid #f0dca3;border-radius:12px;padding:13px 16px;margin:18px 0;font-size:14px"><i class="fa-solid fa-clock"></i> Your business is now <b>pending admin approval</b> — we'll email you the moment it goes live.</p>
    <?php else: ?>
      <p style="color:var(--muted);margin:0 0 20px"><?= esc(hi_c('signup_done.p_we_ve_emailed_you_a_welcome_guide_with_every', 'We\'ve emailed you a welcome guide with everything you can do on HiNoida.')) ?></p>
    <?php endif; ?>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:22px">
      <a href="<?= base_url('dashboard') ?>" style="background:var(--blue);color:#fff;padding:13px 24px;border-radius:11px;font-weight:700"><i class="fa-solid fa-gauge-high"></i> Go to my dashboard</a>
      <a href="<?= esc($redirect ?: base_url('/')) ?>" style="border:1px solid var(--line);color:var(--ink);padding:13px 24px;border-radius:11px;font-weight:700"><i class="fa-solid fa-house"></i> <?= $redirect ? 'Continue where I was' : 'Go to homepage' ?></a>
    </div>
  </div>
</div></section>
<?= $this->endSection() ?>
