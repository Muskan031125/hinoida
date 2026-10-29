<?php
/**
 * Instagram strip. Lives in the layout so it appears on every page, as the
 * original does by putting it in footer.php.
 *
 * Three sources, in order:
 *   1. Reel links pasted into Admin → Frontend → "Instagram reels". These are
 *      rendered with Instagram's own embed, so a reel plays in place and stays
 *      current without anyone uploading anything.
 *   2. MP4s dropped into assets/video/ — how the original did it.
 *   3. Neither: the handle and a follow button, which is what this showed
 *      before, because assets/video/ has never existed in this repository.
 *
 * The original autoplays four MP4s totalling around 318 MB on every visit,
 * which is punishing on a phone. Local clips are never preloaded and play on
 * hover (or on tap); the embed script is only requested when there are reels.
 */
$ig = hi_fe_social()['instagram'] ?? '';
if ($ig === '') { return; }

// 1. straight from the account, when a token is configured
$api = [];
try { $api = (new \App\Libraries\Instagram())->reels(4); }
catch (\Throwable $e) { log_message('error', 'Instagram strip: ' . $e->getMessage()); }

// 2. reels pasted by hand — one URL per line, used when there is no token
$reels = [];
foreach (preg_split('/\R+/', (string) hi_setting('fe_ig_reels')) ?: [] as $line) {
    $line = trim($line);
    if ($line === '') { continue; }
    // only accept real instagram permalinks; anything else is ignored quietly
    if (! preg_match('~^https?://(www\.)?instagram\.com/(reel|reels|p|tv)/[A-Za-z0-9_-]+~', $line)) { continue; }
    $reels[] = rtrim(explode('?', $line)[0], '/') . '/';
}
$reels = $api ? [] : array_slice(array_unique($reels), 0, 4);

// 2. local clips, only when no reels are configured
$clips = [];
if (! $api && ! $reels) {
    foreach (['five.mp4', 'video15.mp4', 'insta-video12.mp4', 'insta-video15.mp4'] as $name) {
        if (is_file(FCPATH . 'assets/video/' . $name)) { $clips[] = base_url('assets/video/' . $name); }
    }
    foreach (glob(FCPATH . 'assets/video/*.mp4') ?: [] as $f) {
        $u = base_url('assets/video/' . basename($f));
        if (! in_array($u, $clips, true)) { $clips[] = $u; }
    }
    $clips = array_slice($clips, 0, 4);
}

// the handle, for display — "@hinoida_official" out of the profile URL
$handle = trim((string) parse_url($ig, PHP_URL_PATH), '/');
$handle = $handle !== '' ? '@' . $handle : 'Instagram';
?>
<section class="sec igsec">
  <div class="wrap">
    <div class="sec-head" data-eyebrow="Follow along">
      <h2><i class="fa-brands fa-instagram" style="color:#d6249f"></i> Follow our journey</h2>
      <p>The latest moments from
        <a href="<?= esc($ig, 'attr') ?>" target="_blank" rel="noopener noreferrer"><b><?= esc($handle) ?></b></a>
      </p>
    </div>

    <?php if ($api): ?>
      <div class="iggrid">
        <?php foreach ($api as $m): ?>
          <a class="igcard" href="<?= esc($m['permalink'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
             aria-label="<?= esc($m['caption'] !== '' ? mb_strimwidth($m['caption'], 0, 70, '…') : 'Watch on Instagram', 'attr') ?>">
            <img src="<?= esc($m['thumb'], 'attr') ?>" alt="" loading="lazy">
            <?php if ($m['video'] !== ''): ?><span class="igplay"><i class="fa-solid fa-play"></i></span><?php endif; ?>
            <?php if ($m['caption'] !== ''): ?>
              <span class="igcap"><?= esc(mb_strimwidth($m['caption'], 0, 64, '…')) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php elseif ($reels): ?>
      <div class="igreels">
        <?php foreach ($reels as $r): ?>
          <blockquote class="instagram-media" data-instgrm-permalink="<?= esc($r, 'attr') ?>"
                      data-instgrm-version="14"></blockquote>
        <?php endforeach; ?>
      </div>
      <script async src="https://www.instagram.com/embed.js"></script>
    <?php elseif ($clips): ?>
      <div class="iggrid">
        <?php foreach ($clips as $c): ?>
          <a class="igcard" href="<?= esc($ig, 'attr') ?>" target="_blank" rel="noopener noreferrer" aria-label="Watch on Instagram">
            <video muted loop playsinline preload="none"
                   onmouseover="this.play()" onmouseout="this.pause()"
                   ontouchstart="this.paused?this.play():this.pause()">
              <source src="<?= esc($c, 'attr') ?>" type="video/mp4">
            </video>
            <span class="igplay"><i class="fa-solid fa-play"></i></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:<?= ($api || $reels || $clips) ? '26px' : '4px' ?>">
      <a class="freelist" href="<?= esc($ig, 'attr') ?>" target="_blank" rel="noopener noreferrer">
        <i class="fa-brands fa-instagram"></i> Follow us on Instagram
      </a>
    </div>
  </div>
</section>
