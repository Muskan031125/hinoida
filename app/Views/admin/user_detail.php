<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;font-family:'Inter',sans-serif;color:#1c2432;background:linear-gradient(180deg,#eaf0f9,#f0ecf8,#eaf4f1) fixed}
.wrap{max-width:1000px;margin:0 auto;padding:26px 24px}
.top{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.top a.back{color:#1741a6;font-weight:600;text-decoration:none}
h1{font-family:'Inter',system-ui,sans-serif;font-weight:600;font-size:30px;margin:0}
.panel{background:rgba(255,255,255,.75);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.6);border-radius:15px;box-shadow:0 6px 18px rgba(15,28,51,.05);overflow:hidden;margin-bottom:20px}
.panel h3{margin:0;padding:15px 20px;font-size:15px;border-bottom:1px solid #eef1f6}
.pad{padding:20px}
table{width:100%;border-collapse:collapse;font-size:13.5px}
th{text-align:left;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9aa6bd;padding:12px 20px;background:#fafbfd;border-bottom:1px solid #eceef3}
td{padding:12px 20px;border-bottom:1px solid #f1f3f7;color:#33405a}
.chip{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
.chip-live{background:#e6f6ec;color:#1a7a3d}.chip-pending{background:#fff3e0;color:#b26a00}.chip-adm{background:#eef3fc;color:#1741a6}
.kv{display:grid;grid-template-columns:150px 1fr;gap:8px 16px;font-size:14px}
.kv b{color:#7c8aa3;font-weight:600}
.acts{display:flex;flex-wrap:wrap;gap:9px;margin-top:4px}
.acts a{padding:9px 14px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none}
.a-blue{background:#1741a6;color:#fff}.a-green{background:#0b8a3d;color:#fff}.a-soft{background:#eef3fc;color:#1741a6}.a-red{background:#fdecef;color:#c0324b}
.saved{background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:10px;padding:11px 15px;margin-bottom:16px}
.empty{text-align:center;color:#9aa6bd;padding:30px}
</style>
</head>
<body>
<?php $role=(int)($u['role_id']??3); $rl=$role===1?'Admin':($role===2?'Business':'Member'); $ver=(int)($u['is_verified']??1)===1; $app=(int)($u['approved']??1)===1; ?>
<div class="wrap">
  <div class="top"><h1><i class="fa-solid fa-user" style="color:#1741a6"></i> <?= esc($u['name']) ?></h1><a class="back" href="<?= base_url('admin/users') ?>"><i class="fa-solid fa-arrow-left"></i> All members</a></div>
  <?php if(!empty($saved)): ?><div class="saved"><i class="fa-solid fa-circle-check"></i> <?= esc($saved) ?></div><?php endif; ?>

  <div class="panel"><h3>Account</h3><div class="pad">
    <div class="kv">
      <b>Name</b><span><?= esc($u['name']) ?></span>
      <b>Email</b><span><?= esc($u['email']) ?></span>
      <b>Phone</b><span><?= esc($u['phone']??'—') ?></span>
      <b>Role</b><span><span class="chip <?= $role===1?'chip-adm':'' ?>"><?= $rl ?></span></span>
      <b>Email verified</b><span><?= $ver?'<span class="chip chip-live">Verified</span>':'<span class="chip chip-pending">Unverified</span>' ?></span>
      <?php if($role===2): ?><b>Business approval</b><span><?= $app?'<span class="chip chip-live">Approved</span>':'<span class="chip chip-pending">Pending</span>' ?></span><?php endif; ?>
    </div>
    <div class="acts">
      <?php if(!$ver): ?><a class="a-green" href="<?= base_url('admin/user/verify/'.(int)$u['id']) ?>"><i class="fa-solid fa-check"></i> Mark email verified</a><?php endif; ?>
      <?php if($role===2 && !$app): ?><a class="a-green" href="<?= base_url('admin/user/approve/'.(int)$u['id']) ?>"><i class="fa-solid fa-thumbs-up"></i> Approve business</a><?php endif; ?>
      <?php if($role!==1): ?><a class="a-soft" href="<?= base_url('admin/user/makeadmin/'.(int)$u['id']) ?>">Make admin</a><?php endif; ?>
      <?php if($role!==2): ?><a class="a-soft" href="<?= base_url('admin/user/makebiz/'.(int)$u['id']) ?>">Make business</a><?php endif; ?>
      <?php if($role!==3): ?><a class="a-soft" href="<?= base_url('admin/user/makeuser/'.(int)$u['id']) ?>">Make member</a><?php endif; ?>
      <a class="a-red" href="<?= base_url('admin/user/delete/'.(int)$u['id']) ?>" onclick="return confirm('Delete this user permanently?')"><i class="fa-solid fa-trash"></i> Delete</a>
    </div>
  </div></div>

  <div class="panel"><h3>Their listings (<?= count($listings) ?>)</h3>
    <?php if(!$listings): ?><div class="empty">No listings owned by this user.</div>
    <?php else: ?><table><tr><th>Name</th><th>Status</th><th></th></tr>
      <?php foreach($listings as $l): ?><tr><td><b><?= esc($l['name']) ?></b></td><td><span class="chip <?= ($l['status']??'')==='active'?'chip-live':'chip-pending' ?>"><?= esc(ucfirst($l['status']??'pending')) ?></span></td><td style="text-align:right"><a class="a-soft" style="padding:6px 11px" href="<?= base_url('admin/listing/'.(int)$l['id']) ?>">Edit</a> <a class="a-soft" style="padding:6px 11px" href="<?= hi_listing_url($l) ?>">View</a></td></tr><?php endforeach; ?>
    </table><?php endif; ?>
  </div>

  <div class="panel"><h3>Enquiries received (<?= count($enq) ?>)</h3>
    <?php if(!$enq): ?><div class="empty">No enquiries.</div>
    <?php else: ?><table><tr><th>Name</th><th>Phone</th><th>Message</th></tr><?php foreach($enq as $e): ?><tr><td><b><?= esc($e['name']??'') ?></b></td><td><?= esc($e['phone']??'') ?></td><td><?= esc(mb_substr($e['message']??'',0,80)) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
  </div>
  <div class="panel"><h3>Bookings received (<?= count($bok) ?>)</h3>
    <?php if(!$bok): ?><div class="empty">No bookings.</div>
    <?php else: ?><table><tr><th>Name</th><th>Type</th><th>When</th></tr><?php foreach($bok as $bk): ?><tr><td><b><?= esc($bk['name']??'') ?></b></td><td><?= esc($bk['booking_type']??'') ?></td><td><?= esc(trim(($bk['booking_date']??'').' '.($bk['booking_time']??''))) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
  </div>
</div>
</body>
</html>
