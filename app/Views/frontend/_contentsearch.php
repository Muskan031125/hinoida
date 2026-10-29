<?php
/**
 * Search bar for the news / events / offers listings.
 *
 * $action  where the form posts to
 * $q       current term
 * $shown   how many are on screen after filtering
 * $total   how many there are without a term
 * $noun    what to call them ("post", "event", "offer")
 * $kind    news | event | offer — drives the /{kind}/category/{slug} chip links
 */
$q     = trim((string) ($q ?? ''));
$shown = (int) ($shown ?? 0);
$total = (int) ($total ?? $shown);
$noun  = (string) ($noun ?? 'result');
?>
<?php $cats = $cats ?? []; $cat = (int) ($cat ?? 0); $kind = (string) ($kind ?? 'news');
      $types = $types ?? []; $type = (string) ($type ?? '');
      // a category row with only one entry tells nobody anything
      if (count($cats) < 2) { $cats = []; } ?>
<div class="sx sx-card">
<form class="sx-top" method="get" action="<?= esc($action, 'attr') ?>">
  <label class="sx-q"><i class="fa-solid fa-magnifying-glass"></i>
    <input type="search" name="q" value="<?= esc($q) ?>" autocomplete="off" placeholder="Search by title, description or category…"></label>
  <?php if ($cat): ?><input type="hidden" name="cat" value="<?= $cat ?>"><?php endif; ?>
  <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= esc($type, 'attr') ?>"><?php endif; ?>
  <button type="submit" class="sx-go"><i class="fa-solid fa-magnifying-glass"></i><span>Search</span></button>
</form>
<?php if ($types): $_qs = $q !== '' ? '&q=' . urlencode($q) : ''; ?>
  <?php /* what kind of event — one tap each */ ?>
  <div class="sx-quick sx-cats sx-types">
    <a href="<?= esc($action, 'attr') ?><?= $q !== '' ? '?q=' . urlencode($q) : '' ?>" class="sx-chip<?= ($type === '' && ! $cat) ? ' on' : '' ?>"><i class="fa-solid fa-border-all"></i> All <em><?= number_format($total) ?></em></a>
    <?php foreach ($types as $t): ?>
      <a href="<?= esc($action, 'attr') ?>?type=<?= esc($t['slug'], 'url') ?><?= $_qs ?>" class="sx-chip<?= $type === $t['slug'] ? ' on' : '' ?>"><i class="fa-solid fa-<?= esc($t['icon'], 'attr') ?>"></i> <?= esc($t['name']) ?> <em><?= (int) $t['n'] ?></em></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php if ($cats): ?>
  <?php /* what kinds of thing are here, with counts — one tap each */ ?>
  <div class="sx-quick sx-cats">
    <a href="<?= esc($action, 'attr') ?><?= $q !== '' ? '?q=' . urlencode($q) : '' ?>" class="sx-chip<?= $cat ? '' : ' on' ?>">
      All <em><?= number_format($total) ?></em></a>
    <?php foreach ($cats as $c): ?>
      <?php /* the live per-topic URL: /news/category/restaurants */ ?>
      <?php $chip = hi_post_category_url($kind, (string) ($c['slug'] ?? '')) . ($q !== '' ? '?q=' . urlencode($q) : ''); ?>
      <a href="<?= esc($chip, 'attr') ?>" class="sx-chip<?= $cat === (int) $c['id'] ? ' on' : '' ?>"><?= esc($c['name']) ?> <em><?= number_format((int) $c['n']) ?></em></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>
<p class="sx-count">
  <?php if ($q !== ''): ?>
    <b style="color:var(--ink)"><?= number_format($shown) ?></b>
    <?= esc($noun) ?><?= $shown === 1 ? '' : 's' ?> matching &ldquo;<?= esc($q) ?>&rdquo;
    &middot; <a href="<?= esc($action, 'attr') ?>" style="font-weight:600">show all <?= number_format($total) ?></a>
  <?php else: ?>
    <b style="color:var(--ink)"><?= number_format($total) ?></b> <?= esc($noun) ?><?= $total === 1 ? '' : 's' ?>
  <?php endif; ?>
</p>
