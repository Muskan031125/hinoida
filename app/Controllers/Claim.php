<?php

namespace App\Controllers;

/**
 * "Is this your business?" — a visitor asks to take ownership of a listing
 * that nobody manages yet. An admin approves, and the listing moves into that
 * user's dashboard.
 *
 * The production claimed_listing table is only (id, listing_id, status), so the
 * contact fields are added on first use; existing rows keep working.
 */
class Claim extends BaseController
{
    private function db()
    {
        $db = \Config\Database::connect();
        foreach ([
            'user_id'    => 'INTEGER',
            'claim_name' => 'TEXT',
            'claim_email'=> 'TEXT',
            'claim_phone'=> 'TEXT',
            'claim_note' => 'TEXT',
            'created_at' => 'TEXT',
        ] as $col => $type) {
            if (! $db->fieldExists($col, 'claimed_listing')) {
                try { $db->query("ALTER TABLE claimed_listing ADD COLUMN {$col} {$type}"); } catch (\Throwable $e) {}
            }
        }

        return $db;
    }

    /**
     * Claimable while nobody real owns it and no claim is open.
     *
     * Most of the directory was imported under the admin account, so an
     * admin-owned listing counts as unowned — that is exactly the case this
     * feature exists for.
     */
    public static function claimable(array $l): bool
    {
        $db  = \Config\Database::connect();
        $uid = (int) ($l['user_id'] ?? 0);
        if ($uid > 0) {
            $owner = $db->table('user')->select('role_id')->where('id', $uid)->get()->getRowArray();
            // no such user means the import left a dangling id — still unowned
            if ($owner && (int) $owner['role_id'] !== 1) { return false; }
        }
        if (! $db->tableExists('claimed_listing')) { return false; }

        return ! $db->table('claimed_listing')
            ->where('listing_id', (int) $l['id'])->whereIn('status', [0, 1])
            ->countAllResults();
    }

    public function form(int $id)
    {
        if (! session()->get('uid')) {
            session()->set('auth_redirect', 'claim/' . $id);
            return redirect()->to('/login');
        }
        $db = $this->db();
        $l  = $db->table('listing')->where('id', $id)->get()->getRowArray();
        if (! $l) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }

        $me = $db->table('user')->where('id', (int) session()->get('uid'))->get()->getRowArray() ?: [];

        return view('frontend/claim', [
            'title'           => 'Claim ' . $l['name'] . ' | HiNoida',
            'metaDescription' => 'Claim ownership of ' . $l['name'] . ' on HiNoida to manage its details, photos, offers and customer enquiries.',
            'robots'          => 'noindex,follow',
            'l'               => $l,
            'me'              => $me,
            'taken'           => ! self::claimable($l),
            'err'             => session()->getFlashdata('err'),
        ]);
    }

    public function send(int $id)
    {
        if (! session()->get('uid')) { return redirect()->to('/login'); }

        $db = $this->db();
        $l  = $db->table('listing')->where('id', $id)->get()->getRowArray();
        if (! $l) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        if (! self::claimable($l)) {
            session()->setFlashdata('err', 'This listing already has an owner or a claim under review.');
            return redirect()->to('claim/' . $id);
        }

        $name  = trim((string) $this->request->getPost('name'));
        $email = trim((string) $this->request->getPost('email'));
        $phone = preg_replace('/[^0-9+]/', '', (string) $this->request->getPost('phone'));
        $note  = trim((string) $this->request->getPost('note'));

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) $phone) < 8) {
            session()->setFlashdata('err', 'Please give your name, a valid email and a contact number.');
            return redirect()->to('claim/' . $id);
        }

        $db->table('claimed_listing')->insert([
            'listing_id'  => $id,
            'status'      => 0,                 // 0 pending, 1 approved, 2 rejected
            'user_id'     => (int) session()->get('uid'),
            'claim_name'  => $name,
            'claim_email' => $email,
            'claim_phone' => $phone,
            'claim_note'  => $note,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $admin = hi_setting('system_email') ?: hi_setting('email');
        if ($admin) {
            (new \App\Libraries\Mailer())->queue($admin, 'HiNoida admin',
                'Ownership claim — ' . $l['name'],
                '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;color:#33405a">'
                . '<h2 style="color:#101a2c">New ownership claim</h2>'
                . '<p><b>' . esc($name) . '</b> has claimed <b>' . esc($l['name']) . '</b>.</p>'
                . '<p>Email: ' . esc($email) . '<br>Phone: ' . esc((string) $phone) . '</p>'
                . ($note !== '' ? '<p style="padding:12px 15px;background:#f5f7fb;border-radius:10px">' . esc($note) . '</p>' : '')
                . '<p><a href="' . base_url('admin/claims') . '">Review it in the admin console</a></p></div>');
        }

        session()->setFlashdata('saved', 'Your claim has been sent. We will verify it and email you.');
        return redirect()->to(hi_listing_url($id));
    }

    /** Admin: approve or reject a claim. */
    public function decide(int $cid, string $action)
    {
        if ((int) session()->get('role') !== 1) { return redirect()->to('/login'); }

        $db = $this->db();
        $c  = $db->table('claimed_listing')->where('id', $cid)->get()->getRowArray();
        if (! $c) { return redirect()->to('admin/claims'); }

        $ok = $action === 'approve';
        $db->table('claimed_listing')->where('id', $cid)->update(['status' => $ok ? 1 : 2]);

        if ($ok && (int) ($c['user_id'] ?? 0) > 0) {
            $db->table('listing')->where('id', (int) $c['listing_id'])->update(['user_id' => (int) $c['user_id']]);
            // a claimant who signed up as a member becomes a business owner
            $db->table('user')->where('id', (int) $c['user_id'])->where('role_id', 3)
                ->update(['role_id' => 2, 'approved' => 1]);
        }

        $to = trim((string) ($c['claim_email'] ?? ''));
        if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $biz = (string) ($db->table('listing')->select('name')->where('id', (int) $c['listing_id'])
                ->get()->getRowArray()['name'] ?? 'the listing');
            (new \App\Libraries\Mailer())->queue($to, (string) ($c['claim_name'] ?? 'there'),
                ($ok ? 'You now manage ' : 'About your claim for ') . $biz,
                '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;color:#33405a">'
                . '<h2 style="color:#101a2c">' . ($ok ? 'Claim approved' : 'Claim not approved') . '</h2>'
                . '<p>Hi ' . esc((string) ($c['claim_name'] ?? 'there')) . ',</p>'
                . ($ok
                    ? '<p><b>' . esc($biz) . '</b> is now in your dashboard — you can edit details, add photos and reply to enquiries.</p>'
                      . '<p><a href="' . base_url('dashboard/listings') . '" style="background:#014dda;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">Open my dashboard</a></p>'
                    : '<p>We could not verify your ownership of <b>' . esc($biz) . '</b>. Reply to this email with proof of business and we will take another look.</p>')
                . '</div>');
        }

        session()->setFlashdata('saved', $ok
            ? 'Claim approved — the listing has been handed over.'
            : 'Claim rejected — the claimant has been emailed.');
        return redirect()->to('admin/claims');
    }
}
