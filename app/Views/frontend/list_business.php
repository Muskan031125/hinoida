<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['List your business', null]],
  'title'  => esc(hi_c('list_business.list_your_business', 'List your business')),
  'sub'    => hi_c('list_business.p_reach_thousands_searching_noida_it_s_free', "Reach thousands searching Noida — it's free."),
  'facts'  => [['indian-rupee-sign', 'Cost', 'Free to list'], ['clock', 'Takes', 'About 2 minutes'], ['users', 'Reach', 'People searching Noida']],
  'links'  => [['tags', 'See pricing', base_url('pricing')], ['circle-question', 'FAQ', base_url('faq')]],
], ['saveData' => false]) ?>
<section class="sec"><div class="wrap" style="max-width:640px">
  <div style="border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);background:#fff">
    <?php if($err): ?><div style="background:#fdecec;color:#c0392b;border-radius:10px;padding:11px 14px;margin-bottom:14px"><?= esc($err) ?></div><?php endif; ?>
    <form method="post" action="<?= base_url('list') ?>">
      <?php $inp='width:100%;border:1px solid var(--line);border-radius:10px;padding:12px 14px;font-size:14.5px;font-family:inherit';$lab='font-size:13px;font-weight:600;color:#444;display:block;margin:12px 0 6px'; ?>
      <label style="<?= $lab ?>">Business name *</label><input name="name" required style="<?= $inp ?>">
      <label style="<?= $lab ?>">Category *</label>
      <select name="category" required style="<?= $inp ?>"><option value="">Select…</option><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= esc($c['name']) ?></option><?php endforeach; ?></select>
      <label style="<?= $lab ?>">Area</label>
      <select name="locality" style="<?= $inp ?>"><option value="">Select area…</option><?php foreach($locs as $l): ?><option value="<?= (int)$l['id'] ?>"><?= esc($l['name']) ?></option><?php endforeach; ?></select>
      <label style="<?= $lab ?>">Address</label><input name="address" style="<?= $inp ?>">
      <label style="<?= $lab ?>">Phone</label><input name="phone" style="<?= $inp ?>">
      <label style="<?= $lab ?>">Email</label><input name="email" type="email" style="<?= $inp ?>">
      <label style="<?= $lab ?>">Description</label><textarea name="description" rows="4" style="<?= $inp ?>"></textarea>
      <button type="submit" style="width:100%;margin-top:18px;background:var(--blue);color:#fff;border:0;border-radius:10px;padding:13px;font-size:15px;font-weight:700;cursor:pointer"><?= esc(hi_c('list_business.btn_publish_listing', 'Publish listing')) ?></button>
    </form>
  </div>
</div></section>
<?= $this->endSection() ?>
