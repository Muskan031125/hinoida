<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/dash.css') ?>?v=9">
</head>
<body>
<?php
  $b = base_url();
  // Sidebar grouped by purpose, rendered as accordions.
  $groups = ($mode === 'business')
    ? [
        ['Overview', 'gauge-high', [
          ['overview','gauge-high','Dashboard',null],
          ['insights','chart-line','Insights',null],
          ['activity','wave-square','Activity',null],
        ]],
        ['My business', 'store', [
          ['listings','store','My Listings', count($mine) ?: null],
          ['properties','building','My Properties', count($myProps ?? []) ?: null],
          ['offers','tags','Special offers',null],
        ]],
        ['Customers', 'users', [
          ['leads','inbox','Leads', $stats['leads'] ?: null],
          ['reviews','star','Reviews', $stats['reviews'] ?: null],
          ['questions','circle-question','Questions', ($qaOpen ?? 0) ?: null],
        ]],
        ['As a customer', 'clock-rotate-left', [
          ['bookings','calendar-check','My Bookings', count($myBookings) ?: null],
          ['enquiries','paper-plane','My Enquiries', count($myEnq) ?: null],
          ['myreviews','star','My Reviews', count($myReviews) ?: null],
          ['saved','heart','Saved places', count($saved) ?: null],
        ]],
        ['Account', 'user', [
          ['profile','user','Profile',null],
          ['billing','receipt','Billing',null],
          ['requests','comment-dots','Change requests',null],
        ]],
      ]
    : [
        ['Overview', 'gauge-high', [
          ['overview','gauge-high','Dashboard',null],
        ]],
        ['My activity', 'clock-rotate-left', [
          ['bookings','calendar-check','My Bookings', count($myBookings) ?: null],
          ['enquiries','paper-plane','My Enquiries', count($myEnq) ?: null],
          ['myreviews','star','My Reviews', count($myReviews) ?: null],
          ['myquestions','circle-question','My Questions', count($myQ ?? []) ?: null],
        ]],
        ['Saved', 'heart', [
          ['saved','heart','Saved places', count($saved) ?: null],
        ]],
        ['Account', 'user', [
          ['profile','user','Profile',null],
        ]],
      ];
?>
<div class="shell" id="shell">
  <aside class="side" id="side">
    <div class="brand"><b>Hi</b> <span>NOIDA</span></div>
    <div class="who">
      <span class="whoav" style="background:<?= esc(hi_avatar_bg($name ?: ($me['email'] ?? '')), 'attr') ?>"><?= esc(hi_initial($name, $me['email'] ?? null)) ?></span>
      <div><span>Signed in as</span><b><?= esc($name) ?></b></div>
    </div>
    <nav class="nav">
      <?php foreach ($groups as $g): ?>
        <div class="navsec"><?= esc($g[0]) ?></div>
        <?php foreach ($g[2] as $it): ?>
          <a href="<?= $b ?>dashboard/<?= $it[0] ?>" class="<?= $sec === $it[0] ? 'on' : '' ?>">
            <i class="fa-solid fa-<?= $it[1] ?>"></i> <span><?= esc($it[2]) ?></span>
            <?php if (! empty($it[3])): ?><span class="pill"><?= (int) $it[3] ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?php if ($mode === 'business'): ?>
        <a href="<?= $b ?>list" style="margin-top:6px"><i class="fa-solid fa-plus"></i> <span>Add a listing</span></a>
      <?php else: ?>
        <a href="<?= $b ?>" style="margin-top:6px"><i class="fa-solid fa-compass"></i> <span>Explore Noida</span></a>
      <?php endif; ?>
    </nav>
    <div class="side-foot">
      <?php if ($mode !== 'business'): ?><a href="<?= $b ?>list" style="color:#7fa2ff"><i class="fa-solid fa-store"></i> <span>Own a business? List it free</span></a><?php endif; ?>
      <a href="<?= $b ?>" target="_blank" rel="noopener"><i class="fa-solid fa-house"></i> <span>Visit HiNoida site</span></a>
      <a href="<?= $b ?>logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
  </aside>
  <main class="main">
    <div class="top">
      <button type="button" class="sidetoggle" id="sideToggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
      <h1><?= $sec === 'overview' ? 'Welcome back, ' . esc($name) : esc(ucfirst($sec === 'myreviews' ? 'My reviews' : ($sec === 'myquestions' ? 'My questions' : $sec))) ?></h1>
      <?php if ($mode === 'business'): ?><a class="cta" href="<?= $b ?>list"><i class="fa-solid fa-plus"></i> Add listing</a>
      <?php else: ?><a class="cta" href="<?= $b ?>"><i class="fa-solid fa-compass"></i> Explore Noida</a><?php endif; ?>
    </div>
    <div class="content">
      <?php if(!empty($notice)): ?><div class="banner b-ok"><i class="fa-solid fa-circle-check"></i> <?= esc($notice) ?></div><?php endif; ?>
      <?php if(!empty($flash)): ?><div class="banner b-ok"><i class="fa-solid fa-circle-check"></i> <?= esc($flash) ?></div><?php endif; ?>
      <?php if(!empty($err)): ?><div class="banner" style="background:#fdecec;color:#b3262f;border:1px solid #f5c6c6"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($err) ?></div><?php endif; ?>
      <?php if(!$verified): ?><div class="banner b-warn" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap"><span><b><i class="fa-solid fa-envelope-circle-check"></i> Confirm your email</b> — verify <?= esc($me['email']??'') ?> to unlock everything.</span><a href="<?= base_url('verify-email') ?>" style="background:#8a6d1a;color:#fff;padding:8px 16px;border-radius:8px;font-weight:700;white-space:nowrap">Verify now</a></div>
      <?php elseif($isBusiness && !$approved): ?><div class="banner b-warn"><b><i class="fa-solid fa-clock"></i> Pending admin approval</b> — you'll be emailed once your business is approved.</div>
      <?php elseif($isBusiness && $approved): ?><div class="banner b-ok"><b><i class="fa-solid fa-circle-check"></i> Your business is approved & live.</b></div><?php endif; ?>

      <?php if($sec==='overview' && $mode==='customer'): ?>
        <div class="stats">
          <a class="stat" href="<?= $b ?>dashboard/bookings"><i class="fa-solid fa-calendar-check"></i><b><?= count($myBookings) ?></b><span>My bookings</span></a>
          <a class="stat" href="<?= $b ?>dashboard/enquiries"><i class="fa-solid fa-paper-plane"></i><b><?= count($myEnq) ?></b><span>My enquiries</span></a>
          <a class="stat" href="<?= $b ?>dashboard/myreviews"><i class="fa-solid fa-star"></i><b><?= count($myReviews) ?></b><span>My reviews</span></a>
          <a class="stat" href="<?= $b ?>dashboard/saved"><i class="fa-solid fa-heart"></i><b><?= count($saved) ?></b><span>Saved places</span></a>
        </div>
        <div class="panel"><h3>Recent bookings <a class="abtn" href="<?= $b ?>dashboard/bookings">View all</a></h3>
          <?php if(!$myBookings): ?><div class="empty"><i class="fa-solid fa-calendar-check"></i>No bookings yet. Explore places and book or enquire.<br><a class="btn" style="display:inline-block;margin-top:14px" href="<?= $b ?>">Browse Noida</a></div>
          <?php else: ?><table><tr><th>Place</th><th>Type</th><th>When</th><th>Status</th></tr>
            <?php foreach(array_slice($myBookings,0,5) as $bk): ?><tr><td><b><?= esc($bk['listing_name']??'') ?></b></td><td><?= esc($bk['booking_type']??'') ?></td><td><?= esc(trim(($bk['booking_date']??'').' '.($bk['booking_time']??''))) ?></td><td><span class="chip chip-pending"><?= esc($bk['status']??'pending') ?></span></td></tr><?php endforeach; ?>
          </table><?php endif; ?>
        </div>
        <div class="panel"><h3>Saved places <a class="abtn" href="<?= $b ?>dashboard/saved">View all</a></h3>
          <?php if(!$saved): ?><div class="empty"><i class="fa-solid fa-heart"></i>No saved places yet. Tap the heart on any listing to save it.</div>
          <?php else: foreach(array_slice($saved,0,4) as $l): ?>
            <div class="lrow"><img src="<?= esc(hi_cover($l)) ?>" alt=""><div class="g"><b><?= esc($l['name']) ?></b><small><?= esc($l['locality_name']?:'Noida') ?>, Noida</small></div><a class="abtn" href="<?= $b ?>l/<?= (int)$l['id'] ?>">View</a></div>
          <?php endforeach; endif; ?>
        </div>

      <?php elseif($sec==='overview'): ?>
        <div class="stats">
          <a class="stat" href="<?= $b ?>dashboard/listings"><i class="fa-solid fa-store"></i><b><?= $stats['listings'] ?></b><span>Listings</span></a>
          <a class="stat" href="<?= $b ?>dashboard/listings"><i class="fa-solid fa-circle-check"></i><b><?= $stats['active'] ?></b><span>Active</span></a>
          <a class="stat" href="<?= $b ?>dashboard/leads"><i class="fa-solid fa-inbox"></i><b><?= $stats['leads'] ?></b><span>Leads</span></a>
          <a class="stat" href="<?= $b ?>dashboard/reviews"><i class="fa-solid fa-star"></i><b><?= $stats['rating']?:'—' ?></b><span>Avg rating</span></a>
          <a class="stat" href="<?= $b ?>dashboard/reviews"><i class="fa-solid fa-comment"></i><b><?= $stats['reviews'] ?></b><span>Reviews</span></a>
          <a class="stat" href="<?= $b ?>dashboard/questions"><i class="fa-solid fa-circle-question"></i><b><?= count($qa) ?></b><span>Questions<?= !empty($qaOpen) ? ' · ' . (int)$qaOpen . ' open' : '' ?></span></a>
        </div>
        <div class="panel"><h3>Recent leads <a class="abtn" href="<?= $b ?>dashboard/leads">View all</a></h3>
          <?php $recent=array_slice($enq,0,5); if(!$recent && !$bok): ?><div class="empty"><i class="fa-solid fa-inbox"></i>No leads yet. Share your listing to get enquiries.</div>
          <?php else: ?><table><tr><th>Name</th><th>Phone</th><th>Message</th></tr>
            <?php foreach($recent as $e): ?><tr><td><b><?= esc($e['name']??'') ?></b></td><td><?= esc($e['phone']??'') ?></td><td><?= esc(mb_substr($e['message']??'',0,60)) ?></td></tr><?php endforeach; ?>
            <?php foreach(array_slice($bok,0,3) as $bk): ?><tr><td><b><?= esc($bk['name']??'') ?></b> <span class="chip chip-pending">booking</span></td><td><?= esc($bk['phone']??'') ?></td><td><?= esc($bk['booking_type']??'') ?> <?= esc($bk['booking_date']??'') ?></td></tr><?php endforeach; ?>
          </table><?php endif; ?>
        </div>
        <div class="panel"><h3>Your listings <a class="abtn" href="<?= $b ?>dashboard/listings">Manage</a></h3>
          <?php if(!$mine): ?><div class="empty"><i class="fa-solid fa-store"></i>You haven't added a business yet.<br><a class="btn" style="display:inline-block;margin-top:14px" href="<?= $b ?>list">Add your business</a></div>
          <?php else: foreach(array_slice($mine,0,4) as $l): ?>
            <div class="lrow"><img src="<?= esc(hi_cover($l)) ?>" alt="">
              <div class="g"><b><?= esc($l['name']) ?></b><small><?= esc($l['address']??'Noida') ?></small></div>
              <span class="chip <?= ($l['status']??'')==='active'?'chip-live':'chip-pending' ?>"><?= esc(ucfirst($l['status']??'pending')) ?></span>
              <a class="abtn" href="<?= $b ?>l/<?= (int)$l['id'] ?>">View</a>
            </div>
          <?php endforeach; endif; ?>
        </div>

      <?php elseif($sec==='insights'): ?>
        <div class="stats">
          <a class="stat" href="<?= $b ?>dashboard/insights"><i class="fa-solid fa-eye"></i><b><?= number_format($totalViews) ?></b><span>Total views</span></a>
          <div class="stat"><i class="fa-solid fa-paper-plane"></i><b><?= $stats['enquiries'] ?></b><span>Enquiries</span></div>
          <div class="stat"><i class="fa-solid fa-calendar-check"></i><b><?= $stats['bookings'] ?></b><span>Bookings</span></div>
          <a class="stat" href="<?= $b ?>dashboard/reviews"><i class="fa-solid fa-star"></i><b><?= $stats['rating']?:'—' ?></b><span>Avg rating</span></a>
        </div>
        <div class="panel"><h3><?= esc(hi_c('portal.performance_by_listing', 'Performance by listing')) ?></h3>
          <?php if(!$insights): ?><div class="empty"><i class="fa-solid fa-chart-line"></i>Add a listing to start seeing insights.</div>
          <?php else: ?><table><tr><th>Listing</th><th>Views</th><th title="Tapped the call button">Calls</th><th>WhatsApp</th><th title="Tapped Directions">Directions</th><th>Enquiries</th><th>Bookings</th><th>Reviews</th><th>Rating</th></tr>
            <?php foreach($insights as $it): ?><tr>
              <td><b><a href="<?= $b ?>l/<?= (int)$it['id'] ?>" target="_blank" style="color:#0f1c33"><?= esc($it['name']) ?></a></b></td>
              <td><b style="color:#1741a6"><?= number_format($it['views']) ?></b></td>
              <td><?= number_format($it['acts']['phone'] ?? 0) ?></td>
              <td><?= number_format($it['acts']['whatsapp'] ?? 0) ?></td>
              <td><?= number_format($it['acts']['direction'] ?? 0) ?></td>
              <td><?= $it['enquiries'] ?></td><td><?= $it['bookings'] ?></td><td><?= $it['reviews'] ?></td>
              <td><?= $it['rating']?('<span class="stars">'.$it['rating'].' ★</span>'):'—' ?></td>
            </tr><?php endforeach; ?>
          </table><?php endif; ?>
        </div>
        <p style="color:#94a1b8;font-size:13px;text-align:center"><?= esc(hi_c('portal.p_views_are_counted_each_time_a_customer_opens', 'Views are counted each time a customer opens your listing page.')) ?></p>

      <?php elseif($sec==='activity'):
        $acts=$activity??[];
        $icon=['page'=>'eye','profile'=>'eye','phone'=>'phone','whatsapp'=>'whatsapp','direction'=>'diamond-turn-right','website'=>'globe','share'=>'share-nodes'];
        $word=['page'=>'viewed','profile'=>'viewed','phone'=>'tapped call on','whatsapp'=>'opened WhatsApp for','direction'=>'asked directions to','website'=>'opened the website of','share'=>'shared']; ?>
        <div class="panel"><h3>Recent activity (<?= count($acts) ?>)</h3>
          <?php if(!$acts): ?><div class="empty"><i class="fa-solid fa-wave-square"></i>Nothing yet. This fills up as people find your listings.</div>
          <?php else: foreach($acts as $a): $t=(string)($a['view_type']??'page'); ?>
            <div style="display:flex;gap:12px;padding:13px 20px;border-bottom:1px solid #f1f3f7;align-items:baseline">
              <i class="fa-<?= $t==='whatsapp'?'brands':'solid' ?> fa-<?= $icon[$t] ?? 'eye' ?>" style="color:#1741a6;width:18px;flex:none"></i>
              <div style="flex:1;min-width:0;font-size:13.5px;color:#33405a">
                <b><?= esc($a['who'] ?: 'Someone') ?></b> <?= esc($word[$t] ?? $t) ?>
                <b><?= esc($a['listing_name'] ?: 'a listing') ?></b>
              </div>
              <span style="color:#94a1b8;font-size:12px;white-space:nowrap"><?= esc(mb_substr((string)($a['created_at']??''),0,16)) ?></span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      <?php elseif($sec==='requests'): $reqs=$requests??[]; ?>
        <div class="panel" style="margin-bottom:14px"><h3><i class="fa-solid fa-comment-dots"></i> Ask us to change something</h3>
          <div style="padding:16px 20px">
            <p style="margin:0 0 12px;color:#5b6a86;font-size:13.5px"><?= esc(hi_c('portal.p_for_anything_you_can_t_edit_yourself_a_name_', 'For anything you can\'t edit yourself — a name correction, moving a listing to a different category, or merging a duplicate. We reply here and by email.')) ?></p>
            <form method="post" action="<?= $b ?>dashboard/request/send">
              <select name="listing_id" class="adminp" style="max-width:340px;margin-bottom:9px">
                <option value="0">Not about a specific listing</option>
                <?php foreach($mine as $l): ?><option value="<?= (int)$l['id'] ?>"><?= esc($l['name']) ?></option><?php endforeach; ?>
              </select>
              <textarea name="req_message" class="adminp" rows="3" required minlength="10" placeholder="What would you like changed?"></textarea>
              <button class="btn" style="margin-top:10px"><?= esc(hi_c('portal.btn_send_request', 'Send request')) ?></button>
            </form>
          </div>
        </div>

        <div class="panel"><h3>Your requests (<?= count($reqs) ?>)</h3>
          <?php if(!$reqs): ?><div class="empty"><i class="fa-solid fa-comment-dots"></i>Nothing requested yet.</div>
          <?php else: foreach($reqs as $r): $open=($r['status']??'open')==='open'; ?>
            <div style="padding:15px 20px;border-bottom:1px solid #f1f3f7">
              <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                <b style="color:#0f1c33"><?= esc($r['listing_name'] ?: 'General') ?></b>
                <span><span class="chip <?= $open?'chip-pending':'chip-live' ?>"><?= $open?'Waiting':'Answered' ?></span>
                  <span style="color:#94a1b8;font-size:12px;margin-left:6px"><?= esc(mb_substr((string)($r['created_at']??''),0,10)) ?></span></span>
              </div>
              <p style="margin:7px 0 0;color:#33405a"><?= nl2br(esc($r['req_message'] ?? '')) ?></p>
              <?php /* threaded answers first (live), then the single-column one */ ?>
              <?php foreach(($r['_replies'] ?? []) as $rp): ?>
                <div style="margin-top:9px;padding:11px 14px;background:#eef3fc;border-radius:9px;font-size:13.5px;color:#33405a">
                  <b style="color:#1741a6">HiNoida replied</b>
                  <span style="color:#94a1b8;font-size:12px;margin-left:6px"><?= esc(mb_substr((string)($rp['created_at']??''),0,10)) ?></span>
                  <div style="margin-top:4px"><?= nl2br(esc($rp['reply_message'] ?? '')) ?></div>
                </div>
              <?php endforeach; ?>
              <?php if(empty($r['_replies']) && !empty($r['admin_reply'])): ?>
                <div style="margin-top:9px;padding:11px 14px;background:#eef3fc;border-radius:9px;font-size:13.5px;color:#33405a">
                  <b style="color:#1741a6">HiNoida replied</b>
                  <div style="margin-top:4px"><?= nl2br(esc($r['admin_reply'])) ?></div>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>
      <?php elseif($sec==='properties'): $props=$myProps??[]; ?>
        <?php /* A dealer can have twenty flats — a card grid would be unusable,
                 so this is a compact table with the price and status up front. */ ?>
        <div class="panel">
          <h3><i class="fa-solid fa-building" style="color:#1741a6"></i> Your properties (<?= count($props) ?>)
            <a class="abtn gh" href="<?= $b ?>list?kind=property"><i class="fa-solid fa-plus"></i> Add property</a></h3>
          <?php if(!$props): ?>
            <div class="empty"><i class="fa-solid fa-building"></i>
              No properties yet. Add a flat, PG, plot or office &mdash; they do not count against your listing plan.<br>
              <a class="btn" style="display:inline-block;margin-top:14px" href="<?= $b ?>list?kind=property">Add your first property</a></div>
          <?php else: ?>
            <table>
              <tr><th>Property</th><th>For</th><th>Price</th><th>Quality</th><th>Status</th><th></th></tr>
              <?php foreach($props as $pl): $pp = hi_property_of((int)$pl['id']); $st = $pl['status'] ?? 'pending'; ?>
                <tr>
                  <td><b><?= esc(preg_replace('/^Sample\s*·\s*/u', '', (string) $pl['name'])) ?></b>
                    <?php $sum = hi_property_summary($pp); if($sum!==''): ?>
                      <div style="color:#94a1b8;font-size:12.5px"><?= esc($sum) ?></div><?php endif; ?></td>
                  <td><?= esc(hi_property_deals()[$pp['deal'] ?? ''] ?? '—') ?></td>
                  <td><b><?= esc(hi_property_price($pp)) ?></b></td>
                  <?php [$_qs, $_qt] = hi_quality_score($pl); ?>
                  <td><span class="qs <?= $_qs >= 80 ? 'hi' : ($_qs >= 50 ? 'mid' : 'lo') ?>" title="<?= esc(implode(' · ', $_qt), 'attr') ?>"><?= $_qs ?>%</span></td>
                  <td><span class="chip <?= $st==='active'?'chip-live':'chip-pending' ?>"><?= esc(ucfirst($st)) ?></span></td>
                  <td style="text-align:right;white-space:nowrap">
                    <a class="abtn gh" href="<?= $b ?>list/specs/<?= (int)$pl['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                    <?php if($st==='active'): ?><a class="abtn" href="<?= hi_listing_url($pl) ?>" target="_blank" title="View live"><i class="fa-solid fa-arrow-up-right-from-square"></i></a><?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>

      <?php elseif($sec==='offers'):
        $offs=$offers??[]; $oe=$offerEdit??null; $cls=['active'=>'chip-live','pending'=>'chip-pending']; ?>
        <div class="panel" style="margin-bottom:14px"><h3><i class="fa-solid fa-tags"></i> <?= $oe ? 'Edit offer' : 'Create an offer' ?></h3>
          <div style="padding:16px 20px">
            <p style="margin:0 0 12px;color:#5b6a86;font-size:13.5px"><?= esc(hi_c('portal.p_offers_show_on_the_public_offers_page_and_dr', 'Offers show on the public Offers page and draw people to your listing. New and edited offers are checked before they go live.')) ?></p>
            <form method="post" action="<?= $b ?>dashboard/offer/save">
              <input type="hidden" name="id" value="<?= (int)($oe['id']??0) ?>">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div><label class="admlbl"><?= esc(hi_c('portal.lbl_business', 'Business')) ?></label>
                  <select class="adminp" name="business" required>
                    <?php foreach($mine as $l): ?>
                      <option value="<?= esc($l['name'],'attr') ?>" <?= ($oe['business']??'')===$l['name']?'selected':'' ?>><?= esc($l['name']) ?></option>
                    <?php endforeach; ?>
                  </select></div>
                <div><label class="admlbl"><?= esc(hi_c('portal.lbl_title', 'Title')) ?></label>
                  <input class="adminp" name="title" required value="<?= esc($oe['title']??'') ?>" placeholder="<?= esc(hi_c('portal.ph_flat_20_off_this_month', 'Flat 20% off this month'), 'attr') ?>"></div>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div><label class="admlbl"><?= esc(hi_c('portal.lbl_discount', 'Discount')) ?></label>
                  <input class="adminp" name="discount" value="<?= esc($oe['discount']??'') ?>" placeholder="<?= esc(hi_c('portal.ph_20_off', '20% OFF'), 'attr') ?>"></div>
                <div><label class="admlbl">Code <span style="font-weight:400;color:#9aa6bd">— optional</span></label>
                  <input class="adminp" name="code" value="<?= esc($oe['code']??'') ?>" placeholder="<?= esc(hi_c('portal.ph_save20', 'SAVE20'), 'attr') ?>"></div>
                <div><label class="admlbl"><?= esc(hi_c('portal.lbl_valid_till', 'Valid till')) ?></label>
                  <input class="adminp" type="date" name="valid_till" value="<?= esc($oe['valid_till']??'') ?>"></div>
              </div>
              <label class="admlbl"><?= esc(hi_c('portal.lbl_details', 'Details')) ?></label>
              <textarea class="adminp" name="description" rows="3" placeholder="<?= esc(hi_c('portal.ph_what_the_offer_covers_and_anything_that_does', 'What the offer covers, and anything that does not apply.'), 'attr') ?>"><?= esc($oe['description']??'') ?></textarea>
              <button class="btn" style="margin-top:12px"><?= $oe ? 'Save offer' : 'Create offer' ?></button>
              <?php if($oe): ?><a class="abtn" href="<?= $b ?>dashboard/offers" style="margin-left:8px">Cancel</a><?php endif; ?>
            </form>
          </div>
        </div>

        <div class="panel"><h3>Your offers (<?= count($offs) ?>)</h3>
          <?php if(!$offs): ?><div class="empty"><i class="fa-solid fa-tags"></i>No offers yet. Create one above.</div>
          <?php else: ?><table><tr><th>Offer</th><th>Business</th><th>Discount</th><th>Valid till</th><th>Status</th><th style="text-align:right">Action</th></tr>
            <?php foreach($offs as $o): $st=(string)($o['status']??'pending'); ?><tr>
              <td><b><?= esc($o['title']) ?></b><?php if(!empty($o['code'])): ?><div style="color:#94a1b8;font-size:12px">Code <?= esc($o['code']) ?></div><?php endif; ?></td>
              <td style="color:#94a1b8"><?= esc($o['business']) ?></td>
              <td><?= esc($o['discount'] ?: '—') ?></td>
              <td style="color:#94a1b8"><?= esc($o['valid_till'] ?: '—') ?></td>
              <td><span class="chip <?= $cls[$st] ?? 'chip-pending' ?>"><?= esc(ucfirst($st)) ?></span></td>
              <td style="text-align:right;white-space:nowrap">
                <a class="abtn" href="<?= $b ?>dashboard/offers?edit=<?= (int)$o['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                <form method="post" action="<?= $b ?>dashboard/offer/save" style="display:inline" onsubmit="return confirm('Delete this offer?')">
                  <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                  <button class="abtn" style="background:#fdecec;color:#b3262f;border-color:#f5c6c6"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr><?php endforeach; ?>
          </table><?php endif; ?>
        </div>
      <?php elseif($sec==='billing'):
        $pl=$plan??hi_free_plan(); $dl=$days??null; $pays=$payments??[]; ?>
        <div class="panel" style="margin-bottom:14px"><h3><i class="fa-solid fa-box-open"></i> Current plan
          <?php if(hi_addon('packages')): ?><a class="abtn" href="<?= $b ?>pricing"><?= ($pl['is_free']??true)?'See plans':'Change plan' ?></a><?php endif; ?></h3>
          <div style="padding:14px 20px;color:#5b6a86;font-size:13.5px">
            <b style="color:#0f1c33;font-size:15px"><?= esc($pl['name']??'Free') ?></b>
            &middot; <?= (int)($pl['number_of_listings']??1)>0 ? (int)$pl['number_of_listings'].' listing'.((int)$pl['number_of_listings']===1?'':'s') : 'Unlimited listings' ?>
            <?= !empty($pl['featured']) ? ' &middot; featured placement' : '' ?>
            <?php if($dl!==null): ?><br><?= $dl ?> day<?= $dl===1?'':'s' ?> left<?php endif; ?>
          </div>
        </div>

        <div class="panel"><h3>Payment history (<?= count($pays) ?>)</h3>
          <?php if(!$pays): ?>
            <div class="empty"><i class="fa-solid fa-receipt"></i>No payments yet — you are on the free plan.
              <?php if(hi_addon('packages')): ?><br><a class="btn" style="display:inline-block;margin-top:14px" href="<?= $b ?>pricing">See plans</a><?php endif; ?></div>
          <?php else: ?>
            <table><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Method</th><th>Runs to</th><th style="text-align:right">Invoice</th></tr>
              <?php foreach($pays as $p): $exp=(int)($p['expired_date']??0); ?><tr>
                <td style="color:#94a1b8;white-space:nowrap"><?= !empty($p['purchase_date']) ? esc(date('d M Y',(int)$p['purchase_date'])) : '—' ?></td>
                <td><b><?= esc($p['package_name'] ?: 'Plan') ?></b></td>
                <td><?= (int)($p['amount_paid']??0) ? '₹'.number_format((int)$p['amount_paid']) : 'Free' ?></td>
                <td style="color:#94a1b8"><?= esc(ucfirst((string)($p['payment_method'] ?: '—'))) ?></td>
                <td><?php if($exp): ?><span class="chip <?= $exp>time()?'chip-live':'chip-pending' ?>"><?= esc(date('d M Y',$exp)) ?></span><?php else: ?>—<?php endif; ?></td>
                <td style="text-align:right"><a class="abtn" href="<?= $b ?>dashboard/invoice/<?= (int)$p['id'] ?>" target="_blank" rel="noopener"><i class="fa-solid fa-receipt"></i> View</a></td>
              </tr><?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>

      <?php elseif($sec==='listings'):
        $uid=(int)session()->get('uid'); $plan=hi_plan($uid); $lim=(int)($plan['number_of_listings']??1);
        $used=hi_listing_count($uid); $left=hi_plan_expiry($uid); $room=hi_can_add_listing($uid,(int)session()->get('role')); ?>
        <div class="panel" style="margin-bottom:14px"><h3><i class="fa-solid fa-box-open"></i> <?= esc($plan['name']??'Free') ?> plan
          <a class="abtn" href="<?= $b ?>pricing"><?= $plan['is_free']??true ? 'See plans' : 'Change plan' ?></a></h3>
          <div style="padding:14px 20px;color:#5b6a86;font-size:13.5px">
            <?= $used ?> of <?= $lim>0?$lim:'unlimited' ?> listing<?= $lim===1?'':'s' ?> used<?php
            if($left!==null) echo ' · '.$left.' day'.($left===1?'':'s').' left'; ?>
            <?php if(!$room): ?><br><span style="color:#b3262f"><i class="fa-solid fa-circle-exclamation"></i>
              You have used your whole allowance — upgrade to add another listing.</span><?php endif; ?>
          </div>
        </div>
        <?php if($room): ?>
        <details class="panel" style="margin-bottom:14px;padding:0 20px">
          <summary style="cursor:pointer;padding:15px 0;font-weight:700;color:#0f1c33;list-style:none"><i class="fa-solid fa-file-import"></i> Import several listings from a CSV</summary>
          <div style="padding:0 0 18px">
            <p style="margin:0 0 12px;color:#5b6a86;font-size:13.5px">
              Columns are matched by header name: <code>name, phone, email, address, locality, category, website, description</code>.
              Only <b>name</b> is required. Everything arrives pending review, and the import stops at your plan limit.
            </p>
            <form method="post" action="<?= $b ?>dashboard/listings/import" enctype="multipart/form-data">
              <input class="adminp" type="file" name="csv" accept=".csv,text/csv" required style="max-width:340px">
              <button class="btn" style="margin-top:10px"><i class="fa-solid fa-file-import"></i> Import</button>
            </form>
          </div>
        </details>
        <?php endif; ?>
        <div class="panel"><h3>My listings (<?= count($mine) ?>) <?php if($room): ?><a class="abtn" href="<?= $b ?>list">+ Add</a><?php else: ?><a class="abtn" href="<?= $b ?>pricing">Upgrade to add</a><?php endif; ?></h3>
          <?php if(!$mine): ?><div class="empty"><i class="fa-solid fa-store"></i>No listings yet.<br><a class="btn" style="display:inline-block;margin-top:14px" href="<?= $b ?>list">Add your business</a></div>
          <?php else: foreach($mine as $l): ?>
            <div class="lrow"><img src="<?= esc(hi_cover($l)) ?>" alt="">
              <?php helper('property'); [$_qs, $_qt] = hi_quality_score($l); ?>
              <div class="g"><b><?= esc(preg_replace('/^Sample\s*·\s*/u', '', (string) $l['name'])) ?></b><small><i class="fa-solid fa-location-dot"></i> <?= esc($l['address']?:'Noida') ?></small>
                <?php if ($_qt): ?><ul class="qs-tips"><?php foreach (array_slice($_qt, 0, 3) as $_t): ?><li><?= esc($_t) ?></li><?php endforeach; ?></ul><?php endif; ?></div>
              <span class="qs <?= $_qs >= 80 ? 'hi' : ($_qs >= 50 ? 'mid' : 'lo') ?>" title="Listing quality: how complete this listing is">Quality <?= $_qs ?>%</span>
              <span class="chip <?= ($l['status']??'')==='active'?'chip-live':'chip-pending' ?>"><?= esc(ucfirst($l['status']??'pending')) ?></span>
              <?php $_np = count((array) json_decode($l['photos'] ?? '[]', true)); ?>
              <a class="abtn" href="<?= $b ?>dashboard/photos/<?= (int)$l['id'] ?>"><i class="fa-regular fa-image"></i> Photos<?= $_np ? ' (' . $_np . ')' : '' ?></a>
              <a class="abtn" href="<?= $b ?>dashboard/catalog/<?= (int)$l['id'] ?>"><i class="fa-solid fa-tags"></i> Services</a>
              <a class="abtn" href="<?= $b ?>list/basics/<?= (int)$l['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</a>
              <a class="abtn" href="<?= $b ?>l/<?= (int)$l['id'] ?>" target="_blank" rel="noopener">View <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px"></i></a>
              <?php if(hi_addon('vcard')): ?><a class="abtn" href="<?= $b ?>card/<?= (int)$l['id'] ?>" target="_blank" rel="noopener"><i class="fa-solid fa-qrcode"></i> Card &amp; QR</a><?php endif; ?>
              <form method="post" action="<?= $b ?>dashboard/listing/<?= (int)$l['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Remove “<?= esc($l['name'] ?? '', 'js') ?>”? It will no longer appear on HiNoida.')">
                <button class="abtn" style="background:#fdecec;color:#b3262f;border-color:#f5c6c6"><i class="fa-solid fa-trash"></i> Delete</button>
              </form>
            </div>
          <?php endforeach; endif; ?>
        </div>

      <?php elseif($sec==='leads'):
          $stc=static fn($s)=>$s==='closed'?'chip-live':($s==='contacted'?'chip-adm':'chip-pending');
          $ph=static fn($p)=>preg_replace('/[^0-9]/','',(string)$p);
      ?>
        <div class="stats" style="grid-template-columns:repeat(4,1fr)">
          <div class="stat"><i class="fa-solid fa-inbox"></i><b><?= count($enq)+count($bok) ?></b><span>Total leads</span></div>
          <div class="stat"><i class="fa-solid fa-bell"></i><b><?= (int)($leadPipe['new']??0) ?></b><span>New</span></div>
          <div class="stat"><i class="fa-solid fa-phone"></i><b><?= (int)($leadPipe['contacted']??0) ?></b><span>Contacted</span></div>
          <div class="stat"><i class="fa-solid fa-circle-check"></i><b><?= (int)($leadPipe['closed']??0) ?></b><span>Closed</span></div>
        </div>
        <div class="panel"><h3><i class="fa-solid fa-paper-plane" style="color:#1741a6"></i> Enquiries (<?= count($enq) ?>)</h3>
          <?php if(!$enq): ?><div class="empty"><i class="fa-solid fa-envelope"></i>No enquiries yet. They'll appear here when customers contact you.</div>
          <?php else: foreach($enq as $e): $st=$e['status']??'new'; ?>
            <div style="padding:15px 20px;border-bottom:1px solid #f1f3f7">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                <div><b style="font-size:15px"><?= esc(($e['name'] ?? '') ?: 'Customer') ?></b> <span class="chip <?= $stc($st) ?>" style="margin-left:6px"><?= esc(ucfirst($st)) ?></span>
                  <div style="color:#94a1b8;font-size:12.5px;margin-top:2px"><?= esc(mb_substr((string)($e['created_at']??''),0,16)) ?></div></div>
                <div style="display:flex;gap:7px;flex-wrap:wrap">
                  <?php if(!empty($e['phone'])): ?><a class="abtn" style="background:#1741a6;color:#fff" href="tel:<?= esc($e['phone']) ?>"><i class="fa-solid fa-phone"></i> Call</a>
                  <a class="abtn" style="background:#25d366;color:#fff" target="_blank" href="https://wa.me/<?= esc($ph($e['phone'])) ?>"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
                  <?php if($st!=='contacted'): ?><a class="abtn" href="<?= $b ?>dashboard/lead/enquiry/<?= (int)$e['id'] ?>/contacted">Mark contacted</a><?php endif; ?>
                  <?php if($st!=='closed'): ?><a class="abtn" href="<?= $b ?>dashboard/lead/enquiry/<?= (int)$e['id'] ?>/closed">Close</a><?php endif; ?>
                </div>
              </div>
              <?php if(!empty($e['message'])): ?><p style="margin:8px 0 0;color:#33405a"><?= esc($e['message']) ?></p><?php endif; ?>
              <div style="font-size:12.5px;color:#94a1b8;margin-top:4px"><?= esc($e['email']??'') ?><?= !empty($e['listing_name'])?' · '.esc($e['listing_name']):'' ?></div>
            </div>
          <?php endforeach; endif; ?>
        </div>
        <div class="panel"><h3><i class="fa-solid fa-calendar-check" style="color:#1741a6"></i> Booking requests (<?= count($bok) ?>)</h3>
          <?php if(!$bok): ?><div class="empty"><i class="fa-solid fa-calendar-check"></i>No bookings yet.</div>
          <?php else: foreach($bok as $bk): $st=$bk['status']??'new'; ?>
            <div style="padding:15px 20px;border-bottom:1px solid #f1f3f7">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                <div><b style="font-size:15px"><?= esc(($bk['name'] ?? '') ?: 'Customer') ?></b> <span class="chip chip-adm" style="margin-left:6px"><?= esc(($bk['booking_type'] ?? $bk['listing_type'] ?? '') ?: 'booking') ?></span> <span class="chip <?= $stc($st) ?>"><?= esc(ucfirst($st)) ?></span>
                  <div style="color:#94a1b8;font-size:12.5px;margin-top:2px"><i class="fa-regular fa-calendar"></i> <?= esc(trim(($bk['booking_date']??'').' '.($bk['booking_time']??''))?:'—') ?></div></div>
                <div style="display:flex;gap:7px;flex-wrap:wrap">
                  <?php if(!empty($bk['phone'])): ?><a class="abtn" style="background:#1741a6;color:#fff" href="tel:<?= esc($bk['phone']) ?>"><i class="fa-solid fa-phone"></i> Call</a>
                  <a class="abtn" style="background:#25d366;color:#fff" target="_blank" href="https://wa.me/<?= esc($ph($bk['phone'])) ?>"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
                  <?php if($st!=='approved'): ?><a class="abtn" style="background:#e6f6ec;color:#12653a;border-color:#c4e8d1" href="<?= $b ?>dashboard/booking/<?= (int)$bk['id'] ?>/approve"><i class="fa-solid fa-check"></i> Confirm</a><?php endif; ?>
                  <?php if($st!=='rejected'): ?><a class="abtn" style="background:#fdecec;color:#b3262f;border-color:#f5c6c6" href="<?= $b ?>dashboard/booking/<?= (int)$bk['id'] ?>/decline"><i class="fa-solid fa-xmark"></i> Decline</a><?php endif; ?>
                  <?php if($st!=='contacted'): ?><a class="abtn" href="<?= $b ?>dashboard/lead/booking/<?= (int)$bk['id'] ?>/contacted">Mark contacted</a><?php endif; ?>
                  <?php if($st!=='closed'): ?><a class="abtn" href="<?= $b ?>dashboard/lead/booking/<?= (int)$bk['id'] ?>/closed">Close</a><?php endif; ?>
                </div>
              </div>
              <?php if(!empty($bk['message'])): ?><p style="margin:8px 0 0;color:#33405a"><?= esc($bk['message']) ?></p><?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>

      <?php elseif($sec==='reviews'): ?>
        <div class="panel"><h3>Reviews on your listings (<?= count($rev) ?>) &nbsp; <span class="stars"><?= $stats['rating']?:'' ?> <?php for($i=1;$i<=5;$i++) echo $i<=round($stats['rating'])?'★':'☆'; ?></span></h3>
          <?php if(!$rev): ?><div class="empty"><i class="fa-solid fa-star"></i>No reviews yet.</div>
          <?php else: foreach($rev as $rv): $rt=(int)($rv['review_rating']??0); ?>
            <div class="rev-i"><div style="display:flex;justify-content:space-between"><b><?= esc($rv['listing_name']??'') ?></b><span class="stars"><?php for($i=1;$i<=5;$i++) echo $i<=$rt?'★':'☆'; ?></span></div>
              <p style="margin:6px 0 0;color:#556"><?= esc($rv['review_comment']??($rv['comment']??'')) ?></p>
              <?php if(!empty($rv['owner_reply'])): ?><p style="margin:8px 0 0;padding:8px 12px;background:#eef3fc;border-radius:8px;font-size:13.5px"><b style="color:#1741a6">Your reply:</b> <?= esc($rv['owner_reply']) ?></p>
              <?php else: ?><form method="post" action="<?= $b ?>dashboard/review/<?= (int)$rv['id'] ?>/reply" style="margin-top:8px;display:flex;gap:8px"><input name="reply" placeholder="<?= esc(hi_c('portal.ph_reply_publicly', 'Reply publicly…'), 'attr') ?>" style="flex:1;border:1px solid #dfe4ec;border-radius:8px;padding:8px 10px;font-size:13px;max-width:none"><button class="abtn" style="background:#1741a6;color:#fff"><?= esc(hi_c('portal.btn_reply', 'Reply')) ?></button></form><?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>

      <?php elseif($sec==='questions'): ?>
        <div class="panel"><h3>Questions from customers (<?= count($qa) ?>)
          <?php if(!empty($qaOpen)): ?><span class="chip chip-pending"><?= (int)$qaOpen ?> awaiting answer</span><?php endif; ?></h3>
          <?php if(!$qa): ?>
            <div class="empty"><i class="fa-solid fa-circle-question"></i>No questions yet.<br>
              <span style="font-size:13px">When customers ask something on your listing, it appears here.</span></div>
          <?php else: foreach($qa as $q): ?>
            <div class="rev-i">
              <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <b><a href="<?= $b ?>l/<?= (int)$q['listing_id'] ?>#qa" style="color:#1741a6"><?= esc($q['listing_name']??'') ?></a></b>
                <span class="chip <?= !empty($q['answer'])?'chip-live':'chip-pending' ?>"><?= !empty($q['answer'])?'Answered':'Awaiting answer' ?></span>
              </div>
              <p style="margin:8px 0 0;color:#1c2432;font-weight:600">
                <i class="fa-solid fa-circle-question" style="color:#1741a6"></i> <?= esc($q['question']??'') ?>
              </p>
              <small style="color:#9aa6bd"><?= esc($q['name']??'Customer') ?> &middot; <?= esc(mb_substr((string)($q['created_at']??''),0,10)) ?></small>
              <?php if(!empty($q['answer'])): ?>
                <p style="margin:8px 0 0;padding:9px 13px;background:#eef3fc;border-radius:8px;font-size:13.5px">
                  <b style="color:#1741a6">Your answer:</b> <?= esc($q['answer']) ?></p>
              <?php else: ?>
                <form method="post" action="<?= $b ?>dashboard/question/<?= (int)$q['id'] ?>/answer" style="margin-top:9px;display:flex;gap:8px">
                  <input name="answer" required minlength="5" placeholder="<?= esc(hi_c('portal.ph_answer_this_customer', 'Answer this customer…'), 'attr') ?>" style="flex:1;border:1px solid #dfe4ec;border-radius:8px;padding:9px 11px;font-size:13px;max-width:none">
                  <button class="abtn" style="background:#1741a6;color:#fff;border:0;cursor:pointer"><?= esc(hi_c('portal.btn_answer', 'Answer')) ?></button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>

      <?php elseif($sec==='bookings'): ?>
        <div class="panel"><h3>My bookings (<?= count($myBookings) ?>)</h3>
          <?php if(!$myBookings): ?><div class="empty"><i class="fa-solid fa-calendar-check"></i>No bookings yet.<br><a class="btn" style="display:inline-block;margin-top:14px" href="<?= $b ?>">Browse places</a></div>
          <?php else: ?><table><tr><th>Place</th><th>Type</th><th>When</th><th>Status</th></tr>
            <?php foreach($myBookings as $bk): ?><tr><td><b><a href="<?= $b ?>l/<?= (int)$bk['listing_id'] ?>" style="color:#1741a6"><?= esc($bk['listing_name']??'') ?></a></b></td><td><?= esc($bk['booking_type']??'') ?></td><td><?= esc(trim(($bk['booking_date']??'').' '.($bk['booking_time']??''))) ?></td><td><span class="chip chip-pending"><?= esc($bk['status']??'pending') ?></span></td></tr><?php endforeach; ?>
          </table><?php endif; ?>
        </div>

      <?php elseif($sec==='enquiries'): ?>
        <div class="panel"><h3>My enquiries (<?= count($myEnq) ?>)</h3>
          <?php if(!$myEnq): ?><div class="empty"><i class="fa-solid fa-paper-plane"></i>You haven't sent any enquiries yet.</div>
          <?php else: ?><table><tr><th>Place</th><th>Message</th><th>Sent</th></tr>
            <?php foreach($myEnq as $e): ?><tr><td><b><a href="<?= $b ?>l/<?= (int)$e['listing_id'] ?>" style="color:#1741a6"><?= esc($e['listing_name']??'') ?></a></b></td><td><?= esc($e['message']??'') ?></td><td style="color:#94a1b8"><?= esc(mb_substr((string)($e['created_at']??''),0,10)) ?></td></tr><?php endforeach; ?>
          </table><?php endif; ?>
        </div>

      <?php elseif($sec==='myreviews'): ?>
        <div class="panel"><h3>My reviews (<?= count($myReviews) ?>)</h3>
          <?php if(!$myReviews): ?><div class="empty"><i class="fa-solid fa-star"></i>You haven't reviewed anything yet.</div>
          <?php else: foreach($myReviews as $rv): $rt=(int)($rv['review_rating']??0); ?>
            <div class="rev-i"><div style="display:flex;justify-content:space-between"><b><a href="<?= $b ?>l/<?= (int)$rv['listing_id'] ?>" style="color:#1741a6"><?= esc($rv['listing_name']??'') ?></a></b><span class="stars"><?php for($i=1;$i<=5;$i++) echo $i<=$rt?'★':'☆'; ?></span></div><p style="margin:6px 0 0;color:#556"><?= esc($rv['review_comment']??($rv['comment']??'')) ?></p></div>
          <?php endforeach; endif; ?>
        </div>

      <?php elseif($sec==='saved'): $_ss = hi_saved_searches((int) session()->get('uid')); ?>
        <div class="panel" style="margin-bottom:14px"><h3>Saved searches (<?= count($_ss) ?>)</h3>
          <?php if(!$_ss): ?><div class="empty"><i class="fa-regular fa-bookmark"></i>No saved searches. On any property search, tap “Save this search”.</div>
          <?php else: foreach($_ss as $_s): ?>
            <div class="lrow"><div class="g"><b><?= esc($_s['label']) ?></b><small>Saved <?= esc(date('j M Y', strtotime((string) $_s['created_at']))) ?></small></div>
              <a class="abtn" href="<?= base_url(ltrim((string) $_s['url'], '/')) ?>">Open</a>
              <form method="post" action="<?= $b ?>search/delete/<?= (int) $_s['id'] ?>" style="display:inline"><button class="abtn" style="background:#fdecec;color:#b3262f;border-color:#f5c6c6"><i class="fa-solid fa-xmark"></i> Remove</button></form></div>
          <?php endforeach; endif; ?>
        </div>
        <div class="panel"><h3>Saved places (<?= count($saved) ?>)</h3>
          <?php if(!$saved): ?><div class="empty"><i class="fa-solid fa-heart"></i>No saved places. Tap the heart on any listing to save it here.</div>
          <?php else: foreach($saved as $l): ?>
            <div class="lrow"><img src="<?= esc(hi_cover($l)) ?>" alt=""><div class="g"><b><?= esc($l['name']) ?></b><small><i class="fa-solid fa-location-dot"></i> <?= esc($l['locality_name']?:'Noida') ?>, Noida</small></div><a class="abtn" href="<?= $b ?>l/<?= (int)$l['id'] ?>">View</a></div>
          <?php endforeach; endif; ?>
        </div>

      <?php elseif($sec==='profile'): ?>
        <?php $soc = json_decode($me['social'] ?? '[]', true); $soc = is_array($soc) ? $soc : []; ?>
        <div class="panel" style="max-width:560px"><h3><?= esc(hi_c('portal.account', 'Account')) ?></h3>
          <table>
            <tr><th style="width:45%">Email (your login)</th><td><b><?= esc($me['email']??'') ?></b></td></tr>
            <tr><th>Account type</th><td><?= ((int)($me['role_id']??3))===2 ? 'Business owner' : (((int)($me['role_id']??3))===1 ? 'Administrator' : 'Customer') ?></td></tr>
            <tr><th>Email verified</th><td><span class="chip <?= !empty($me['is_verified'])?'chip-live':'chip-pending' ?>"><?= !empty($me['is_verified'])?'Verified':'Not verified' ?></span></td></tr>
            <tr><th>Listings</th><td><?= count($mine) ?> (<?= $stats['active'] ?> active)</td></tr>
            <?php if(!empty($me['updated_date'])): ?><tr><th>Last updated</th><td><?= esc(mb_substr((string)$me['updated_date'],0,10)) ?></td></tr><?php endif; ?>
          </table>
        </div>

        <form method="post" action="<?= $b ?>dashboard/profile/save">
          <div class="panel" style="max-width:560px"><h3><?= esc(hi_c('portal.your_details', 'Your details')) ?></h3>
            <div style="padding:4px 20px 20px">
              <label><?= esc(hi_c('portal.lbl_full_name', 'Full name *')) ?></label><input name="name" value="<?= esc($me['name']??'') ?>" required>
              <label><?= esc(hi_c('portal.lbl_phone_number', 'Phone number')) ?></label><input name="phone" type="tel" value="<?= esc($me['phone']??'') ?>" placeholder="+91 9876543210" pattern="^\+?[0-9][0-9 \-]{6,19}$">
              <label><?= esc(hi_c('portal.lbl_address', 'Address')) ?></label><input name="address" value="<?= esc($me['address']??'') ?>" placeholder="<?= esc(hi_c('portal.ph_sector_noida', 'Sector, Noida'), 'attr') ?>">
            </div>
          </div>

          <div class="panel" style="max-width:560px"><h3>Links <span style="font-size:12px;font-weight:500;color:#9aa6bd">optional</span></h3>
            <div style="padding:4px 20px 20px">
              <?php foreach ([['website','globe','Website'],['facebook','facebook-f','Facebook'],['instagram','instagram','Instagram'],['linkedin','linkedin-in','LinkedIn'],['twitter','x-twitter','X (Twitter)'],['youtube','youtube','YouTube']] as $sx): ?>
                <label><i class="fa-<?= $sx[0]==='website'?'solid':'brands' ?> fa-<?= $sx[1] ?>" style="color:#1741a6;width:16px"></i> <?= $sx[2] ?></label>
                <input name="social_<?= $sx[0] ?>" type="url" value="<?= esc($soc[$sx[0]] ?? '') ?>" placeholder="<?= esc(hi_c('portal.ph_https', 'https://…'), 'attr') ?>">
              <?php endforeach; ?>
            </div>
          </div>

          <div class="panel" style="max-width:560px"><h3><?= esc(hi_c('portal.change_password', 'Change password')) ?></h3>
            <div style="padding:4px 20px 20px">
              <p style="margin:8px 0 0;font-size:13px;color:#6b7a94"><?= esc(hi_c('portal.p_leave_these_blank_to_keep_your_current_passw', 'Leave these blank to keep your current password.')) ?></p>
              <label><?= esc(hi_c('portal.lbl_current_password', 'Current password')) ?></label><input name="current_password" type="password" autocomplete="current-password">
              <label>New password <span style="font-weight:500;text-transform:none;letter-spacing:0">(min 6 characters)</span></label>
              <input name="password" type="password" minlength="6" autocomplete="new-password">
              <label><?= esc(hi_c('portal.lbl_confirm_new_password', 'Confirm new password')) ?></label><input name="password_confirm" type="password" minlength="6" autocomplete="new-password">
            </div>
          </div>

          <div style="max-width:560px;margin-bottom:26px"><button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save changes</button></div>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>
<script>
(function(){var t=document.getElementById("sideToggle"),s=document.getElementById("side"),sh=document.getElementById("shell");
 if(!t||!s)return;
 function close(){s.classList.remove("open");sh.classList.remove("dim");}
 t.addEventListener("click",function(e){e.stopPropagation();s.classList.toggle("open");sh.classList.toggle("dim",s.classList.contains("open"));});
 document.addEventListener("click",function(e){if(s.classList.contains("open")&&!s.contains(e.target)&&e.target!==t)close();});
 document.addEventListener("keydown",function(e){if(e.key==="Escape")close();});
})();
</script>
</body>
</html>
