<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $_slides = []; foreach (array_slice($posts ?? [], 0, 6) as $_p) { $_slides[] = [! empty($_p['blog_thumbnail']) ? hi_media($_p['blog_thumbnail'], 'blog') : hi_topic_img('blog', (int) $_p['id'], 900, 600, $_p['title'] ?? ''), hi_news_url($_p), $_p['title']]; } ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['News & Guides', null]],
  'title'  => esc(hi_c('blog_list.news_local_guides', 'News & Local Guides')),
  'sub'    => 'Updates, guides and stories from across Noida.',
  'facts'  => [['newspaper', 'Posts', number_format($total ?? count($posts))], ['city', 'City', 'Noida']],
  'links'  => [['calendar', 'Events', hi_page('events')], ['star', 'Offers', hi_page('offers')], ['folder-open', 'Categories', hi_page('categories')]],
  'tone'   => 'light',   // small light-blue header on the News page
], ['saveData' => false]) ?>
<section class="sec">
  <div class="wrap">
    <?php if (($q ?? '') === '' && ! ($cat ?? 0) && (int) ($pg['page'] ?? 1) <= 1): ?>
      <?= view('frontend/_newsbrief', ['latest' => $latest ?? [], 'cats' => $cats ?? []], ['saveData' => false]) ?>
    <?php endif; ?>
    <?= view('frontend/_contentsearch', ['action'=>hi_page('news'),'q'=>$q ?? '','shown'=>$shown ?? count($posts),'total'=>$total ?? count($posts),'noun'=>'post','kind'=>'news','cats'=>$cats ?? [],'cat'=>$cat ?? 0]) ?>
  <?php if (! $posts): ?>
      <div class="sec-head" data-eyebrow="Journal"><h2><?= esc(hi_c('blog_list.coming_soon', 'Coming soon')) ?></h2><p><?= esc(hi_c('blog_list.p_fresh_guides_are_on_the_way', 'Fresh guides are on the way.')) ?></p></div>
    <?php else: ?>
    <div class="lgrid bgrid">
      <?php foreach ($posts as $_i => $p): $img = ! empty($p['blog_thumbnail']) ? hi_media($p['blog_thumbnail'], 'blog') : hi_topic_img('blog', (int) $p['id'], 440, 300, $p['title'] ?? ''); ?>
        <?php
          /* A headline laid over its own photograph is only readable when the
             photograph cooperates. Half of these are bright skies and white
             buildings, so the titles were washing out. Picture on top, words
             on white underneath — and there is room for an excerpt now. */
          $_ex = trim(html_entity_decode(strip_tags((string) ($p['blog_text'] ?? '')), ENT_QUOTES, 'UTF-8'));
          $_ex = preg_replace('/\s+/u', ' ', $_ex);
        ?>
        <?php $_type = ucfirst(trim((string) ($p['blog_type'] ?? '')) ?: 'News'); $_top = $_i === 0 && (int) ($pg['page'] ?? 1) <= 1 && ($q ?? '') === ''; ?>
        <a class="bcard<?= $_top ? ' is-top' : '' ?>" href="<?= hi_news_url($p) ?>" data-id="<?= (int) $p['id'] ?>" data-c="<?= esc(implode(',', array_filter(array_map('intval', preg_split('/[,\s]+/', (string) ($p['category_id'] ?? '')) ?: []))), 'attr') ?>">
          <span class="bcard-ph"><img src="<?= esc($img) ?>" alt="<?= esc(html_entity_decode((string) $p['title'], ENT_QUOTES, 'UTF-8')) ?>" loading="<?= $_top ? 'eager' : 'lazy' ?>">
            <span class="bcard-tag"><?= $_top ? '<i class="fa-solid fa-bolt"></i> Top story' : esc($_type) ?></span></span>
          <span class="bcard-bd">
            <h3><?= esc($p['title']) ?></h3>
            <?php if ($_ex !== ''): ?>
              <span class="bcard-ex"><?= esc(mb_strimwidth($_ex, 0, $_top ? 260 : 140, '…')) ?></span>
            <?php endif; ?>
            <span class="bcard-date">
              <i class="fa-regular fa-calendar"></i>
              <?= esc(date('d M Y', strtotime($p['added_date'] ?: 'now'))) ?>
              <em>Read story <i class="fa-solid fa-arrow-right"></i></em>
            </span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
    <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
    <?php endif; ?>
  </div>
</section>
<?= $this->endSection() ?>
