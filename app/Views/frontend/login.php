<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$reg = ($mode === 'register');
$biz = ! empty($biz);
$inp = 'width:100%;border:1px solid var(--line);border-radius:10px;padding:12px 14px;font-size:14.5px;font-family:inherit';
$lab = 'font-size:13px;font-weight:600;color:#444;display:block;margin:12px 0 6px';
?>
<?php
  // what the left panel says, per mode
  if ($reg && $biz) {
      $_t = 'List your business on HiNoida'; $_s = 'Sign up as a business owner to add and manage your listing.';
      $_pts = [['store', 'Free business listing', 'Add your address, timings, photos and services.'],
               ['phone', 'Get calls & enquiries', 'Customers contact you directly — no middleman.'],
               ['star', 'Collect reviews', 'Build trust with real customer ratings.'],
               ['chart-line', 'See your views', 'Know how many people found you each month.']];
  } elseif ($reg) {
      $_t = 'Create your account'; $_s = 'Save places, book, review and enquire across Noida.';
      $_pts = [['heart', 'Save your favourite places', 'Keep a list of shops, PGs and services you like.'],
               ['star', 'Write reviews', 'Help others pick the best in your area.'],
               ['paper-plane', 'Send enquiries', 'Ask businesses directly and get replies fast.'],
               ['bell', 'Get updates', 'New offers and events near you.']];
  } else {
      $_t = 'Welcome back'; $_s = 'Log in to your HiNoida account.';
      $_pts = [['heart', 'Your saved places', 'Everything you shortlisted, in one place.'],
               ['star', 'Your reviews', 'See and edit what you have written.'],
               ['paper-plane', 'Your enquiries', 'Track replies from businesses.'],
               ['store', 'Your business', 'Manage your listing, photos and leads.']];
  }
?>
<section class="au-sec"><div class="wrap">
 <div class="au-box">
  <aside class="au-side" style="--aubg:url('<?= base_url('assets/img/noida.jpg') ?>')">
    <div class="au-side-in">
      <span class="au-kick"><i class="fa-solid fa-location-dot"></i> HiNoida</span>
      <h1><?= esc($_t) ?></h1>
      <p class="au-sub"><?= esc($_s) ?></p>
      <ul class="au-pts">
        <?php foreach ($_pts as $_p): ?>
          <li><i class="fa-solid fa-<?= $_p[0] ?>"></i><div><b><?= esc($_p[1]) ?></b><span><?= esc($_p[2]) ?></span></div></li>
        <?php endforeach; ?>
      </ul>
      <div class="au-trust"><span><b>1,400+</b> listings</span><span><b>200+</b> localities</span><span><b>Free</b> to join</span></div>
    </div>
  </aside>
  <div class="au-form">
    <h2 class="au-h"><?= $reg ? ($biz ? 'Create business account' : 'Sign up') : 'Log in' ?></h2>

    <?php if (! empty($ok)): ?><div style="background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:10px;padding:13px 15px;font-size:14px;margin-bottom:14px"><i class="fa-solid fa-circle-check"></i> <?= esc($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div style="background:#fdecec;color:#c0392b;border-radius:10px;padding:11px 14px;font-size:14px;margin-bottom:14px"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($err) ?></div><?php endif; ?>
    <div style="text-align:center;position:relative;margin:2px 0 16px"><span style="background:#fff;padding:0 12px;font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;color:#9aa6bd;position:relative;z-index:1"><?= $reg ? 'Your details' : 'Login credentials' ?></span><span style="position:absolute;top:50%;left:0;right:0;height:1px;background:var(--line)"></span></div>
    <form method="post" action="<?= base_url($reg ? 'register' : 'login') ?>">
      <?php if ($biz): ?><input type="hidden" name="type" value="business"><?php endif; ?>
      <?php if (! empty($redirect)): ?><input type="hidden" name="redirect" value="<?= esc($redirect) ?>"><?php endif; ?>
      <?php $old = session()->getFlashdata('old') ?: []; ?>
      <?php if ($reg): ?>
        <label style="<?= $lab ?>"><?= $biz ? 'Business / owner name' : 'Full name' ?></label><input name="name" required value="<?= esc($old['name'] ?? '') ?>" style="<?= $inp ?>">
        <label style="<?= $lab ?>">Phone</label><input name="phone" value="<?= esc($old['phone'] ?? '') ?>" style="<?= $inp ?>">
      <?php endif; ?>
      <label style="<?= $lab ?>">Email</label><input type="email" name="email" required value="<?= esc($old['email'] ?? '') ?>" autocapitalize="none" autocorrect="off" spellcheck="false" style="<?= $inp ?>">
      <label style="<?= $lab ?>">Password</label>
      <input type="password" name="password" required autocapitalize="none" autocorrect="off" spellcheck="false" style="<?= $inp ?>">
      <?php if (! $reg): ?>
        <p style="margin:8px 0 0;text-align:right"><a href="<?= hi_page('forgot') ?>" style="font-size:13px;color:var(--blue);font-weight:600">Forgot password?</a></p>
      <?php endif; ?>
      <?php if ($reg): ?><p style="font-size:12px;color:var(--muted);margin:8px 0 0"><i class="fa-solid fa-circle-info"></i> We'll send a 6-digit code to this email — double-check it's correct.</p><?php endif; ?>
      <button type="submit" style="width:100%;margin-top:18px;background:var(--blue);color:#fff;border:0;border-radius:10px;padding:13px;font-size:15px;font-weight:700;cursor:pointer"><?= $reg ? ($biz ? 'Create business account' : 'Create account') : 'Log in' ?></button>
    </form>

    <?php if (! $reg): ?>
      <div style="text-align:center;position:relative;margin:20px 0 14px"><span style="background:#fff;padding:0 12px;font-size:12px;font-weight:600;text-transform:uppercase;color:#9aa6bd;position:relative;z-index:1">New to HiNoida?</span><span style="position:absolute;top:50%;left:0;right:0;height:1px;background:var(--line)"></span></div>
      <?php /* one way in — business owners can still switch on the sign-up page */ ?>
      <a href="<?= hi_page('register') ?>" style="display:flex;align-items:center;justify-content:center;gap:8px;font-size:14.5px;font-weight:700;color:var(--blue);background:#fff;border:1.5px solid var(--blue);border-radius:10px;padding:13px;text-decoration:none"><i class="fa-solid fa-user-plus"></i> Sign up</a>
    <?php else: ?>
      <p style="text-align:center;margin-top:16px;font-size:14px;color:#666">Already have an account? <a href="<?= hi_page('login') ?>">Log in</a><?php if ($biz): ?><br>Just want to save places? <a href="<?= hi_page('register') ?>">Sign up as a user</a><?php else: ?><br>Own a business? <a href="<?= hi_page('register/business') ?>">Sign up as a business</a><?php endif; ?></p>
    <?php endif; ?>
  </div>
 </div>
</div></section>
<style>
/* login / signup — two columns: why join + the form */
.au-sec{padding:34px 0 50px}
.au-box{display:grid;grid-template-columns:1.05fr 1fr;max-width:1000px;margin:0 auto;background:#fff;border:1px solid var(--line);
  border-radius:22px;overflow:hidden;box-shadow:0 1px 2px rgba(16,26,44,.05),0 24px 50px -28px rgba(16,26,44,.45)}
.au-side{position:relative;color:#fff;background:#0b1428;overflow:hidden}
.au-side::before{content:"";position:absolute;inset:0;background-image:var(--aubg);background-size:cover;background-position:center;opacity:.35}
.au-side::after{content:"";position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 100% 0%,rgba(61,110,224,.45),transparent 70%),
  linear-gradient(160deg,rgba(9,22,52,.92),rgba(12,40,110,.88))}
.au-side-in{position:relative;z-index:1;padding:36px 34px;height:100%;display:flex;flex-direction:column}
.au-kick{display:inline-flex;align-items:center;gap:7px;align-self:flex-start;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  padding:6px 12px;border-radius:999px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2)}
.au-side h1{color:#fff!important;font-size:32px;font-weight:800;letter-spacing:-.025em;line-height:1.15;margin:16px 0 8px}
.au-sub{color:rgba(255,255,255,.78);font-size:15px;margin:0 0 22px;line-height:1.5}
.au-pts{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:14px}
.au-pts li{display:flex;gap:13px;align-items:flex-start}
.au-pts li>i{width:38px;height:38px;flex:none;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:14px;
  background:rgba(120,160,255,.18);color:#a9c3ff}
.au-pts b{display:block;font-size:14.5px;font-weight:700;color:#fff}
.au-pts span{display:block;font-size:13px;color:rgba(255,255,255,.66);line-height:1.45;margin-top:2px}
.au-trust{display:flex;flex-wrap:wrap;gap:8px;margin-top:auto;padding-top:24px}
.au-trust span{font-size:12.5px;color:rgba(255,255,255,.75);padding:6px 11px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14)}
.au-trust b{color:#fff}
.au-form{padding:34px 36px}
.au-h{font-size:24px!important;font-weight:800!important;letter-spacing:-.02em;margin:0 0 14px!important;color:var(--ink)}
.au-h::before,.au-h::after{display:none!important}
.au-form input{background:#f7f9fc}
.au-form input:focus{outline:0;border-color:var(--blue)!important;background:#fff;box-shadow:0 0 0 3px rgba(23,65,166,.12)}
.au-form button[type=submit]{transition:filter .2s,transform .2s}
.au-form button[type=submit]:hover{filter:brightness(1.1);transform:translateY(-1px)}
@media(max-width:820px){
  .au-sec{padding:18px 0 30px}
  .au-box{grid-template-columns:1fr;border-radius:18px}
  .au-side-in{padding:24px 22px}
  .au-side h1{font-size:25px;margin-top:12px}
  .au-sub{margin-bottom:16px}
  .au-pts{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .au-pts span{display:none}
  .au-trust{padding-top:16px}
  .au-form{padding:24px 20px}
}
</style>
<?= $this->endSection() ?>
