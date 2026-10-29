<?php
namespace App\Controllers;

use App\Libraries\Mailer;

/**
 * HTTP endpoint for external cron services (e.g. cron-job.org) that can't
 * run shell commands — they just hit a URL. Protected by a secret key.
 */
class Cron extends BaseController
{
    // Change this to your own random string, and use the SAME value in cron-job.org's URL.
    private const SECRET = 'e7dab5202ce7a6dcf49b9325ec1164c65d150e39';

    public function emailSend()
    {
        $key = (string) $this->request->getGet('key');
        if (! hash_equals(self::SECRET, $key)) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }
        $m = new Mailer();

        // ?test=you@example.com sends one message and returns the entire SMTP
        // conversation. The queue only ever stored a summary, which was not
        // enough to tell why a send that authenticates still fails.
        $test = trim((string) $this->request->getGet('test'));
        if ($test !== '' && filter_var($test, FILTER_VALIDATE_EMAIL)) {
            $email = \Config\Services::email();
            $email->initialize([
                'protocol'      => 'smtp',
                'SMTPHost'      => $m->setting('smtp_host'),
                'SMTPUser'      => $m->setting('smtp_user'),
                'SMTPPass'      => $m->setting('smtp_pass'),
                'SMTPPort'      => (int) $m->setting('smtp_port', '587'),
                'SMTPCrypto'    => $m->setting('smtp_secure', 'tls'),
                'mailType'      => 'html',
                'charset'       => 'UTF-8',
                'SMTPTimeout'   => 10,
                'SMTPKeepAlive' => false,
            ]);
            $from = $m->setting('mail_from', $m->setting('system_email', 'no-reply@new.hinoida.com'));
            $email->setFrom($from, $m->setting('mail_from_name', 'HiNoida'));
            $email->setTo($test);
            $email->setSubject('HiNoida SMTP test');
            $email->setMessage('<p>If you are reading this, sending works.</p>');
            $ok = $email->send(false);

            return $this->response->setJSON([
                'test_to'    => $test,
                'sent'       => $ok,
                'from'       => $from,
                'transcript' => preg_split('/\R/', trim(strip_tags($email->printDebugger([])))),
            ]);
        }

        [$sent, $logged, $failed] = $m->processQueue(25);

        // diagnostics so email problems are visible instead of silent
        $db = \Config\Database::connect();
        $q = ['pending'=>0,'sent'=>0,'logged'=>0,'failed'=>0];
        $lastErr = null;
        if ($db->tableExists('email_queue')) {
            foreach ($db->table('email_queue')->select('status, COUNT(*) c')->groupBy('status')->get()->getResultArray() as $r) {
                $q[$r['status']] = (int) $r['c'];
            }
            $e = $db->table('email_queue')->where('error IS NOT NULL')->orderBy('id','DESC')->limit(1)->get()->getRowArray();
            if ($e) { $lastErr = ['to'=>$e['to_email'],'error'=>mb_substr((string)$e['error'],0,300)]; }
        }
        $pw = $m->setting('smtp_pass');
        return $this->response->setJSON([
            'ok' => true, 'sent' => $sent, 'logged' => $logged, 'failed' => $failed, 'time' => date('c'),
            'queue_totals' => $q,
            'smtp' => [
                'host' => $m->setting('smtp_host'),
                'port' => $m->setting('smtp_port'),
                'user' => $m->setting('smtp_user'),
                'pass_len' => strlen($pw),           // never expose the password itself
                'pass_set' => $pw !== '' ? 'yes' : 'NO',
                'from' => $m->setting('mail_from'),
                // 'ssl' on port 587 fails at connect with no useful message —
                // 587 wants 'tls' (STARTTLS), 465 wants 'ssl'
                'secure' => $m->setting('smtp_secure', '(unset — defaults to tls)'),
                'from_matches_user' => strcasecmp(
                    trim($m->setting('mail_from', $m->setting('system_email'))),
                    trim($m->setting('smtp_user'))
                ) === 0 ? 'yes' : 'NO — Gmail rejects a From it did not authenticate',
                'openssl' => extension_loaded('openssl') ? 'yes' : 'NO',
                // which of the two places each value actually came from
                'source_user' => $m->settingSource('smtp_user'),
                'source_pass' => $m->settingSource('smtp_pass'),
                'source_from' => $m->settingSource('mail_from'),
            ],
            'last_error' => $lastErr,
        ]);
    }

    /**
     * Daily featured rotation, ported from ListingCron::rotate_featured.
     *
     * The original wiped is_featured on every row and re-picked 8 at random
     * with a date-seeded shuffle. On its own that erases a customer's paid
     * placement every night, and the original sells featured in packages — so
     * the two contradict each other. Resolved by rotating only among listings
     * whose owner has NOT paid for featured: free listings still get their
     * turn, paid ones are left alone.
     */
    public function rotateFeatured()
    {
        $key = (string) $this->request->getGet('key');
        if (! hash_equals(self::SECRET, $key)) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        $db    = \Config\Database::connect();
        $slots = max(1, (int) ($this->request->getGet('slots') ?: 8));

        // owners whose live plan includes featured, plus admins — untouchable
        $paid = [];
        if ($db->tableExists('package_purchased_history') && $db->tableExists('package')) {
            foreach ($db->table('package_purchased_history h')
                ->select('h.user_id')->join('package p', 'p.id = h.package_id', 'left')
                ->where('h.expired_date >', time())->where('p.featured', 1)
                ->get()->getResultArray() as $r) { $paid[(int) $r['user_id']] = true; }
        }
        foreach ($db->table('user')->select('id')->where('role_id', 1)->get()->getResultArray() as $r) {
            $paid[(int) $r['id']] = true;
        }

        // clear yesterday's rotation, leaving paid placements alone
        $clear = $db->table('listing')->where('is_featured', 1);
        if ($paid) { $clear->whereNotIn('user_id', array_keys($paid)); }
        $clear->update(['is_featured' => 0]);

        // same idea as the original's RAND(seed): stable for a given day
        $pool = $db->table('listing')->select('id')->where('status', 'active');
        if ($paid) { $pool->whereNotIn('user_id', array_keys($paid)); }
        $ids = array_map(static fn ($r) => (int) $r['id'], $pool->get()->getResultArray());

        $picked = [];
        if ($ids) {
            mt_srand(crc32(date('Y-m-d')));
            shuffle($ids);
            mt_srand();
            $picked = array_slice($ids, 0, $slots);
            $db->table('listing')->whereIn('id', $picked)->update(['is_featured' => 1]);
        }

        return $this->response->setJSON([
            'ok' => true, 'rotated' => count($picked), 'pool' => count($ids),
            'paid_untouched' => count($paid), 'date' => date('Y-m-d'),
        ]);
    }

    /**
     * Expire featured placement when the plan that paid for it has run out.
     *
     * Buying a featured package sets is_featured on the owner's listings;
     * without this nothing ever unsets it, so one month's payment would buy
     * featured placement forever. Run daily.
     */
    public function expireFeatured()
    {
        $key = (string) $this->request->getGet('key');
        if (! hash_equals(self::SECRET, $key)) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        $db = \Config\Database::connect();
        if (! $db->tableExists('package_purchased_history') || ! $db->tableExists('package')) {
            return $this->response->setJSON(['ok' => true, 'skipped' => 'no package tables']);
        }

        // everyone who currently holds a live plan that includes featured
        $entitled = [];
        foreach ($db->table('package_purchased_history h')
            ->select('h.user_id')->join('package p', 'p.id = h.package_id', 'left')
            ->where('h.expired_date >', time())->where('p.featured', 1)
            ->get()->getResultArray() as $r) {
            $entitled[(int) $r['user_id']] = true;
        }

        // admins keep whatever they set by hand
        foreach ($db->table('user')->select('id')->where('role_id', 1)->get()->getResultArray() as $r) {
            $entitled[(int) $r['id']] = true;
        }

        $b = $db->table('listing')->where('is_featured', 1);
        if ($entitled) { $b->whereNotIn('user_id', array_keys($entitled)); }
        $expired = $b->countAllResults(false);
        if ($expired) { $b->update(['is_featured' => 0]); }

        return $this->response->setJSON([
            'ok' => true, 'unfeatured' => $expired,
            'still_entitled' => count($entitled), 'time' => date('c'),
        ]);
    }
}
