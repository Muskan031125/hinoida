<?php
/**
 * "Popular searches" — the phrase links the original carries on every page.
 *
 * Built from the categories that actually have the most listings rather than a
 * fixed list, so the links always lead somewhere worth arriving at. Phrased the
 * way people search ("Top Restaurants in Noida"), which is also what these
 * links are worth for search engines.
 */
$db = \Config\Database::connect();

$rows = $db->table('category c')
    ->select('c.name, c.slug, (SELECT COUNT(*) FROM listing li WHERE li.status = "active"
              AND (li.categories LIKE \'%"\' || c.id || \'"%\')) AS n')
    ->where('c.parent', 0)
    ->orderBy('n', 'DESC')->limit(8)->get()->getResultArray();
$rows = array_values(array_filter($rows, static fn ($r) => (int) $r['n'] > 0));
if (! $rows) { return; }

// "Top Restaurants in Noida", "Best Salons in Noida" — alternated, as the
// original does, so the block does not read as one repeated template
$lead = ['Top', 'Best', 'Popular', 'Trusted'];
?>
<section class="sec popsearch">
  <div class="wrap">
    <h2><?= esc(hi_c('_popularsearch.popular_searches', 'Popular searches')) ?></h2>
    <div class="popsearch-list">
      <?php foreach ($rows as $i => $r): ?>
        <a href="<?= hi_category_url(esc($r['slug'], 'url')) ?>">
          <?= esc($lead[$i % count($lead)]) ?> <?= esc($r['name']) ?> in Noida
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
