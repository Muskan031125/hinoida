<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section style="background:linear-gradient(120deg,#0a2a6b,#123a8f);color:#fff;padding:44px 20px;text-align:center"><div class="wrap">
  <h1 style="color:#fff;margin:0"><?= esc(hi_c('verify_email.verify_your_email', 'Verify your email')) ?></h1>
  <p style="opacity:.9;margin:8px 0 0"><?= esc(hi_c('verify_email.p_enter_the_6_digit_code_we_just_sent_you', 'Enter the 6-digit code we just sent you.')) ?></p>
</div></section>
<section class="sec"><div class="wrap" style="max-width:440px">
  <div style="border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);background:#fff;text-align:center">
    <?php if (! empty($ok)): ?><div style="background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:10px;padding:11px 14px;font-size:14px;margin-bottom:14px"><i class="fa-solid fa-circle-check"></i> <?= esc($ok) ?></div><?php endif; ?>
    <?php if (! empty($err)): ?><div style="background:#fdecec;color:#c0392b;border-radius:10px;padding:11px 14px;font-size:14px;margin-bottom:14px"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($err) ?></div><?php endif; ?>

    <div style="width:64px;height:64px;border-radius:50%;background:#eef3fc;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 14px"><i class="fa-solid fa-envelope-open-text"></i></div>
    <p style="color:var(--body);margin:0 0 4px">Code sent to</p>
    <p style="font-weight:700;color:var(--ink);margin:0 0 20px"><?= esc($email) ?></p>

    <form method="post" action="<?= base_url('verify-email') ?>">
      <input name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required placeholder="______"
        style="width:100%;text-align:center;letter-spacing:14px;font-size:30px;font-weight:800;border:1px solid var(--line);border-radius:12px;padding:14px;font-family:inherit;color:var(--ink)"
        autofocus oninput="this.value=this.value.replace(/\D/g,'')">
      <button type="submit" style="width:100%;margin-top:16px;background:var(--blue);color:#fff;border:0;border-radius:11px;padding:14px;font-size:15px;font-weight:700;cursor:pointer"><?= esc(hi_c('verify_email.btn_verify_my_email', 'Verify my email')) ?></button>
    </form>

    <div style="margin-top:16px;font-size:13.5px;color:var(--muted)">
      Didn't get the email?
      <form method="post" action="<?= base_url('verify-email/resend') ?>" style="display:inline">
        <button type="submit" style="background:none;border:0;color:var(--blue);font-weight:700;cursor:pointer;font-family:inherit;font-size:13.5px;padding:0"><?= esc(hi_c('verify_email.btn_resend_code', 'Resend code')) ?></button>
      </form>
    </div>
    <p style="font-size:12px;color:var(--muted);margin:12px 0 0"><?= esc(hi_c('verify_email.p_check_your_spam_folder_too_the_code_stays_va', 'Check your spam folder too. The code stays valid for 2 hours.')) ?></p>
    <p style="margin:14px 0 0"><a href="<?= base_url('dashboard') ?>" style="font-size:13px;color:var(--muted)">Skip for now</a></p>
  </div>
</div></section>
<?= $this->endSection() ?>
