<?php

namespace App\Controllers;

class Packages extends BaseController
{
    private function ensure($db): void
    {
        if (! $db->tableExists('package')) {
            $db->query('CREATE TABLE package (id INTEGER PRIMARY KEY ' . ($db->DBDriver==='MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', name VARCHAR(120), price INT DEFAULT 0, validity INT DEFAULT 365, number_of_listings INT DEFAULT 1, featured INT DEFAULT 0, is_recommended INT DEFAULT 0, description TEXT)');
            $db->table('package')->insertBatch([
                ['name' => 'Free', 'price' => 0, 'validity' => 3650, 'number_of_listings' => 1, 'featured' => 0, 'is_recommended' => 0],
                ['name' => 'Premium', 'price' => 499, 'validity' => 30, 'number_of_listings' => 3, 'featured' => 1, 'is_recommended' => 1],
                ['name' => 'Business', 'price' => 1499, 'validity' => 30, 'number_of_listings' => 10, 'featured' => 1, 'is_recommended' => 0],
            ]);
        }
    }

    public function pricing(): string
    {
        if (! hi_addon('packages')) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $db = \Config\Database::connect();
        $this->ensure($db);
        return view('frontend/pricing', [
            'title' => 'Pricing & Plans | HiNoida',
            'plans' => $db->table('package')->orderBy('price', 'ASC')->get()->getResultArray(),
            'flash' => session()->getFlashdata('flash'),
            'mine'  => session()->get('uid') ? hi_plan((int) session()->get('uid')) : null,
            'days'  => session()->get('uid') ? hi_plan_expiry((int) session()->get('uid')) : null,
            'used'  => session()->get('uid') ? hi_listing_count((int) session()->get('uid')) : 0,
        ]);
    }

    public function buy()
    {
        if (! session()->get('uid')) {
            return redirect()->to('/login');
        }
        $db  = \Config\Database::connect();
        $pid = (int) $this->request->getPost('package_id');
        $pkg = $db->table('package')->where('id', $pid)->get()->getRowArray();
        if (! $db->tableExists('package_purchased_history')) {
            $db->query('CREATE TABLE package_purchased_history (id INTEGER PRIMARY KEY ' . ($db->DBDriver==='MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', user_id INT, package_id INT, purchase_date INT, expired_date INT, amount_paid INT, payment_method VARCHAR(30))');
        }
        if (! $pkg) { return redirect()->to(hi_page('pricing')); }

        $uid   = (int) session()->get('uid');
        $price = (int) ($pkg['price'] ?? 0);

        // Only a free plan can be taken by clicking, which is what the
        // original's free_package did. A paid plan is granted by a verified
        // gateway return (payment_success) or by an admin under Revenue
        // (offline_payment) — never by the click itself, or every paid plan
        // would be free for the asking.
        if ($price === 0) {
            $db->table('package_purchased_history')->insert([
                'user_id'        => $uid,
                'package_id'     => $pid,
                'purchase_date'  => time(),
                'expired_date'   => time() + ((int) ($pkg['validity'] ?? 365)) * 86400,
                'amount_paid'    => 0,
                'payment_method' => 'free',
            ]);
            // a featured plan promotes what the owner already has listed
            if (! empty($pkg['featured'])) {
                $db->table('listing')->where('user_id', $uid)->update(['is_featured' => 1]);
            }
            session()->setFlashdata('flash', $pkg['name'] . ' is active.');

            return redirect()->to('/dashboard/billing');
        }

        // a configured gateway takes over from here
        if (\App\Libraries\Gateway::configured()) {
            return redirect()->to('pricing/checkout/' . $pid);
        }

        $how = 'To start ' . $pkg['name'] . ', send ₹' . number_format($price) . ' and we will activate it.';
        if (hi_setting('pay_upi'))  { $how .= ' Pay ₹' . number_format($price) . ' to UPI ' . hi_setting('pay_upi') . '.'; }
        if (hi_setting('pay_bank_details')) { $how .= ' ' . hi_setting('pay_bank_details'); }
        if (hi_setting('pay_note')) { $how .= ' ' . hi_setting('pay_note'); }

        session()->setFlashdata('flash', $how);
        return redirect()->to('/dashboard/billing');
    }
    /** Hand off to the gateway — the original's paypal_checkout / stripe_checkout. */
    public function checkout(int $pid)
    {
        if (! session()->get('uid')) { return redirect()->to('/login'); }
        if (! hi_addon('packages')) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }

        $db  = \Config\Database::connect();
        $pkg = $db->table('package')->where('id', $pid)->get()->getRowArray();
        if (! $pkg || (int) $pkg['price'] <= 0) { return redirect()->to(hi_page('pricing')); }
        if (! \App\Libraries\Gateway::configured()) { return redirect()->to(hi_page('pricing')); }

        $paise = (int) $pkg['price'] * 100;
        $order = \App\Libraries\Gateway::createOrder($paise, 'pkg-' . $pid . '-u' . session()->get('uid'));

        // the amount is fixed on the gateway's side before the browser sees it
        session()->set('pay_expect', ['package_id' => $pid, 'paise' => $paise, 'order' => $order['id'] ?? '']);

        return view('frontend/checkout', [
            'title'   => 'Checkout — ' . $pkg['name'] . ' | HiNoida',
            'robots'  => 'noindex,nofollow',
            'pkg'     => $pkg,
            'paise'   => $paise,
            'order'   => $order,
            'gateway' => \App\Libraries\Gateway::name(),
            'keyId'   => hi_setting('pay_key_id'),
            'currency'=> \App\Libraries\Gateway::currency(),
            'err'     => session()->getFlashdata('err'),
        ]);
    }

    /**
     * The gateway sent the customer back. Nothing is granted until the payment
     * has been confirmed with the gateway server-side, and the amount is taken
     * from the session rather than the request — the original's payment_success
     * read user_id, package_id and amount straight off the URL, which let
     * anyone grant themselves a plan.
     */
    public function paymentReturn()
    {
        if (! session()->get('uid')) { return redirect()->to('/login'); }

        $expect = session()->get('pay_expect');
        session()->remove('pay_expect');
        if (! is_array($expect) || empty($expect['package_id'])) {
            session()->setFlashdata('err', 'That checkout has expired — please start again.');
            return redirect()->to(hi_page('pricing'));
        }

        $db  = \Config\Database::connect();
        $uid = (int) session()->get('uid');
        $pkg = $db->table('package')->where('id', (int) $expect['package_id'])->get()->getRowArray();
        if (! $pkg) { return redirect()->to(hi_page('pricing')); }

        $res = \App\Libraries\Gateway::verify($this->request->getPost() ?: [], (int) $expect['paise']);
        if (! $res['ok']) {
            session()->setFlashdata('err', $res['error'] . ' Nothing has been charged to your plan.');
            return redirect()->to(hi_page('pricing'));
        }

        // don't credit the same gateway reference twice
        if ($db->fieldExists('payment_ref', 'package_purchased_history')
            && $db->table('package_purchased_history')->where('payment_ref', $res['ref'])->countAllResults()) {
            return redirect()->to('/dashboard/billing');
        }
        if (! $db->fieldExists('payment_ref', 'package_purchased_history')) {
            try { $db->query('ALTER TABLE package_purchased_history ADD COLUMN payment_ref VARCHAR(120)'); } catch (\Throwable $e) {}
        }

        $db->table('package_purchased_history')->insert([
            'user_id'        => $uid,
            'package_id'     => (int) $pkg['id'],
            'purchase_date'  => time(),
            'expired_date'   => time() + max(1, (int) $pkg['validity']) * 86400,
            'amount_paid'    => (int) round($res['amount'] / 100),
            'payment_method' => \App\Libraries\Gateway::name(),
            'payment_ref'    => $res['ref'],
        ]);
        if (! empty($pkg['featured'])) {
            $db->table('listing')->where('user_id', $uid)->update(['is_featured' => 1]);
        }

        session()->setFlashdata('flash', 'Payment received — your ' . $pkg['name'] . ' plan is active.');

        return redirect()->to('/dashboard/billing');
    }
}