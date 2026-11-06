<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $tabs = [['overview','gauge-high','Overview'],['bookings','calendar-check','My Bookings'],['enquiries','paper-plane','My Enquiries'],['myreviews','star','My Reviews'],['myquestions','circle-question','My Questions'],['saved','heart','Saved'],['profile','user','Profile']]; ?>
<?php if(!empty($err)): ?><div class="wrap" style="padding-top:14px"><div style="background:#fdecec;color:#b3262f;border:1px solid #f5c6c6;border-radius:12px;padding:12px 16px"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($err) ?></div></div><?php endif; ?>
<section class="acc-head"><div class="wrap">
  <div class="det-crumb" style="margin-bottom:18px"><a href="<?= base_url('/') ?>">Home</a> &rsaquo; <span>My Account</span></div>
  <div class="acc-id">
    <span class="acc-avatar" style="background:<?= esc(hi_avatar_bg($name ?: ($me['email'] ?? '')), 'attr') ?>"><?= esc(hi_initial($name, $me['email'] ?? null)) ?></span>
    <div>
      <h1 class="acc-hello">Hi, <?= esc($name) ?></h1>
      <p class="acc-sub"><?= esc($me['email'] ?? '') ?><?= $verified ? ' &middot; <span style="color:#0b8a3d;font-weight:700">Verified</span>' : '' ?></p>
    </div>
    <a class="acc-explore" href="<?= base_url('/') ?>"><i class="fa-solid fa-compass"></i> Explore Noida</a>
  </div>
</div></section>

<section class="sec" style="padding-top:26px"><div class="wrap">
  <?php if (! empty($notice)): ?><div style="background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:12px;padding:13px 18px;margin-bottom:18px"><i class="fa-solid fa-circle-check"></i> <?= esc($notice) ?></div><?php endif; ?>
  <?php if (! empty($flash)): ?><div style="background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:12px;padding:13px 18px;margin-bottom:18px"><i class="fa-solid fa-circle-check"></i> <?= esc($flash) ?></div><?php endif; ?>
  <?php if (! $verified): ?><div style="background:#fff8e6;color:#8a6d1a;border:1px solid #f0dca3;border-radius:12px;padding:14px 18px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><span><b><i class="fa-solid fa-envelope-circle-check"></i> Confirm your email</b> — verify <?= esc($me['email']??'') ?> to unlock everything.</span><a href="<?= base_url('verify-email') ?>" style="background:#8a6d1a;color:#fff;padding:8px 16px;border-radius:8px;font-weight:700;white-space:nowrap">Verify now</a></div><?php endif; ?>

  <div class="tabbar">
    <?php foreach ($tabs as $t): ?>
      <a href="<?= base_url('dashboard/' . $t[0]) ?>" class="<?= $sec===$t[0]?'on':'' ?>"><i class="fa-solid fa-<?= $t[1] ?>"></i> <?= $t[2] ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($sec === 'overview'): ?>
    <div class="stats">
      <div class="stat"><i class="fa-solid fa-calendar-check"></i><b><?= count($myBookings) ?></b><span>Bookings</span></div>
      <div class="stat"><i class="fa-solid fa-paper-plane"></i><b><?= count($myEnq) ?></b><span>Enquiries</span></div>
      <div class="stat"><i class="fa-solid fa-star"></i><b><?= count($myReviews) ?></b><span>Reviews</span></div>
      <div class="stat"><i class="fa-solid fa-heart"></i><b><?= count($saved) ?></b><span>Saved</span></div>
    </div>
    <?php if (! $myBookings && ! $saved): ?>
      <div class="acc-empty"><i class="fa-solid fa-compass"></i><p><?= esc(hi_c('account.p_you_haven_t_explored_yet_browse_noida_to_boo', 'You haven\'t explored yet — browse Noida to book, enquire, and save your favourite places.')) ?></p><a class="nav btn" style="display:inline-block;padding:11px 22px;border-radius:9px" href="<?= base_url('/') ?>">Explore HiNoida</a></div>
    <?php else: ?>
      <?php if ($myBookings): ?>
      <h2 style="margin:26px 0 12px"><?= esc(hi_c('account.recent_bookings', 'Recent bookings')) ?></h2>
      <div class="acc-panel"><table><tr><th>Place</th><th>Type</th><th>When</th><th>Status</th></tr>
        <?php foreach (array_slice($myBookings,0,5) as $bk): ?><tr><td><b><a href="<?= hi_listing_url((int)$bk['listing_id']) ?>"><?= esc($bk['listing_name']??'') ?></a></b></td><td><?= esc($bk['booking_type']??'') ?></td><td><?= esc(trim(($bk['booking_date']??'').' '.($bk['booking_time']??''))) ?></td><td><span class="chip chip-pending"><?= esc($bk['status']??'pending') ?></span></td></tr><?php endforeach; ?>
      </table></div>
      <?php endif; ?>
      <?php if ($saved): ?>
      <h2 style="margin:26px 0 12px"><?= esc(hi_c('account.saved_places', 'Saved places')) ?></h2>
      <div class="lgrid">
        <?php foreach (array_slice($saved,0,4) as $l): ?>
          <a class="lcard" href="<?= hi_listing_url($l) ?>"><span class="ph"><img src="<?= esc(hi_cover($l)) ?>" alt="<?= esc($l['name']) ?>"></span><span class="bd"><b><?= esc($l['name']) ?></b><span class="loc"><i class="fa-solid fa-location-dot"></i> <?= esc($l['locality_name']?:'Noida') ?>, Noida</span></span></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>

  <?php elseif ($sec === 'bookings'): ?>
    <div class="acc-panel"><table><tr><th>Place</th><th>Type</th><th>When</th><th>Status</th></tr>
      <?php if (! $myBookings): ?><tr><td colspan="4"><div class="acc-empty"><i class="fa-solid fa-calendar-check"></i><p><?= esc(hi_c('account.p_no_bookings_yet', 'No bookings yet.')) ?></p><a class="nav btn" style="display:inline-block;padding:10px 18px;border-radius:9px" href="<?= base_url('/') ?>">Browse places</a></div></td></tr>
      <?php else: foreach ($myBookings as $bk): ?><tr><td><b><a href="<?= hi_listing_url((int)$bk['listing_id']) ?>"><?= esc($bk['listing_name']??'') ?></a></b></td><td><?= esc($bk['booking_type']??'') ?></td><td><?= esc(trim(($bk['booking_date']??'').' '.($bk['booking_time']??''))) ?></td><td><span class="chip chip-pending"><?= esc($bk['status']??'pending') ?></span></td></tr><?php endforeach; endif; ?>
    </table></div>

  <?php elseif ($sec === 'enquiries'): ?>
    <div class="acc-panel"><table><tr><th>Place</th><th>Message</th><th>Sent</th></tr>
      <?php if (! $myEnq): ?><tr><td colspan="3"><div class="acc-empty"><i class="fa-solid fa-paper-plane"></i><p><?= esc(hi_c('account.p_no_enquiries_sent_yet', 'No enquiries sent yet.')) ?></p></div></td></tr>
      <?php else: foreach ($myEnq as $e): ?><tr><td><b><a href="<?= hi_listing_url((int)$e['listing_id']) ?>"><?= esc($e['listing_name']??'') ?></a></b></td><td><?= esc($e['message']??'') ?></td><td style="color:var(--muted)"><?= esc(mb_substr((string)($e['created_at']??''),0,10)) ?></td></tr><?php endforeach; endif; ?>
    </table></div>

  <?php elseif ($sec === 'myreviews'): ?>
    <?php if (! $myReviews): ?><div class="acc-empty"><i class="fa-solid fa-star"></i><p><?= esc(hi_c('account.p_you_haven_t_reviewed_anything_yet', 'You haven\'t reviewed anything yet.')) ?></p></div>
    <?php else: foreach ($myReviews as $rv): $rt=(int)($rv['review_rating']??0); ?>
      <div class="acc-panel" style="padding:16px 20px;margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
          <b><a href="<?= hi_listing_url((int)$rv['listing_id']) ?>"><?= esc($rv['listing_name']??'') ?></a></b>
          <span class="stars"><?php for($i=1;$i<=5;$i++) echo $i<=$rt?'★':'☆'; ?></span>
        </div>
        <p style="margin:6px 0 0;color:var(--body)" data-rv-text><?= esc($rv['review_comment']??($rv['comment']??'')) ?></p>

        <div style="display:flex;gap:8px;margin-top:10px">
          <button type="button" class="abtn" data-rv-edit="<?= (int)$rv['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</button>
          <form method="post" action="<?= base_url('dashboard/review/'.(int)$rv['id'].'/delete') ?>"
                onsubmit="return confirm('Delete this review? This cannot be undone.')" style="display:inline">
            <button class="abtn" style="background:#fdecec;color:#b3262f;border-color:#f5c6c6"><i class="fa-solid fa-trash"></i> Delete</button>
          </form>
        </div>

        <form method="post" action="<?= base_url('dashboard/review/'.(int)$rv['id'].'/edit') ?>"
              id="rvform<?= (int)$rv['id'] ?>" hidden style="margin-top:12px;padding-top:12px;border-top:1px solid var(--line)">
          <label class="acc-lbl"><?= esc(hi_c('account.lbl_rating', 'Rating')) ?></label>
          <select class="acc-inp" name="rating">
            <?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>" <?= $rt===$i?'selected':'' ?>><?= $i ?> ★</option><?php endfor; ?>
          </select>
          <label class="acc-lbl"><?= esc(hi_c('account.lbl_your_review', 'Your review')) ?></label>
          <textarea class="acc-inp" name="comment" rows="3" required minlength="10"><?= esc($rv['review_comment']??($rv['comment']??'')) ?></textarea>
          <button type="submit" style="margin-top:10px;background:var(--blue);color:#fff;border:0;border-radius:10px;padding:11px 20px;font-weight:700;cursor:pointer"><?= esc(hi_c('account.btn_save_changes', 'Save changes')) ?></button>
        </form>
      </div>
    <?php endforeach; endif; ?>

  <?php elseif ($sec === 'myquestions'): ?>
    <?php if (empty($myQ)): ?><div class="acc-empty"><i class="fa-solid fa-circle-question"></i><p><?= esc(hi_c('account.p_you_haven_t_asked_any_questions_yet_open_any', 'You haven\'t asked any questions yet. Open any listing and use the Q&A section.')) ?></p></div>
    <?php else: foreach ($myQ as $q): ?>
      <div class="acc-panel" style="padding:16px 20px;margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
          <b><a href="<?= hi_listing_url((int)$q['listing_id']) ?>#qa"><?= esc($q['listing_name'] ?? '') ?></a></b>
          <span class="chip <?= !empty($q['answer']) ? 'chip-live' : 'chip-pending' ?>"><?= !empty($q['answer']) ? 'Answered' : 'Awaiting reply' ?></span>
        </div>
        <p style="margin:8px 0 0;color:var(--ink);font-weight:600"><i class="fa-solid fa-circle-question" style="color:var(--blue)"></i> <?= esc($q['question'] ?? '') ?></p>
        <?php if (!empty($q['answer'])): ?><p style="margin:8px 0 0;color:var(--body);padding-left:18px;border-left:3px solid #cfe0ff"><?= esc($q['answer']) ?></p><?php endif; ?>
        <small style="color:var(--muted)"><?= esc(mb_substr((string)($q['created_at'] ?? ''),0,10)) ?></small>
      </div>
    <?php endforeach; endif; ?>

  <?php elseif ($sec === 'saved'): ?>
    <?php $_ss = hi_saved_searches((int) session()->get('uid')); ?>
    <h2 style="margin:0 0 12px">Saved searches</h2>
    <?php if (! $_ss): ?>
      <div class="ds-empty" style="margin-bottom:28px"><b>No saved searches</b>Search for homes, pick your filters, then tap “Save this search” to come back to it in one click.
        <br><a class="ds-btn" href="<?= base_url('property') ?>"><i class="fa-solid fa-magnifying-glass"></i> Search homes</a></div>
    <?php else: ?>
      <table class="ds-table" style="margin-bottom:28px">
        <tr><th>Search</th><th>Saved on</th><th></th></tr>
        <?php foreach ($_ss as $_s): ?>
          <tr><td><a href="<?= base_url(ltrim((string) $_s['url'], '/')) ?>"><b><?= esc($_s['label']) ?></b></a></td>
            <td><?= esc(date('j M Y', strtotime((string) $_s['created_at']))) ?></td>
            <td style="text-align:right;white-space:nowrap"><a class="ds-btn ghost" href="<?= base_url(ltrim((string) $_s['url'], '/')) ?>">Open</a>
              <form method="post" action="<?= base_url('search/delete/' . (int) $_s['id']) ?>" style="display:inline"><button class="ds-btn ghost" type="submit" title="Remove"><i class="fa-solid fa-xmark"></i></button></form></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
    <h2 style="margin:0 0 12px">Saved places</h2>
    <?php if (! $saved): ?><div class="acc-empty"><i class="fa-solid fa-heart"></i><p><?= esc(hi_c('account.p_no_saved_places_yet_tap_the_heart_on_any_lis', 'No saved places yet. Tap the heart on any listing to save it here.')) ?></p></div>
    <?php else: ?><div class="lgrid">
      <?php foreach ($saved as $l): ?><a class="lcard" href="<?= hi_listing_url($l) ?>"><span class="ph"><img src="<?= esc(hi_cover($l)) ?>" alt="<?= esc($l['name']) ?>"></span><span class="bd"><b><?= esc($l['name']) ?></b><span class="loc"><i class="fa-solid fa-location-dot"></i> <?= esc($l['locality_name']?:'Noida') ?>, Noida</span></span></a><?php endforeach; ?>
    </div><?php endif; ?>

  <?php elseif ($sec === 'profile'): ?>
    <div class="acc-panel" style="max-width:460px;padding:22px">
      <form method="post" action="<?= base_url('dashboard/profile/save') ?>">
        <label class="acc-lbl"><?= esc(hi_c('account.lbl_name', 'Name')) ?></label><input class="acc-inp" name="name" value="<?= esc($me['name']??'') ?>" required>
        <label class="acc-lbl"><?= esc(hi_c('account.lbl_email', 'Email')) ?></label><input class="acc-inp" value="<?= esc($me['email']??'') ?>" disabled style="background:#f4f6fa;color:var(--muted)">
        <label class="acc-lbl"><?= esc(hi_c('account.lbl_phone', 'Phone')) ?></label><input class="acc-inp" name="phone" type="tel" value="<?= esc($me['phone']??'') ?>" placeholder="+91 9876543210" pattern="^\+?[0-9][0-9 \-]{6,19}$">
        <label class="acc-lbl"><?= esc(hi_c('account.lbl_address', 'Address')) ?></label><input class="acc-inp" name="address" value="<?= esc($me['address']??'') ?>" placeholder="<?= esc(hi_c('account.ph_sector_noida', 'Sector, Noida'), 'attr') ?>">

        <div style="margin-top:22px;padding-top:16px;border-top:1px solid var(--line)">
          <b style="font-size:14px;color:var(--ink)">Change password</b>
          <p style="margin:4px 0 0;font-size:12.5px;color:var(--muted)"><?= esc(hi_c('account.p_leave_blank_to_keep_your_current_password', 'Leave blank to keep your current password.')) ?></p>
          <label class="acc-lbl"><?= esc(hi_c('account.lbl_current_password', 'Current password')) ?></label><input class="acc-inp" name="current_password" type="password" autocomplete="current-password">
          <label class="acc-lbl"><?= esc(hi_c('account.lbl_new_password_min_6_characters', 'New password (min 6 characters)')) ?></label><input class="acc-inp" name="password" type="password" minlength="6" autocomplete="new-password">
          <label class="acc-lbl"><?= esc(hi_c('account.lbl_confirm_new_password', 'Confirm new password')) ?></label><input class="acc-inp" name="password_confirm" type="password" minlength="6" autocomplete="new-password">
        </div>
        <button type="submit" style="margin-top:16px;width:100%;background:var(--blue);color:#fff;border:0;border-radius:10px;padding:13px;font-weight:700;cursor:pointer"><?= esc(hi_c('account.btn_save_profile', 'Save profile')) ?></button>
      </form>
    </div>
  <?php endif; ?>
</div></section>
<script>
document.querySelectorAll('[data-rv-edit]').forEach(function(b){
  b.addEventListener('click',function(){
    var f=document.getElementById('rvform'+b.dataset.rvEdit); if(!f)return;
    f.hidden=!f.hidden;
    b.innerHTML = f.hidden ? '<i class=\"fa-solid fa-pen\"></i> Edit' : '<i class=\"fa-solid fa-xmark\"></i> Cancel';
  });
});
</script>
<?= $this->endSection() ?>
