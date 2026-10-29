<?php
/**
 * Photo viewer.
 *
 * Gallery images used to be plain links to the raw file with target="_blank",
 * which dumped people on a bare image in a new tab with no way back —
 * especially awkward on a phone. This keeps them on the page: Esc, the close
 * button, a click on the backdrop or the browser's own Back all get out, and
 * arrow keys move between photos.
 */
?>
<div class="lbox" id="lbox" hidden role="dialog" aria-modal="true" aria-label="Photo viewer">
  <button type="button" class="lbox-x" id="lboxX" aria-label="Close"><?= esc(hi_c('_lightbox.btn_', '×')) ?></button>
  <button type="button" class="lbox-nav lbox-prev" id="lboxPrev" aria-label="Previous photo">&#8249;</button>
  <img class="lbox-img" id="lboxImg" alt="">
  <button type="button" class="lbox-nav lbox-next" id="lboxNext" aria-label="Next photo">&#8250;</button>
  <div class="lbox-count" id="lboxCount"></div>
</div>
<script>
(function(){
  var box = document.getElementById('lbox'), img = document.getElementById('lboxImg'),
      cnt = document.getElementById('lboxCount');
  var shots = [], at = 0, lastFocus = null;

  function collect(){
    shots = [].slice.call(document.querySelectorAll('.gallery-grid a[data-lb]'));
  }
  function show(i){
    if (!shots.length) { return; }
    at = (i + shots.length) % shots.length;
    var a = shots[at];
    img.src = a.getAttribute('href');
    img.alt = (a.querySelector('img') || {}).alt || '';
    cnt.textContent = (at + 1) + ' / ' + shots.length;
  }
  function open(i){
    collect();
    lastFocus = document.activeElement;
    show(i);
    box.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    // a history entry, so the phone's Back button closes the photo
    // instead of leaving the listing
    history.pushState({lbox:1}, '');
    document.getElementById('lboxX').focus();
  }
  function close(fromPop){
    if (box.hidden) { return; }
    box.hidden = true;
    img.removeAttribute('src');
    document.documentElement.style.overflow = '';
    if (lastFocus) { try { lastFocus.focus(); } catch(e){} }
    if (!fromPop && history.state && history.state.lbox) { history.back(); }
  }

  document.addEventListener('click', function(e){
    var a = e.target.closest ? e.target.closest('.gallery-grid a[data-lb]') : null;
    if (!a) { return; }
    e.preventDefault();
    collect();
    open(shots.indexOf(a));
  });

  document.getElementById('lboxX').addEventListener('click', function(){ close(); });
  document.getElementById('lboxPrev').addEventListener('click', function(e){ e.stopPropagation(); show(at - 1); });
  document.getElementById('lboxNext').addEventListener('click', function(e){ e.stopPropagation(); show(at + 1); });
  // a click on the backdrop, but not on the photo itself
  box.addEventListener('click', function(e){ if (e.target === box) { close(); } });
  window.addEventListener('popstate', function(){ close(true); });
  document.addEventListener('keydown', function(e){
    if (box.hidden) { return; }
    if (e.key === 'Escape')     { close(); }
    if (e.key === 'ArrowLeft')  { show(at - 1); }
    if (e.key === 'ArrowRight') { show(at + 1); }
  });
})();
</script>
