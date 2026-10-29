<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="sec"><div class="wrap" style="max-width:520px">
  <div class="det-crumb" style="margin-bottom:18px">
    <a href="<?= base_url('/') ?>">Home</a> &rsaquo; <a href="<?= hi_page('pricing') ?>">Plans</a> &rsaquo; <span>Checkout</span>
  </div>

  <?php if (! empty($err)): ?>
    <div style="background:#fdecec;color:#b3262f;border:1px solid #f5c6c6;border-radius:12px;padding:13px 18px;margin-bottom:16px">
      <i class="fa-solid fa-circle-exclamation"></i> <?= esc($err) ?>
    </div>
  <?php endif; ?>

  <div class="sidecard" style="padding:26px">
    <h1 style="font-size:22px;margin:0 0 4px"><?= esc($pkg['name']) ?></h1>
    <p style="color:var(--muted);margin:0 0 18px">
      <?= (int) $pkg['validity'] ?> days &middot;
      <?= (int) $pkg['number_of_listings'] ?: 'Unlimited' ?> listing<?= (int) $pkg['number_of_listings'] === 1 ? '' : 's' ?>
      <?= ! empty($pkg['featured']) ? ' &middot; featured placement' : '' ?>
    </p>

    <div style="display:flex;justify-content:space-between;align-items:baseline;padding:16px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);margin-bottom:20px">
      <span style="color:var(--body)">Total</span>
      <b style="font-size:26px;color:var(--ink)">₹<?= number_format((int) $pkg['price']) ?></b>
    </div>

    <?php if ($gateway === 'razorpay' && ! empty($order['id'])): ?>
      <button type="button" id="payBtn" class="authbtn">Pay ₹<?= number_format((int) $pkg['price']) ?></button>
      <form method="post" action="<?= base_url('pricing/return') ?>" id="payForm">
        <input type="hidden" name="razorpay_payment_id" id="rp_pid">
        <input type="hidden" name="razorpay_order_id"   id="rp_oid">
        <input type="hidden" name="razorpay_signature"  id="rp_sig">
      </form>
      <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
      <script>
      document.getElementById('payBtn').addEventListener('click', function(){
        var rz = new Razorpay({
          key: <?= json_encode($keyId) ?>,
          amount: <?= (int) $paise ?>,
          currency: <?= json_encode($currency) ?>,
          name: 'HiNoida',
          description: <?= json_encode($pkg['name'] . ' plan') ?>,
          order_id: <?= json_encode($order['id']) ?>,
          handler: function (r) {
            document.getElementById('rp_pid').value = r.razorpay_payment_id;
            document.getElementById('rp_oid').value = r.razorpay_order_id;
            document.getElementById('rp_sig').value = r.razorpay_signature;
            document.getElementById('payForm').submit();
          },
          theme: { color: '#1741a6' }
        });
        rz.open();
      });
      </script>

    <?php elseif ($gateway === 'stripe'): ?>
      <p style="color:var(--body);font-size:14px;line-height:1.6;margin:0 0 16px"><?= esc(hi_c('checkout.p_you_ll_be_taken_to_stripe_to_pay_then_brough', 'You\'ll be taken to Stripe to pay, then brought straight back.')) ?></p>
      <form method="post" action="<?= base_url('pricing/return') ?>">
        <input type="hidden" name="session_id" value="">
        <button class="authbtn" disabled><?= esc(hi_c('checkout.btn_stripe_checkout_needs_a_session', 'Stripe checkout needs a session')) ?></button>
      </form>
      <p style="font-size:12.5px;color:var(--muted);margin:12px 0 0"><?= esc(hi_c('checkout.p_add_your_stripe_keys_under_admin_settings_pa', 'Add your Stripe keys under Admin → Settings → Payments to enable this.')) ?></p>

    <?php elseif ($gateway === 'paypal'): ?>
      <p style="color:var(--body);font-size:14px;line-height:1.6;margin:0 0 16px"><?= esc(hi_c('checkout.p_you_ll_be_taken_to_paypal_to_approve_the_pay', 'You\'ll be taken to PayPal to approve the payment, then brought back here.')) ?></p>
      <form method="post" action="<?= base_url('pricing/return') ?>">
        <input type="hidden" name="token" value="">
        <button class="authbtn" disabled><?= esc(hi_c('checkout.btn_paypal_order_needs_creating', 'PayPal order needs creating')) ?></button>
      </form>

    <?php else: ?>
      <p style="color:var(--body)">No payment gateway is switched on. <a href="<?= hi_page('pricing') ?>">Back to plans</a>.</p>
    <?php endif; ?>

    <p style="font-size:12.5px;color:var(--muted);margin:16px 0 0;text-align:center"><?= esc(hi_c('checkout.p_your_plan_starts_only_once_we_ve_confirmed_t', 'Your plan starts only once we\'ve confirmed the payment with the gateway.')) ?></p>
  </div>
</div></section>
<?= $this->endSection() ?>
