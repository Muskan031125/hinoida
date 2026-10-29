<?php

namespace App\Libraries;

/**
 * Payment gateway hand-off and verification.
 *
 * Mirrors what the original did in Payment_model: the browser talks to the
 * gateway, and the plan is only granted after this class has checked the
 * result with the gateway server-side. Every method fails closed — if a
 * check cannot be completed, it returns false and nothing is granted.
 */
class Gateway
{
    public static function name(): string
    {
        return strtolower(trim(hi_setting('pay_gateway')));
    }

    public static function configured(): bool
    {
        return self::name() !== '' && hi_setting('pay_key_id') !== '' && hi_setting('pay_key_secret') !== '';
    }

    public static function currency(): string
    {
        return strtoupper(hi_setting('pay_currency', 'INR'));
    }

    /**
     * Verify a completed payment with the gateway.
     *
     * @param array $post whatever the gateway handed back to the browser
     * @return array{ok:bool, ref:string, amount:int, error:string}
     */
    public static function verify(array $post, int $expectedPaise): array
    {
        $fail = static fn (string $why) => ['ok' => false, 'ref' => '', 'amount' => 0, 'error' => $why];

        if (! self::configured()) { return $fail('No gateway is configured.'); }

        switch (self::name()) {
            case 'razorpay': return self::verifyRazorpay($post, $expectedPaise);
            case 'stripe':   return self::verifyStripe($post, $expectedPaise);
            case 'paypal':   return self::verifyPaypal($post, $expectedPaise);
        }

        return $fail('That gateway is not supported yet.');
    }

    /**
     * Razorpay signs order_id|payment_id with the key secret. Recomputing it
     * is the whole check — a browser cannot forge it without the secret.
     */
    private static function verifyRazorpay(array $p, int $expectedPaise): array
    {
        $fail = static fn (string $why) => ['ok' => false, 'ref' => '', 'amount' => 0, 'error' => $why];

        $order = (string) ($p['razorpay_order_id'] ?? '');
        $pay   = (string) ($p['razorpay_payment_id'] ?? '');
        $sig   = (string) ($p['razorpay_signature'] ?? '');
        if ($order === '' || $pay === '' || $sig === '') { return $fail('Incomplete payment response.'); }

        $expected = hash_hmac('sha256', $order . '|' . $pay, hi_setting('pay_key_secret'));
        if (! hash_equals($expected, $sig)) { return $fail('Payment signature did not match.'); }

        // signature proves the payment belongs to our order; now confirm the
        // amount with Razorpay rather than trusting anything from the browser
        $res = self::http('https://api.razorpay.com/v1/payments/' . rawurlencode($pay), null,
            hi_setting('pay_key_id') . ':' . hi_setting('pay_key_secret'));
        if (! is_array($res)) { return $fail('Could not reach the gateway to confirm the payment.'); }

        $status = (string) ($res['status'] ?? '');
        if (! in_array($status, ['captured', 'authorized'], true)) { return $fail('Payment is not captured (' . $status . ').'); }
        if ((int) ($res['amount'] ?? 0) < $expectedPaise) { return $fail('Amount paid is less than the plan price.'); }

        return ['ok' => true, 'ref' => $pay, 'amount' => (int) $res['amount'], 'error' => ''];
    }

    /** Stripe: fetch the checkout session and read its real payment_status. */
    private static function verifyStripe(array $p, int $expectedPaise): array
    {
        $fail = static fn (string $why) => ['ok' => false, 'ref' => '', 'amount' => 0, 'error' => $why];

        $sid = (string) ($p['session_id'] ?? '');
        if ($sid === '') { return $fail('No checkout session returned.'); }

        $res = self::http('https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sid), null,
            hi_setting('pay_key_secret') . ':');
        if (! is_array($res)) { return $fail('Could not reach Stripe to confirm the payment.'); }
        if (($res['payment_status'] ?? '') !== 'paid') { return $fail('Stripe reports this session as unpaid.'); }
        if ((int) ($res['amount_total'] ?? 0) < $expectedPaise) { return $fail('Amount paid is less than the plan price.'); }

        return ['ok' => true, 'ref' => $sid, 'amount' => (int) $res['amount_total'], 'error' => ''];
    }

    /** PayPal: capture the order, then check it really completed. */
    private static function verifyPaypal(array $p, int $expectedPaise): array
    {
        $fail = static fn (string $why) => ['ok' => false, 'ref' => '', 'amount' => 0, 'error' => $why];

        $order = (string) ($p['token'] ?? $p['orderID'] ?? '');
        if ($order === '') { return $fail('No PayPal order returned.'); }

        $live = hi_setting('pay_mode', 'live') !== 'sandbox';
        $base = $live ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $tok = self::http($base . '/v1/oauth2/token', 'grant_type=client_credentials',
            hi_setting('pay_key_id') . ':' . hi_setting('pay_key_secret'));
        if (! is_array($tok) || empty($tok['access_token'])) { return $fail('Could not authenticate with PayPal.'); }

        $cap = self::http($base . '/v2/checkout/orders/' . rawurlencode($order) . '/capture', '{}', null,
            ['Authorization: Bearer ' . $tok['access_token'], 'Content-Type: application/json']);
        if (! is_array($cap)) { return $fail('Could not capture the PayPal order.'); }
        if (($cap['status'] ?? '') !== 'COMPLETED') { return $fail('PayPal reports this order as ' . ($cap['status'] ?? 'incomplete') . '.'); }

        $paid = (float) ($cap['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0);
        if ((int) round($paid * 100) < $expectedPaise) { return $fail('Amount paid is less than the plan price.'); }

        return ['ok' => true, 'ref' => $order, 'amount' => (int) round($paid * 100), 'error' => ''];
    }

    /** Create the gateway-side order, so the amount is fixed before payment. */
    public static function createOrder(int $paise, string $receipt): ?array
    {
        if (self::name() !== 'razorpay' || ! self::configured()) { return null; }

        $res = self::http('https://api.razorpay.com/v1/orders',
            json_encode(['amount' => $paise, 'currency' => self::currency(), 'receipt' => $receipt]),
            hi_setting('pay_key_id') . ':' . hi_setting('pay_key_secret'),
            ['Content-Type: application/json']);

        return is_array($res) && ! empty($res['id']) ? $res : null;
    }

    /** @return array|null decoded JSON, or null on any transport failure */
    private static function http(string $url, ?string $body = null, ?string $basic = null, array $headers = [])
    {
        if (! function_exists('curl_init')) { return null; }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => $headers ?: ['Accept: application/json'],
        ]);
        if ($basic !== null) { curl_setopt($ch, CURLOPT_USERPWD, $basic); }
        if ($body !== null)  { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }

        $out  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($out === false || $code >= 400) { return null; }
        $j = json_decode((string) $out, true);

        return is_array($j) ? $j : null;
    }
}
