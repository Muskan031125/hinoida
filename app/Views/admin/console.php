<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box}html,body{margin:0;height:100%}
body{font-family:'Inter',sans-serif;color:#1c2432;
  background:linear-gradient(180deg,#eaf0f9 0%,#f0ecf8 50%,#eaf4f1 100%) fixed}
body::before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
  background:radial-gradient(600px 420px at 8% -6%,rgba(23,65,166,.12),transparent 60%),
             radial-gradient(560px 460px at 100% 6%,rgba(120,60,200,.10),transparent 60%)}
.shell{display:flex;min-height:100vh;position:relative;z-index:1}
/* glass surfaces */
@keyframes admIn{from{transform:translateY(16px)}to{transform:none}}
@keyframes admFade{from{opacity:0}to{opacity:1}}
.stat,.panel{animation:admIn .5s cubic-bezier(.2,.7,.3,1) both}
.stat:nth-child(2){animation-delay:.06s}.stat:nth-child(3){animation-delay:.12s}.stat:nth-child(4){animation-delay:.18s}.stat:nth-child(5){animation-delay:.24s}
.content>*{animation:admFade .5s .1s both}
.nav a,.stat,.panel,.chip,.av{transition:transform .22s cubic-bezier(.2,.7,.3,1),box-shadow .22s,background .2s,color .2s}
.nav a:hover{transform:translateX(3px)}
.stat:hover{transform:translateY(-4px);box-shadow:0 16px 34px rgba(15,28,51,.12)}
@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
.side{width:260px;flex:0 0 260px;background:#0b1a33;color:#c7d2e4;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto;overscroll-behavior:contain}
.side::-webkit-scrollbar{width:8px}
.side::-webkit-scrollbar-thumb{background:rgba(255,255,255,.16);border-radius:8px}
.side::-webkit-scrollbar-track{background:transparent}
.brand{display:flex;align-items:center;gap:12px;padding:24px 22px;border-bottom:1px solid rgba(255,255,255,.07);font-weight:800;color:#fff}
.brand span{background:#1741a6;padding:4px 9px;border-radius:9px}
.nav{flex:1 0 auto;padding:14px;display:flex;flex-direction:column;gap:2px}
.nav a{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;color:#a9b6ce;font-size:14px;font-weight:500;text-decoration:none;transition:.18s}
.nav a i{width:18px;text-align:center;color:#6b7fa2}
.nav a:hover{background:rgba(255,255,255,.05);color:#fff}
.nav a.on{background:linear-gradient(90deg,rgba(23,65,166,.9),rgba(23,65,166,.55));color:#fff;box-shadow:0 6px 18px rgba(23,65,166,.35)}
.nav a.on i{color:#fff}
.badge{margin-left:auto;background:#e0446b;color:#fff;font-size:11px;font-weight:800;min-width:20px;height:20px;line-height:20px;text-align:center;border-radius:10px}
.side-foot{padding:16px 18px;border-top:1px solid rgba(255,255,255,.07)}.side-foot a{color:#8697b5;font-size:13px;text-decoration:none}
.main{flex:1;min-width:0}
.top{display:flex;align-items:center;justify-content:space-between;padding:24px 40px;background:rgba(255,255,255,.72);backdrop-filter:blur(16px) saturate(1.3);-webkit-backdrop-filter:blur(16px) saturate(1.3);border-bottom:1px solid rgba(255,255,255,.5);position:sticky;top:0;z-index:5}
.eyebrow{margin:0 0 4px;font-size:11px;font-weight:600;letter-spacing:.22em;text-transform:uppercase;color:#9aa6bd}
.h{margin:0;font-family:'Inter',system-ui,sans-serif;font-weight:600;font-size:32px;color:#0f1c33}
.who{display:flex;align-items:center;gap:11px}.av{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#1741a6,#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700}
.content{padding:34px 40px;max-width:1320px;margin-left:auto;margin-right:auto;box-sizing:border-box}
.top{padding-left:max(40px,calc((100% - 1320px) / 2 + 40px))!important;padding-right:max(40px,calc((100% - 1320px) / 2 + 40px))!important}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:20px;margin-bottom:30px}
.stat{background:rgba(255,255,255,.66);backdrop-filter:blur(14px) saturate(1.2);-webkit-backdrop-filter:blur(14px) saturate(1.2);border:1px solid rgba(255,255,255,.6);border-radius:16px;padding:24px;position:relative;overflow:hidden}
.stat::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:linear-gradient(#1741a6,#3b82f6)}
.stat b{display:block;font-family:'Inter',system-ui,sans-serif;font-size:40px;font-weight:600;color:#0f1c33;line-height:1}
.stat span{display:block;margin-top:7px;font-size:12px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#93a0b7}
.panel{background:rgba(255,255,255,.7);backdrop-filter:blur(14px) saturate(1.2);-webkit-backdrop-filter:blur(14px) saturate(1.2);border:1px solid rgba(255,255,255,.6);border-radius:16px;overflow:hidden;box-shadow:0 6px 20px rgba(15,28,51,.05)}
table{width:100%;border-collapse:collapse;font-size:13.5px}
th{text-align:left;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#9aa6bd;padding:15px 20px;background:#fafbfd;border-bottom:1px solid #eceef3}
td{padding:15px 20px;border-bottom:1px solid #f1f3f7;color:#33405a}
tr:hover td{background:#f8faff}
.chip{font-size:11.5px;font-weight:700;padding:4px 11px;border-radius:20px}
.chip-live{background:#e6f6ec;color:#1a7a3d}.chip-pending{background:#fff3e0;color:#b26a00}.chip-red{background:#fdecef;color:#c0324b}
.sec-title{font-family:'Inter',system-ui,sans-serif;font-size:24px;color:#0f1c33;margin:6px 0 14px}
/* sidebar accordions */
.navgrp{margin-bottom:2px}
.navgrp-h{width:100%;display:flex;align-items:center;gap:11px;padding:10px 14px;border:0;border-radius:10px;
  background:none;color:#8697b5;font:inherit;font-size:11.5px;font-weight:700;letter-spacing:.1em;
  text-transform:uppercase;cursor:pointer;text-align:left}
.navgrp-h:hover{background:rgba(255,255,255,.05);color:#dbe4f5}
.navgrp-h>i:first-child{width:18px;text-align:center;font-size:13px;color:#6b7fa2}
.navgrp-h span{flex:1;min-width:0}
.navgrp-c{font-size:10px;transition:transform .2s;color:#6b7fa2}
.navgrp.open .navgrp-c{transform:rotate(180deg)}
.navgrp.open .navgrp-h{color:#dbe4f5}
.navgrp-b{display:none;padding:2px 0 6px}
.navgrp.open .navgrp-b{display:block}
.navgrp-b a{padding-left:30px;font-size:13.5px}
.navgrp-h .badge{margin-left:auto;margin-right:4px}

.admlbl{display:block;font-size:12px;font-weight:600;color:#5a6b86;margin:12px 0 6px}
.adminp{width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:11px 13px;font-size:14px;
  background:#fff;color:#0f1c33;font-family:inherit}
.adminp:focus{outline:0;border-color:#1741a6;box-shadow:0 0 0 3px rgba(23,65,166,.09)}
textarea.adminp{resize:vertical;min-height:70px}
@media(max-width:900px){.admgrid{grid-template-columns:1fr !important}}
/* phones and small tablets: menu on top, page full width, wide tables scroll inside themselves */
@media(max-width:820px){
  .shell{flex-direction:column}
  .side{width:auto!important;flex:none!important;height:auto!important;position:relative!important;max-height:none}
  .side .navgrp-b{padding-left:0}
  .main{width:100%;min-width:0}
  .main>*{max-width:100%;box-sizing:border-box}
  .admgrid>*{min-width:0}
  .main [style*="grid-template-columns"]{grid-template-columns:1fr!important}
  .main table{display:block;overflow-x:auto;white-space:nowrap}
  .main img,.main iframe{max-width:100%}
  .top{flex-wrap:wrap;gap:8px;padding:14px 16px!important}
  .side .brand{display:flex;align-items:center;justify-content:space-between}
  .adm-menu{display:inline-flex!important}
  .side:not(.open) .nav,.side:not(.open) .side-foot{display:none!important}
}
.adm-menu{display:none;align-items:center;gap:6px;background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:8px 14px;font:600 14px/1 inherit;cursor:pointer}
/* ===== POLISH LAYER — same editorial calm as the rest of the site ===== */
:root{--line:#e8e4dd;--paper:#fbfaf8;--blue:#1741a6}
body{background:#fbfaf8}
body::before{display:none}
.main{background:#fbfaf8}
.top{background:#fbfaf8;border-bottom:1px solid #e8e4dd;backdrop-filter:none;-webkit-backdrop-filter:none}
.h{font-weight:700;letter-spacing:-.01em;font-size:24px}
.eyebrow{letter-spacing:.2em}
.panel{background:#fff;border:1px solid #e8e4dd;border-radius:4px;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none}
.stat{background:#fff;border:1px solid #e8e4dd;border-radius:4px;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none;padding:26px}
.stat::before{display:none}
.stat b{font-weight:700;font-size:30px;letter-spacing:-.02em}
.stat span{font-size:10.5px;letter-spacing:.16em}
.stat:hover{transform:none;box-shadow:0 10px 28px rgba(26,31,43,.07)}
th{background:transparent;border-bottom:1px solid #e8e4dd;font-size:10.5px;letter-spacing:.14em;padding:14px 20px}
td{border-bottom:1px solid #f3f0ea;padding:16px 20px}
tr:hover td{background:#fbfaf8}
.sec-title{font-weight:700;font-size:20px;letter-spacing:-.015em}
.chip{border-radius:2px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;font-size:10px}
.abtn{border-radius:2px;font-weight:500;letter-spacing:.04em}
.btn{border-radius:2px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;font-size:12px;padding:13px 24px}
.adminp{border-radius:2px;border-color:#e8e4dd}
.admlbl{font-size:10.5px;letter-spacing:.14em;text-transform:uppercase}
.side{background:#12192b}
.form{border-radius:4px;box-shadow:none;border-color:#e8e4dd}
</style>
</head>
<body>
<?php
// Sidebar grouped into accordions — 30-odd entries is too many as one list.
$navGroups = [
  ['Overview', 'gauge-high', [
    ['overview','admin','gauge-high','Dashboard'],
    ['analytics','admin/analytics','chart-line','Analytics'],
    ['reports','admin/reports','file-csv','Reports'],
  ]],
  ['Needs attention', 'bell', [
    ['pending','admin/pending','clock','Pending listings'],
    ['approvals','admin/approvals','user-check','Business approvals'],
    ['claims','admin/claims','hand-holding-heart','Ownership claims'],
    ['requests','admin/requests','comment-dots','Change requests'],
    ['reported','admin/reported','flag','Reported listings'],
    ['comments','admin/comments','message','Blog comments'],
  ]],
  ['Listings', 'store', [
    ['listings','admin/listings','store','All listings'],
    ['changes','admin/changes','pen-to-square','Change requests'],
    ['import','admin/import','file-import','Import'],
    ['bookings','admin/bookings','calendar-check','Bookings'],
    ['reviews','admin/reviews','star-half-stroke','Reviews'],
    ['quality','admin/quality','ranking-star','Rating labels'],
  ]],
  ['Taxonomy', 'layer-group', [
    ['categories','admin/categories','layer-group','Categories'],
    ['places','admin/places','city','Cities &amp; states'],
    ['areas','admin/areas','location-dot','Areas'],
    ['amenities','admin/amenities','star','Amenities'],
  ]],
  ['Content', 'newspaper', [
    ['c_blog','admin/content/blog','newspaper','Blog / News'],
    ['c_events','admin/content/events','calendar-day','Events'],
    ['c_offers','admin/content/offers','tags','Offers'],
    ['c_qa','admin/content/qa','comments','Q&amp;A'],
  ]],
  ['People', 'users', [
    ['users','admin/users','users','Members'],
    ['messages','admin/messages','envelope-open-text','Messages'],
    ['team','admin/team','user-shield','Admin team'],
  ]],
  ['Money', 'indian-rupee-sign', [
    ['packages','admin/packages','box-open','Packages'],
    ['revenue','admin/revenue','indian-rupee-sign','Revenue'],
  ]],
  ['Setup', 'gear', [
    ['seo','admin/seo','magnifying-glass','SEO'],
    ['map','admin/map','location-dot','Map'],
    ['seo-bulk','admin/seo-bulk','file-csv','SEO bulk edit'],
    ['frontend','admin/frontend','palette','Frontend'],
    ['sitetext','admin/sitetext','pen-nib','Site text &amp; images'],
    ['language','admin/language','language','Language'],
    ['addons','admin/addons','puzzle-piece','Add-ons'],
    ['email','admin/email','paper-plane','Email queue'],
    ['settings','admin/settings','gear','Settings'],
    ['profile','admin/profile','user-gear','My account'],
  ]],
];
$e=$edit??null; $b=base_url();
$fld=static function($r,$k){return isset($r[$k])?esc($r[$k]):'';}; ?>
<style>
.note{background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:12px;padding:13px 18px;font-size:14px;margin-bottom:20px}
.split{display:flex;gap:24px;align-items:flex-start}
.form{flex:0 0 320px;background:#fff;border:1px solid #e8ebf1;border-radius:16px;padding:22px;box-shadow:0 6px 20px rgba(15,28,51,.05)}
.form h3{font-family:'Inter',system-ui,sans-serif;font-size:21px;margin:0 0 14px;color:#0f1c33}
.form label{display:block;font-size:12px;font-weight:600;color:#5a6b86;margin:12px 0 6px}
.form input,.form select{width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:11px 13px;font-size:14px;font-family:inherit}
.abtn{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;font-family:inherit;padding:8px 13px;border-radius:9px;border:0;cursor:pointer;text-decoration:none}
.ok{background:#e6f6ec;color:#15803d}.ok:hover{background:#15803d;color:#fff}
.no{background:#fdecec;color:#c0392b}.no:hover{background:#c0392b;color:#fff}
.gh{background:#eef1f6;color:#33405a}.gh:hover{background:#1741a6;color:#fff}
.acts{display:flex;gap:8px;justify-content:flex-end}
</style>
<div class="shell">
  <aside class="side">
    <div class="brand"><div style="display:flex;align-items:center;gap:12px"><span>Hi</span> NOIDA</div><button type="button" class="adm-menu" onclick="this.closest('.side').classList.toggle('open');this.textContent=this.closest('.side').classList.contains('open')?'Close':'Menu'">Menu</button></div>
    <nav class="nav">
      <?php foreach($navGroups as $gi => $g):
        $items = $g[2];
        // the group holding the current page starts open
        $isHere = (bool) array_filter($items, static fn($i) => $i[0] === $sec);
        // anything needing attention in this group?
        $badge = 0;
        foreach ($items as $i) { if ($i[0] === 'pending') { $badge += (int) $stats['pending']; } if ($i[0] === 'approvals') { $badge += (int) $stats['approvals']; } }
      ?>
        <div class="navgrp<?= $isHere ? ' open' : '' ?>" data-grp="<?= $gi ?>">
          <button type="button" class="navgrp-h">
            <i class="fa-solid fa-<?= $g[1] ?>"></i>
            <span><?= esc($g[0]) ?></span>
            <?php if ($badge > 0): ?><em class="badge"><?= $badge ?></em><?php endif; ?>
            <i class="fa-solid fa-chevron-down navgrp-c"></i>
          </button>
          <div class="navgrp-b">
            <?php foreach($items as $n): ?>
              <a href="<?= base_url($n[1]) ?>" class="<?= $sec===$n[0]?'on':'' ?>">
                <i class="fa-solid fa-<?= $n[2] ?>"></i> <?= $n[3] ?>
                <?php if($n[0]==='pending' && $stats['pending']>0): ?><em class="badge"><?= $stats['pending'] ?></em><?php endif; ?>
                <?php if($n[0]==='approvals' && $stats['approvals']>0): ?><em class="badge"><?= $stats['approvals'] ?></em><?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <script>
      (function(){
        var KEY='hi_admnav';
        var open={};
        try { open=JSON.parse(localStorage.getItem(KEY)||'{}'); } catch(e){}
        document.querySelectorAll('.navgrp').forEach(function(g){
          var id=g.dataset.grp;
          // a remembered choice wins, except the group we are currently in
          if(!g.classList.contains('open') && open[id]) g.classList.add('open');
          g.querySelector('.navgrp-h').addEventListener('click',function(){
            g.classList.toggle('open');
            open[id]=g.classList.contains('open');
            try { localStorage.setItem(KEY,JSON.stringify(open)); } catch(e){}
          });
        });
      })();
      </script>
    </nav>
    <div class="side-foot"><a href="<?= base_url('/') ?>"><i class="fa-solid fa-arrow-left"></i> Back to site</a></div>
  </aside>
  <main class="main">
    <header class="top">
      <div><p class="eyebrow">HiNoida Directory</p><h1 class="h"><?= esc(ucfirst($sec)) ?></h1></div>
      <div class="who"><span class="av"><?= esc(hi_initial($name)) ?></span><b><a href="<?= $b ?>admin/profile" style="color:inherit"><?= esc($name) ?></a></b> <a href="<?= base_url('logout') ?>" style="margin-left:8px;color:#8697b5"><i class="fa-solid fa-arrow-right-from-bracket"></i></a></div>
    </header>
    <div class="content">
      <?php if($sec==='overview'): ?>
        <div class="stats">
          <div class="stat"><b><?= number_format($stats['listings']) ?></b><span>Listings</span></div>
          <div class="stat"><b><?= number_format($stats['pending']) ?></b><span>Pending</span></div>
          <div class="stat"><b><?= number_format($stats['categories']) ?></b><span>Categories</span></div>
          <div class="stat"><b><?= number_format($stats['users']) ?></b><span>Members</span></div>
        </div>
        <p style="color:#8b98b0">Welcome back. Use the sidebar to manage listings, approvals and members. This CI4 console mirrors the Discover admin and connects to the same data as hinoida's backend.</p>
      <?php else: ?>
        <?php if(!empty($saved)): ?><div class="note"><i class="fa-solid fa-circle-check"></i> <?= esc($saved) ?></div><?php endif; ?>

        <?php if($sec==='pending'): ?>
          <h2 class="sec-title">Businesses awaiting approval (<?= count($rows) ?>)</h2>
          <div class="panel"><table>
            <tr><th>Business</th><th>What it is</th><th>Phone</th><th style="text-align:right">Decision</th></tr>
            <?php if(!$rows): ?><tr><td colspan="4" style="text-align:center;color:#9aa6bd;padding:40px">Nothing pending 🎉</td></tr><?php endif; ?>
            <?php helper('property'); ?>
            <?php foreach($rows as $r): ?><tr>
              <td><b><?= esc(html_entity_decode((string) ($r['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b>
                <a href="<?= $b ?>admin/listing/<?= (int)$r['id'] ?>" style="display:block;font-size:11.5px;color:#7c8aa3;text-decoration:none">Open &rsaquo;</a></td>
              <?php /* Approving blind is the wrong ask: a property is judged on its
                       price, size and RERA number, none of which a name and a phone
                       number tell you. */ ?>
              <td style="color:#94a1b8;font-size:12.5px">
                <?php if(hi_is_property($r)): $pp=hi_property_of((int)$r['id']); ?>
                  <span style="display:inline-block;font-size:10.5px;font-weight:700;letter-spacing:.04em;padding:2px 8px;border-radius:20px;background:#e8f0ff;color:#1741a6">PROPERTY</span>
                  <?= esc(hi_property_price($pp)) ?>
                  <?php $ps=hi_property_summary($pp); if($ps!==''): ?><br><?= esc($ps) ?><?php endif; ?>
                  <?php if(trim((string)($pp['rera'] ?? ''))!==''): ?><br>RERA <?= esc($pp['rera']) ?><?php endif; ?>
                <?php else: ?>
                  <?= esc(ucfirst((string)($r['listing_type'] ?? 'general'))) ?>
                <?php endif; ?>
              </td>
              <td style="color:#94a1b8"><?= $fld($r,'phone') ?></td>
              <td><div class="acts">
                <form method="post" action="<?= $b ?>admin/moderate" style="display:inline"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="abtn ok" name="decision" value="approve"><i class="fa-solid fa-check"></i> Approve</button></form>
                <form method="post" action="<?= $b ?>admin/moderate" style="display:inline"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="abtn no" name="decision" value="reject"><i class="fa-solid fa-xmark"></i> Reject</button></form>
              </div></td></tr><?php endforeach; ?>
          </table></div>

        <?php elseif(in_array($sec,['categories','areas','amenities'],true)): ?>
          <h2 class="sec-title"><?= esc(ucfirst($sec)) ?> (<?= count($rows) ?>)</h2>
          <div class="split">
            <div class="panel" style="flex:1;overflow:hidden"><table>
              <tr><th>Name</th><th style="text-align:right"></th></tr>
              <?php foreach($rows as $r): ?><tr><td><b><?= esc(html_entity_decode((string) ($r['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b></td>
                <td><div class="acts">
                  <a class="abtn gh" href="<?= $b ?>admin/<?= $sec ?>?edit=<?= (int)$r['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                  <form method="post" action="<?= $b ?>admin/crud/<?= $sec ?>" onsubmit="return confirm('Delete?')" style="display:inline"><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="abtn no"><i class="fa-solid fa-trash"></i></button></form>
                </div></td></tr><?php endforeach; ?>
            </table></div>
            <form class="form" method="post" action="<?= $b ?>admin/crud/<?= $sec ?>">
              <h3><?= $e?'Edit':'Add' ?> <?= rtrim($sec,'s') ?></h3>
              <input type="hidden" name="act" value="save"><input type="hidden" name="id" value="<?= $e?(int)$e['id']:0 ?>">
              <label>Name</label><input name="name" required value="<?= $e?esc(html_entity_decode((string) ($e['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')):'' ?>">
              <?php if($sec!=='amenities'): ?><label>Slug (blank = auto)</label><input name="slug" value="<?= $e?$fld($e,'slug'):'' ?>"><?php endif; ?>
              <?php if($sec==='amenities'): ?><label>Icon (e.g. wifi)</label><input name="icon" value="<?= $e?$fld($e,'icon'):'' ?>"><?php endif; ?>
              <button class="abtn ok" type="submit" style="width:100%;justify-content:center;padding:12px;margin-top:16px"><i class="fa-solid fa-check"></i> <?= $e?'Save':'Add' ?></button>
              <?php if($e): ?><a href="<?= $b ?>admin/<?= $sec ?>" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:#8b98b0">Cancel</a><?php endif; ?>
            </form>
          </div>

        <?php elseif($sec==='users'): ?>
          <?php $x=$extra??[]; $uc=$x['counts']??[]; $urole=(int)($x['role']??0); $uq=(string)($x['q']??''); ?>
          <h2 class="sec-title">Members<?= isset($x['total'])?' ('.number_format($x['total']).')':'' ?></h2>
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:-4px 0 16px">
            <?php foreach([0=>'All',2=>'Business',3=>'Customers',1=>'Admins'] as $rk=>$rl):
                    $n=$rk===0?($uc['all']??0):($uc[$rk]??0);
                    $qs=http_build_query(array_filter(['role'=>$rk?:null,'q'=>$uq?:null])); ?>
              <a href="<?= $b ?>admin/users<?= $qs?'?'.$qs:'' ?>"
                 style="padding:7px 13px;border-radius:20px;font-size:12.5px;font-weight:700;text-decoration:none;<?= $urole===$rk?'background:#1741a6;color:#fff':'background:#eef3fc;color:#1741a6' ?>">
                <?= $rl ?> (<?= number_format($n) ?>)
              </a>
            <?php endforeach; ?>
            <form method="get" action="<?= $b ?>admin/users" style="margin-left:auto;display:flex;gap:7px">
              <?php if($urole): ?><input type="hidden" name="role" value="<?= $urole ?>"><?php endif; ?>
              <input name="q" value="<?= esc($uq) ?>" placeholder="Search name, email, phone"
                     style="border:1px solid #dfe4ec;border-radius:9px;padding:8px 12px;font-size:13px;min-width:220px">
              <button class="abtn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
              <?php if($uq!==''||$urole): ?><a class="abtn" href="<?= $b ?>admin/users">Clear</a><?php endif; ?>
            </form>
          </div>
          <?php if(!$rows): ?>
            <div class="panel" style="padding:36px;text-align:center;color:#9aa6bd">
              No members match<?= $uq!==''?' “'.esc($uq).'”':'' ?>.
            </div>
          <?php else: ?>
          <div class="panel"><table><tr><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Status</th><th></th></tr>
            <?php foreach($rows as $r): $role=(int)($r['role_id']??3); $rl=$role===1?'Admin':($role===2?'Business':'Member'); $ver=(int)($r['is_verified']??1)===1; $app=(int)($r['approved']??1)===1; ?>
            <tr>
              <td><b><a href="<?= $b ?>admin/user/<?= (int)$r['id'] ?>" style="color:#0f1c33"><?= esc(html_entity_decode((string) ($r['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></a></b></td>
              <td style="color:#94a1b8"><?= esc($r['email']) ?></td>
              <td><span class="chip <?= $role===1?'chip-live':'' ?>"><?= $rl ?></span></td>
              <td><?= $ver?'<span class="chip chip-live"><i class="fa-solid fa-check"></i></span>':'<span class="chip chip-pending">No</span>' ?></td>
              <td><?= $role===2?($app?'<span class="chip chip-live">Approved</span>':'<span class="chip chip-pending">Pending</span>'):'—' ?></td>
              <td style="text-align:right"><a class="abtn gh" href="<?= $b ?>admin/user/<?= (int)$r['id'] ?>"><i class="fa-solid fa-gear"></i> Manage</a></td>
            </tr><?php endforeach; ?>
          </table></div>
          <?php $pages=(int)($x['pages']??1); $pg=(int)($x['page']??1); ?>
          <?php if($pages>1): ?>
            <div style="display:flex;gap:7px;justify-content:center;margin-top:18px;flex-wrap:wrap">
              <?php for($i=max(1,$pg-3);$i<=min($pages,$pg+3);$i++):
                      $qs=http_build_query(array_filter(['role'=>$urole?:null,'q'=>$uq?:null,'page'=>$i>1?$i:null])); ?>
                <a href="<?= $b ?>admin/users<?= $qs?'?'.$qs:'' ?>"
                   style="padding:7px 12px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;<?= $i===$pg?'background:#1741a6;color:#fff':'background:#eef3fc;color:#1741a6' ?>"><?= $i ?></a>
              <?php endfor; ?>
              <span style="align-self:center;color:#9aa6bd;font-size:12.5px">page <?= $pg ?> of <?= $pages ?></span>
            </div>
          <?php endif; ?>
          <?php endif; ?>

        <?php elseif($sec==='approvals'): ?>
          <h2 class="sec-title">Business approvals (<?= count($rows) ?>)</h2>
          <div class="panel"><table><tr><th>Business</th><th>Email</th><th>Phone</th><th style="text-align:right">Action</th></tr>
            <?php if(!$rows): ?><tr><td colspan="4" style="text-align:center;color:#9aa6bd;padding:40px">No businesses awaiting approval. 🎉</td></tr><?php endif; ?>
            <?php foreach($rows as $r): ?><tr>
              <td><b><?= esc(html_entity_decode((string) ($r['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b> <span class="chip chip-pending">email <?= (int)($r['is_verified']??0)===1?'confirmed':'unconfirmed' ?></span></td>
              <td style="color:#94a1b8"><?= esc($r['email']) ?></td>
              <td style="color:#94a1b8"><?= esc($r['phone']??'') ?></td>
              <td style="text-align:right"><a class="abtn" style="background:#0b8a3d;color:#fff" href="<?= $b ?>admin/approve-user/<?= (int)$r['id'] ?>" onclick="return confirm('Approve this business?')"><i class="fa-solid fa-check"></i> Approve</a></td>
            </tr><?php endforeach; ?>
          </table></div>

        <?php elseif($sec==='import'): ?>
          <h2 class="sec-title">Import listings</h2>
          <div class="panel" style="padding:24px;max-width:620px">
            <p style="color:#5a6b86;font-size:13.5px;line-height:1.6;margin:0 0 16px">
              Upload a CSV and each row becomes a listing. Columns are matched by their header name,
              so the order does not matter and extra columns are ignored. Only <b>name</b> is required.
            </p>
            <p style="margin:0 0 18px;padding:12px 15px;background:#f4f7fc;border-radius:9px;font-size:12.5px;color:#5a6b86">
              Recognised headers: <code>name, phone, email, address, locality, category, website, description, status</code><br>
              <b>locality</b> and <b>category</b> must match names you already have — anything unknown is
              reported back and left blank. Rows with the same <b>name and phone</b> as an existing
              listing update it instead of creating a duplicate. Everything arrives as
              <b>pending</b> unless the status column says otherwise.
            </p>
            <a class="abtn gh" href="<?= $b ?>admin/import/template" style="margin-bottom:16px;display:inline-block">
              <i class="fa-solid fa-download"></i> Download template CSV</a>
            <form method="post" action="<?= $b ?>admin/import/listings" enctype="multipart/form-data">
              <label class="admlbl">CSV file</label>
              <input class="adminp" type="file" name="csv" accept=".csv,text/csv" required>
              <button class="btn" style="margin-top:14px"><i class="fa-solid fa-file-import"></i> Import</button>
            </form>
          </div>

        <?php elseif($sec==='packages'): $e=$edit?:[]; ?>
          <h2 class="sec-title">Packages (<?= count($rows) ?>)</h2>
          <?php /* What someone gets before they buy anything. Kept next to the
                   paid packages because it is the same decision. */ ?>
          <form method="post" action="<?= $b ?>admin/settings/save" class="panel" style="padding:18px 20px;margin-bottom:16px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:220px">
              <label style="display:block;font-size:12px;font-weight:600;color:#5a6b86;margin:0 0 6px">Free listings per member</label>
              <input name="free_listing_limit" type="number" min="0" value="<?= esc(hi_setting('free_listing_limit', '0')) ?>"
                style="width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:11px 13px;font-size:14px">
              <p style="font-size:11.5px;color:#93a0b6;margin:6px 0 0">
                How many listings someone with no paid package may post. <b>0 means unlimited.</b>
                Properties are always unlimited and never count towards this.</p>
            </div>
            <button class="abtn ok" type="submit" style="padding:11px 22px"><i class="fa-solid fa-check"></i> Save</button>
          </form>
          <div style="display:grid;grid-template-columns:1fr 330px;gap:18px;align-items:start" class="admgrid">
            <div class="panel"><table><tr><th>Package</th><th>Price</th><th>Listings</th><th>Validity</th><th style="text-align:right">Action</th></tr>
              <?php if(!$rows): ?><tr><td colspan="5" style="text-align:center;color:#9aa6bd;padding:40px">No packages yet — add one on the right.</td></tr><?php endif; ?>
              <?php foreach($rows as $r): ?><tr>
                <td><b><?= esc(html_entity_decode((string) ($r['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b>
                  <?php if(!empty($r['is_recommended'])): ?><span class="chip chip-live">Recommended</span><?php endif; ?>
                  <?php if(!empty($r['featured'])): ?><span class="chip chip-adm">Featured</span><?php endif; ?>
                  <?php if(!empty($r['description'])): ?><div style="color:#94a1b8;font-size:12.5px;margin-top:3px"><?= esc($r['description']) ?></div><?php endif; ?></td>
                <td><?= (int)$r['price'] ? '₹'.number_format((int)$r['price']) : 'Free' ?></td>
                <td><?= (int)$r['number_of_listings'] ?: 'Unlimited' ?></td>
                <td><?= (int)$r['validity'] ?> days</td>
                <td style="text-align:right;white-space:nowrap">
                  <a class="abtn gh" href="<?= $b ?>admin/packages?edit=<?= (int)$r['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                  <form method="post" action="<?= $b ?>admin/package/save" style="display:inline" onsubmit="return confirm('Delete this package? Members already on it keep it until it expires.')">
                    <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="abtn gh" style="color:#b3262f"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr><?php endforeach; ?>
            </table></div>

            <div class="panel" style="padding:18px 20px">
              <h3 style="margin:0 0 14px"><?= $e ? 'Edit package' : 'New package' ?></h3>
              <form method="post" action="<?= $b ?>admin/package/save">
                <input type="hidden" name="id" value="<?= (int)($e['id']??0) ?>">
                <label class="admlbl">Name</label><input class="adminp" name="name" required value="<?= esc($e['name']??'') ?>">
                <label class="admlbl">Price (₹, 0 for free)</label><input class="adminp" type="number" min="0" name="price" value="<?= (int)($e['price']??0) ?>">
                <label class="admlbl">Validity (days)</label><input class="adminp" type="number" min="1" name="validity" value="<?= (int)($e['validity']??365) ?>">
                <label class="admlbl">Listings allowed (0 = unlimited)</label><input class="adminp" type="number" min="0" name="number_of_listings" value="<?= (int)($e['number_of_listings']??1) ?>">
                <label class="admlbl">Description</label><textarea class="adminp" name="description" rows="3"><?= esc($e['description']??'') ?></textarea>
                <label style="display:block;margin:10px 0;font-size:13.5px"><input type="checkbox" name="featured" value="1" <?= !empty($e['featured'])?'checked':'' ?>> Featured placement</label>
                <label style="display:block;margin:10px 0;font-size:13.5px"><input type="checkbox" name="ability_to_add_video" value="1" <?= !empty($e['ability_to_add_video'])?'checked':'' ?>> Can add video</label>
                <label style="display:block;margin:10px 0;font-size:13.5px"><input type="checkbox" name="is_recommended" value="1" <?= !empty($e['is_recommended'])?'checked':'' ?>> Show as recommended</label>
                <button class="btn" style="width:100%;margin-top:8px">Save package</button>
              </form>
              <?php if($e): ?><a href="<?= $b ?>admin/packages" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:#8b98b0">Cancel</a><?php endif; ?>
            </div>
          </div>

        <?php elseif($sec==='revenue'):
          $opts=$edit?:['packages'=>[],'members'=>[]];
          $now=time(); $m30=$now-30*86400;
          $sum=static fn($rs)=>array_sum(array_map(static fn($r)=>(int)($r['amount_paid']??0),$rs));
          $recent=array_values(array_filter($rows, static fn($r)=>(int)($r['purchase_date']??0)>=$m30));
          $live=array_values(array_filter($rows, static fn($r)=>(int)($r['expired_date']??0)>$now)); ?>
          <h2 class="sec-title">Revenue <a class="abtn gh" style="font-size:12.5px" href="<?= $b ?>admin/export/revenue"><i class="fa-solid fa-download"></i> Export CSV</a></h2>
          <div class="stats" style="margin-bottom:18px">
            <div class="stat"><i class="fa-solid fa-indian-rupee-sign"></i><b>₹<?= number_format($sum($rows)) ?></b><span>All time</span></div>
            <div class="stat"><i class="fa-solid fa-calendar-days"></i><b>₹<?= number_format($sum($recent)) ?></b><span>Last 30 days</span></div>
            <div class="stat"><i class="fa-solid fa-box-open"></i><b><?= count($live) ?></b><span>Active plans</span></div>
            <div class="stat"><i class="fa-solid fa-receipt"></i><b><?= count($rows) ?></b><span>Payments</span></div>
          </div>

          <div class="panel" style="padding:18px 20px;margin-bottom:18px">
            <h3 style="margin:0 0 14px"><i class="fa-solid fa-hand-holding-dollar"></i> Record an offline payment</h3>
            <form method="post" action="<?= $b ?>admin/payment/offline" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end">
              <div><label class="admlbl">Member</label><select class="adminp" name="user_id" required>
                <option value="">Choose…</option>
                <?php foreach($opts['members'] as $m): ?><option value="<?= (int)$m['id'] ?>"><?= esc($m['name']?:$m['email']) ?> — <?= esc($m['email']) ?></option><?php endforeach; ?>
              </select></div>
              <div><label class="admlbl">Package</label><select class="adminp" name="package_id" required>
                <option value="">Choose…</option>
                <?php foreach($opts['packages'] as $p): ?><option value="<?= (int)$p['id'] ?>"><?= esc(html_entity_decode((string) ($p['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?> — ₹<?= number_format((int)$p['price']) ?></option><?php endforeach; ?>
              </select></div>
              <div><label class="admlbl">Amount (blank = list price)</label><input class="adminp" type="number" min="0" name="amount_paid"></div>
              <div><label class="admlbl">Method</label><select class="adminp" name="payment_method">
                <option value="offline">Offline</option><option value="cash">Cash</option>
                <option value="upi">UPI</option><option value="bank">Bank transfer</option><option value="cheque">Cheque</option>
              </select></div>
              <div><button class="btn" style="width:100%">Record payment</button></div>
            </form>
            <p style="color:#94a1b8;font-size:12.5px;margin:12px 0 0">The member is emailed as soon as you record it, and the plan starts today.</p>
          </div>

          <div class="panel"><table><tr><th>Date</th><th>Member</th><th>Package</th><th>Amount</th><th>Method</th><th>Expires</th></tr>
            <?php if(!$rows): ?><tr><td colspan="6" style="text-align:center;color:#9aa6bd;padding:40px">No payments recorded yet.</td></tr><?php endif; ?>
            <?php foreach($rows as $r): $exp=(int)($r['expired_date']??0); ?><tr>
              <td style="color:#94a1b8"><?= $r['purchase_date'] ? esc(date('d M Y',(int)$r['purchase_date'])) : '—' ?></td>
              <td><b><?= esc($r['user_name'] ?: ($r['email'] ?: 'User #'.(int)$r['user_id'])) ?></b></td>
              <td><?= esc($r['package_name'] ?: '—') ?></td>
              <td><?= (int)($r['amount_paid']??0) ? '₹'.number_format((int)$r['amount_paid']) : 'Free' ?></td>
              <td style="color:#94a1b8"><?= esc($r['payment_method'] ?: '—') ?></td>
              <td><?php if($exp): ?><span class="chip <?= $exp>$now?'chip-live':'chip-adm' ?>"><?= esc(date('d M Y',$exp)) ?></span><?php else: ?>—<?php endif; ?></td>
            </tr><?php endforeach; ?>
          </table></div>

        <?php elseif($sec==='claims'):
          $cst=[0=>['chip-pending','Pending'],1=>['chip-live','Approved'],2=>['chip-adm','Rejected']]; ?>
          <h2 class="sec-title">Ownership claims (<?= count(array_filter($rows, static fn($r)=>(int)($r['status']??0)===0)) ?> pending)</h2>
          <div class="panel"><table><tr><th>Listing</th><th>Claimed by</th><th>Contact</th><th>Status</th><th style="text-align:right">Action</th></tr>
            <?php if(!$rows): ?><tr><td colspan="5" style="text-align:center;color:#9aa6bd;padding:40px">No claims yet. Listings without an owner show a “Claim this business” link.</td></tr><?php endif; ?>
            <?php foreach($rows as $r): $st=(int)($r['status']??0); $c=$cst[$st]??$cst[0]; ?><tr>
              <td><b><a href="<?= $b ?>l/<?= (int)$r['listing_id'] ?>" style="color:#0f1c33" target="_blank" rel="noopener"><?= esc($r['listing_name']?:'Listing #'.(int)$r['listing_id']) ?></a></b>
                <?php if(!empty($r['claim_note'])): ?><div style="color:#94a1b8;font-size:12.5px;margin-top:3px"><?= esc($r['claim_note']) ?></div><?php endif; ?></td>
              <td><?= esc($r['claim_name'] ?? '—') ?></td>
              <td style="color:#94a1b8;font-size:12.5px"><?= esc($r['claim_email'] ?? '') ?><?= !empty($r['claim_phone']) ? '<br>'.esc($r['claim_phone']) : '' ?></td>
              <td><span class="chip <?= $c[0] ?>"><?= $c[1] ?></span></td>
              <td style="text-align:right;white-space:nowrap">
                <?php if($st===0): ?>
                  <a class="abtn" style="background:#0b8a3d;color:#fff" href="<?= $b ?>admin/claim/<?= (int)$r['id'] ?>/approve" onclick="return confirm('Hand this listing over to the claimant?')"><i class="fa-solid fa-check"></i> Approve</a>
                  <a class="abtn gh" href="<?= $b ?>admin/claim/<?= (int)$r['id'] ?>/reject" onclick="return confirm('Reject this claim?')"><i class="fa-solid fa-xmark"></i> Reject</a>
                <?php else: ?><span style="color:#9aa6bd;font-size:12.5px">Decided</span><?php endif; ?>
              </td>
            </tr><?php endforeach; ?>
          </table></div>

        <?php elseif($sec==='bookings'):
          $bch=static fn($s)=>in_array($s,['approved','closed'],true)?'chip-live':($s==='rejected'?'chip-adm':'chip-pending'); ?>
          <h2 class="sec-title">Bookings (<?= count($rows) ?>)</h2>
          <div class="panel"><table><tr><th>Customer</th><th>Place</th><th>When</th><th>Status</th><th style="text-align:right">Action</th></tr>
            <?php if(!$rows): ?><tr><td colspan="5" style="text-align:center;color:#9aa6bd;padding:40px">No bookings yet.</td></tr><?php endif; ?>
            <?php foreach($rows as $r): $st=strtolower((string)($r['status']??'new')); ?><tr>
              <td><b><?= $fld($r,'name') ?></b>
                <div style="color:#94a1b8;font-size:12.5px"><?= $fld($r,'phone') ?><?= !empty($r['email'])?' · '.esc($r['email']):'' ?></div></td>
              <td><?php if(!empty($r['listing_id'])): ?><a href="<?= $b ?>l/<?= (int)$r['listing_id'] ?>" target="_blank" rel="noopener" style="color:#0f1c33"><?= esc($r['listing_name']?:'#'.(int)$r['listing_id']) ?></a><?php else: ?>—<?php endif; ?>
                <div style="color:#94a1b8;font-size:12.5px"><?= $fld($r,'booking_type') ?></div></td>
              <td style="color:#94a1b8;white-space:nowrap"><?= esc(trim((string)($r['booking_date']??'').' '.(string)($r['booking_time']??'')))?:'—' ?></td>
              <td><span class="chip <?= $bch($st) ?>"><?= esc(ucfirst($st)) ?></span></td>
              <td style="text-align:right;white-space:nowrap">
                <?php if($st!=='approved'): ?><a class="abtn gh" href="<?= $b ?>admin/booking/<?= (int)$r['id'] ?>/approve" title="Approve"><i class="fa-solid fa-check" style="color:#0b8a3d"></i></a><?php endif; ?>
                <?php if($st!=='rejected'): ?><a class="abtn gh" href="<?= $b ?>admin/booking/<?= (int)$r['id'] ?>/reject" title="Reject"><i class="fa-solid fa-xmark" style="color:#b26a00"></i></a><?php endif; ?>
                <a class="abtn gh" href="<?= $b ?>admin/booking/<?= (int)$r['id'] ?>/delete" onclick="return confirm('Delete this booking for good?')" title="Delete"><i class="fa-solid fa-trash" style="color:#b3262f"></i></a>
              </td>
            </tr><?php endforeach; ?>
          </table></div>

        <?php elseif($sec==='requests'): ?>
          <h2 class="sec-title">Change requests (<?= count(array_filter($rows, static fn($r)=>($r['status']??'open')==='open')) ?> open)</h2>
          <p style="margin:-8px 0 16px;font-size:13px;color:#7b8aa5;max-width:640px">
            Business owners ask here for things they cannot change themselves — a name correction,
            a category move, a duplicate to merge. Replying emails them.
          </p>
          <div class="panel">
            <?php if(!$rows): ?><div style="text-align:center;color:#9aa6bd;padding:40px">Nothing requested yet.</div><?php endif; ?>
            <?php foreach($rows as $r): $open=($r['status']??'open')==='open'; ?>
              <div style="padding:17px 20px;border-bottom:1px solid #f1f3f7">
                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:baseline">
                  <div><b style="color:#0f1c33"><?= esc($r['owner_name'] ?: 'Owner') ?></b>
                    <span style="color:#94a1b8;font-size:12.5px"><?= esc($r['owner_email'] ?? '') ?></span>
                    <?php if(!empty($r['listing_id'])): ?>
                      &middot; <a href="<?= $b ?>l/<?= (int)$r['listing_id'] ?>" target="_blank" rel="noopener"><?= esc($r['listing_name'] ?: '#'.(int)$r['listing_id']) ?></a>
                    <?php endif; ?>
                  </div>
                  <div style="white-space:nowrap">
                    <span class="chip <?= $open?'chip-pending':'chip-live' ?>"><?= $open?'Open':'Closed' ?></span>
                    <span style="color:#94a1b8;font-size:12px;margin-left:6px"><?= esc(mb_substr((string)($r['created_at']??''),0,16)) ?></span>
                  </div>
                </div>
                <p style="margin:9px 0 0;color:#33405a"><?= nl2br(esc($r['req_message'] ?? '')) ?></p>
                <?php /* live hinoida threads replies, so show the whole conversation */ ?>
                <?php foreach(($r['_replies'] ?? []) as $_p): ?>
                  <div style="margin-top:10px;padding:11px 14px;background:#eef3fc;border-radius:9px;font-size:13.5px;color:#33405a">
                    <b style="color:#1741a6"><?= esc($_p['by_name'] ?: 'Reply') ?></b>
                    <span style="color:#94a1b8;font-size:12px"><?= esc(mb_substr((string)($_p['created_at'] ?? ''),0,16)) ?></span>
                    <div style="margin-top:4px"><?= nl2br(esc($_p['reply_message'] ?? '')) ?></div>
                  </div>
                <?php endforeach; ?>
                <?php if(empty($r['_replies']) && !empty($r['admin_reply'])): ?>
                  <div style="margin-top:10px;padding:11px 14px;background:#eef3fc;border-radius:9px;font-size:13.5px;color:#33405a">
                    <b style="color:#1741a6">Your reply</b> <span style="color:#94a1b8;font-size:12px"><?= esc(mb_substr((string)($r['replied_at']??''),0,16)) ?></span>
                    <div style="margin-top:4px"><?= nl2br(esc($r['admin_reply'])) ?></div>
                  </div>
                <?php endif; ?>
                <form method="post" action="<?= $b ?>admin/request/<?= (int)$r['id'] ?>/reply" style="margin-top:11px">
                  <textarea class="adminp" name="admin_reply" rows="2" placeholder="<?= empty($r['_replies']) && empty($r['admin_reply']) ? 'Reply to the owner…' : 'Add another reply…' ?>"></textarea>
                  <div style="display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap">
                    <button class="abtn ok"><i class="fa-solid fa-paper-plane"></i> Send reply</button>
                    <label style="font-size:12.5px;color:#5a6b86"><input type="checkbox" name="close" value="1" <?= $open?'':'checked' ?>> and close it</label>
                    <?php if($open): ?><a class="abtn gh" href="<?= $b ?>admin/request/<?= (int)$r['id'] ?>/close">Close without replying</a>
                    <?php else: ?><a class="abtn gh" href="<?= $b ?>admin/request/<?= (int)$r['id'] ?>/open">Reopen</a><?php endif; ?>
                    <a class="abtn gh" style="color:#b3262f" href="<?= $b ?>admin/request/<?= (int)$r['id'] ?>/delete" onclick="return confirm('Delete this request?')">Delete</a>
                  </div>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php elseif($sec==='team'): $e=$edit?:[]; $meId=(int)session()->get('uid'); ?>
          <h2 class="sec-title">Admin team (<?= count($rows) ?>)</h2>
          <p style="margin:-8px 0 18px;font-size:13px;color:#7b8aa5;max-width:640px">
            Everyone here can sign in and run this console — approve listings, edit content, see revenue.
            Removing someone steps them down to a normal member; it does not delete their account.
          </p>
          <div style="display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start" class="admgrid">
            <div class="panel"><table><tr><th>Name</th><th>Email</th><th>Phone</th><th style="text-align:right">Action</th></tr>
              <?php foreach($rows as $r): $isMe=(int)$r['id']===$meId; ?><tr>
                <td><b><?= esc($r['name'] ?: '—') ?></b><?php if($isMe): ?> <span class="chip chip-live">You</span><?php endif; ?></td>
                <td style="color:#94a1b8"><?= esc($r['email']) ?></td>
                <td style="color:#94a1b8"><?= esc($r['phone'] ?? '') ?: '—' ?></td>
                <td style="text-align:right;white-space:nowrap">
                  <a class="abtn gh" href="<?= $b ?>admin/team?edit=<?= (int)$r['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                  <?php if(!$isMe && count($rows)>1): ?>
                    <form method="post" action="<?= $b ?>admin/team/save" style="display:inline"
                          onsubmit="return confirm('Remove admin access for <?= esc($r['name'],'js') ?>? Their account stays.')">
                      <input type="hidden" name="act" value="remove"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="abtn gh" style="color:#b3262f"><i class="fa-solid fa-user-minus"></i></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr><?php endforeach; ?>
            </table></div>

            <div class="panel" style="padding:18px 20px">
              <h3 style="margin:0 0 12px"><?= $e ? 'Edit admin' : 'Add an admin' ?></h3>
              <form method="post" action="<?= $b ?>admin/team/save">
                <input type="hidden" name="id" value="<?= (int)($e['id']??0) ?>">
                <label class="admlbl">Name</label>
                <input class="adminp" name="name" required value="<?= esc($e['name']??'') ?>">
                <label class="admlbl">Email <span style="font-weight:400;color:#9aa6bd">— their login</span></label>
                <input class="adminp" type="email" name="email" required value="<?= esc($e['email']??'') ?>">
                <label class="admlbl">Phone</label>
                <input class="adminp" name="phone" value="<?= esc($e['phone']??'') ?>">
                <label class="admlbl">Password <?= $e ? '<span style="font-weight:400;color:#9aa6bd">— blank to keep</span>' : '' ?></label>
                <input class="adminp" type="password" name="password" autocomplete="new-password" minlength="8" <?= $e ? '' : 'required' ?>>
                <button class="btn" style="width:100%;margin-top:12px"><?= $e ? 'Save' : 'Add admin' ?></button>
              </form>
              <?php if($e): ?><a href="<?= $b ?>admin/team" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:#8b98b0">Cancel</a><?php endif; ?>
              <?php if(!$e): ?><p style="font-size:12px;color:#7b8aa5;margin:12px 0 0">They are emailed their sign-in details and asked to change the password.</p><?php endif; ?>
            </div>
          </div>
        <?php elseif($sec==='places'):
          $P=$rows?:['states'=>[],'cities'=>[]]; $E=$edit?:['what'=>'city','row'=>null]; $er=$E['row']?:[]; ?>
          <h2 class="sec-title">Cities &amp; states</h2>
          <p style="margin:-8px 0 18px;font-size:13px;color:#7b8aa5;max-width:640px">
            Areas hang off a city, and cities off a state. HiNoida covers Noida today —
            this is where you add another city if the directory ever widens.
          </p>
          <div style="display:grid;grid-template-columns:1fr 300px;gap:18px;align-items:start" class="admgrid">
            <div>
              <div class="panel" style="margin-bottom:16px"><table><tr><th>State</th><th style="text-align:right">Cities</th><th style="text-align:right">Action</th></tr>
                <?php if(!$P['states']): ?><tr><td colspan="3" style="text-align:center;color:#9aa6bd;padding:30px">No states yet.</td></tr><?php endif; ?>
                <?php foreach($P['states'] as $st): $nc=count(array_filter($P['cities'], static fn($c)=>(int)$c['state_id']===(int)$st['id'])); ?><tr>
                  <td><b><?= esc(html_entity_decode((string) ($st['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b></td>
                  <td style="text-align:right"><?= $nc ?></td>
                  <td style="text-align:right;white-space:nowrap">
                    <a class="abtn gh" href="<?= $b ?>admin/places?t=state&edit=<?= (int)$st['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                    <form method="post" action="<?= $b ?>admin/place/state/save" style="display:inline" onsubmit="return confirm('Delete this state?')">
                      <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$st['id'] ?>">
                      <button class="abtn gh" style="color:#b3262f"><i class="fa-solid fa-trash"></i></button>
                    </form>
                  </td>
                </tr><?php endforeach; ?>
              </table></div>
              <div class="panel"><table><tr><th>City</th><th>State</th><th style="text-align:right">Areas</th><th style="text-align:right">Action</th></tr>
                <?php if(!$P['cities']): ?><tr><td colspan="4" style="text-align:center;color:#9aa6bd;padding:30px">No cities yet.</td></tr><?php endif; ?>
                <?php foreach($P['cities'] as $ct): ?><tr>
                  <td><b><?= esc(html_entity_decode((string) ($ct['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b></td>
                  <td style="color:#94a1b8"><?= esc($ct['state_name'] ?: '—') ?></td>
                  <td style="text-align:right"><?= (int)$ct['areas'] ?></td>
                  <td style="text-align:right;white-space:nowrap">
                    <a class="abtn gh" href="<?= $b ?>admin/places?t=city&edit=<?= (int)$ct['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                    <form method="post" action="<?= $b ?>admin/place/city/save" style="display:inline" onsubmit="return confirm('Delete this city?')">
                      <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$ct['id'] ?>">
                      <button class="abtn gh" style="color:#b3262f"><i class="fa-solid fa-trash"></i></button>
                    </form>
                  </td>
                </tr><?php endforeach; ?>
              </table></div>
            </div>
            <div class="panel" style="padding:18px 20px">
              <h3 style="margin:0 0 12px"><?= $er ? 'Edit ' . esc($E['what']) : 'Add' ?></h3>
              <div style="display:flex;gap:7px;margin-bottom:12px">
                <a class="abtn <?= $E['what']==='state'?'ok':'gh' ?>" href="<?= $b ?>admin/places?t=state">State</a>
                <a class="abtn <?= $E['what']==='city'?'ok':'gh' ?>" href="<?= $b ?>admin/places?t=city">City</a>
              </div>
              <form method="post" action="<?= $b ?>admin/place/<?= esc($E['what'],'url') ?>/save">
                <input type="hidden" name="id" value="<?= (int)($er['id']??0) ?>">
                <label class="admlbl">Name</label>
                <input class="adminp" name="name" required value="<?= esc($er['name']??'') ?>">
                <?php if($E['what']==='city'): ?>
                  <label class="admlbl">State</label>
                  <select class="adminp" name="state_id">
                    <option value="">Select…</option>
                    <?php foreach($P['states'] as $st): ?>
                      <option value="<?= (int)$st['id'] ?>" <?= (int)($er['state_id']??0)===(int)$st['id']?'selected':'' ?>><?= esc(html_entity_decode((string) ($st['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php endif; ?>
                <button class="btn" style="width:100%;margin-top:12px">Save</button>
              </form>
              <?php if($er): ?><a href="<?= $b ?>admin/places" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:#8b98b0">Cancel</a><?php endif; ?>
            </div>
          </div>
        <?php elseif($sec==='quality'): $e=$edit?:[]; ?>
          <h2 class="sec-title">Rating labels</h2>
          <p style="margin:-8px 0 18px;font-size:13px;color:#7b8aa5;max-width:620px">
            The word shown beside a listing's star average — "Excellent" for 4.5 and up, and so on.
            Bands are inclusive at both ends; a rating that falls in no band shows no word.
          </p>
          <div style="display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start" class="admgrid">
            <div class="panel"><table><tr><th>From</th><th>To</th><th>Word</th><th style="text-align:right">Action</th></tr>
              <?php if(!$rows): ?><tr><td colspan="4" style="text-align:center;color:#9aa6bd;padding:36px">No bands yet.</td></tr><?php endif; ?>
              <?php foreach($rows as $r): ?><tr>
                <td><?= esc(rtrim(rtrim(number_format((float)$r['rating_from'],1),'0'),'.')) ?: '0' ?></td>
                <td><?= esc(rtrim(rtrim(number_format((float)$r['rating_to'],1),'0'),'.')) ?: '0' ?></td>
                <td><b><?= esc($r['quality']) ?></b></td>
                <td style="text-align:right;white-space:nowrap">
                  <a class="abtn gh" href="<?= $b ?>admin/quality?edit=<?= (int)$r['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                  <form method="post" action="<?= $b ?>admin/quality/save" style="display:inline" onsubmit="return confirm('Delete this band?')">
                    <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="abtn gh" style="color:#b3262f"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr><?php endforeach; ?>
            </table></div>
            <div class="panel" style="padding:18px 20px">
              <h3 style="margin:0 0 12px"><?= $e ? 'Edit band' : 'New band' ?></h3>
              <form method="post" action="<?= $b ?>admin/quality/save">
                <input type="hidden" name="id" value="<?= (int)($e['id']??0) ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                  <div><label class="admlbl">From</label><input class="adminp" type="number" step="0.1" min="0" max="5" name="rating_from" value="<?= esc($e['rating_from']??'4.5') ?>"></div>
                  <div><label class="admlbl">To</label><input class="adminp" type="number" step="0.1" min="0" max="5" name="rating_to" value="<?= esc($e['rating_to']??'5') ?>"></div>
                </div>
                <label class="admlbl">Word</label><input class="adminp" name="quality" required value="<?= esc($e['quality']??'') ?>" placeholder="Excellent">
                <button class="btn" style="width:100%;margin-top:12px">Save band</button>
              </form>
              <?php if($e): ?><a href="<?= $b ?>admin/quality" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:#8b98b0">Cancel</a><?php endif; ?>
            </div>
          </div>
        <?php elseif($sec==='reported'): ?>
          <h2 class="sec-title">Reported listings (<?= count(array_filter($rows, static fn($r)=>(int)($r['status']??0)===0)) ?> open)</h2>
          <div class="panel"><table><tr><th>Report</th><th>Listing</th><th>Reported by</th><th>Status</th><th style="text-align:right">Action</th></tr>
            <?php if(!$rows): ?><tr><td colspan="5" style="text-align:center;color:#9aa6bd;padding:40px">Nothing reported. Signed-in visitors can flag a listing from its page.</td></tr><?php endif; ?>
            <?php foreach($rows as $r): $open=(int)($r['status']??0)===0; ?><tr<?= $open?'':' style="opacity:.6"' ?>>
              <td style="max-width:340px;color:#33405a"><?= esc(mb_substr((string)($r['report']??''),0,240)) ?>
                <div style="color:#94a1b8;font-size:12px;margin-top:3px"><?= !empty($r['date_added']) ? esc(date('d M Y',(int)$r['date_added'])) : '' ?></div></td>
              <td><a href="<?= $b ?>l/<?= (int)$r['listing_id'] ?>" target="_blank" rel="noopener" style="color:#0f1c33"><b><?= esc($r['listing_name'] ?: '#'.(int)$r['listing_id']) ?></b></a>
                <div><span class="chip <?= ($r['listing_status']??'')==='active'?'chip-live':'chip-pending' ?>"><?= esc(ucfirst((string)($r['listing_status']??'—'))) ?></span></div></td>
              <td style="color:#94a1b8;font-size:12.5px"><?= esc($r['reporter_name'] ?: 'User #'.(int)$r['reporter_id']) ?><?= !empty($r['reporter_email']) ? '<br>'.esc($r['reporter_email']) : '' ?></td>
              <td><span class="chip <?= $open?'chip-pending':'chip-live' ?>"><?= $open?'Open':'Closed' ?></span></td>
              <td style="text-align:right;white-space:nowrap">
                <?php if($open): ?>
                  <a class="abtn gh" href="<?= $b ?>admin/report/<?= (int)$r['id'] ?>/pending" onclick="return confirm('Take this listing down while you check it?')" title="Take down"><i class="fa-solid fa-eye-slash" style="color:#b26a00"></i></a>
                  <a class="abtn gh" href="<?= $b ?>admin/report/<?= (int)$r['id'] ?>/active" title="Keep live, close report"><i class="fa-solid fa-check" style="color:#0b8a3d"></i></a>
                <?php endif; ?>
                <a class="abtn gh" href="<?= $b ?>admin/report/<?= (int)$r['id'] ?>/delete" onclick="return confirm('Dismiss and delete this report?')" title="Dismiss"><i class="fa-solid fa-trash" style="color:#b3262f"></i></a>
              </td>
            </tr><?php endforeach; ?>
          </table></div>

        <?php elseif($sec==='comments'):
          $ccl=['approved'=>'chip-live','rejected'=>'chip-adm']; ?>
          <h2 class="sec-title">Blog comments (<?= count(array_filter($rows, static fn($r)=>($r['status']??'')==='pending')) ?> waiting)</h2>
          <div class="panel"><table><tr><th>Comment</th><th>On</th><th>By</th><th>Status</th><th style="text-align:right">Action</th></tr>
            <?php if(!$rows): ?><tr><td colspan="5" style="text-align:center;color:#9aa6bd;padding:40px">No comments yet. Visitors' comments wait here for approval; signed-in members post straight away.</td></tr><?php endif; ?>
            <?php foreach($rows as $r): $st=(string)($r['status']??'pending'); ?><tr<?= $st==='rejected'?' style="opacity:.55"':'' ?>>
              <td style="max-width:360px;color:#33405a"><?= esc(mb_substr((string)($r['comment']??''),0,220)) ?></td>
              <td><?php if(!empty($r['slug'])): ?><a href="<?= $b ?>news/<?= esc($r['slug'],'url') ?>#comments" target="_blank" rel="noopener" style="color:#0f1c33"><?= esc($r['post_title'] ?: 'Post') ?></a><?php else: ?>—<?php endif; ?></td>
              <td><?= esc($r['name'] ?: 'Visitor') ?><?php if(!empty($r['email'])): ?><div style="color:#94a1b8;font-size:12px"><?= esc($r['email']) ?></div><?php endif; ?></td>
              <td><span class="chip <?= $ccl[$st] ?? 'chip-pending' ?>"><?= esc(ucfirst($st)) ?></span></td>
              <td style="text-align:right;white-space:nowrap">
                <?php if($st!=='approved'): ?><a class="abtn gh" href="<?= $b ?>admin/comment/<?= (int)$r['id'] ?>/approve" title="Publish"><i class="fa-solid fa-check" style="color:#0b8a3d"></i></a><?php endif; ?>
                <?php if($st!=='rejected'): ?><a class="abtn gh" href="<?= $b ?>admin/comment/<?= (int)$r['id'] ?>/reject" title="Reject"><i class="fa-solid fa-xmark" style="color:#b26a00"></i></a><?php endif; ?>
                <a class="abtn gh" href="<?= $b ?>admin/comment/<?= (int)$r['id'] ?>/delete" onclick="return confirm('Delete this comment for good?')" title="Delete"><i class="fa-solid fa-trash" style="color:#b3262f"></i></a>
              </td>
            </tr><?php endforeach; ?>
          </table></div>

        <?php elseif($sec==='reviews'): ?>
          <h2 class="sec-title">Reviews (<?= count($rows) ?>) <a class="abtn gh" style="font-size:12.5px" href="<?= $b ?>admin/export/reviews"><i class="fa-solid fa-download"></i> Export CSV</a></h2>
          <div class="panel"><table><tr><th>Review</th><th>Place</th><th>By</th><th style="text-align:right">Action</th></tr>
            <?php if(!$rows): ?><tr><td colspan="4" style="text-align:center;color:#9aa6bd;padding:40px">No reviews yet.</td></tr><?php endif; ?>
            <?php foreach($rows as $r): $hid=!empty($r['is_hidden']); $rt=(int)($r['review_rating']??0); ?><tr<?= $hid?' style="opacity:.55"':'' ?>>
              <td style="max-width:380px">
                <span style="color:#e8a33d"><?php for($i=1;$i<=5;$i++) echo $i<=$rt?'★':'☆'; ?></span>
                <?php if($hid): ?><span class="chip chip-adm">Hidden</span><?php endif; ?>
                <div style="color:#33405a;font-size:13px;margin-top:3px"><?= esc(mb_substr((string)($r['review_comment']??''),0,200)) ?></div>
              </td>
              <td><a href="<?= $b ?>l/<?= (int)$r['listing_id'] ?>" target="_blank" rel="noopener" style="color:#0f1c33"><?= esc($r['listing_name']?:'#'.(int)$r['listing_id']) ?></a></td>
              <td style="color:#94a1b8"><?= esc($r['reviewer_name'] ?: 'Guest') ?></td>
              <td style="text-align:right;white-space:nowrap">
                <a class="abtn gh" href="<?= $b ?>admin/review/<?= (int)$r['id'] ?>/<?= $hid?'show':'hide' ?>" title="<?= $hid?'Restore':'Hide' ?>"><i class="fa-solid fa-eye<?= $hid?'':'-slash' ?>"></i></a>
                <a class="abtn gh" href="<?= $b ?>admin/review/<?= (int)$r['id'] ?>/delete" onclick="return confirm('Delete this review for good?')" title="Delete"><i class="fa-solid fa-trash" style="color:#b3262f"></i></a>
              </td>
            </tr><?php endforeach; ?>
          </table></div>

        <?php elseif($sec==='analytics'): ?>
          <h2 class="sec-title">Analytics</h2>
          <div class="panel" style="padding:20px 22px;margin-bottom:22px">
            <h3 style="margin:0 0 4px;font-size:16px;color:#0f1c33"><i class="fa-solid fa-file-csv" style="color:#1741a6"></i> Reports</h3>
            <p style="margin:0 0 14px;font-size:12.5px;color:#7b8aa5">Download as CSV — opens directly in Excel or Google Sheets.</p>
            <div style="display:flex;flex-wrap:wrap;gap:9px">
              <?php foreach ([
                ['listings','store','All listings','name, contact, area, status'],
                ['categories','sitemap','Categories','every category with its parent and listing count'],
                ['users','users','Members','role, verified, listings, reviews, bookings'],
                ['leads','inbox','Leads','enquiries and bookings together'],
                ['reviews','star','Reviews','ratings, comments and owner replies'],
              ] as $r): ?>
                <a class="abtn gh" href="<?= $b ?>admin/export/<?= $r[0] ?>" title="<?= esc($r[3]) ?>"
                   style="font-size:12.5px;display:inline-flex;align-items:center;gap:7px">
                  <i class="fa-solid fa-<?= $r[1] ?>"></i> <?= esc($r[2]) ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="stats">
            <div class="stat"><b><?= number_format($rows['members']) ?></b><span>Members</span></div>
            <div class="stat"><b><?= number_format($rows['reviews']) ?></b><span>Reviews</span></div>
            <div class="stat"><b><?= number_format($rows['bookings']) ?></b><span>Bookings</span></div>
            <div class="stat"><b><?= number_format($rows['enquiries']) ?></b><span>Enquiries</span></div>
          </div>
          <h2 class="sec-title" style="margin-top:24px">This week vs last week</h2>
          <p style="margin:-8px 0 14px;font-size:12.5px;color:#7b8aa5">
            Last 7 days compared with the 7 before them.
            <?php if(empty(array_filter(array_column($rows['week']??[], 'prev')))): ?>
              Percentages appear once there is a previous week to compare against.
            <?php endif; ?>
          </p>
          <div class="stats" style="margin-bottom:22px">
            <?php foreach(($rows['week']??[]) as $w): $d=(int)$w['delta']; $up=$d>0; $flat=$d===0; ?>
              <div class="stat">
                <i class="fa-solid fa-<?= $w['icon'] ?>" style="color:#1741a6"></i>
                <b><?= number_format((int)$w['now']) ?></b>
                <span><?= esc($w['label']) ?></span>
                <div style="margin-top:8px;font-size:12.5px;font-weight:700;color:<?= $flat?'#9aa6bd':($up?'#1a7a3d':'#b3262f') ?>">
                  <i class="fa-solid fa-<?= $flat?'minus':($up?'arrow-trend-up':'arrow-trend-down') ?>"></i>
                  <?= $flat ? 'No change' : ($up?'+':'').$d.'%' ?>
                  <span style="color:#9aa6bd;font-weight:500">vs <?= number_format((int)$w['prev']) ?> last week</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if(!empty($rows['daily'])): $mx=max(1,max(array_column($rows['daily'],'n'))); ?>
            <div class="panel" style="padding:20px 22px;margin-bottom:22px">
              <h3 style="margin:0 0 16px;font-size:16px;color:#0f1c33"><i class="fa-solid fa-chart-column" style="color:#1741a6"></i> Page views, last 14 days</h3>
              <div style="display:flex;align-items:flex-end;gap:5px;height:110px">
                <?php foreach($rows['daily'] as $dd): $h=max(3,(int)round(((int)$dd['n']/$mx)*100)); ?>
                  <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-end;height:100%"
                       title="<?= esc(date('D d M',strtotime($dd['d']))) ?> — <?= (int)$dd['n'] ?> views">
                    <div style="height:<?= $h ?>%;background:linear-gradient(180deg,#3b82f6,#1741a6);border-radius:5px 5px 0 0;min-height:3px"></div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11.5px;color:#9aa6bd">
                <span><?= esc(date('d M',strtotime($rows['daily'][0]['d']))) ?></span>
                <span>peak <?= number_format($mx) ?>/day</span>
                <span><?= esc(date('d M',strtotime(end($rows['daily'])['d']))) ?></span>
              </div>
            </div>
          <?php endif; ?>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start" class="admgrid">
            <div>
              <h2 class="sec-title">Busiest listings this week</h2>
              <div class="panel"><table><tr><th>Listing</th><th style="text-align:right">Views</th></tr>
                <?php if(empty($rows['busiest'])): ?><tr><td colspan="2" style="text-align:center;color:#9aa6bd;padding:30px">No views recorded this week yet.</td></tr><?php endif; ?>
                <?php foreach(($rows['busiest']??[]) as $t): ?><tr>
                  <td><a href="<?= $b ?>l/<?= (int)$t['listing_id'] ?>" target="_blank" rel="noopener" style="color:#0f1c33"><b><?= esc($t['name'] ?: '#'.(int)$t['listing_id']) ?></b></a></td>
                  <td style="text-align:right"><?= number_format((int)$t['n']) ?></td>
                </tr><?php endforeach; ?>
              </table></div>
            </div>
            <div>
              <h2 class="sec-title">Top categories</h2>
              <div class="panel"><table><tr><th>Category</th><th style="text-align:right">Listings</th></tr>
                <?php foreach($rows['top'] as $t): ?><tr><td><b><?= esc(html_entity_decode((string) ($t['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b></td><td style="text-align:right"><?= number_format($t['cnt']) ?></td></tr><?php endforeach; ?>
              </table></div>
            </div>
          </div>

        <?php elseif($sec==='sitetext'): ?>
          <h2 class="sec-title">Site text &amp; images</h2>
          <p style="color:#7c8aa3;font-size:13.5px;margin:-6px 0 18px;max-width:640px">
            Every fixed word and picture on the site &mdash; the header, the footer, the brand.
            Leave a box empty to keep the built-in wording; clearing a box you have changed
            puts the original back.
          </p>
          <?php $s=$edit??[]; $ff=static fn($k)=>isset($s['c_'.$k])?esc($s['c_'.$k]):'';
                $st='width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:11px 13px;font-size:14px;margin-bottom:4px';
                $sl='display:block;font-size:12px;font-weight:600;color:#5a6b86;margin:12px 0 6px';
                $sh='font-size:11.5px;color:#93a0b6;margin:0 0 2px'; ?>
          <form method="post" action="<?= $b ?>admin/sitetext/save" enctype="multipart/form-data" style="max-width:680px">
            <?php foreach(hi_content_fields() as $grp => $fields): ?>
              <div class="panel" style="padding:24px;margin-bottom:16px">
                <h3 style="margin:0 0 4px;font-size:15px"><?= esc($grp) ?></h3>
                <?php foreach($fields as $k => $fld): [$lbl,$type,$def,$hint] = $fld + [3=>'']; ?>
                  <label style="<?= $sl ?>"><?= esc($lbl) ?></label>
                  <?php if($hint !== ''): ?><p style="<?= $sh ?>"><?= esc($hint) ?></p><?php endif; ?>
                  <?php if($type === 'image'): ?>
                    <?php if($ff($k)): ?>
                      <div style="margin-bottom:6px"><img src="<?= $b.$ff($k) ?>" alt=""
                        style="max-height:70px;max-width:220px;background:#f2f5fa;border-radius:7px;padding:4px"></div>
                    <?php endif; ?>
                    <input type="file" name="<?= $k ?>" accept="image/*" style="<?= $st ?>">
                  <?php elseif($type === 'area'): ?>
                    <textarea name="<?= $k ?>" rows="3" placeholder="<?= esc($def,'attr') ?>"
                      style="<?= $st ?>;font-family:inherit"><?= $ff($k) ?></textarea>
                  <?php else: ?>
                    <input name="<?= $k ?>" value="<?= $ff($k) ?>" placeholder="<?= esc($def,'attr') ?>" style="<?= $st ?>">
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
            <?php /* Everything else the views have registered by using it.
                     Grouped by the part of the key before the first dot. */ ?>
            <?php foreach(hi_c_registry() as $grp => $items): ?>
              <div class="panel" style="padding:24px;margin-bottom:16px">
                <h3 style="margin:0 0 4px;font-size:15px;text-transform:capitalize"><?= esc($grp) ?></h3>
                <p style="<?= $sh ?>"><?= count($items) ?> editable <?= count($items) === 1 ? 'string' : 'strings' ?> on this part of the site.</p>
                <?php foreach($items as $k => $val): ?>
                  <label style="<?= $sl ?>"><?= esc(str_contains($k,'.') ? substr($k, strpos($k,'.')+1) : $k) ?></label>
                  <?php if(mb_strlen($val) > 90): ?>
                    <textarea name="reg[<?= esc($k,'attr') ?>]" rows="3" style="<?= $st ?>;font-family:inherit"><?= esc($val) ?></textarea>
                  <?php else: ?>
                    <input name="reg[<?= esc($k,'attr') ?>]" value="<?= esc($val) ?>" style="<?= $st ?>">
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
            <button class="abtn ok" type="submit" style="margin-top:4px;padding:12px 26px"><i class="fa-solid fa-check"></i> Save site text</button>
          </form>
        <?php elseif($sec==='frontend'): ?>
          <h2 class="sec-title">Frontend</h2>
          <?php $s=$edit??[]; $ff=static fn($k)=>isset($s[$k])?esc($s[$k]):'';
                $st='width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:11px 13px;font-size:14px;margin-bottom:4px';
                $sl='display:block;font-size:12px;font-weight:600;color:#5a6b86;margin:12px 0 6px';
                $sh='font-size:11.5px;color:#93a0b6;margin:0 0 2px'; ?>
          <form method="post" action="<?= $b ?>admin/frontend/save" enctype="multipart/form-data" style="max-width:640px">
            <div class="panel" style="padding:24px;margin-bottom:16px">
              <h3 style="margin:0 0 4px;font-size:15px">Branding</h3>
              <?php foreach(['fe_logo'=>'Logo','fe_favicon'=>'Favicon','fe_hero_image'=>'Home banner image'] as $k=>$lbl): ?>
                <label style="<?= $sl ?>"><?= $lbl ?></label>
                <?php if($ff($k)): ?>
                  <div style="margin-bottom:6px"><img src="<?= $b.$ff($k) ?>" alt="" style="max-height:46px;max-width:200px;background:#f2f5fa;border-radius:7px;padding:4px"></div>
                <?php endif; ?>
                <input type="file" name="<?= $k ?>" accept="image/*" style="<?= $st ?>">
              <?php endforeach; ?>
              <p style="<?= $sh ?>">Leave a file box empty to keep the current image.</p>
            </div>
            <div class="panel" style="padding:24px;margin-bottom:16px">
              <h3 style="margin:0 0 4px;font-size:15px">Social links</h3>
              <p style="<?= $sh ?>">Shown in the footer. Leave blank to hide that icon.</p>
              <?php foreach(['fe_facebook'=>'Facebook','fe_instagram'=>'Instagram','fe_linkedin'=>'LinkedIn','fe_youtube'=>'YouTube','fe_twitter'=>'X / Twitter'] as $k=>$lbl): ?>
                <label style="<?= $sl ?>"><?= $lbl ?></label>
                <input name="<?= $k ?>" value="<?= $ff($k) ?>" style="<?= $st ?>" placeholder="https://">
              <?php endforeach; ?>
              <label style="<?= $sl ?>">Instagram access token &mdash; pulls reels automatically</label>
              <p style="<?= $sh ?>">Long-lived token from a Business or Creator account (Meta &rarr; Instagram API setup). Reels refresh hourly and the token renews itself every 30 days, so this is a one-time job. Leave blank to use the pasted links below instead.</p>
              <input name="fe_ig_token" value="<?= $ff('fe_ig_token') ?>" style="<?= $st ?>" placeholder="IGQVJ...">
              <label style="<?= $sl ?>">Instagram reels &mdash; one link per line</label>
              <p style="<?= $sh ?>">Paste reel or post links (https://www.instagram.com/reel/&hellip;). The first four are embedded on every page. Leave blank to show just the follow button.</p>
              <textarea name="fe_ig_reels" rows="4" style="<?= $st ?>;font-family:inherit" placeholder="https://www.instagram.com/reel/XXXXXXXXX/"><?= $ff('fe_ig_reels') ?></textarea>
            </div>
            <div class="panel" style="padding:24px">
              <h3 style="margin:0 0 4px;font-size:15px">Page copy</h3>
              <p style="<?= $sh ?>">Leave blank to keep the built-in text for that page.</p>
              <?php foreach(['fe_about'=>'About us','fe_faq'=>'FAQ','fe_privacy'=>'Privacy policy','fe_terms'=>'Terms &amp; conditions'] as $k=>$lbl): ?>
                <label style="<?= $sl ?>"><?= $lbl ?></label>
                <textarea name="<?= $k ?>" rows="5" style="<?= $st ?>;font-family:inherit"><?= $ff($k) ?></textarea>
              <?php endforeach; ?>
            </div>
            <button class="abtn ok" type="submit" style="margin-top:16px;padding:12px 26px"><i class="fa-solid fa-check"></i> Save frontend settings</button>
          </form>

        <?php elseif($sec==='changes'): ?>
          <h2 class="sec-title">Change requests</h2>
          <p style="color:#7c8aa3;font-size:13.5px;margin:-6px 0 18px;max-width:640px">
            When the owner of a live listing edits its name, phone, address or category, the listing stays as it is
            until you approve the change here. Photos, hours and amenities are not held.
          </p>
          <?php $pend=array_values(array_filter($rows??[],static fn($r)=>$r['status']==='pending')); ?>
          <?php if(!($rows??[])): ?>
            <div class="panel" style="padding:36px;text-align:center;color:#9aa6bd">No change requests.</div>
          <?php else: ?>
            <?php foreach($rows as $r): ?>
              <div class="panel" style="padding:18px 20px;margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap">
                  <div>
                    <b style="font-size:15px"><?= esc($r['listing_name'] ?: 'Listing #'.$r['listing_id']) ?></b>
                    <div style="font-size:12.5px;color:#7c8aa3;margin-top:2px">
                      <?php /* the live table has no user_id, so there is no owner name to join */ ?>
                      by <?= esc(($r['owner_name'] ?? '') ?: 'unknown') ?> &middot; <?= esc($r['created_at'] ?? $r['updated_at'] ?? '') ?>
                    </div>
                  </div>
                  <span class="chip <?= $r['status']==='pending'?'chip-pending':($r['status']==='approved'?'chip-live':'chip-red') ?>"><?= esc(ucfirst($r['status'])) ?></span>
                </div>
                <table style="width:100%;border-collapse:collapse;margin-top:12px;font-size:13.5px">
                  <tr><th style="text-align:left;padding:6px 8px;color:#9aa6bd;font-size:11px;text-transform:uppercase">Field</th>
                      <th style="text-align:left;padding:6px 8px;color:#9aa6bd;font-size:11px;text-transform:uppercase">Now</th>
                      <th style="text-align:left;padding:6px 8px;color:#9aa6bd;font-size:11px;text-transform:uppercase">Proposed</th></tr>
                  <?php foreach(($r['_diff']??[]) as $d): ?>
                    <tr style="border-top:1px solid #f1f3f7">
                      <td style="padding:8px;color:#7c8aa3"><?= esc($d['label'] ?? '') ?></td>
                      <td style="padding:8px;color:#c0324b"><?= esc(mb_strimwidth((string)($d['old'] ?? ''),0,90,'…')) ?: '<em>empty</em>' ?></td>
                      <td style="padding:8px;color:#1a7a3d;font-weight:600"><?= esc(mb_strimwidth((string)($d['new'] ?? ''),0,90,'…')) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </table>
                <?php if($r['status']==='pending'): ?>
                  <div style="display:flex;gap:9px;margin-top:14px">
                    <a class="abtn ok" href="<?= $b ?>admin/change/<?= (int)$r['id'] ?>/approve"><i class="fa-solid fa-check"></i> Approve</a>
                    <a class="abtn" href="<?= $b ?>admin/change/<?= (int)$r['id'] ?>/reject"><i class="fa-solid fa-xmark"></i> Reject</a>
                    <a class="abtn" href="<?= $b ?>l/<?= (int)$r['listing_id'] ?>" target="_blank">View listing</a>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

        <?php elseif($sec==='map'): ?>
          <h2 class="sec-title">Map</h2>
          <form method="post" action="<?= $b ?>admin/map/save" style="max-width:560px"><div class="panel" style="padding:24px">
            <?php $s=$edit??[]; $mf=static fn($k,$d='')=>isset($s[$k])&&$s[$k]!==''?esc($s[$k]):$d;
                  $st='width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:11px 13px;font-size:14px;margin-bottom:4px';
                  $sl='display:block;font-size:12px;font-weight:600;color:#5a6b86;margin:12px 0 6px';
                  $sh='font-size:11.5px;color:#93a0b6;margin:0 0 2px'; ?>
            <label style="<?= $sl ?>">Default location</label>
            <input name="map_default_location" value="<?= $mf('map_default_location','28.5355,77.3910') ?>" style="<?= $st ?>" placeholder="28.5355,77.3910">
            <p style="<?= $sh ?>">Latitude,longitude the map opens on when a listing has no coordinates. Noida centre is 28.5355,77.3910.</p>
            <label style="<?= $sl ?>">Zoom &mdash; listings page</label>
            <input name="map_min_zoom_listings" value="<?= $mf('map_min_zoom_listings','13') ?>" style="<?= $st ?>" type="number" min="1" max="20">
            <label style="<?= $sl ?>">Zoom &mdash; directory page</label>
            <input name="map_min_zoom_directory" value="<?= $mf('map_min_zoom_directory','12') ?>" style="<?= $st ?>" type="number" min="1" max="20">
            <label style="<?= $sl ?>">Maximum zoom</label>
            <input name="map_max_zoom" value="<?= $mf('map_max_zoom','18') ?>" style="<?= $st ?>" type="number" min="1" max="20">
            <p style="<?= $sh ?>">How far in people can zoom. 18 is street level.</p>
            </div><button class="abtn ok" type="submit" style="margin-top:16px;padding:12px 26px"><i class="fa-solid fa-check"></i> Save map settings</button></form>

        <?php elseif($sec==='seo'): ?>
          <h2 class="sec-title">SEO</h2>
          <form method="post" action="<?= $b ?>admin/seo/save" style="max-width:560px"><div class="panel" style="padding:24px">
            <?php $s=$edit??[]; $sf=static fn($k)=>isset($s[$k])?esc($s[$k]):''; $st='width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:11px 13px;font-size:14px;margin-bottom:4px';$sl='display:block;font-size:12px;font-weight:600;color:#5a6b86;margin:12px 0 6px'; ?>
            <label style="<?= $sl ?>">Home page title</label><input name="seo_home_title" value="<?= $sf('seo_home_title') ?>" style="<?= $st ?>">
            <label style="<?= $sl ?>">Home meta description</label><input name="seo_home_desc" value="<?= $sf('seo_home_desc') ?>" style="<?= $st ?>">
            <label style="<?= $sl ?>">Default title suffix</label><input name="seo_default_title" value="<?= $sf('seo_default_title') ?>" style="<?= $st ?>">
            <label style="<?= $sl ?>">Default meta description</label><input name="seo_default_desc" value="<?= $sf('seo_default_desc') ?>" style="<?= $st ?>">

            <?php
              $sh   = 'display:block;font-size:12px;color:#7b8aa5;margin:0 0 4px';
              $isProd = ENVIRONMENT === 'production';
              $chip = static fn (bool $on, string $yes, string $no) =>
                '<span style="font-size:11px;font-weight:700;padding:2px 9px;border-radius:20px;'
                . ($on ? 'background:#e6f6ec;color:#1a7a3d' : 'background:#fff3e0;color:#b26a00') . '">'
                . ($on ? $yes : $no) . '</span>';
            ?>

            <h3 style="font-size:17px;margin:26px 0 4px;color:#0f1c33">Analytics &amp; verification</h3>
            <p style="<?= $sh ?>">
              Environment: <?= $chip($isProd, 'production — tracking active', 'development — tracking paused') ?><br>
              Tags are deliberately suppressed outside production so test traffic never reaches your reports.
            </p>

            <label style="<?= $sl ?>">Google Analytics 4 — Measurement ID
              <?= $sf('seo_ga4_id') !== '' ? $chip(true, 'connected', '') : $chip(false, '', 'not set') ?>
            </label>
            <input name="seo_ga4_id" value="<?= $sf('seo_ga4_id') ?>" placeholder="G-XXXXXXXXXX" style="<?= $st ?>">
            <span style="<?= $sh ?>">
              <b>Where to find it:</b> <a href="https://analytics.google.com/" target="_blank" rel="noopener">analytics.google.com</a>
              → Admin (bottom-left) → Data streams → click your web stream → <b>Measurement ID</b> at the top right.
              It looks like <code>G-ABCD123XYZ</code>. Paste the ID only, not the whole script.
            </span>

            <label style="<?= $sl ?>">Google Search Console — verification token
              <?= $sf('seo_gsc_token') !== '' ? $chip(true, 'set', '') : $chip(false, '', 'not set') ?>
            </label>
            <input name="seo_gsc_token" value="<?= $sf('seo_gsc_token') ?>" placeholder="only the content=&quot;…&quot; value" style="<?= $st ?>">
            <span style="<?= $sh ?>">
              <b>Where to find it:</b> <a href="https://search.google.com/search-console" target="_blank" rel="noopener">search.google.com/search-console</a>
              → add your property → choose <b>HTML tag</b> → you are given<br>
              <code style="font-size:11.5px">&lt;meta name="google-site-verification" content="<b>AbC123…</b>"&gt;</code><br>
              Paste <b>only the bold part</b>. Save here first, then press Verify in Google.
            </span>

            <h3 style="font-size:17px;margin:26px 0 4px;color:#0f1c33">Custom code</h3>
            <p style="<?= $sh ?>">Pasted verbatim into every public page. Scripts only — this is not escaped, so
               do not paste anything you do not trust.</p>

            <label style="<?= $sl ?>">Extra code in &lt;head&gt;</label>
            <textarea name="seo_head_code" rows="3" placeholder="Meta Pixel · Microsoft Clarity · Google Tag Manager" style="<?= $st ?>;font-family:monospace;font-size:12.5px"><?= $sf('seo_head_code') ?></textarea>
            <span style="<?= $sh ?>">For anything that must load early — pixels, tag managers, verification tags.</span>

            <label style="<?= $sl ?>">Extra code before &lt;/body&gt;</label>
            <textarea name="seo_body_code" rows="3" placeholder="chat widget · remarketing · conversion tracking" style="<?= $st ?>;font-family:monospace;font-size:12.5px"><?= $sf('seo_body_code') ?></textarea>
            <span style="<?= $sh ?>">For anything that can load last — chat widgets, support bubbles.</span>

            <p style="margin:18px 0 0;padding:11px 14px;background:#f4f7fc;border-radius:9px;font-size:12.5px;color:#5a6b86">
              <i class="fa-solid fa-circle-info" style="color:#1741a6"></i>
              <b>Before Google can index the site:</b> <code>robots.txt</code> and the page meta must allow it.
              Staging builds ship with <code>noindex,nofollow</code> — remove that on the live domain, then submit
              <a href="<?= rtrim(base_url(), '/') ?>/sitemap.xml" target="_blank" rel="noopener">/sitemap.xml</a> in Search Console.
            </p>
          </div><button class="abtn ok" type="submit" style="margin-top:16px;padding:12px 26px"><i class="fa-solid fa-check"></i> Save SEO</button></form>

          <?php $rep = $rows ?: ['total'=>0,'gaps'=>[]]; ?>
          <h2 class="sec-title" style="margin-top:34px">What's holding your search visibility back</h2>
          <p style="margin:-8px 0 16px;font-size:13px;color:#7b8aa5;max-width:640px">
            Counted across <?= number_format((int)$rep['total']) ?> active listings. Biggest gap first —
            fixing the top two or three is usually worth more than everything else combined.
          </p>
          <?php if(empty($rep['gaps'])): ?>
            <div class="panel" style="padding:30px;text-align:center;color:#1a7a3d">
              <i class="fa-solid fa-circle-check" style="font-size:28px;display:block;margin-bottom:10px"></i>
              No gaps found — every active listing has a description, contact details, an area, a category and a photo.
            </div>
          <?php else: ?>
            <div class="panel">
              <?php foreach($rep['gaps'] as $g): ?>
                <div style="display:flex;gap:16px;padding:17px 20px;border-bottom:1px solid #f1f3f7;align-items:flex-start">
                  <i class="fa-solid fa-<?= $g['icon'] ?>" style="color:#1741a6;font-size:17px;width:22px;margin-top:3px;flex:none"></i>
                  <div style="flex:1;min-width:0">
                    <b style="color:#0f1c33;font-size:14.5px"><?= number_format((int)$g['n']) ?> <?= esc($g['label']) ?></b>
                    <div style="color:#7b8aa5;font-size:12.5px;margin-top:3px"><?= esc($g['why']) ?></div>
                    <div style="color:#33405a;font-size:12.5px;margin-top:5px"><i class="fa-solid fa-arrow-right" style="color:#0b8a3d"></i> <?= esc($g['fix']) ?></div>
                  </div>
                  <?php if($g['pct'] !== null): ?>
                    <span class="chip <?= $g['pct']>=50?'chip-adm':($g['pct']>=20?'chip-pending':'chip-live') ?>" style="flex:none"><?= (int)$g['pct'] ?>%</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php elseif($sec==='reports'):
          $R=$rows?:['which'=>'categories','from'=>'','to'=>'','cats'=>[],'users'=>[]];
          $qs=($R['from']!==''?'&from='.urlencode($R['from']):'').($R['to']!==''?'&to='.urlencode($R['to']):''); ?>
          <h2 class="sec-title">Reports</h2>
          <div style="display:flex;gap:9px;margin-bottom:16px;flex-wrap:wrap">
            <a class="abtn <?= $R['which']==='categories'?'ok':'gh' ?>" href="<?= $b ?>admin/reports?r=categories"><i class="fa-solid fa-sitemap"></i> Categories</a>
            <a class="abtn <?= $R['which']==='users'?'ok':'gh' ?>" href="<?= $b ?>admin/reports?r=users"><i class="fa-solid fa-users"></i> Listings per member</a>
          </div>

          <?php if($R['which']==='users'): ?>
            <form method="get" action="<?= $b ?>admin/reports" class="panel" style="padding:16px 20px;margin-bottom:16px;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
              <input type="hidden" name="r" value="users">
              <div><label class="admlbl">From</label><input class="adminp" type="date" name="from" value="<?= esc($R['from']) ?>"></div>
              <div><label class="admlbl">To</label><input class="adminp" type="date" name="to" value="<?= esc($R['to']) ?>"></div>
              <button class="abtn gh"><i class="fa-solid fa-filter"></i> Apply</button>
              <?php if($R['from']!==''||$R['to']!==''): ?><a class="abtn gh" href="<?= $b ?>admin/reports?r=users">Clear</a><?php endif; ?>
              <a class="abtn gh" style="margin-left:auto" href="<?= $b ?>admin/export/user-stats?x=1<?= $qs ?>"><i class="fa-solid fa-download"></i> Export CSV</a>
            </form>
            <div class="panel"><table><tr><th>Member</th><th>Role</th><th style="text-align:right">Listings</th><th>Last added</th><th style="text-align:right">Their listings</th></tr>
              <?php if(!$R['users']): ?><tr><td colspan="5" style="text-align:center;color:#9aa6bd;padding:40px">Nothing in that range.</td></tr><?php endif; ?>
              <?php $rl=[1=>'Admin',2=>'Business',3=>'Member']; foreach($R['users'] as $u): ?><tr>
                <td><b><a href="<?= $b ?>admin/user/<?= (int)$u['id'] ?>" style="color:#0f1c33"><?= esc($u['name'] ?: '—') ?></a></b>
                  <div style="color:#94a1b8;font-size:12px"><?= esc($u['email'] ?? '') ?></div></td>
                <td><span class="chip chip-adm"><?= esc($rl[(int)($u['role_id']??3)] ?? '') ?></span></td>
                <td style="text-align:right"><b><?= number_format((int)$u['listing_count']) ?></b></td>
                <td style="color:#94a1b8"><?= esc(mb_substr((string)($u['last_listing'] ?? ''),0,10)) ?: '—' ?></td>
                <td style="text-align:right"><?php if((int)$u['listing_count']): ?><a class="abtn gh" href="<?= $b ?>admin/export/user-listings/<?= (int)$u['id'] ?>"><i class="fa-solid fa-download"></i> CSV</a><?php endif; ?></td>
              </tr><?php endforeach; ?>
            </table></div>
          <?php else: ?>
            <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
              <a class="abtn gh" href="<?= $b ?>admin/export/category-tree"><i class="fa-solid fa-download"></i> Export CSV</a>
            </div>
            <div class="panel"><table><tr><th>Category</th><th style="text-align:right">Listings</th></tr>
              <?php foreach($R['cats'] as $c): ?>
                <tr><td><b><?= esc(html_entity_decode((string) ($c['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b></td><td style="text-align:right"><b><?= number_format((int)$c['n']) ?></b></td></tr>
                <?php foreach($c['kids'] as $k): ?>
                  <tr><td style="padding-left:38px;color:#5b6a86"><i class="fa-solid fa-turn-up fa-rotate-90" style="color:#c7d2e4;font-size:11px"></i> <?= esc(html_entity_decode((string) ($k['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></td>
                      <td style="text-align:right;color:#5b6a86"><?= number_format((int)$k['n']) ?></td></tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </table></div>
          <?php endif; ?>

        <?php elseif($sec==='profile'): $me=$edit?:[]; ?>
          <h2 class="sec-title">My account</h2>
          <form method="post" action="<?= $b ?>admin/profile/save" style="max-width:480px">
            <div class="panel" style="padding:24px">
              <h3 style="font-size:17px;margin:0 0 14px;color:#0f1c33">Your details</h3>
              <label class="admlbl">Name</label>
              <input class="adminp" name="name" required value="<?= esc($me['name']??'') ?>">
              <label class="admlbl">Email <span style="font-weight:400;color:#9aa6bd">— this is your login</span></label>
              <input class="adminp" type="email" name="email" required value="<?= esc($me['email']??'') ?>">
              <label class="admlbl">Phone</label>
              <input class="adminp" name="phone" value="<?= esc($me['phone']??'') ?>">
            </div>

            <div class="panel" style="padding:24px;margin-top:18px">
              <h3 style="font-size:17px;margin:0 0 4px;color:#0f1c33">Change password</h3>
              <p style="font-size:12px;color:#7b8aa5;margin:0 0 4px">Leave these blank to keep your current password.</p>
              <label class="admlbl">Current password</label>
              <input class="adminp" type="password" name="current_password" autocomplete="current-password">
              <label class="admlbl">New password</label>
              <input class="adminp" type="password" name="new_password" autocomplete="new-password" minlength="8">
              <label class="admlbl">Repeat new password</label>
              <input class="adminp" type="password" name="confirm_password" autocomplete="new-password" minlength="8">
            </div>

            <button class="abtn ok" type="submit" style="margin-top:16px;padding:12px 26px"><i class="fa-solid fa-check"></i> Save</button>
          </form>

        <?php elseif($sec==='language'):
          $L=$rows?:['codes'=>['english'],'editing'=>'english','phrases'=>[],'active'=>'english','q'=>'','total'=>0,'writable'=>true]; ?>
          <h2 class="sec-title">Language</h2>
          <?php if(!$L['writable']): ?>
            <div class="panel" style="padding:16px 20px;margin-bottom:16px;color:#b3262f;background:#fdecec">
              <i class="fa-solid fa-circle-exclamation"></i>
              <b>writable/language is not writable</b> — phrases cannot be saved until that folder is writable by PHP.
            </div>
          <?php endif; ?>

          <div class="panel" style="padding:18px 20px;margin-bottom:18px">
            <h3 style="margin:0 0 12px;font-size:16px;color:#0f1c33">Languages</h3>
            <div style="display:flex;gap:9px;flex-wrap:wrap;align-items:center">
              <?php foreach($L['codes'] as $c): $on=$c===$L['active']; $ed=$c===$L['editing']; ?>
                <span style="display:inline-flex;align-items:center;gap:6px;border:1px solid <?= $ed?'#1741a6':'#dfe4ec' ?>;border-radius:9px;padding:6px 10px;background:<?= $ed?'#eef3fc':'#fff' ?>">
                  <a href="<?= $b ?>admin/language?lang=<?= esc($c,'url') ?>" style="font-weight:700;color:#0f1c33"><?= esc(ucfirst($c)) ?></a>
                  <?php if($on): ?><span class="chip chip-live">In use</span>
                  <?php else: ?>
                    <form method="post" action="<?= $b ?>admin/language/activate" style="display:inline">
                      <input type="hidden" name="code" value="<?= esc($c,'attr') ?>">
                      <button class="abtn gh" style="font-size:11.5px" title="Use this language on the site">Use</button>
                    </form>
                    <?php if($c!=='english'): ?>
                      <form method="post" action="<?= $b ?>admin/language/delete" style="display:inline" onsubmit="return confirm('Delete <?= esc($c,'js') ?> and its translations?')">
                        <input type="hidden" name="code" value="<?= esc($c,'attr') ?>">
                        <button class="abtn gh" style="font-size:11.5px;color:#b3262f" title="Delete"><i class="fa-solid fa-trash"></i></button>
                      </form>
                    <?php endif; ?>
                  <?php endif; ?>
                </span>
              <?php endforeach; ?>
              <form method="post" action="<?= $b ?>admin/language/add" style="display:flex;gap:7px;align-items:center;margin-left:6px">
                <input class="adminp" name="code" placeholder="hindi" style="width:130px" required pattern="[A-Za-z0-9_-]+">
                <button class="abtn gh"><i class="fa-solid fa-plus"></i> Add language</button>
              </form>
            </div>
            <p style="margin:12px 0 0;font-size:12.5px;color:#7b8aa5">
              A new language starts as a copy of English, so every phrase is there ready to translate.
              Files live in <code>writable/language/</code>.
            </p>
          </div>

          <h2 class="sec-title" style="margin-top:8px">
            Phrases — <?= esc(ucfirst($L['editing'])) ?>
            <span style="font-size:14px;color:#9aa6bd;font-weight:400">(<?= number_format((int)$L['total']) ?> total<?= $L['q']!=='' ? ', '.count($L['phrases']).' shown' : '' ?>)</span>
          </h2>

          <form method="get" action="<?= $b ?>admin/language" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
            <input type="hidden" name="lang" value="<?= esc($L['editing'],'attr') ?>">
            <input class="adminp" name="q" value="<?= esc($L['q']) ?>" placeholder="Search a phrase or its key…" style="max-width:320px">
            <button class="abtn gh"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <?php if($L['q']!==''): ?><a class="abtn gh" href="<?= $b ?>admin/language?lang=<?= esc($L['editing'],'url') ?>">Clear</a><?php endif; ?>
          </form>

          <form method="post" action="<?= $b ?>admin/language/save">
            <input type="hidden" name="code" value="<?= esc($L['editing'],'attr') ?>">
            <input type="hidden" name="q" value="<?= esc($L['q'],'attr') ?>">
            <div class="panel"><table><tr><th style="width:34%">Key</th><th>Text shown on the site</th></tr>
              <?php if(!$L['phrases']): ?><tr><td colspan="2" style="text-align:center;color:#9aa6bd;padding:40px">No phrases match.</td></tr><?php endif; ?>
              <?php foreach($L['phrases'] as $k=>$v): ?><tr>
                <td style="color:#7b8aa5;font-family:monospace;font-size:12.5px"><?= esc($k) ?></td>
                <td><input class="adminp" name="phrase[<?= esc($k,'attr') ?>]" value="<?= esc($v) ?>"></td>
              </tr><?php endforeach; ?>
            </table></div>
            <?php if($L['phrases']): ?>
              <button class="abtn ok" type="submit" style="margin-top:16px;padding:12px 26px"><i class="fa-solid fa-check"></i> Save phrases</button>
            <?php endif; ?>
          </form>

          <div class="panel" style="padding:18px 20px;margin-top:18px;max-width:520px">
            <h3 style="margin:0 0 4px;font-size:16px;color:#0f1c33">Add a phrase</h3>
            <p style="margin:0 0 12px;font-size:12.5px;color:#7b8aa5">Keys are lowercase with underscores, e.g. <code>book_now</code>.</p>
            <form method="post" action="<?= $b ?>admin/language/addphrase" style="display:grid;grid-template-columns:1fr 1fr auto;gap:9px;align-items:end">
              <input type="hidden" name="code" value="<?= esc($L['editing'],'attr') ?>">
              <div><label class="admlbl">Key</label><input class="adminp" name="key" required placeholder="book_now"></div>
              <div><label class="admlbl">Text</label><input class="adminp" name="value" placeholder="Book now"></div>
              <button class="abtn gh"><i class="fa-solid fa-plus"></i> Add</button>
            </form>
          </div>

        <?php elseif($sec==='addons'): ?>
          <h2 class="sec-title">Add-ons</h2>
          <p style="margin:-8px 0 18px;font-size:13px;color:#7b8aa5;max-width:640px">
            Switch off anything you don't want on the site. Turning one off hides it everywhere at once —
            existing data is kept, so switching it back on brings it straight back.
          </p>
          <div class="panel"><table><tr><th>Feature</th><th>What it covers</th><th style="text-align:right">Status</th></tr>
            <?php foreach($rows as $r): ?><tr>
              <td style="white-space:nowrap"><i class="fa-solid fa-<?= $r['icon'] ?>" style="color:#1741a6;width:18px"></i>
                <b style="margin-left:6px"><?= $r['name'] ?></b></td>
              <td style="color:#7b8aa5;font-size:12.5px"><?= $r['note'] ?></td>
              <td style="text-align:right;white-space:nowrap">
                <span class="chip <?= $r['on']?'chip-live':'chip-adm' ?>" style="margin-right:8px"><?= $r['on']?'On':'Off' ?></span>
                <a class="abtn gh" href="<?= $b ?>admin/addon/<?= esc($r['key'],'url') ?>"
                   onclick="return confirm('<?= $r['on'] ? 'Switch this off across the whole site?' : 'Switch this back on?' ?>')">
                  <?= $r['on'] ? 'Switch off' : 'Switch on' ?></a>
              </td>
            </tr><?php endforeach; ?>
          </table></div>

        <?php elseif($sec==='settings'): ?>
          <h2 class="sec-title">Settings</h2>
          <form method="post" action="<?= $b ?>admin/settings/save" style="max-width:520px">
            <div class="panel" style="padding:24px">
              <?php $s=$edit??[]; $sf=static fn($k)=>isset($s[$k])?esc($s[$k]):''; $st='width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:11px 13px;font-size:14px;margin-bottom:4px'; $sl='display:block;font-size:12px;font-weight:600;color:#5a6b86;margin:12px 0 6px'; ?>
              <?php $sh='display:block;font-size:12px;color:#7b8aa5;margin:0 0 4px'; ?>

              <h3 style="font-size:17px;margin:0 0 4px;color:#0f1c33">Site details</h3>
              <p style="<?= $sh ?>">Shown in the browser tab, the footer and the contact page.</p>
              <label style="<?= $sl ?>">Website title</label>
              <input name="website_title" value="<?= $sf('website_title') ?>" placeholder="HiNoida — Noida Business Directory" style="<?= $st ?>">
              <label style="<?= $sl ?>">Contact email</label>
              <input name="system_email" type="email" value="<?= $sf('system_email') ?>" placeholder="hello@new.hinoida.com" style="<?= $st ?>">
              <span style="<?= $sh ?>">Where enquiries about the site itself go.</span>
              <label style="<?= $sl ?>">Phone</label>
              <input name="phone" value="<?= $sf('phone') ?>" placeholder="+91 98765 43210" style="<?= $st ?>">
              <label style="<?= $sl ?>">Address</label>
              <input name="address" value="<?= $sf('address') ?>" placeholder="Sector 62, Noida, Uttar Pradesh" style="<?= $st ?>">
              <label style="<?= $sl ?>">Footer text</label>
              <input name="footer_text" value="<?= $sf('footer_text') ?>" placeholder="© HiNoida. All rights reserved." style="<?= $st ?>">

              <h3 style="font-size:17px;margin:26px 0 4px;color:#0f1c33">Outgoing email</h3>
              <p style="<?= $sh ?>">Used for OTP codes, welcome mails and business notifications.
                 Anything set in <code>.env</code> overrides these.</p>

              <label style="<?= $sl ?>">From address</label>
              <input name="mail_from" type="email" value="<?= $sf('mail_from') ?>" placeholder="noreply@new.hinoida.com" style="<?= $st ?>">
              <span style="<?= $sh ?>">Must be an address your SMTP account is allowed to send as, or mail will bounce.</span>
              <label style="<?= $sl ?>">From name</label>
              <input name="mail_from_name" value="<?= $sf('mail_from_name') ?>" placeholder="HiNoida" style="<?= $st ?>">

              <label style="<?= $sl ?>">SMTP host</label>
              <input name="smtp_host" value="<?= $sf('smtp_host') ?>" placeholder="smtp.gmail.com" style="<?= $st ?>">
              <span style="<?= $sh ?>">Gmail <code>smtp.gmail.com</code> · Hostinger <code>smtp.hostinger.com</code> · Outlook <code>smtp.office365.com</code></span>
              <label style="<?= $sl ?>">Port</label>
              <input name="smtp_port" value="<?= $sf('smtp_port') ?>" placeholder="587" style="<?= $st ?>">
              <span style="<?= $sh ?>">587 with TLS (usual) · 465 with SSL</span>
              <label style="<?= $sl ?>">Encryption</label>
              <select name="smtp_secure" style="<?= $st ?>">
                <?php foreach (['tls'=>'TLS (port 587)','ssl'=>'SSL (port 465)',''=>'None'] as $k=>$v): ?>
                  <option value="<?= $k ?>" <?= ($s['smtp_secure'] ?? 'tls')===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
              <label style="<?= $sl ?>">SMTP username</label>
              <input name="smtp_user" value="<?= $sf('smtp_user') ?>" placeholder="you@gmail.com" style="<?= $st ?>">
              <label style="<?= $sl ?>">SMTP password</label>
              <input name="smtp_pass" type="password" value="<?= $sf('smtp_pass') ?>" autocomplete="new-password" style="<?= $st ?>">
              <span style="<?= $sh ?>">For Gmail this is a <b>16-character App Password</b>, not your normal password —
                 Google Account → Security → 2-Step Verification → App passwords.</span>

              <p style="margin:18px 0 0;padding:11px 14px;background:#f4f7fc;border-radius:9px;font-size:12.5px;color:#5a6b86">
                <i class="fa-solid fa-circle-info" style="color:#1741a6"></i>
                Mail is queued, not sent immediately. A cron job drains the queue:<br>
                <code style="font-size:12px">*/5 * * * * curl -s <?= rtrim(base_url(), '/') ?>/cron/email &gt;/dev/null</code><br>
                Check <a href="<?= $b ?>admin/email">Email queue</a> to see what has actually gone out.
              </p>
            </div>

            <div class="panel" style="padding:24px;margin-top:18px">
              <h3 style="font-size:17px;margin:0 0 4px;color:#0f1c33">Home page</h3>
              <p style="<?= $sh ?>">The headline and search box visitors see first. Leave blank to keep the defaults.</p>
              <label style="<?= $sl ?>">Headline</label>
              <input name="hero_title" value="<?= $sf('hero_title') ?>" placeholder="Everything in Noida. Curated." style="<?= $st ?>">
              <label style="<?= $sl ?>">Sub-heading <span style="font-weight:400;color:#9aa6bd">— optional</span></label>
              <input name="hero_subtitle" value="<?= $sf('hero_subtitle') ?>" placeholder="Find and compare 2,000 local businesses across every sector." style="<?= $st ?>">
              <label style="<?= $sl ?>">Search box placeholder</label>
              <input name="hero_placeholder" value="<?= $sf('hero_placeholder') ?>" placeholder="Search hospitals, restaurants, salons, shops..." style="<?= $st ?>">

              <h3 style="font-size:15px;margin:22px 0 4px;color:#0f1c33">The three boxes under the hero</h3>
              <p style="<?= $sh ?>">Leave a heading blank to hide that box. Icons are Font Awesome classes —
                for example <code>fa-solid fa-magnifying-glass</code>.</p>
              <label style="<?= $sl ?>">Section heading <span style="font-weight:400;color:#9aa6bd">— optional</span></label>
              <input name="hero_boxes_title" value="<?= $sf('hero_boxes_title') ?>" placeholder="How HiNoida works" style="<?= $st ?>">
              <?php foreach ([['one','Box 1','fa-solid fa-magnifying-glass'],['two','Box 2','fa-solid fa-location-dot'],['three','Box 3','fa-solid fa-star']] as $hb): ?>
                <label style="<?= $sl ?>"><?= $hb[1] ?> — heading</label>
                <input name="hero_topic_<?= $hb[0] ?>" value="<?= $sf('hero_topic_' . $hb[0]) ?>" style="<?= $st ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                  <div><label style="<?= $sl ?>">Icon</label>
                    <input name="hero_icon_<?= $hb[0] ?>" value="<?= $sf('hero_icon_' . $hb[0]) ?>" placeholder="<?= $hb[2] ?>" style="<?= $st ?>"></div>
                  <div><label style="<?= $sl ?>">Text under it</label>
                    <input name="hero_subtopic_<?= $hb[0] ?>" value="<?= $sf('hero_subtopic_' . $hb[0]) ?>" style="<?= $st ?>"></div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="panel" style="padding:24px;margin-top:18px">
              <h3 style="font-size:17px;margin:0 0 4px;color:#0f1c33">Cookie notice &amp; popup</h3>
              <p style="<?= $sh ?>">Both stay hidden until you switch them on and write the text.</p>
              <label style="display:flex;align-items:center;gap:8px;margin:10px 0;font-size:13.5px">
                <input type="checkbox" name="cookie_notice" value="1" <?= $sf('cookie_notice')==='1'?'checked':'' ?>> Show a cookie notice
              </label>
              <label style="<?= $sl ?>">Cookie notice text</label>
              <input name="cookie_text" value="<?= $sf('cookie_text') ?>" placeholder="We use cookies to make HiNoida work better." style="<?= $st ?>">

              <label style="display:flex;align-items:center;gap:8px;margin:14px 0 10px;font-size:13.5px">
                <input type="checkbox" name="popup_enabled" value="1" <?= $sf('popup_enabled')==='1'?'checked':'' ?>> Show a popup to visitors
              </label>
              <span style="<?= $sh ?>">Appears once per visit, a couple of seconds in.</span>
              <label style="<?= $sl ?>">Popup heading</label>
              <input name="popup_title" value="<?= $sf('popup_title') ?>" style="<?= $st ?>">
              <label style="<?= $sl ?>">Popup text</label>
              <input name="popup_text" value="<?= $sf('popup_text') ?>" style="<?= $st ?>">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div><label style="<?= $sl ?>">Button link</label><input name="popup_link" value="<?= $sf('popup_link') ?>" placeholder="https://…" style="<?= $st ?>"></div>
                <div><label style="<?= $sl ?>">Button text</label><input name="popup_link_text" value="<?= $sf('popup_link_text') ?>" placeholder="Find out more" style="<?= $st ?>"></div>
              </div>
            </div>

            <div class="panel" style="padding:24px;margin-top:18px">
              <h3 style="font-size:17px;margin:0 0 4px;color:#0f1c33">Tracking &amp; maps</h3>
              <label style="<?= $sl ?>">Meta (Facebook) Pixel ID</label>
              <input name="pixel_id" value="<?= $sf('pixel_id') ?>" placeholder="1234567890" style="<?= $st ?>">
              <span style="<?= $sh ?>">Like the analytics tags, this only fires in production — test traffic never reaches your reports.</span>
              <label style="<?= $sl ?>">Map default position</label>
              <input name="map_default_location" value="<?= $sf('map_default_location') ?>" placeholder="28.5355,77.3910" style="<?= $st ?>">
              <span style="<?= $sh ?>">Where the map picker opens when a listing has no coordinates yet. Blank uses Noida.</span>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div><label style="<?= $sl ?>">Min zoom</label><input name="map_min_zoom" type="number" min="1" max="20" value="<?= $sf('map_min_zoom') ?>" placeholder="11" style="<?= $st ?>"></div>
                <div><label style="<?= $sl ?>">Max zoom</label><input name="map_max_zoom" type="number" min="1" max="20" value="<?= $sf('map_max_zoom') ?>" placeholder="18" style="<?= $st ?>"></div>
              </div>
              <label style="<?= $sl ?>">Map access token <span style="font-weight:400;color:#9aa6bd">— optional</span></label>
              <input name="map_access_token" value="<?= $sf('map_access_token') ?>" style="<?= $st ?>">
              <span style="<?= $sh ?>">Only needed for a paid tile provider. Blank uses OpenStreetMap, which needs no key.</span>
            </div>

            <div class="panel" style="padding:24px;margin-top:18px">
              <h3 style="font-size:17px;margin:0 0 4px;color:#0f1c33">Payments</h3>
              <p style="<?= $sh ?>">Leave the keys blank until you have a gateway account — plans then stay
                offline-only, and you record each payment yourself under Revenue.</p>

              <label style="<?= $sl ?>">Gateway</label>
              <select name="pay_gateway" style="<?= $st ?>">
                <?php foreach([''=>'Offline only (no gateway)','razorpay'=>'Razorpay','payu'=>'PayU','stripe'=>'Stripe','paypal'=>'PayPal'] as $gk=>$gl): ?>
                  <option value="<?= $gk ?>" <?= ($s['pay_gateway']??'')===$gk?'selected':'' ?>><?= $gl ?></option>
                <?php endforeach; ?>
              </select>

              <label style="<?= $sl ?>">Currency</label>
              <input name="pay_currency" value="<?= $sf('pay_currency') ?>" placeholder="INR" style="<?= $st ?>">

              <label style="<?= $sl ?>">Key / Publishable ID</label>
              <input name="pay_key_id" value="<?= $sf('pay_key_id') ?>" placeholder="rzp_live_…" autocomplete="off" style="<?= $st ?>">

              <label style="<?= $sl ?>">Secret key</label>
              <input name="pay_key_secret" type="password" value="<?= $sf('pay_key_secret') ?>" autocomplete="new-password" style="<?= $st ?>">
              <span style="<?= $sh ?>">Stored in the database. Never share it, and never put it in a public repo.</span>

              <label style="<?= $sl ?>">UPI ID for manual payments</label>
              <input name="pay_upi" value="<?= $sf('pay_upi') ?>" placeholder="hinoida@okhdfcbank" style="<?= $st ?>">

              <label style="<?= $sl ?>">Bank details shown to buyers</label>
              <textarea name="pay_bank_details" rows="3" style="<?= $st ?>"><?= $sf('pay_bank_details') ?></textarea>

              <label style="<?= $sl ?>">Note on the pricing page</label>
              <input name="pay_note" value="<?= $sf('pay_note') ?>" placeholder="Pay by UPI and email the screenshot — we activate within a day." style="<?= $st ?>">
            </div>

            <button class="abtn ok" type="submit" style="margin-top:16px;padding:12px 26px"><i class="fa-solid fa-check"></i> Save settings</button>
          </form>

        <?php elseif($sec==='messages'): ?>
          <h2 class="sec-title">Contact messages (<?= count($rows) ?>)</h2>
          <div class="panel">
            <table>
              <tr><th>From</th><th>Message</th><th>Received</th></tr>
              <?php if (! $rows): ?>
                <tr><td colspan="3"><div style="text-align:center;color:#9aa6bd;padding:38px 20px">
                  <i class="fa-solid fa-inbox" style="font-size:30px;display:block;margin-bottom:10px;color:#c7d2e4"></i>
                  No messages yet. They arrive from the
                  <a href="<?= rtrim($b,'/') ?>/contact" target="_blank" rel="noopener">contact page</a>.</div></td></tr>
              <?php else: foreach ($rows as $r): ?>
                <tr>
                  <td style="white-space:nowrap">
                    <b><?= esc($r['name'] ?? '') ?></b><br>
                    <a href="mailto:<?= esc($r['email'] ?? '', 'attr') ?>" style="font-size:12.5px"><?= esc($r['email'] ?? '') ?></a>
                    <?php if (! empty($r['phone'])): ?><br><small style="color:#9aa6bd"><?= esc($r['phone']) ?></small><?php endif; ?>
                  </td>
                  <td style="max-width:520px"><?= nl2br(esc($r['message'] ?? '')) ?></td>
                  <td style="color:#9aa6bd;white-space:nowrap"><?= esc(mb_substr((string)($r['created_at'] ?? ''),0,16)) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </table>
          </div>

        <?php elseif($sec==='email'): ?>
          <?php $q = $edit ?? ['pending'=>0,'sent'=>0,'logged'=>0,'failed'=>0]; ?>
          <h2 class="sec-title" style="display:flex;justify-content:space-between;align-items:center">Email queue
            <a class="abtn gh" href="<?= rtrim($b,'/') ?>/cron/email" target="_blank" rel="noopener" style="font-size:12.5px"><i class="fa-solid fa-paper-plane"></i> Send queue now</a>
          </h2>
          <div class="stats" style="margin-bottom:22px">
            <?php foreach ([['pending','Pending','clock','#b26a00'],['sent','Sent','circle-check','#1a7a3d'],
                            ['logged','Logged','file-lines','#5a6b86'],['failed','Failed','triangle-exclamation','#b3262f']] as $c): ?>
              <div class="stat"><i class="fa-solid fa-<?= $c[2] ?>" style="color:<?= $c[3] ?>"></i>
                <b><?= (int)($q[$c[0]] ?? 0) ?></b><span><?= $c[1] ?></span></div>
            <?php endforeach; ?>
          </div>
          <p style="margin:0 0 16px;padding:11px 14px;background:#f4f7fc;border-radius:9px;font-size:12.5px;color:#5a6b86">
            <i class="fa-solid fa-circle-info" style="color:#1741a6"></i>
            Mail is queued and sent by cron, so signups stay fast. Add this on your host:<br>
            <code style="font-size:12px">*/5 * * * * curl -s <?= rtrim($b,'/') ?>/cron/email &gt;/dev/null</code><br>
            <b>Pending stuck above zero?</b> The cron is not running, or SMTP is rejecting —
            check the error column, then <a href="<?= $b ?>admin/settings">Settings</a>.
          </p>
          <p style="margin:0 0 16px;padding:11px 14px;background:#f4f7fc;border-radius:9px;font-size:12.5px;color:#5a6b86">
            <i class="fa-solid fa-crown" style="color:#1741a6"></i>
            <b>Featured placement</b> is paid for by the month, so a second job has to take it away
            again when a plan runs out — without it, one payment buys featured forever:<br>
            <code style="font-size:12px">10 3 * * * curl -s <?= rtrim($b,'/') ?>/cron/expire-featured?key=YOUR_CRON_KEY &gt;/dev/null</code><br>
            Use the same key as the email job (it is in <code>app/Controllers/Cron.php</code>).
          </p>
          <div class="panel">
            <table>
              <tr><th>To</th><th>Subject</th><th>Status</th><th>Tries</th><th>Queued</th></tr>
              <?php if (! $rows): ?>
                <tr><td colspan="5"><div class="empty" style="padding:38px 20px;text-align:center;color:#9aa6bd">
                  <i class="fa-solid fa-envelope" style="font-size:30px;display:block;margin-bottom:10px;color:#c7d2e4"></i>
                  Nothing queued.</div></td></tr>
              <?php else: foreach ($rows as $r):
                $st = strtolower((string)($r['status'] ?? 'pending'));
                $cls = $st==='sent' ? 'chip-live' : ($st==='failed' ? 'chip-no' : 'chip-pending'); ?>
                <tr>
                  <td><?= esc($r['to_email'] ?? $r['email'] ?? '—') ?></td>
                  <td style="max-width:320px"><?= esc($r['subject'] ?? '—') ?>
                    <?php if (! empty($r['error'])): ?><br><small style="color:#b3262f"><?= esc(mb_substr((string)$r['error'],0,300)) ?></small><?php endif; ?>
                  </td>
                  <td><span class="chip <?= $cls ?>"><?= esc(ucfirst($st)) ?></span></td>
                  <td><?= (int)($r['attempts'] ?? 0) ?></td>
                  <td style="color:#9aa6bd;white-space:nowrap"><?= esc(mb_substr((string)($r['created_at'] ?? ''),0,16)) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </table>
          </div>

        <?php else: ?>
          <h2 class="sec-title" style="display:flex;justify-content:space-between;align-items:center">Listings (<?= count($rows) ?>)
            <span style="display:flex;gap:8px"><a class="abtn gh" href="<?= $b ?>admin/listing/new" style="font-size:12.5px;background:#0b8a3d;color:#fff"><i class="fa-solid fa-plus"></i> Add listing</a><a class="abtn gh" href="<?= $b ?>admin/export/listings" style="font-size:12.5px"><i class="fa-solid fa-download"></i> Export CSV</a></span></h2>
          <div class="panel"><table><tr><th>Name</th><th>Status</th><th style="text-align:right">Actions</th></tr>
            <?php foreach($rows as $r): ?><tr>
              <td><b><?= esc(html_entity_decode((string) ($r['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></b></td>
              <td><span class="chip <?= ($r['status']??'')==='active'?'chip-live':'chip-pending' ?>"><?= esc(ucfirst($r['status']??'pending')) ?></span></td>
              <td style="text-align:right;white-space:nowrap">
                <a class="abtn gh" href="<?= $b ?>admin/listing/<?= (int)$r['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                <form method="post" action="<?= $b ?>admin/listing/delete/<?= (int)$r['id'] ?>" style="display:inline" onsubmit="return confirm('Delete this listing?')"><button class="abtn no"><i class="fa-solid fa-trash"></i></button></form>
              </td>
            </tr><?php endforeach; ?>
          </table></div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
