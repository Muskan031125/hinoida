<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  body{font-family:'Inter',system-ui,sans-serif;color:#1c2432;background:#eef2f8;margin:0;padding:30px 18px}
  .sheet{max-width:760px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(15,28,51,.08);overflow:hidden}
  .top{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;padding:30px 34px;background:linear-gradient(120deg,#0a2a6b,#123a8f);color:#fff;flex-wrap:wrap}
  .top h1{margin:0;font-size:23px}
  .top .no{text-align:right;font-size:13px;opacity:.92}
  .body{padding:30px 34px}
  .cols{display:grid;grid-template-columns:1fr 1fr;gap:26px;margin-bottom:28px}
  .lbl{font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#9aa6bd;margin:0 0 6px}
  .val{margin:0;font-size:14px;line-height:1.65;color:#33405a}
  .val b{color:#0f1c33}
  table{width:100%;border-collapse:collapse;font-size:14px}
  th{text-align:left;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#9aa6bd;
     padding:11px 0;border-bottom:2px solid #eceef3}
  td{padding:15px 0;border-bottom:1px solid #f1f3f7;color:#33405a;vertical-align:top}
  .right{text-align:right}
  .total td{border:0;padding-top:18px;font-size:18px;font-weight:800;color:#0f1c33}
  .paid{display:inline-block;background:#e6f6ec;color:#1a7a3d;font-size:12px;font-weight:800;padding:5px 13px;border-radius:20px}
  .foot{padding:20px 34px 30px;color:#7b8aa5;font-size:12.5px;line-height:1.7;border-top:1px solid #f1f3f7}
  .actions{max-width:760px;margin:18px auto 0;display:flex;gap:10px;justify-content:flex-end}
  .actions a,.actions button{font:inherit;font-size:13.5px;font-weight:700;padding:10px 18px;border-radius:9px;
    border:1px solid #dfe4ec;background:#fff;color:#0f1c33;cursor:pointer;text-decoration:none}
  @media print{
    body{background:#fff;padding:0}
    .sheet{box-shadow:none;border-radius:0;max-width:none}
    .actions{display:none}
  }
</style>
</head>
<body>
<?php
  $amount = (int) ($p['amount_paid'] ?? 0);
  $when   = (int) ($p['purchase_date'] ?? 0);
  $till   = (int) ($p['expired_date'] ?? 0);
?>
<div class="sheet">
  <div class="top">
    <div>
      <h1><?= esc(hi_c('invoice.hinoida', 'HiNoida')) ?></h1>
      <div style="opacity:.9;font-size:13px;margin-top:4px">Noida Business Directory</div>
      <?php if (hi_setting('system_email')): ?><div style="opacity:.8;font-size:12.5px;margin-top:8px"><?= esc(hi_setting('system_email')) ?></div><?php endif; ?>
      <?php if (hi_setting('phone')): ?><div style="opacity:.8;font-size:12.5px"><?= esc(hi_setting('phone')) ?></div><?php endif; ?>
    </div>
    <div class="no">
      <div style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;opacity:.75">Invoice</div>
      <div style="font-size:19px;font-weight:800;margin:3px 0 6px"><?= esc($invNo) ?></div>
      <div><?= $when ? esc(date('d M Y', $when)) : '—' ?></div>
    </div>
  </div>

  <div class="body">
    <div class="cols">
      <div>
        <p class="lbl">Billed to</p>
        <p class="val">
          <b><?= esc($buyer['name'] ?? 'Customer') ?></b><br>
          <?= esc($buyer['email'] ?? '') ?><?= ! empty($buyer['phone']) ? '<br>' . esc($buyer['phone']) : '' ?>
          <?= ! empty($buyer['address']) ? '<br>' . nl2br(esc($buyer['address'])) : '' ?>
        </p>
      </div>
      <div>
        <p class="lbl">Payment</p>
        <p class="val">
          Method: <b><?= esc(ucfirst((string) ($p['payment_method'] ?: 'offline'))) ?></b><br>
          <?php if ($till): ?>Plan runs to: <b><?= esc(date('d M Y', $till)) ?></b><br><?php endif; ?>
          <span class="paid" style="margin-top:6px">Paid</span>
        </p>
      </div>
    </div>

    <table>
      <tr><th>Description</th><th class="right">Amount</th></tr>
      <tr>
        <td>
          <b style="color:#0f1c33"><?= esc($p['package_name'] ?: 'HiNoida plan') ?></b><br>
          <span style="color:#7b8aa5;font-size:12.5px">
            <?= (int) ($p['validity'] ?? 0) ?: (int) round(max(0, $till - $when) / 86400) ?> days
            &middot; <?= (int) ($p['number_of_listings'] ?? 1) ?: 'Unlimited' ?> listing<?= (int) ($p['number_of_listings'] ?? 1) === 1 ? '' : 's' ?>
            <?= ! empty($p['featured']) ? ' &middot; featured placement' : '' ?>
          </span>
        </td>
        <td class="right"><?= $amount ? '₹' . number_format($amount) : 'Free' ?></td>
      </tr>
      <tr class="total"><td class="right">Total paid</td><td class="right"><?= $amount ? '₹' . number_format($amount) : '₹0' ?></td></tr>
    </table>
  </div>

  <div class="foot">
    Thank you. This is a computer-generated invoice and is valid without a signature.
    <?php if (hi_setting('address')): ?><br><?= esc(hi_setting('address')) ?><?php endif; ?>
  </div>
</div>

<div class="actions">
  <a href="<?= base_url('dashboard/billing') ?>">&larr; Back to billing</a>
  <button type="button" onclick="window.print()"><?= esc(hi_c('invoice.btn_print_save_as_pdf', 'Print / Save as PDF')) ?></button>
</div>
</body>
</html>
