<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [[$group[0], null]],
  'title'  => '<i class="fa-solid fa-' . esc($group[1], 'attr') . '"></i> ' . esc($group[0]),
  'sub'    => 'Every ' . strtolower($group[0]) . ' category in Noida, in one place.',
  'facts'  => [['layer-group', 'Categories', (string) count($cats)], ['city', 'City', 'Noida']],
  'actions'=> [['magnifying-glass', 'Search Noida', hi_page('search'), 'primary'], ['folder-open', 'All categories', hi_page('categories')]],
  'links'  => [['map', 'Browse by area', hi_page('areas')], ['square-plus', 'List your business', base_url('list')]],
], ['saveData' => false]) ?>
<section class="sec"><div class="wrap">
  <div class="sec-head"><h2><?= esc(hi_c('group.choose_a_category', 'Choose a category')) ?></h2></div>
  <div class="typechips">
    <?php foreach ($cats as $c): ?>
      <a href="<?= hi_category_url(esc($c['slug'], 'url')) ?>"><?= esc($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
</div></section>
<?= $this->endSection() ?>
