<?php helper('image'); $b = base_url() . '/'; ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/dash.css') ?>?v=7"></head><body>
<div class="wrap">
  <div class="top">
    <a class="back" href="<?= $b ?>dashboard/listings"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <div><h1><?= esc(hi_c('portal_photos.photos', 'Photos')) ?></h1><p class="sub"><?= esc($listing['name']) ?></p></div>
  </div>

  <?php if (! empty($saved)): ?><div class="ok"><i class="fa-solid fa-circle-check"></i> <?= esc($saved) ?></div><?php endif; ?>

  <form class="card" method="post" enctype="multipart/form-data" action="<?= $b ?>dashboard/photos/<?= (int) $listing['id'] ?>/save">
    <label>Current photos <?= $photos ? '<span style="font-weight:400;color:#6b7a94">— use the bin icon to remove one; changes apply when you save</span>' : '' ?></label>
    <?php if ($photos): ?>
      <div class="grid">
        <?php foreach ($photos as $i => $p): ?>
          <label class="ph" data-ph>
            <img src="<?= str_starts_with($p, 'http') ? esc($p) : base_url($p) ?>" alt="">
            <input type="checkbox" name="keep[]" value="<?= esc($p) ?>" checked hidden>
            <button type="button" class="ph-del" title="Remove this photo" aria-label="Remove photo"><i class="fa-solid fa-trash"></i></button>
            <?php if ($i === 0): ?><span class="cover">COVER</span><?php endif; ?>
            <span class="ph-off">Removed &middot; click to undo</span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty"><i class="fa-regular fa-image" style="font-size:26px;display:block;margin-bottom:8px"></i>
        No photos yet — your listing is showing a category tile.</div>
    <?php endif; ?>

    <label><?= esc(hi_c('portal_photos.lbl_upload_new_photos', 'Upload new photos')) ?></label>
    <input type="file" name="photos[]" accept="image/*" multiple>
    <p class="hint"><?= esc(hi_c('portal_photos.p_jpg_png_webp_or_gif_up_to_5_mb_each_10_photo', 'JPG, PNG, WEBP or GIF · up to 5 MB each · 10 photos maximum.')) ?></p>

    <label>Or add photo paths / links <span style="font-weight:400;color:#6b7a94">— one per line</span></label>
    <textarea name="photo_paths" placeholder="<?= esc(hi_c('portal_photos.ph_assets_img_cat_food_1_jpg_https_example_com_', 'assets/img/cat/food-1.jpg
https://example.com/my-shop.jpg'), 'attr') ?>"></textarea>
    <p class="hint"><?= esc(hi_c('portal_photos.p_a_path_relative_to_the_site_root_or_a_full_h', 'A path relative to the site root, or a full https:// link. The first photo becomes your cover image.')) ?></p>

    <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save photos</button>
  </form>
</div>
<script>
document.querySelectorAll('[data-ph]').forEach(function(ph){
  var cb=ph.querySelector('input[type=checkbox]'), btn=ph.querySelector('.ph-del');
  function paint(){ ph.classList.toggle('off', !cb.checked);
    btn.innerHTML = cb.checked ? '<i class=\"fa-solid fa-trash\"></i>' : '<i class=\"fa-solid fa-rotate-left\"></i>';
    btn.title = cb.checked ? 'Remove this photo' : 'Undo remove'; }
  function toggle(e){ e.preventDefault(); e.stopPropagation(); cb.checked=!cb.checked; paint(); }
  btn.addEventListener('click',toggle);
  ph.addEventListener('click',toggle);
  paint();
});
</script>
</body></html>
