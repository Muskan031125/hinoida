<?php if (! empty($pg) && $pg['pages'] > 1): ?>
<nav class="pager" aria-label="Pages">
  <?php if ($pg['page'] > 1): ?>
    <a class="pg" href="<?= esc(hi_page_url($pg['page'] - 1)) ?>" rel="prev"><i class="fa-solid fa-chevron-left"></i></a>
  <?php else: ?><span class="pg off"><i class="fa-solid fa-chevron-left"></i></span><?php endif; ?>

  <?php foreach (hi_pager_links($pg['page'], $pg['pages']) as $n): ?>
    <?php if ($n === 0): ?><span class="pg gap">…</span>
    <?php elseif ($n === $pg['page']): ?><span class="pg on" aria-current="page"><?= $n ?></span>
    <?php else: ?><a class="pg" href="<?= esc(hi_page_url($n)) ?>"><?= $n ?></a><?php endif; ?>
  <?php endforeach; ?>

  <?php if ($pg['page'] < $pg['pages']): ?>
    <a class="pg" href="<?= esc(hi_page_url($pg['page'] + 1)) ?>" rel="next"><i class="fa-solid fa-chevron-right"></i></a>
  <?php else: ?><span class="pg off"><i class="fa-solid fa-chevron-right"></i></span><?php endif; ?>

  <span class="pgcount"><?= $pg['from'] ?>–<?= $pg['to'] ?> of <?= number_format($pg['total']) ?></span>
</nav>
<?php endif; ?>
