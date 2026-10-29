<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $_slides = []; foreach (array_slice($articles ?? [], 0, 6) as $_a) { $_slides[] = [hi_topic_img('news', (int) $_a[0], 900, 600, $_a[1] ?? ''), null, $_a[1]]; } ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['News & Guides', null]],
  'title'  => esc(hi_c('news.news_local_guides', 'News & Local Guides')),
  'sub'    => 'Curated guides to the best of Noida.',
  'facts'  => [['newspaper', 'Guides', (string) count($articles ?? [])], ['city', 'City', 'Noida']],
  'links'  => [['calendar', 'Events', hi_page('events')], ['star', 'Offers', hi_page('offers')], ['folder-open', 'Categories', hi_page('categories')]],
  'slides' => $_slides,
], ['saveData' => false]) ?>

<section class="sec">
  <div class="wrap">
    <div class="sec-head"><span class="kicker">Explore Noida</span><h2><?= esc(hi_c('news.latest_guides', 'Latest guides')) ?></h2><p><?= esc(hi_c('news.p_handpicked_local_guides_to_help_you_find_the', 'Handpicked local guides to help you find the best places in Noida.')) ?></p></div>
    <div class="lgrid">
      <?php foreach ($articles as $a): ?>
        <a class="lcard" href="<?= hi_category_url('') ?>#" onclick="return false" style="cursor:default">
          <span class="ph"><img src="<?= esc(hi_topic_img('news', (int) $a[0], 440, 300, $a[1] ?? '')) ?>" alt="<?= esc($a[1]) ?>">
            <span class="rate" style="background:var(--blue)"><i class="fa-solid fa-newspaper"></i> <?= esc($a[4]) ?></span>
          </span>
          <span class="bd">
            <b><?= esc($a[1]) ?></b>
            <span class="loc" style="color:var(--body);line-height:1.5"><?= esc($a[3]) ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;color:var(--muted);margin-top:26px;font-size:14px">More guides published regularly. Want your business featured? <a href="<?= base_url('list') ?>">List it on HiNoida</a>.</p>
  </div>
</section>
<?= $this->endSection() ?>
