<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="sec">
  <div class="wrap" style="max-width:440px">
    <div class="authcard">
      <h1><?= esc(hi_c('reset.set_a_new_password', 'Set a new password')) ?></h1>
      <p class="authsub">
        We sent a 6-digit code to <b><?= esc($email) ?></b>. It expires in 1 hour.
      </p>

      <form method="post" action="<?= base_url('reset') ?>">
        <label class="authlbl"><?= esc(hi_c('reset.lbl_reset_code', 'Reset code')) ?></label>
        <input class="authinp authcode" name="code" required inputmode="numeric"
               maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code"
               placeholder="000000" autofocus>

        <label class="authlbl"><?= esc(hi_c('reset.lbl_new_password', 'New password')) ?></label>
        <input class="authinp" type="password" name="password" required minlength="6"
               autocomplete="new-password" placeholder="<?= esc(hi_c('reset.ph_at_least_6_characters', 'At least 6 characters'), 'attr') ?>">

        <label class="authlbl"><?= esc(hi_c('reset.lbl_confirm_new_password', 'Confirm new password')) ?></label>
        <input class="authinp" type="password" name="password_confirm" required minlength="6"
               autocomplete="new-password" placeholder="<?= esc(hi_c('reset.ph_type_it_again', 'Type it again'), 'attr') ?>">

        <button class="authbtn" type="submit"><?= esc(hi_c('reset.btn_change_password', 'Change password')) ?></button>
      </form>

      <p class="authfoot">
        Didn't get it? Check spam, or <a href="<?= hi_page('forgot') ?>">request a new code</a>.
      </p>
    </div>
  </div>
</section>
<?= $this->endSection() ?>
