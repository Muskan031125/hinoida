<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $_free = 0; $_bz = []; foreach (($offers ?? []) as $_o) { if (preg_match('/\bfree\b/i', ($_o['discount'] ?? '') . ' ' . ($_o['title'] ?? ''))) { $_free++; } if (trim((string) ($_o['business'] ?? '')) !== '') { $_bz[strtolower(trim($_o['business']))] = 1; } } $_biz = count($_bz); ?>
<?php $_slides = []; foreach (array_slice($offers ?? [], 0, 6) as $_o) { $_slides[] = [hi_topic_img('offer', (int) $_o['id'], 900, 600, ($_o['title'] ?? '') . ' ' . ($_o['business'] ?? '')), hi_offer_url($_o), $_o['title']]; } ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Offers & Deals', null]],
  'title'  => esc(hi_c('offers_list.exclusive_offers_in_noida', 'Exclusive Offers in Noida')),
  'sub'    => 'The best deals, discounts and special offers near you in Noida.',
  'facts'  => array_values(array_filter([['tag', 'Offers', number_format($total ?? count($offers))],
               $_free ? ['gift', 'Free', (string) $_free] : null, $_biz ? ['shop', 'Businesses', (string) $_biz] : null])),
  'links'  => [['calendar', 'Events', hi_page('events')], ['newspaper', 'News & guides', hi_page('news')], ['folder-open', 'Categories', hi_page('categories')]],
  'tone'   => 'light',
  'eyebrow'=> ['tags', 'Deals near you'],
], ['saveData' => false]) ?>
<section class="sec"><div class="wrap">
  <?= view('frontend/_contentsearch', ['action'=>hi_page('offers'),'q'=>$q ?? '','shown'=>$shown ?? count($offers),'total'=>$total ?? count($offers),'noun'=>'offer','kind'=>'offer','cats'=>$cats ?? [],'cat'=>$cat ?? 0]) ?>
  <?php if (! $offers): ?><div class="sec-head"><h2><?= esc(hi_c('offers_list.no_offers_right_now', 'No offers right now')) ?></h2><p><?= esc(hi_c('offers_list.p_check_back_soon', 'Check back soon.')) ?></p></div><?php else: ?>
  <div class="lgrid">
    <?php foreach ($offers as $o): ?>
      <a class="lcard" href="<?= hi_offer_url($o) ?>">
        <span class="ph"><img src="<?= esc(hi_topic_img('offer', (int) $o['id'], 440, 300, ($o['title'] ?? '').' '.($o['business'] ?? ''))) ?>" alt="<?= esc($o['title']) ?>">
          <span class="rate" style="background:#e0446b"><i class="fa-solid fa-tag"></i> <?= esc($o['discount']) ?></span>
        </span>
        <?php
          // Live offers are articles with no business or locality attached, so
          // printing them unconditionally left a bare "🏪 ," on every card.
          $_meta = implode(', ', array_filter([trim((string) ($o['business'] ?? '')), trim((string) ($o['locality'] ?? ''))]));
        ?>
        <?php $_oc = ''; foreach (($cats ?? []) as $_c) { if (in_array((int) $_c['id'], array_map('intval', preg_split('/[,\s]+/', (string) ($o['category_id'] ?? '')) ?: []), true)) { $_oc = $_c['name']; break; } } ?>
        <span class="bd"><span class="cd-tags"><span><?= esc($_oc !== '' ? $_oc : 'Offer') ?></span></span><b><?= esc($o['title']) ?></b>
          <?php if ($_meta !== ''): ?>
            <span class="loc"><i class="fa-solid fa-shop"></i> <?= esc($_meta) ?></span>
          <?php elseif (! empty($o['valid_till'])): ?>
            <span class="loc"><i class="fa-regular fa-clock"></i> Valid till <?= esc($o['valid_till']) ?></span>
          <?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
  <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
  <?php endif; ?>
</div></section>
<?= $this->endSection() ?>
