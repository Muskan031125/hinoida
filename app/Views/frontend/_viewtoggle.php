<?php
/**
 * Grid / list switch for a results page.
 *
 * Toggles a class on .lgrid — the cards themselves are unchanged, CSS does
 * the reshaping. The choice is remembered so it survives paging and filters.
 */
?>
<div class="viewtoggle" role="group" aria-label="Result layout">
  <button type="button" data-view="grid" title="Grid view"><i class="fa-solid fa-grip"></i> Grid</button>
  <button type="button" data-view="list" title="List view"><i class="fa-solid fa-list"></i> List</button>
</div>
<script>
(function(){
  var KEY = 'hi_view';
  var btns = document.querySelectorAll('.viewtoggle [data-view]');
  if (!btns.length) { return; }

  function apply(v){
    document.querySelectorAll('.lgrid').forEach(function(g){ g.classList.toggle('aslist', v === 'list'); });
    btns.forEach(function(b){ b.classList.toggle('on', b.dataset.view === v); });
  }

  var saved = 'grid';
  try { saved = localStorage.getItem(KEY) || 'grid'; } catch (e) {}

  // this partial renders above the grid, so the grid does not exist yet
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ apply(saved); });
  } else {
    apply(saved);
  }

  btns.forEach(function(b){
    b.addEventListener('click', function(){
      apply(b.dataset.view);
      try { localStorage.setItem(KEY, b.dataset.view); } catch (e) {}
    });
  });
})();
</script>
