<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="sec">
  <div class="wrap" style="max-width:440px">
    <div class="authcard">
      <h1><?= esc(hi_c('forgot.reset_your_password', 'Reset your password')) ?></h1>
      <p class="authsub"><?= esc(hi_c('forgot.p_enter_the_email_you_signed_up_with_and_we_ll', 'Enter the email you signed up with and we\'ll send you a 6-digit code.')) ?></p>

      <form method="post" action="<?= hi_page('forgot') ?>">
        <label class="authlbl"><?= esc(hi_c('forgot.lbl_email_address', 'Email address')) ?></label>
        <input class="authinp" type="email" name="email" required autofocus
               autocomplete="email" placeholder="<?= esc(hi_c('forgot.ph_you_example_com', 'you@example.com'), 'attr') ?>">
        <button class="authbtn" type="submit"><?= esc(hi_c('forgot.btn_send_reset_code', 'Send reset code')) ?></button>
      </form>

      <p class="authfoot">
        Remembered it? <a href="<?= hi_page('login') ?>">Back to sign in</a>
      </p>
    </div>
  </div>
</section>
<?= $this->endSection() ?>
