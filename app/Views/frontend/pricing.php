<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Pricing', null]],
  'title'  => esc(hi_c('pricing.simple_honest_pricing', 'Simple, honest pricing')),
  'sub'    => hi_c('pricing.p_start_free_upgrade_to_stand_out_in_noida', 'Start free. Upgrade to stand out in Noida.'),
  'facts'  => [['layer-group', 'Plans', $plans ? (string) count($plans) : 'Free'], ['indian-rupee-sign', 'Starts at', '₹0 — free forever'], ['star', 'Upgrade for', 'Featured placement']],
  'actions'=> [['square-plus', 'List your business', base_url('list'), 'primary']],
], ['saveData' => false]) ?>
<section class="sec"><div class="wrap">
  <?php if ($flash): ?><div style="background:#e6f6ec;color:#1a7a3d;border-radius:12px;padding:14px 18px;margin-bottom:20px"><?= esc($flash) ?></div><?php endif; ?>
  <?php if (! empty($mine)): $lim = (int) ($mine['number_of_listings'] ?? 1); ?>
    <div style="background:#eef3fc;border:1px solid #dbe6fb;border-radius:12px;padding:14px 18px;margin-bottom:20px;color:var(--body)">
      <b style="color:var(--ink)">Your plan: <?= esc($mine['name'] ?? 'Free') ?></b> —
      <?= (int) $used ?> of <?= $lim > 0 ? $lim : '∞' ?> listing<?= $lim === 1 ? '' : 's' ?> used<?php
      if ($days !== null) { echo ', ' . $days . ' day' . ($days === 1 ? '' : 's') . ' left'; } ?>.
    </div>
  <?php endif; ?>
  <?php if (! $plans): ?>
    <?php /* no paid plans set up in Admin yet: say plainly what is free, and how to get more */ ?>
    <div class="pr-free">
      <div class="pr-col">
        <span class="ds-badge ok">Free</span>
        <h2>Listing on HiNoida is free</h2>
        <p>Post a flat, PG, shop or business at no cost. People can call you, WhatsApp you, ask questions and leave reviews.</p>
        <ul>
          <li><i class="fa-solid fa-check"></i> Your listing with photos and full details</li>
          <li><i class="fa-solid fa-check"></i> Enquiries and leads in your dashboard</li>
          <li><i class="fa-solid fa-check"></i> Reviews, questions and replies</li>
          <li><i class="fa-solid fa-check"></i> Views and enquiries per listing</li>
        </ul>
        <a class="ds-btn" href="<?= base_url('list') ?>"><i class="fa-solid fa-plus"></i> List for free</a>
      </div>
      <div class="pr-col">
        <span class="ds-badge info">Featured</span>
        <h2>Want to be seen first?</h2>
        <p>Featured listings show at the top of their sector and category, and on the home page. Tell us what you want to promote and we will share the options.</p>
        <a class="ds-btn ghost" href="<?= base_url('contact') ?>"><i class="fa-solid fa-envelope"></i> Ask about featuring</a>
      </div>
    </div>
  <?php endif; ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:22px;margin-top:16px">
    <?php foreach ($plans as $p): $free = (int) $p['price'] === 0; ?>
      <div style="border:<?= (int) $p['is_recommended'] ? '2px solid #1741a6' : '1px solid var(--line)' ?>;border-radius:18px;padding:28px;background:#fff;box-shadow:var(--shadow);position:relative">
        <?php if ((int) $p['is_recommended']): ?><span style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#1741a6;color:#fff;font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px">Most popular</span><?php endif; ?>
        <h3 style="margin:0 0 6px"><?= esc($p['name']) ?></h3>
        <div style="font-size:34px;font-weight:800;color:var(--ink)"><?= $free ? '&#8377;0' : '&#8377;' . number_format($p['price']) ?><span style="font-size:14px;color:var(--muted);font-weight:500"><?= $free ? ' forever' : '/mo' ?></span></div>
        <ul style="list-style:none;padding:0;margin:16px 0;font-size:14px;color:var(--body)">
          <li style="padding:5px 0"><i class="fa-solid fa-check" style="color:#1a7a3d"></i> <?= (int) $p['number_of_listings'] ?> listing<?= (int) $p['number_of_listings'] > 1 ? 's' : '' ?></li>
          <?php if ((int) $p['featured']): ?><li style="padding:5px 0"><i class="fa-solid fa-check" style="color:#1a7a3d"></i> Featured placement</li><?php endif; ?>
          <li style="padding:5px 0"><i class="fa-solid fa-check" style="color:#1a7a3d"></i> Enquiries &amp; reviews</li>
        </ul>
        <form method="post" action="<?= base_url('pricing/buy') ?>"><input type="hidden" name="package_id" value="<?= (int) $p['id'] ?>"><button style="width:100%;border:0;border-radius:10px;padding:13px;font-weight:700;cursor:pointer;<?= $free ? 'background:#eef2f8;color:#1741a6' : 'background:#1741a6;color:#fff' ?>"><?= $free ? 'Activate free' : 'Choose plan' ?></button></form>
      </div>
    <?php endforeach; ?>
  </div>
</div></section>
<?= $this->endSection() ?>
