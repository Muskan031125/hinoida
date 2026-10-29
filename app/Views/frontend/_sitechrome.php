<?php
/**
 * Cookie notice and promotional popup.
 *
 * Ported from the original's eu-cookie.php and modal.php. Both are off until
 * an admin fills them in, and neither renders anything when empty.
 */
$ckText = hi_setting('cookie_text');
$ckOn   = hi_setting('cookie_notice') === '1' && $ckText !== '';

$popOn    = hi_setting('popup_enabled') === '1';
$popTitle = hi_setting('popup_title');
$popText  = hi_setting('popup_text');
$popOn    = $popOn && ($popTitle !== '' || $popText !== '');
$popLink  = hi_setting('popup_link');
$popCta   = hi_setting('popup_link_text', 'Find out more');
?>
<?php if ($ckOn): ?>
<div class="cookiebar" id="cookieBar" hidden>
  <p><?= esc($ckText) ?> <a href="<?= base_url('cookie') ?>">Cookie policy</a></p>
  <button type="button" id="cookieOk"><?= esc(hi_c('_sitechrome.btn_got_it', 'Got it')) ?></button>
</div>
<script>
(function(){
  var KEY = 'hi_cookie_ok', bar = document.getElementById('cookieBar');
  var seen = false;
  try { seen = localStorage.getItem(KEY) === '1'; } catch (e) { seen = true; }
  if (seen) { return; }
  bar.hidden = false;
  document.getElementById('cookieOk').addEventListener('click', function(){
    bar.hidden = true;
    try { localStorage.setItem(KEY, '1'); } catch (e) {}
  });
})();
</script>
<?php endif; ?>

<?php if ($popOn): ?>
<div class="sitepop" id="sitePop" hidden>
  <div class="sitepop-box" role="dialog" aria-modal="true" aria-labelledby="sitePopTitle">
    <button type="button" class="sitepop-x" id="sitePopX" aria-label="Close"><?= esc(hi_c('_sitechrome.btn_', '×')) ?></button>
    <?php if ($popTitle !== ''): ?><h3 id="sitePopTitle"><?= esc($popTitle) ?></h3><?php endif; ?>
    <?php if ($popText !== ''): ?><p><?= esc($popText) ?></p><?php endif; ?>
    <?php if ($popLink !== ''): ?><a class="sitepop-cta" href="<?= esc($popLink) ?>"><?= esc($popCta) ?></a><?php endif; ?>
  </div>
</div>
<script>
(function(){
  // once per browsing session, and never on top of the cookie bar
  var KEY = 'hi_pop_seen', pop = document.getElementById('sitePop');
  try { if (sessionStorage.getItem(KEY) === '1') { return; } } catch (e) { return; }
  setTimeout(function(){
    pop.hidden = false;
    try { sessionStorage.setItem(KEY, '1'); } catch (e) {}
  }, 2500);
  function close(){ pop.hidden = true; }
  document.getElementById('sitePopX').addEventListener('click', close);
  pop.addEventListener('click', function(e){ if (e.target === pop) { close(); } });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { close(); } });
})();
</script>
<?php endif; ?>
