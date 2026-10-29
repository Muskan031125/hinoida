<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/dash.css') ?>?v=7"></head><body>
<div class="wrap">
  <div class="top"><h1><i class="fa-solid fa-tags" style="color:#1741a6"></i> Services &amp; pricing</h1><a class="back" href="<?= base_url('dashboard/listings') ?>"><i class="fa-solid fa-arrow-left"></i> My listings</a></div>
  <p style="color:#5a6b86;margin:-8px 0 18px">for <b><?= esc($listing['name']) ?></b> — these show on your public listing page.</p>
  <?php if(!empty($saved)): ?><div class="saved"><i class="fa-solid fa-circle-check"></i> <?= esc($saved) ?></div><?php endif; ?>
  <div class="grid">
    <div class="panel"><h3>Your services (<?= count($items) ?>)</h3>
      <?php if(!$items): ?><div class="empty"><i class="fa-solid fa-tags"></i>No services yet. Add your first on the right.</div>
      <?php else: foreach($items as $s): ?>
        <div class="srow">
          <?php if(!empty($s['image'])): ?><img src="<?= base_url($s['image']) ?>" alt=""><?php endif; ?>
          <div class="g"><b><?= esc($s['name']) ?></b><small><?= esc($s['description']??'') ?></small></div>
          <span class="price"><?= esc($s['price']??'') ?></span>
          <a class="abtn no" href="<?= base_url('dashboard/catalog/'.(int)$listing['id'].'/delete/'.(int)$s['id']) ?>" onclick="return confirm('Remove this service?')"><i class="fa-solid fa-trash"></i></a>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <div class="panel"><form class="form" method="post" action="<?= base_url('dashboard/catalog/'.(int)$listing['id'].'/save') ?>" enctype="multipart/form-data">
      <h3 style="border:0;padding:0;margin:0 0 6px"><?= esc(hi_c('portal_catalog.add_a_service', 'Add a service')) ?></h3>
      <label><?= esc(hi_c('portal_catalog.lbl_name', 'Name')) ?></label><input name="name" required placeholder="<?= esc(hi_c('portal_catalog.ph_e_g_consultation', 'e.g. Consultation'), 'attr') ?>">
      <label><?= esc(hi_c('portal_catalog.lbl_price', 'Price')) ?></label><input name="price" placeholder="<?= esc(hi_c('portal_catalog.ph_e_g_500_starts_at_999', 'e.g. ₹500 / Starts at ₹999'), 'attr') ?>">
      <label><?= esc(hi_c('portal_catalog.lbl_description', 'Description')) ?></label><textarea name="description" rows="2" placeholder="<?= esc(hi_c('portal_catalog.ph_short_description', 'Short description'), 'attr') ?>"></textarea>
      <label><?= esc(hi_c('portal_catalog.lbl_photo_optional', 'Photo (optional)')) ?></label><input type="file" name="image" accept="image/*">
      <button class="btn"><i class="fa-solid fa-plus"></i> Add service</button>
    </form></div>
  </div>
</div></body></html>
