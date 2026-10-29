<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
  /* Social links come from our own fe_* settings when they are set, otherwise
     from the live social_links blob — same source the footer uses. */
  $social = function_exists('hi_fe_social') ? hi_fe_social() : [];
  $mapQ   = trim((string) ($address ?? ''));
?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Contact', null]],
  'title'  => esc(hi_c('contact.contact_us', 'Contact us')),
  'sub'    => 'We usually reply within one working day.',
  // email, address and buttons are in the contact cards below — the header stays clean
  'links'  => [['circle-question', 'FAQ', base_url('faq')], ['square-plus', 'List your business', base_url('list')]],
  'tone'   => 'light',                                  // small, centred, light-blue header
  'eyebrow'=> ['headset', 'We are here to help'],
], ['saveData' => false]) ?>

<section class="sec"><div class="wrap">
  <div class="ct-grid">

    <?php /* ---------- the form ---------- */ ?>
    <div class="ct-form">
      <h2 class="ct-h"><?= esc(hi_c('contact.send_us_a_message', 'Send us a message')) ?></h2>
      <p class="ct-sub"><?= esc(hi_c('contact.p_questions_about_a_listing_your_account_or_ad', 'Questions about a listing, your account, or advertising with us? Fill this in and we\'ll reply by email.')) ?></p>

      <?php /* The controller has always set these; the page simply never showed
               them, so a sent message looked identical to a failed one. */ ?>
      <?php if (! empty($flash)): ?>
        <div class="ct-note ct-ok" role="status">
          <i class="fa-solid fa-circle-check"></i> <span><?= esc($flash) ?></span>
        </div>
      <?php endif; ?>
      <?php if (! empty($err)): ?>
        <div class="ct-note ct-bad" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i> <span><?= esc($err) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= hi_page('contact') ?>" novalidate>
        <?php /* honeypot — hidden from people, irresistible to bots */ ?>
        <div style="position:absolute;left:-9999px" aria-hidden="true">
          <label>Website<input name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="ct-row">
          <div class="ct-f">
            <label class="authlbl" for="ct-name"><?= esc(hi_c('contact.lbl_your_name', 'Your name *')) ?></label>
            <input class="authinp" id="ct-name" name="name" required maxlength="120"
                   autocomplete="name" value="<?= esc($old['name'] ?? '') ?>">
          </div>
          <div class="ct-f">
            <label class="authlbl" for="ct-email"><?= esc(hi_c('contact.lbl_email', 'Email *')) ?></label>
            <input class="authinp" id="ct-email" type="email" name="email" required maxlength="160"
                   autocomplete="email" value="<?= esc($old['email'] ?? '') ?>">
          </div>
        </div>

        <label class="authlbl" for="ct-phone"><?= esc(hi_c('contact.lbl_phone', 'Phone *')) ?></label>
        <?= view('frontend/_phone', ['name' => 'phone', 'ph' => 'So we can call you back', 'required' => true]) ?>

        <label class="authlbl" for="ct-msg"><?= esc(hi_c('contact.lbl_message', 'Message *')) ?></label>
        <textarea class="authinp" id="ct-msg" name="message" required minlength="10" rows="6"
                  placeholder="How can we help?"><?= esc($old['message'] ?? '') ?></textarea>

        <button class="authbtn" type="submit"><i class="fa-solid fa-paper-plane"></i> Send message</button>
        <p class="ct-fine">By sending this you agree to our
          <a href="<?= hi_page('privacy') ?>">privacy policy</a>.</p>
      </form>
    </div>

    <?php /* ---------- the details ---------- */ ?>
    <aside class="ct-side">
      <div class="ct-card">
        <h2 class="ct-h"><?= esc(hi_c('contact.reach_us_directly', 'Reach us directly')) ?></h2>

        <a class="ct-item" href="mailto:<?= esc(($email ?? '') ?: 'hello@new.hinoida.com', 'attr') ?>">
          <i class="fa-solid fa-envelope"></i>
          <span><b>Email</b><em><?= esc(($email ?? '') ?: 'hello@new.hinoida.com') ?></em></span>
        </a>

        <?php if (! empty($phone)): ?>
          <a class="ct-item" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') ?>">
            <i class="fa-solid fa-phone"></i>
            <span><b>Phone</b><em><?= esc($phone) ?></em></span>
          </a>
        <?php endif; ?>

        <?php if ($mapQ !== ''): ?>
          <?php /* A search link rather than a pin: the settings hold a postal
                   address, not coordinates, and guessing them would drop the
                   marker somewhere that is not the office. */ ?>
          <a class="ct-item" target="_blank" rel="noopener"
             href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($mapQ) ?>">
            <i class="fa-solid fa-location-dot"></i>
            <span><b>Office</b><em><?= esc($mapQ) ?></em>
              <u>Open in Google Maps <i class="fa-solid fa-arrow-up-right-from-square"></i></u></span>
          </a>
        <?php endif; ?>
      </div>

      <?php if ($social): ?>
        <div class="ct-card">
          <h2 class="ct-h"><?= esc(hi_c('contact.follow_hinoida', 'Follow HiNoida')) ?></h2>
          <div class="ct-social">
            <?php foreach ($social as $k => $url): ?>
              <a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener"
                 aria-label="<?= esc(ucfirst(str_replace('x-twitter', 'X', $k)), 'attr') ?>">
                <i class="fa-brands fa-<?= esc($k, 'attr') ?>"></i>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="ct-card ct-cta">
        <i class="fa-solid fa-store"></i>
        <b>Own a business in Noida?</b>
        <p><?= esc(hi_c('contact.p_list_it_free_and_reach_customers_across_the_', 'List it free and reach customers across the city.')) ?></p>
        <a class="freelist" href="<?= base_url('list') ?>">Add your business</a>
      </div>

      <div class="ct-card ct-help">
        <b>Looking for something else?</b>
        <a href="<?= hi_page('faq') ?>"><i class="fa-solid fa-circle-question"></i> Read the FAQ</a>
        <a href="<?= hi_page('pricing') ?>"><i class="fa-solid fa-tag"></i> Listing plans &amp; pricing</a>
        <a href="<?= base_url('advertise') ?>"><i class="fa-solid fa-bullhorn"></i> Advertise with us</a>
      </div>
    </aside>

  </div>
</div></section>
<?= $this->endSection() ?>
