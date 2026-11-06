<?php
/**
 * "Notify me" — leave a name and number, hear when a matching home is listed.
 * Expects: $alert = [deal, kind, bhk, budget, area, title] (all optional)
 */
$_a     = (isset($alert) && is_array($alert)) ? $alert : [];
$_s     = static fn ($k) => is_scalar($_a[$k] ?? null) ? (string) $_a[$k] : '';
$deal   = $_s('deal');
$kind   = $_s('kind');
$bhk    = (int) $_s('bhk');
$budget = $_s('budget');
$area   = trim($_s('area'));
$what   = trim(($bhk ? $bhk . ' BHK ' : '') . (['sale' => 'homes for sale', 'rent' => 'homes for rent', 'pg' => 'PGs', 'commercial' => 'commercial spaces', 'plots' => 'plots'][$deal] ?? 'homes'));
$title  = $_s('title') !== '' ? $_s('title') : 'Get told when new ' . $what . ($area !== '' ? ' in ' . $area : '') . ' are listed';
$done   = session()->getFlashdata('alert_ok');
$err    = session()->getFlashdata('alert_err');
?>
<div class="al-box" id="notify">
  <div class="al-ic"><i class="fa-solid fa-bell"></i></div>
  <div class="al-main">
    <?php if ($done): ?>
      <b>You're on the list.</b>
      <p>We'll call or WhatsApp you on <?= esc($done) ?> when a matching home is listed.</p>
    <?php else: ?>
      <b><?= esc($title) ?></b>
      <p>Leave your number. We'll WhatsApp you as soon as one comes up.</p>
      <form method="post" action="<?= base_url('property/alert') ?>" class="al-form">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <input type="hidden" name="deal" value="<?= esc($deal, 'attr') ?>">
        <input type="hidden" name="kind" value="<?= esc($kind, 'attr') ?>">
        <input type="hidden" name="bhk" value="<?= $bhk ?>">
        <input type="hidden" name="budget" value="<?= esc($budget, 'attr') ?>">
        <input type="hidden" name="area" value="<?= esc($area, 'attr') ?>">
        <input type="hidden" name="page" value="<?= esc(current_url() . (($qs = (string) service('request')->getUri()->getQuery()) !== '' ? '?' . $qs : ''), 'attr') ?>">
        <input type="text" name="name" placeholder="Your name" maxlength="100" autocomplete="name">
        <input type="tel" name="phone" placeholder="Mobile number" required pattern="[0-9+ ]{10,15}" maxlength="15" autocomplete="tel">
        <button type="submit"><i class="fa-solid fa-bell"></i> Notify me</button>
      </form>
      <?php if ($err): ?><p class="al-err"><?= esc($err) ?></p><?php endif; ?>
    <?php endif; ?>
  </div>
</div>
