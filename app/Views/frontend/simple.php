<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
  require_once __DIR__ . '/_posthelp.php';
  // About / Privacy / Terms copy comes from the live frontend_settings table and
  // is written as HTML by an admin, so it is rendered rather than escaped.
  // Plain text still passes through esc().
  $isHtml  = (bool) preg_match('/<\/?(p|h[1-6]|ul|ol|li|br|strong|em|a|div|table)\b/i', (string) $body);
  $bodyOut = $isHtml ? hi_fix_mojibake((string) $body) : '<p>' . nl2br(esc(hi_fix_mojibake((string) $body))) . '</p>';
  $mins    = hi_read_mins($bodyOut);
  // section headings inside the copy become a small "On this page" list
  preg_match_all('~<h([23])[^>]*>(.*?)</h\1>~is', $bodyOut, $hs);
  $toc = [];
  foreach ($hs[2] as $i => $h) {
      $t = trim(hi_dec(strip_tags($h))); if ($t === '') continue;
      $id = 'sp-' . ($i + 1); $toc[] = [$id, $t];
      $bodyOut = preg_replace('~<h([23])(?![^>]*\bid=)([^>]*)>' . preg_quote($h, '~') . '~i', '<h$1 id="' . $id . '"$2>' . $h, $bodyOut, 1);
  }
?>
<?= view('frontend/_pagehero', [
  'crumbs' => [[$heading, null]],
  'title'  => esc($heading),
  'chips'  => [['clock', $mins . ' min read', 'ok']],
  'links'  => [['envelope', 'Contact', base_url('contact')], ['circle-question', 'FAQ', base_url('faq')]],
], ['saveData' => false]) ?>
<div class="wrap ev-grid">
  <div class="ev-main">
    <article class="ev-card bl-art">
      <div class="prose bl-prose"><?= $bodyOut ?></div>
    </article>
  </div>
  <aside class="ev-side">
    <div class="ev-ticket">
      <div class="ev-tk-bd">
        <?php if (count($toc) > 1): ?>
          <h3>On this page</h3>
          <nav class="sp-toc"><?php foreach ($toc as $t): ?><a href="#<?= $t[0] ?>"><?= esc($t[1]) ?></a><?php endforeach; ?></nav>
        <?php else: ?>
          <h3>Need help?</h3>
          <p><i class="fa-regular fa-envelope"></i> Questions about this page? Our team replies fast.</p>
        <?php endif; ?>
      </div>
      <div class="ev-btns">
        <a class="ev-btn primary" href="<?= base_url('contact') ?>"><i class="fa-regular fa-envelope"></i> Contact us</a>
        <a class="ev-btn" href="<?= base_url('faq') ?>"><i class="fa-regular fa-circle-question"></i> FAQ</a>
      </div>
    </div>
  </aside>
</div>
<?= $this->endSection() ?>
