<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $_up = 0; foreach (($events ?? []) as $_e) { $_t = strtotime((string) ($_e['event_date'] ?? '')); if ($_t && $_t > strtotime('2000-01-01') && $_t >= strtotime('today')) { $_up++; } } ?>
<?php $_slides = []; foreach (array_slice($events ?? [], 0, 6) as $_e) { $_slides[] = [hi_topic_img('event', (int) $_e['id'], 900, 600, $_e['title'] ?? ''), hi_event_url($_e), $_e['title']]; } ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['Events', null]],
  'title'  => esc(hi_c('events_list.events_in_noida', 'Events in Noida')),
  'sub'    => "Festivals, expos, concerts and more — what's on across Noida.",
  'facts'  => array_values(array_filter([['calendar', 'Events', number_format($total ?? count($events))],
               $_up ? ['clock', 'Upcoming', (string) $_up] : null, count($cats ?? []) ? ['tags', 'Types', (string) count($cats)] : null])),
  'links'  => [['star', 'Offers', hi_page('offers')], ['newspaper', 'News & guides', hi_page('news')], ['folder-open', 'Categories', hi_page('categories')]],
  'tone'   => 'light',
  'eyebrow'=> ['champagne-glasses', "What's on"],
], ['saveData' => false]) ?>
<section class="sec"><div class="wrap">
  <?= view('frontend/_contentsearch', ['action'=>hi_page('events'),'q'=>$q ?? '','shown'=>$shown ?? count($events),'total'=>$total ?? count($events),'noun'=>'event','kind'=>'event','cats'=>$cats ?? [],'cat'=>$cat ?? 0,'types'=>$types ?? [],'type'=>$type ?? '']) ?>
  <?php if (! $events): ?><div class="sec-head"><h2><?= esc(hi_c('events_list.no_events_right_now', 'No events right now')) ?></h2><p><?= esc(hi_c('events_list.p_check_back_soon', 'Check back soon.')) ?></p></div><?php else: ?>
  <div class="lgrid">
    <?php foreach ($events as $e): ?>
      <a class="lcard" href="<?= hi_event_url($e) ?>">
        <span class="ph"><img src="<?= esc(hi_topic_img('event', (int) $e['id'], 440, 300, $e['title'] ?? '')) ?>" alt="<?= esc($e['title']) ?>">
          <span class="rate" style="background:var(--blue)"><i class="fa-regular fa-calendar"></i> <?= esc(date('d M', strtotime($e['event_date']))) ?></span>
        </span>
        <?php
          // live events carry no venue or locality, so printing both left a
          // bare pin and a comma on every card
          $_where = implode(', ', array_filter([trim((string) ($e['venue'] ?? '')), trim((string) ($e['locality'] ?? ''))]));
          $_when  = trim((string) ($e['event_date'] ?? '')) ?: trim((string) ($e['added_date'] ?? ''));
        ?>
        <?php
          // the event's kind — its types first, else the category it is filed under
          $_tg = array_slice($e['_tags'] ?? [], 0, 2);
          if (! $_tg) { foreach (($cats ?? []) as $_c) { if (in_array((int) $_c['id'], array_map('intval', preg_split('/[,\s]+/', (string) ($e['category_id'] ?? '')) ?: []), true)) { $_tg[] = $_c['name']; break; } } }
          if (! $_tg) { $_tg = ['Event']; }
        ?>
        <span class="bd"><span class="cd-tags"><?php foreach ($_tg as $_t): ?><span><?= esc($_t) ?></span><?php endforeach; ?></span><b><?= esc($e['title']) ?></b>
          <?php if ($_where !== ''): ?>
            <span class="loc"><i class="fa-solid fa-location-dot"></i> <?= esc($_where) ?></span>
          <?php elseif ($_when !== ''): ?>
            <span class="loc"><i class="fa-regular fa-calendar"></i> <?= esc(date('d M Y', strtotime($_when))) ?></span>
          <?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
  <?= view('frontend/_pager', ['pg' => $pg ?? null]) ?>
  <?php endif; ?>
</div></section>
<?= $this->endSection() ?>
