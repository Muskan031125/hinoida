<?php
/**
 * "Noida Today" — news for people in a hurry.
 *
 * One section: topic chips on top (All, Education, Hospitals…), one big top
 * story with its photo, and the next four as small picture rows. Tapping a
 * chip switches the stories instantly (no page reload); "Show more" adds more
 * of the same topic. The last chosen topic is remembered on this device.
 *
 *  $latest  newest posts (blog rows), newest first
 *  $cats    [{id, name, slug, n}] post topics
 */
$latest = array_values(array_slice($latest ?? [], 0, 40));
if (! $latest) { return; }
$dec = static fn ($s) => html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$catName = [];
foreach (($cats ?? []) as $_c) { $catName[(int) $_c['id']] = $dec($_c['name']); }

$ago = static function ($d): string {
    $t = strtotime((string) $d);
    if (! $t || $t < strtotime('2000-01-01')) { return ''; }
    $s = time() - $t;
    if ($s < 3600)     { return max(1, (int) floor($s / 60)) . ' min ago'; }
    if ($s < 86400)    { return (int) floor($s / 3600) . ' h ago'; }
    if ($s < 86400 * 7){ $n = (int) floor($s / 86400); return $n === 1 ? 'Yesterday' : $n . ' days ago'; }
    return date('d M', $t);
};
$line = static function (array $p) use ($dec): string {
    $t = trim(preg_replace('/\s+/u', ' ', $dec(strip_tags((string) ($p['blog_text'] ?? '')))));
    $t = preg_replace('/^Noida\s*:\s*/i', '', $t);
    if (preg_match('/^(.{30,170}?[.!?])(\s|$)/u', $t, $m)) { return $m[1]; }
    return mb_strimwidth($t, 0, 150, '…');
};
$ids  = static fn (array $p): array => array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', (string) ($p['category_id'] ?? '')) ?: [])));
$img  = static fn (array $p, int $w, int $h): string => ! empty($p['blog_thumbnail']) ? hi_media($p['blog_thumbnail'], 'blog') : hi_topic_img('blog', (int) $p['id'], $w, $h, $p['title'] ?? '');
$mins = static fn (array $p): int => max(1, (int) round(str_word_count(strip_tags((string) ($p['blog_text'] ?? ''))) / 200));

// topics that actually have stories here, busiest first
$count = [];
foreach ($latest as $p) { foreach ($ids($p) as $i) { if (isset($catName[$i])) { $count[$i] = ($count[$i] ?? 0) + 1; } } }
arsort($count);
// property topics lead, the rest stay busiest-first
uksort($count, static function ($a, $b) use ($count, $catName) {
    $pa = (int) (bool) preg_match('/propert|commercial/i', $catName[$a] ?? ''); $pb = (int) (bool) preg_match('/propert|commercial/i', $catName[$b] ?? '');
    return $pb <=> $pa ?: $count[$b] <=> $count[$a];
});
$chips = array_slice($count, 0, 10, true);
$icons = ['education' => 'graduation-cap', 'school' => 'graduation-cap', 'hospital' => 'hospital', 'medical' => 'stethoscope', 'property' => 'building',
          'restaurant' => 'utensils', 'food' => 'utensils', 'fitness' => 'dumbbell', 'gym' => 'dumbbell', 'fashion' => 'shirt', 'jewel' => 'gem',
          'hotel' => 'hotel', 'banquet' => 'champagne-glasses', 'commercial' => 'briefcase', 'traffic' => 'car', 'event' => 'calendar'];
$iconFor = static function (string $n) use ($icons): string { foreach ($icons as $k => $v) { if (stripos($n, $k) !== false) { return $v; } } return 'tag'; };
?>
<section class="nt" id="noidaToday" data-show="5">
  <div class="nt-head">
    <div>
      <span class="nt-live"><i></i> Noida Today</span>
      <h2 class="nt-title">What's happening in Noida today</h2>
    </div>
    <span class="nt-meta" id="ntMeta"><?= min(5, count($latest)) ?> stories · about 1 minute</span>
  </div>

  <div class="nt-chips" role="tablist" aria-label="Choose a topic">
    <button type="button" class="nt-chip on" data-c="all" role="tab" aria-selected="true"><i class="fa-solid fa-bolt"></i> All <em><?= count($latest) ?></em></button>
    <?php foreach ($chips as $cid => $n): ?>
      <button type="button" class="nt-chip" data-c="<?= $cid ?>" role="tab" aria-selected="false"><i class="fa-solid fa-<?= $iconFor($catName[$cid]) ?>"></i> <?= esc($catName[$cid]) ?> <em><?= $n ?></em></button>
    <?php endforeach; ?>
  </div>

  <p class="nt-why" id="ntWhy" hidden><i class="fa-solid fa-wand-magic-sparkles"></i> <span></span> <button type="button" id="ntReset">Reset</button></p>
  <div class="nt-grid" id="ntGrid">
    <?php foreach ($latest as $i => $p): $c = $ids($p); $tag = $catName[$c[0] ?? 0] ?? 'News'; $a = $ago($p['added_date'] ?? ''); ?>
      <a class="nt-item<?= $i === 0 ? ' is-lead' : '' ?>" href="<?= hi_news_url($p) ?>" data-c="<?= esc(implode(',', $c), 'attr') ?>" data-id="<?= (int) $p['id'] ?>" data-ts="<?= (int) (strtotime((string) ($p['added_date'] ?? '')) ?: 0) ?>"<?= $i >= 5 ? ' hidden' : '' ?>>
        <span class="nt-ph"><img src="<?= esc($img($p, 900, 560)) ?>" alt="" loading="<?= $i < 5 ? 'eager' : 'lazy' ?>"><span class="nt-tag"><?= esc($tag) ?></span></span>
        <span class="nt-bd">
          <b><?= esc($dec($p['title'])) ?></b>
          <span class="nt-x"><?= esc($line($p)) ?></span>
          <span class="nt-foot"><?php if ($a): ?><span><i class="fa-regular fa-clock"></i> <?= esc($a) ?></span><?php endif; ?><span><i class="fa-regular fa-file-lines"></i> <?= $mins($p) ?> min read</span><span class="nt-read">Read <i class="fa-solid fa-arrow-right"></i></span></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="nt-empty" id="ntEmpty" hidden>No stories on this topic yet.</p>
  <div class="nt-more-wrap"><button type="button" class="nt-more" id="ntMore"><i class="fa-solid fa-plus"></i> Show more stories</button></div>

  <script>
  (function(){
    var box=document.getElementById('noidaToday'); if(!box) return;
    var KEY='hinoida_reads', TKEY='hinoida_topic';
    var items=[].slice.call(box.querySelectorAll('.nt-item')), wrap=box.querySelector('.nt-chips'),
        more=document.getElementById('ntMore'), meta=document.getElementById('ntMeta'), empty=document.getElementById('ntEmpty'),
        why=document.getElementById('ntWhy'), cur='all', shown=5;
    function get(){ try{ return JSON.parse(localStorage.getItem(KEY)||'{}')||{}; }catch(e){ return {}; } }
    function put(m){ try{ localStorage.setItem(KEY,JSON.stringify(m)); }catch(e){} }
    var mem=get(); mem.t=mem.t||{}; mem.r=mem.r||[];
    function cats(it){ return (it.dataset.c||'').split(',').filter(Boolean); }
    function reads(){ var n=0; for(var k in mem.t) n+=mem.t[k]; return n; }
    function nameOf(id){ var c=wrap.querySelector('.nt-chip[data-c="'+id+'"]'); if(!c) return ''; var t=c.cloneNode(true), e=t.querySelector('em'); if(e) e.remove(); return t.textContent.trim(); }
    // score: how much the reader likes the topics, plus a little for being new; already-read stories sink
    function score(it){
      var s=0; cats(it).forEach(function(c){ s+=(mem.t[c]||0); });
      var age=(Date.now()/1000-(+it.dataset.ts||0))/86400; s+=Math.max(0,3-age*0.15);
      if(mem.r.indexOf(it.dataset.id)>-1) s-=100;
      return s;
    }
    function match(it){ return cur==='all'||cur==='you'||cats(it).indexOf(cur)>-1; }
    function draw(anim){
      var list=items.filter(match);
      if(cur==='you'){ list=list.slice().sort(function(a,b){ return score(b)-score(a); }); }
      items.forEach(function(it){ it.hidden=true; it.classList.remove('is-lead','nt-in'); it.classList.toggle('is-read',mem.r.indexOf(it.dataset.id)>-1); });
      var g=document.getElementById('ntGrid');
      list.slice(0,shown).forEach(function(it,i){ g.appendChild(it); it.hidden=false; if(i===0) it.classList.add('is-lead'); if(anim){ it.style.animationDelay=(i*45)+'ms'; it.classList.add('nt-in'); } });
      list.slice(shown).forEach(function(it){ g.appendChild(it); });
      var n=Math.min(shown,list.length); meta.textContent=n+' stor'+(n===1?'y':'ies')+' · about '+Math.max(1,Math.round(n*0.25))+' minute'+(n>4?'s':'');
      more.hidden=list.length<=shown; empty.hidden=list.length>0;
      if(cur==='you'){ var top=Object.keys(mem.t).sort(function(a,b){return mem.t[b]-mem.t[a];}).slice(0,2).map(nameOf).filter(Boolean);
        why.querySelector('span').textContent='Picked for you because you read '+(top.length?top.join(' and '):'these topics'); why.hidden=false; }
      else why.hidden=true;
    }
    function select(ch,anim){
      cur=ch.dataset.c; shown=5;
      wrap.querySelectorAll('.nt-chip').forEach(function(x){ var on=x===ch; x.classList.toggle('on',on); x.setAttribute('aria-selected',on?'true':'false'); });
      draw(anim);
    }
    // favourite topics move to the front; "For you" appears once there is something to go on
    if(reads()>=2){
      var all=wrap.querySelector('.nt-chip[data-c="all"]');
      [].slice.call(wrap.querySelectorAll('.nt-chip:not([data-c="all"])')).sort(function(a,b){ var pa=/propert|commercial/i.test(a.textContent)?1:0, pb=/propert|commercial/i.test(b.textContent)?1:0; return (pb-pa)||((mem.t[b.dataset.c]||0)-(mem.t[a.dataset.c]||0)); })
        .forEach(function(c){ wrap.appendChild(c); });
      var you=document.createElement('button'); you.type='button'; you.className='nt-chip is-you'; you.dataset.c='you'; you.setAttribute('role','tab');
      you.innerHTML='<i class="fa-solid fa-wand-magic-sparkles"></i> For you'; wrap.insertBefore(you,all);
    }
    wrap.querySelectorAll('.nt-chip').forEach(function(ch){ ch.addEventListener('click',function(){
      try{ localStorage.setItem(TKEY,ch.dataset.c); }catch(e){} select(ch,true); }); });
    more.addEventListener('click',function(){ shown+=4; draw(false); });
    document.getElementById('ntReset').addEventListener('click',function(){ try{ localStorage.removeItem(KEY); localStorage.removeItem(TKEY); }catch(e){} location.reload(); });
    // learn: every story opened from here counts for its topics
    function learn(el){ cats(el).forEach(function(c){ mem.t[c]=(mem.t[c]||0)+1; }); var id=el.dataset.id; if(id&&mem.r.indexOf(id)<0){ mem.r.push(id); mem.r=mem.r.slice(-200); } put(mem); }
    items.forEach(function(it){ it.addEventListener('click',function(){ learn(it); }); });
    document.querySelectorAll('.bgrid .bcard[data-c]').forEach(function(c){ c.addEventListener('click',function(){ learn(c); }); });
    // start on: "For you" when we know the reader, else their last topic, else All
    var saved=null; try{ saved=localStorage.getItem(TKEY); }catch(e){}
    var start=(saved&&wrap.querySelector('.nt-chip[data-c="'+saved+'"]'))||wrap.querySelector('.nt-chip[data-c="you"]')||wrap.querySelector('.nt-chip[data-c="all"]');
    select(start,false);
  })();
  </script>
</section>
