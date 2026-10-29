<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="sec"><div class="wrap" style="max-width:640px">
  <div class="det-crumb" style="margin-bottom:18px">
    <a href="<?= base_url('/') ?>">Home</a> &rsaquo;
    <a href="<?= hi_listing_url($l) ?>"><?= esc($l['name']) ?></a> &rsaquo; <span>Claim</span>
  </div>

  <h1 style="font-size:26px;margin:0 0 6px">Claim <?= esc($l['name']) ?></h1>
  <p style="color:var(--muted);margin:0 0 22px">
    <i class="fa-solid fa-location-dot"></i> <?= esc(trim(($l['address'] ?? '') ?: 'Noida')) ?>
  </p>

  <?php if (! empty($err)): ?>
    <div style="background:#fdecec;color:#b3262f;border:1px solid #f5c6c6;border-radius:12px;padding:12px 16px;margin-bottom:16px">
      <i class="fa-solid fa-circle-exclamation"></i> <?= esc($err) ?>
    </div>
  <?php endif; ?>

  <?php if ($taken): ?>
    <div class="sidecard" style="padding:22px">
      <p style="margin:0 0 14px"><?= esc(hi_c('claim.p_this_listing_already_has_an_owner_or_a_claim', 'This listing already has an owner, or a claim for it is being reviewed.')) ?></p>
      <a class="btn" style="display:inline-block" href="<?= hi_page('contact') ?>">Contact us about it</a>
    </div>
  <?php else: ?>
    <div class="sidecard" style="padding:22px">
      <p style="margin:0 0 18px;color:var(--body);line-height:1.6">
        Tell us how to reach you. Once we have verified that you run this business, the listing moves
        into your dashboard — you can then edit its details, add photos and services, and answer
        customer enquiries.
      </p>
      <form method="post" action="<?= base_url('claim/' . (int) $l['id']) ?>">
        <label class="authlbl"><?= esc(hi_c('claim.lbl_your_name', 'Your name')) ?></label>
        <input class="authinp" name="name" required value="<?= esc($me['name'] ?? '') ?>">

        <label class="authlbl"><?= esc(hi_c('claim.lbl_email', 'Email')) ?></label>
        <input class="authinp" type="email" name="email" required value="<?= esc($me['email'] ?? '') ?>">

        <label class="authlbl"><?= esc(hi_c('claim.lbl_phone', 'Phone')) ?></label>
        <input class="authinp" name="phone" required value="<?= esc($me['phone'] ?? '') ?>" placeholder="<?= esc(hi_c('claim.ph_business_contact_number', 'Business contact number'), 'attr') ?>">

        <label class="authlbl">Anything that proves it's yours <span style="color:var(--muted);font-weight:400">(optional)</span></label>
        <textarea class="authinp" name="note" rows="4" placeholder="<?= esc(hi_c('claim.ph_gst_number_website_or_the_number_listed_on_t', 'GST number, website, or the number listed on the page'), 'attr') ?>"></textarea>

        <button class="authbtn" style="margin-top:14px"><?= esc(hi_c('claim.btn_send_claim', 'Send claim')) ?></button>
      </form>
    </div>
  <?php endif; ?>
</div></section>
<?= $this->endSection() ?>
