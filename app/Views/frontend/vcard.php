<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $loc = $l['locality_name'] ?: 'Noida'; $ph = preg_replace('/[^0-9+]/', '', $l['phone'] ?? ''); ?>
<section class="sec"><div class="wrap" style="max-width:440px">
  <div style="border:1px solid var(--line);border-radius:22px;overflow:hidden;box-shadow:var(--shadow-lg);background:#fff">
    <div style="background:linear-gradient(120deg,#0a2a6b,#123a8f);color:#fff;padding:30px 26px;text-align:center">
      <div style="width:76px;height:76px;border-radius:50%;background:rgba(255,255,255,.16);border:2px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;margin:0 auto 12px"><?= esc(strtoupper(substr($l['name'],0,1))) ?></div>
      <h1 style="color:#fff;font-family:'Inter',system-ui,sans-serif;font-size:26px;margin:0"><?= esc($l['name']) ?></h1>
      <p style="opacity:.9;margin:6px 0 0;font-size:14px"><i class="fa-solid fa-location-dot"></i> <?= esc($loc) ?>, Noida</p>
    </div>
    <div style="padding:24px 26px;text-align:center">
      <img src="<?= esc($qr) ?>" alt="Scan to save <?= esc($l['name']) ?> contact" width="200" height="200" style="border-radius:12px;border:1px solid var(--line)" loading="lazy">
      <p style="color:var(--muted);font-size:13px;margin:12px 0 20px"><?= esc(hi_c('vcard.p_scan_to_save_this_contact_to_your_phone', 'Scan to save this contact to your phone')) ?></p>
      <div style="display:grid;gap:9px">
        <?php if ($ph): ?><a style="display:block;background:var(--blue);color:#fff;padding:12px;border-radius:10px;font-weight:700" href="tel:<?= esc($ph) ?>"><i class="fa-solid fa-phone"></i> Call <?= esc($l['phone']) ?></a>
        <a style="display:block;background:#25d366;color:#fff;padding:12px;border-radius:10px;font-weight:700" target="_blank" rel="noopener" href="https://wa.me/<?= esc(preg_replace('/[^0-9]/','',$ph)) ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a><?php endif; ?>
        <?php if (! empty($l['website'])): ?><a style="display:block;background:#111827;color:#fff;padding:12px;border-radius:10px;font-weight:700" target="_blank" rel="noopener" href="<?= esc($l['website']) ?>"><i class="fa-solid fa-globe"></i> Website</a><?php endif; ?>
        <a style="display:block;background:#0b8a3d;color:#fff;padding:12px;border-radius:10px;font-weight:700" href="<?= base_url('card/' . (int)$l['id'] . '/download') ?>"><i class="fa-solid fa-address-card"></i> Save contact (.vcf)</a>
        <a style="display:block;border:1px solid var(--line);color:var(--ink);padding:12px;border-radius:10px;font-weight:700" href="<?= hi_listing_url($l) ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> View full listing</a>
        <button type="button" id="cardcopy" style="border:1px solid var(--line);background:#fff;color:var(--ink);padding:12px;border-radius:10px;font-weight:700;cursor:pointer;font-family:inherit;font-size:14px"><i class="fa-solid fa-link"></i> Copy card link</button>
      </div>
      <script>
      document.getElementById('cardcopy').addEventListener('click', function(){
        var b = this;
        var done = function(){ b.innerHTML = '<i class="fa-solid fa-check"></i> Link copied'; };
        var url = '<?= base_url('card/' . (int)$l['id']) ?>';
        if (navigator.clipboard) { navigator.clipboard.writeText(url).then(done, function(){}); }
        else { window.prompt('Copy this link', url); }
      });
      </script>
    </div>
  </div>
</div></section>
<?= $this->endSection() ?>
