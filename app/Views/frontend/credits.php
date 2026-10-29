<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="det-hero" style="--dethero:none">
  <div class="wrap">
    <div class="det-crumb"><a href="<?= base_url('/') ?>">Home</a> &rsaquo; <span>Photo credits</span></div>
    <h1 class="det-name"><?= esc(hi_c('credits.photo_credits', 'Photo credits')) ?></h1>
    <div class="det-meta"><span class="dm-loc">Category photographs used on HiNoida, with their authors and licences.</span></div>
  </div>
</section>
<div class="wrap" style="padding:34px 20px 60px">
  <p style="color:var(--body);line-height:1.7;max-width:70ch">
    Where a business has not uploaded its own photograph, HiNoida shows a representative
    category image. Those images come from Wikimedia Commons and are reproduced here under
    their respective Creative Commons licences, with attribution as required.
  </p>
  <?php if (empty($credits)): ?>
    <p style="color:var(--muted)"><?= esc(hi_c('credits.p_no_third_party_photographs_are_currently_in_', 'No third-party photographs are currently in use.')) ?></p>
  <?php else: ?>
    <div style="border:1px solid var(--line);border-radius:14px;overflow-x:auto;margin-top:18px;-webkit-overflow-scrolling:touch">
      <table style="width:100%;border-collapse:collapse;font-size:13.5px">
        <thead><tr style="background:rgba(238,244,255,.7)">
          <th style="text-align:left;padding:11px 14px">Image</th>
          <th style="text-align:left;padding:11px 14px">Author</th>
          <th style="text-align:left;padding:11px 14px">Licence</th>
        </tr></thead>
        <tbody>
        <?php foreach ($credits as $c): ?>
          <tr style="border-top:1px solid var(--line)">
            <td style="padding:10px 14px">
              <?php if (! empty($c['source'])): ?>
                <a href="<?= esc($c['source']) ?>" target="_blank" rel="noopener"><?= esc($c['title'] ?: $c['file']) ?></a>
              <?php else: ?><?= esc($c['title'] ?: $c['file']) ?><?php endif; ?>
            </td>
            <td style="padding:10px 14px;color:var(--body)"><?= esc($c['author'] ?: '—') ?></td>
            <td style="padding:10px 14px;color:var(--muted)"><?= esc($c['license'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
