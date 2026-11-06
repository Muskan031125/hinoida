<?php
namespace App\Controllers;

class Dashboard extends BaseController
{
    private function uid(){ return (int) session()->get('uid'); }

    private function base(): array
    {
        $db = \Config\Database::connect();
        $uid = $this->uid();
        $me = $db->table('user')->where('id', $uid)->get()->getRowArray();
        $mineQ = $db->table('listing')->where('user_id', $uid)->orderBy('id', 'DESC');
        // a listing the owner deleted should stay gone from their own view too
        if ($db->fieldExists('deleted_at', 'listing')) { $mineQ->where('deleted_at', null); }
        $mine = $mineQ->get()->getResultArray();
        $ids = array_map(static fn ($r) => (int) $r['id'], $mine);

        $enq = $bok = $rev = []; $ravg = 0; $rcount = 0;
        if ($ids) {
            if ($db->tableExists('discover_enquiry')) $enq = $db->table('discover_enquiry')->whereIn('listing_id', $ids)->orderBy('id', 'DESC')->get()->getResultArray();
            if ($db->tableExists('booking')) {
                // The live `booking` table has no name/phone of its own — the
                // person is a user row behind requester_id. Without this join
                // the leads page died on an undefined "name" key.
                $bq  = $db->table('booking b')->select('b.*')->whereIn('b.listing_id', $ids)->orderBy('b.id', 'DESC');
                $who = $db->fieldExists('requester_id', 'booking') ? 'b.requester_id' : 'b.user_id';
                if ($db->tableExists('user')) {
                    $bq->select('u.name AS name, u.email AS email, u.phone AS phone')
                       ->join('user u', 'u.id = ' . $who, 'left');
                }
                $bok = $bq->get()->getResultArray();
            }
            if ($db->tableExists('review')) {
                $rev = $db->table('review r')->select('r.*, r.' . hi_review_pk($db) . ' AS id, li.name AS listing_name')->join('listing li', 'li.id = r.listing_id', 'left')->whereIn('r.listing_id', $ids)->orderBy('r.' . hi_review_pk($db), 'DESC')->limit(50)->get()->getResultArray();
                $rr = $db->table('review')->select('AVG(review_rating) a, COUNT(*) c')->whereIn('listing_id', $ids)->get()->getRowArray();
                $ravg = round((float) ($rr['a'] ?? 0), 1); $rcount = (int) ($rr['c'] ?? 0);
            }
        }
        $qa = [];
        if ($ids && $db->tableExists('listing_qa')) {
            $qa = $db->table('listing_qa q')->select('q.*, li.name AS listing_name')
                ->join('listing li', 'li.id = q.listing_id', 'left')
                ->whereIn('q.listing_id', $ids)->orderBy('q.id', 'DESC')->get()->getResultArray();
        }
        // customer-side data (things THIS user did as a visitor)
        $myBookings = $myEnq = $myReviews = $saved = [];
        if ($db->tableExists('booking')) $myBookings = $db->table('booking b')->select('b.*, li.name AS listing_name')->join('listing li', 'li.id = b.listing_id', 'left')->where('b.user_id', $uid)->orderBy('b.id', 'DESC')->get()->getResultArray();
        $myQ = [];
        if ($db->tableExists('listing_qa')) { $myQ = $db->table('listing_qa q')->select('q.*, li.name AS listing_name')
            ->join('listing li', 'li.id = q.listing_id', 'left')->where('q.user_id', $uid)->orderBy('q.id', 'DESC')->get()->getResultArray(); }
        if ($db->tableExists('discover_enquiry')) $myEnq = $db->table('discover_enquiry e')->select('e.*, li.name AS listing_name')->join('listing li', 'li.id = e.listing_id', 'left')->where('e.user_id', $uid)->orderBy('e.id', 'DESC')->get()->getResultArray();
        if ($db->tableExists('review')) $myReviews = $db->table('review r')->select('r.*, r.' . hi_review_pk($db) . ' AS id, li.name AS listing_name')->join('listing li', 'li.id = r.listing_id', 'left')->where('r.reviewer_id', $uid)->orderBy('r.' . hi_review_pk($db), 'DESC')->get()->getResultArray();
        // per-listing insights (views, enquiries, bookings, reviews, rating)
        // Per-listing insights. This used to run five queries for every listing
        // the account owns, on every dashboard page — one account here owns
        // 1,057 listings, so the page fired over five thousand queries and hung
        // rather than loading. Each metric is now one grouped query instead.
        $insights = []; $totalViews = 0;
        if ($ids) {
            $grouped = static function (string $table, string $sel, array $extra = []) use ($db, $ids): array {
                if (! $db->tableExists($table)) { return []; }
                $q = $db->table($table)->select('listing_id, ' . $sel)->whereIn('listing_id', $ids);
                foreach ($extra as $col => $vals) { $q->whereIn($col, $vals); }

                return $q->groupBy('listing_id')->get()->getResultArray();
            };
            $pv   = hi_pv_col($db);
            $acts = ['phone'=>0,'whatsapp'=>0,'direction'=>0,'website'=>0];

            $viewsBy = $enqBy = $bokBy = $revBy = $actsBy = [];
            // 'profile' is what the original site wrote before the rename — both mean a page view
            foreach ($grouped('profile_views', 'COUNT(*) c', [$pv => ['page','profile']]) as $r) {
                $viewsBy[(int) $r['listing_id']] = (int) $r['c'];
            }
            // what customers actually did, not just looked at
            if ($db->tableExists('profile_views')) {
                foreach ($db->table('profile_views')->select('listing_id, ' . $pv . ' AS view_type, COUNT(*) c')
                    ->whereIn('listing_id', $ids)->whereIn($pv, array_keys($acts))
                    ->groupBy('listing_id')->groupBy($pv)->get()->getResultArray() as $a) {
                    $actsBy[(int) $a['listing_id']][$a['view_type']] = (int) $a['c'];
                }
            }
            foreach ($grouped('discover_enquiry', 'COUNT(*) c') as $r) { $enqBy[(int) $r['listing_id']] = (int) $r['c']; }
            foreach ($grouped('booking', 'COUNT(*) c') as $r)          { $bokBy[(int) $r['listing_id']] = (int) $r['c']; }
            foreach ($grouped('review', 'AVG(review_rating) a, COUNT(*) c') as $r) {
                $revBy[(int) $r['listing_id']] = ['a' => (float) $r['a'], 'c' => (int) $r['c']];
            }

            foreach ($mine as $l) {
                $lid    = (int) $l['id'];
                $views  = $viewsBy[$lid] ?? 0;
                $totalViews += $views;
                $insights[] = ['id'=>$lid,'name'=>$l['name'],'status'=>$l['status']??'pending','views'=>$views,
                    'enquiries'=>$enqBy[$lid] ?? 0,'bookings'=>$bokBy[$lid] ?? 0,
                    'reviews'=>(int) ($revBy[$lid]['c'] ?? 0),'rating'=>round((float) ($revBy[$lid]['a'] ?? 0), 1),
                    'acts'=>($actsBy[$lid] ?? []) + $acts];
            }
        }

        $wl = json_decode($me['wishlists'] ?? '[]', true);
        if (is_array($wl) && $wl) { $saved = $db->table('listing li')->select('li.*, l.name AS locality_name')->join('locality l', 'l.id = li.locality_id', 'left')->whereIn('li.id', array_map('intval', $wl))->get()->getResultArray(); }

        // "My Listings" means shopfronts; a dealer's flats get their own screen,
        // or twenty properties bury the one business they belong to.
        helper('property');
        $myProps = array_values(array_filter($mine, static fn ($r) => hi_is_property($r)));
        $mineBiz = array_values(array_filter($mine, static fn ($r) => ! hi_is_property($r)));

        return [
            'db' => $db, 'me' => $me, 'mine' => $mineBiz, 'mineAll' => $mine, 'myProps' => $myProps,
            'enq' => $enq, 'bok' => $bok, 'rev' => $rev,
            'insights' => $insights, 'totalViews' => $totalViews, 'qa' => $qa,
            'qaOpen' => count(array_filter($qa, static fn ($x) => empty($x['answer']))),
            'leadPipe' => (function () use ($enq, $bok) {
                $all = array_merge($enq, $bok); $p = ['new'=>0,'contacted'=>0,'closed'=>0];
                foreach ($all as $x) { $s = $x['status'] ?? 'new'; $p[$s] = ($p[$s] ?? 0) + 1; }
                return $p;
            })(),
            'myBookings' => $myBookings, 'myEnq' => $myEnq, 'myReviews' => $myReviews, 'myQ' => $myQ, 'saved' => $saved,
            'isBusiness' => (int) ($me['role_id'] ?? 3) === 2,
            'verified' => (int) ($me['is_verified'] ?? 1) === 1,
            'approved' => (int) ($me['approved'] ?? 1) === 1,
            'stats' => [
                'listings' => count($mine),
                'active' => count(array_filter($mine, static fn ($l) => ($l['status'] ?? '') === 'active')),
                'enquiries' => count($enq), 'bookings' => count($bok),
                'reviews' => $rcount, 'rating' => $ravg,
                'leads' => count($enq) + count($bok),
            ],
        ];
    }

    public function index(){ return $this->section('overview'); }

    public function section(string $sec = 'overview')
    {
        if (! $this->uid()) { return redirect()->to('/login'); }
        $sec = in_array($sec, ['overview','listings','properties','leads','reviews','questions','profile','insights','bookings','enquiries','myreviews','myquestions','saved','billing','requests','activity','offers'], true) ? $sec : 'overview';
        $data = $this->base();
        // business = owns listings OR role 2; else treat as customer
        $data['mode'] = ($data['isBusiness'] || count($data['mineAll'] ?? $data['mine']) > 0) ? 'business' : 'customer';
        $data['sec'] = $sec;
        $data['title'] = ucfirst($sec) . ' | My HiNoida';
        $data['name'] = session()->get('uname');
        $data['flash'] = session()->getFlashdata('flash');
        $data['notice'] = session()->getFlashdata('saved');
        $data['err'] = session()->getFlashdata('err');
        if ($sec === 'offers') {
            $db = \Config\Database::connect();
            hi_ensure_tables($db);   // live has no `offer` table — owner offers need it
            $names = array_map(static fn ($l) => (string) $l['name'], $data['mine']);
            $data['offers'] = ($names && $db->tableExists('offer'))
                ? $db->table('offer')->whereIn('business', $names)->orderBy('id', 'DESC')->get()->getResultArray()
                : [];
            $eid = (int) $this->request->getGet('edit');
            $data['offerEdit'] = null;
            if ($eid) {
                $row = $db->table('offer')->where('id', $eid)->get()->getRowArray();
                // only their own
                if ($row && in_array((string) $row['business'], $names, true)) { $data['offerEdit'] = $row; }
            }
        }
        if ($sec === 'activity') {
            // Dashboard::activity — what visitors did on this owner's listings.
            // The original filtered on pv.user_id, which is who *did* the action,
            // so an owner saw their own browsing rather than their customers'.
            $db  = \Config\Database::connect();
            $ids = array_map(static fn ($r) => (int) $r['id'], $data['mine']);
            $data['activity'] = [];
            if ($ids && $db->tableExists('profile_views')) {
                $data['activity'] = $db->table('profile_views v')
                    ->select('v.*, li.name AS listing_name, u.name AS who')
                    ->join('listing li', 'li.id = v.listing_id', 'left')
                    ->join('user u', 'u.id = v.user_id', 'left')
                    ->whereIn('v.listing_id', $ids)
                    ->orderBy('v.id', 'DESC')->limit(200)->get()->getResultArray();
            }
        }
        if ($sec === 'requests') {
            $db = \Config\Database::connect();
            self::ensureRequests($db);
            // `request` is keyed on id. This used to ask for the *review*
            // table's key, which is review_id on live, so the SQL failed and
            // the page 500'd rather than showing the owner their requests.
            $data['requests'] = $db->table('request r')->select('r.*, li.name AS listing_name')
                ->join('listing li', 'li.id = r.listing_id', 'left')
                ->where('r.created_by', $this->uid())->orderBy('r.id', 'DESC')->get()->getResultArray();
            // Admin answers are threaded in `reply` on live (the single
            // admin_reply column only exists on installs we created), so pull
            // them in or the owner never sees the answer.
            if ($db->tableExists('reply') && $data['requests']) {
                $ids   = array_map(static fn ($x) => (int) $x['id'], $data['requests']);
                $byReq = [];
                foreach ($db->table('reply p')->select('p.*, u.name AS by_name')
                    ->join('user u', 'u.id = p.created_by', 'left')
                    ->whereIn('p.request_id', $ids)->orderBy('p.id', 'ASC')
                    ->get()->getResultArray() as $p) { $byReq[(int) $p['request_id']][] = $p; }
                foreach ($data['requests'] as &$_r) { $_r['_replies'] = $byReq[(int) $_r['id']] ?? []; }
                unset($_r);
            }
        }
        if ($sec === 'billing') {
            $db = \Config\Database::connect();
            $data['payments'] = $db->tableExists('package_purchased_history')
                ? $db->table('package_purchased_history h')->select('h.*, p.name AS package_name, p.number_of_listings, p.featured')
                    ->join('package p', 'p.id = h.package_id', 'left')
                    ->where('h.user_id', $this->uid())->orderBy('h.purchase_date', 'DESC')->get()->getResultArray()
                : [];
            $data['plan'] = hi_plan($this->uid());
            $data['days'] = hi_plan_expiry($this->uid());
        }
        if ($data['mode'] === 'customer') {
            $data['title'] = ucfirst($sec) . ' | My HiNoida Account';
            $data['metaDescription'] = 'Your HiNoida account — bookings, enquiries, reviews and saved places.';
            $data['robots'] = 'noindex,nofollow';
            return view('frontend/account', $data);
        }
        return view('frontend/portal', $data);
    }

    /**
     * Change requests — ported from User::request_profile_change / requests /
     * reply and Admin::request / request_action.
     *
     * An owner asks for something they cannot change themselves (a name
     * correction, a category move, a merge); an admin replies and closes it.
     */
    public static function ensureRequests($db): void
    {
        if ($db->tableExists('request')) { return; }
        $inc = $db->DBDriver === 'MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $db->query('CREATE TABLE request (id INTEGER PRIMARY KEY ' . $inc . ',
            listing_id INT, req_message TEXT, created_at DATETIME, created_by INT,
            status VARCHAR(20) DEFAULT \'open\', admin_reply TEXT, replied_at DATETIME)');
    }

    public function requestSend()
    {
        if (! $this->uid()) { return redirect()->to('/login'); }

        $db  = \Config\Database::connect();
        self::ensureRequests($db);

        $lid = (int) $this->request->getPost('listing_id');
        $msg = trim((string) $this->request->getPost('req_message'));

        if ($lid && ! $this->ownsListing($lid)) { return redirect()->to('/dashboard/requests'); }
        if (mb_strlen($msg) < 10) {
            session()->setFlashdata('err', 'Tell us what you need changed, in at least a few words.');
            return redirect()->to('/dashboard/requests');
        }

        $db->table('request')->insert([
            'listing_id'  => $lid ?: null,
            'req_message' => $msg,
            'created_at'  => date('Y-m-d H:i:s'),
            'created_by'  => $this->uid(),
            'status'      => 'open',
        ]);

        $admin = hi_setting('system_email');
        if ($admin) {
            (new \App\Libraries\Mailer())->queue($admin, 'HiNoida admin', 'Change request from a business owner',
                '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;color:#33405a">'
                . '<h2 style="color:#101a2c">New change request</h2>'
                . '<p style="padding:12px 15px;background:#f5f7fb;border-radius:10px">' . esc($msg) . '</p>'
                . '<p><a href="' . base_url('admin/requests') . '">Open it in the admin console</a></p></div>');
        }

        session()->setFlashdata('flash', 'Sent — we will get back to you here.');
        return redirect()->to('/dashboard/requests');
    }

    /**
     * Bulk import for a business owner — ported from User::import_excel /
     * User_model::listing_import.
     *
     * The original took a positional CSV and did not check the owner's plan,
     * so a free account could upload as many listings as it liked. This one
     * stops at the allowance and says how many it took.
     */
    public function importListings()
    {
        if (! $this->uid()) { return redirect()->to('/login'); }

        $file = $this->request->getFile('csv');
        if (! $file || ! $file->isValid()) {
            session()->setFlashdata('err', 'Choose a CSV file to import.');
            return redirect()->to('/dashboard/listings');
        }
        if (! in_array(strtolower((string) $file->getClientExtension()), ['csv', 'txt'], true)) {
            session()->setFlashdata('err', 'That is not a CSV file.');
            return redirect()->to('/dashboard/listings');
        }

        $db    = \Config\Database::connect();
        $uid   = $this->uid();
        $role  = (int) session()->get('role');
        $limit = hi_plan_limit($uid);
        $room  = $role === 1 || $limit <= 0 ? PHP_INT_MAX : max(0, $limit - hi_listing_count($uid));

        if ($room <= 0) {
            session()->setFlashdata('err', 'Your plan is already full — upgrade before importing.');
            return redirect()->to(hi_page('pricing'));
        }

        $fh = fopen($file->getTempName(), 'r');
        if (! $fh) {
            session()->setFlashdata('err', 'The file could not be read.');
            return redirect()->to('/dashboard/listings');
        }

        $head = fgetcsv($fh);
        if (! $head) {
            fclose($fh);
            session()->setFlashdata('err', 'The file is empty.');
            return redirect()->to('/dashboard/listings');
        }
        $head[0] = preg_replace('/^\x{FEFF}/u', '', (string) $head[0]);
        $head    = array_map(static fn ($h) => strtolower(trim((string) $h)), $head);

        $locs = []; foreach ($db->table('locality')->select('id, name')->get()->getResultArray() as $l) { $locs[strtolower($l['name'])] = (int) $l['id']; }
        $cats = []; foreach ($db->table('category')->select('id, name')->get()->getResultArray() as $c) { $cats[strtolower($c['name'])] = (int) $c['id']; }

        $added = 0; $skipped = 0; $stopped = false; $line = 1; $problems = [];
        while (($r = fgetcsv($fh)) !== false) {
            $line++;
            if ($added >= $room) { $stopped = true; break; }

            $row = [];
            foreach ($head as $i => $h) { $row[$h] = isset($r[$i]) ? trim((string) $r[$i]) : ''; }

            $name = $row['name'] ?? '';
            if ($name === '') { $skipped++; continue; }

            $d = ['name' => $name, 'user_id' => $uid, 'status' => 'pending'];
            foreach (['phone','email','address','website','description'] as $k) {
                if (($row[$k] ?? '') !== '') { $d[$k] = $row[$k]; }
            }
            if (($row['locality'] ?? '') !== '') {
                $key = strtolower($row['locality']);
                if (isset($locs[$key])) { $d['locality_id'] = $locs[$key]; }
                elseif (count($problems) < 5) { $problems[] = "line {$line}: unknown area “{$row['locality']}”"; }
            }
            if (($row['category'] ?? '') !== '') {
                $key = strtolower($row['category']);
                if (isset($cats[$key])) { $d['categories'] = json_encode([(string) $cats[$key]]); }
                elseif (count($problems) < 5) { $problems[] = "line {$line}: unknown category “{$row['category']}”"; }
            }
            if ($db->fieldExists('listing_type', 'listing')) { $d['listing_type'] = 'general'; }

            // don't let one upload create the same place twice
            if (! empty($d['phone']) && $db->table('listing')->where('name', $name)->where('phone', $d['phone'])->where('user_id', $uid)->countAllResults()) {
                $skipped++;
                continue;
            }

            $db->table('listing')->insert($d);
            $added++;
        }
        fclose($fh);

        $msg = "Imported {$added} listing" . ($added === 1 ? '' : 's') . ' — they are pending review.';
        if ($skipped) { $msg .= " {$skipped} skipped."; }
        if ($stopped) { $msg .= ' Stopped at your plan limit of ' . $limit . '.'; }
        if ($problems) { $msg .= ' Issues: ' . implode('; ', $problems) . '.'; }

        session()->setFlashdata('flash', $msg);
        return redirect()->to('/dashboard/listings');
    }

    /**
     * An owner's special offers — backend/user/special_offer_form.php.
     * Offers are matched to a listing by business name, which is how the
     * offer table stores the link.
     */
    public function saveOffer()
    {
        if (! $this->uid()) { return redirect()->to('/login'); }

        $db    = \Config\Database::connect();
        $names = array_map(static fn ($l) => (string) $l['name'],
            $db->table('listing')->select('name')->where('user_id', $this->uid())->get()->getResultArray());
        if (! $names) { return redirect()->to('/dashboard/listings'); }

        $id  = (int) $this->request->getPost('id');
        $act = (string) $this->request->getPost('act');

        // an id that is not theirs is not theirs to touch
        if ($id > 0) {
            $own = $db->table('offer')->where('id', $id)->get()->getRowArray();
            if (! $own || ! in_array((string) $own['business'], $names, true)) {
                return redirect()->to('/dashboard/offers');
            }
        }

        if ($act === 'delete') {
            $db->table('offer')->where('id', $id)->delete();
            session()->setFlashdata('flash', 'Offer removed.');
            return redirect()->to('/dashboard/offers');
        }

        $biz = trim((string) $this->request->getPost('business'));
        if (! in_array($biz, $names, true)) {
            session()->setFlashdata('err', 'Choose one of your own businesses.');
            return redirect()->to('/dashboard/offers');
        }

        $title = trim((string) $this->request->getPost('title'));
        if ($title === '') {
            session()->setFlashdata('err', 'Give the offer a title.');
            return redirect()->to('/dashboard/offers');
        }

        $till = trim((string) $this->request->getPost('valid_till'));
        if ($till !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $till)) { $till = ''; }

        $d = [
            'title'       => $title,
            'slug'        => trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($title)), '-') . '-' . substr(md5($title . $biz), 0, 5),
            'description' => trim((string) $this->request->getPost('description')),
            'business'    => $biz,
            'discount'    => trim((string) $this->request->getPost('discount')),
            'code'        => trim((string) $this->request->getPost('code')),
            'valid_till'  => $till,
            // an owner's offer waits for review, same as a new listing
            'status'      => 'pending',
        ];

        if ($id > 0) {
            unset($d['slug']);              // keep the URL stable once published
            $db->table('offer')->where('id', $id)->update($d);
            session()->setFlashdata('flash', 'Offer updated — it goes back for review.');
        } else {
            $db->table('offer')->insert($d);
            session()->setFlashdata('flash', 'Offer sent for review. It appears once approved.');
        }

        return redirect()->to('/dashboard/offers');
    }
    /** A printable invoice for one payment. Only the buyer may open it. */
    public function invoice(int $id)
    {
        if (! $this->uid()) { return redirect()->to('/login'); }

        $db = \Config\Database::connect();
        if (! $db->tableExists('package_purchased_history')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $p = $db->table('package_purchased_history h')
            ->select('h.*, p.name AS package_name, p.number_of_listings, p.featured, p.validity')
            ->join('package p', 'p.id = h.package_id', 'left')
            ->where('h.id', $id)->get()->getRowArray();

        // an admin can pull any invoice up; everyone else only their own
        if (! $p || ((int) $p['user_id'] !== $this->uid() && (int) session()->get('role') !== 1)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $buyer = $db->table('user')->select('name, email, phone, address')
            ->where('id', (int) $p['user_id'])->get()->getRowArray() ?: [];

        return view('frontend/invoice', [
            'title'   => 'Invoice HN-' . str_pad((string) $p['id'], 5, '0', STR_PAD_LEFT) . ' | HiNoida',
            'robots'  => 'noindex,nofollow',
            'p'       => $p,
            'buyer'   => $buyer,
            'invNo'   => 'HN-' . str_pad((string) $p['id'], 5, '0', STR_PAD_LEFT),
        ]);
    }

    public function saveProfile()
    {
        if (! $this->uid()) { return redirect()->to('/login'); }
        $db  = \Config\Database::connect();
        $req = $this->request;
        $me  = $db->table('user')->where('id', $this->uid())->get()->getRowArray();

        $name = trim((string) $req->getPost('name'));
        if ($name === '') {
            session()->setFlashdata('err', 'Please enter your name.');
            return redirect()->to('/dashboard/profile');
        }
        $phone = trim((string) $req->getPost('phone'));
        if ($phone !== '' && ! preg_match('~^\+?[0-9][0-9 \-]{6,19}$~', $phone)) {
            session()->setFlashdata('err', 'Please enter a valid phone number.');
            return redirect()->to('/dashboard/profile');
        }

        $upd = ['name' => $name, 'phone' => $phone, 'address' => trim((string) $req->getPost('address'))];

        // social links — keep only real URLs
        $social = [];
        foreach (['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'website'] as $net) {
            $v = trim((string) $req->getPost('social_' . $net));
            if ($v !== '' && preg_match('~^https?://~i', $v)) { $social[$net] = $v; }
        }
        $upd['social'] = json_encode($social);

        // password change requires the current password
        $new = (string) $req->getPost('password');
        if ($new !== '') {
            $cur = (string) $req->getPost('current_password');
            if (! hi_pass_check((string) $cur, (string) ($me['password'] ?? ''))) {
                session()->setFlashdata('err', 'Your current password is incorrect — password not changed.');
                return redirect()->to('/dashboard/profile');
            }
            if (strlen($new) < 6) {
                session()->setFlashdata('err', 'Your new password must be at least 6 characters.');
                return redirect()->to('/dashboard/profile');
            }
            if ($new !== (string) $req->getPost('password_confirm')) {
                session()->setFlashdata('err', 'The two new passwords do not match.');
                return redirect()->to('/dashboard/profile');
            }
            $upd['password'] = hi_pass_hash((string) $new);
        }

        // user.updated_date is bigint(20) in production — a datetime string
        // would be truncated to "2026" on MySQL
        $upd['updated_date'] = time();
        $db->table('user')->where('id', $this->uid())->update($upd);
        session()->set('uname', $name);
        session()->setFlashdata('saved', isset($upd['password']) ? 'Profile and password updated.' : 'Profile updated.');
        return redirect()->to('/dashboard/profile');
    }

    /** Update a lead's status — only if it belongs to one of my listings. */
    public function leadStatus(string $type, int $id, string $status){
        if (! $this->uid()) { return redirect()->to('/login'); }
        // Production data already uses pending/approved, so those must be
        // settable — a narrower whitelist made real bookings unmanageable.
        $status = in_array($status, ['new','pending','contacted','approved','rejected','closed'], true) ? $status : 'new';
        $table = $type === 'booking' ? 'booking' : 'discover_enquiry';
        $db = \Config\Database::connect();
        if ($db->tableExists($table)) {
            $myIds = array_map(static fn ($r) => (int) $r['id'], $db->table('listing')->select('id')->where('user_id', $this->uid())->get()->getResultArray());
            $row = $db->table($table)->where('id', $id)->get()->getRowArray();
            if ($row && in_array((int) $row['listing_id'], $myIds, true)) {
                $db->table($table)->where('id', $id)->update(['status' => $status]);
                session()->setFlashdata('saved', 'Lead marked as ' . $status . '.');
            }
        }
        return redirect()->to('/dashboard/leads');
    }

    /** Business owner replies to a review on their listing. */
    /** Business owner: answer a question asked on one of my listings. */
    public function answerQuestion(int $id){
        if (! $this->uid()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect();
        if (! $db->tableExists('listing_qa')) { return redirect()->to('/dashboard/questions'); }
        $myIds = array_map(static fn ($r) => (int) $r['id'], $db->table('listing')->select('id')->where('user_id', $this->uid())->get()->getResultArray());
        $q = $db->table('listing_qa')->where('id', $id)->get()->getRowArray();
        if (! $q || ! in_array((int) $q['listing_id'], $myIds, true)) {
            return redirect()->to('/dashboard/questions');
        }
        $ans = trim((string) $this->request->getPost('answer'));
        if (mb_strlen($ans) < 5) {
            session()->setFlashdata('err', 'Please write a proper answer (at least 5 characters).');
            return redirect()->to('/dashboard/questions');
        }
        $db->table('listing_qa')->where('id', $id)->update(['answer' => $ans]);
        session()->setFlashdata('saved', 'Answer posted — the customer can now see it.');
        return redirect()->to('/dashboard/questions');
    }

    public function replyReview(int $id){
        if (! $this->uid()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect();
        if (! $db->fieldExists('owner_reply', 'review')) { try { $db->query('ALTER TABLE review ADD COLUMN owner_reply TEXT'); } catch (\Throwable $e) {} }
        $myIds = array_map(static fn ($r) => (int) $r['id'], $db->table('listing')->select('id')->where('user_id', $this->uid())->get()->getResultArray());
        $rv = $db->table('review')->where(hi_review_pk($db), $id)->get()->getRowArray();
        if ($rv && in_array((int) $rv['listing_id'], $myIds, true)) {
            $db->table('review')->where(hi_review_pk($db), $id)->update(['owner_reply' => trim((string) $this->request->getPost('reply'))]);
            session()->setFlashdata('saved', 'Reply posted.');
        }
        return redirect()->to('/dashboard/reviews');
    }

    private function ownsListing(int $lid): bool {
        return (bool) \Config\Database::connect()->table('listing')->where('id',$lid)->where('user_id',$this->uid())->countAllResults();
    }
    /** Manage the services/catalog of one of my listings. */
    public function catalog(int $lid){
        if (! $this->uid()) { return redirect()->to('/login'); }
        if (! $this->ownsListing($lid)) { return redirect()->to('/dashboard/listings'); }
        $db = \Config\Database::connect();
        $db->query('CREATE TABLE IF NOT EXISTS service (id INTEGER PRIMARY KEY ' . ($db->DBDriver==='MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', listing_id INT, name TEXT, price TEXT, description TEXT, image TEXT, created_at DATETIME)');
        $listing = $db->table('listing')->where('id',$lid)->get()->getRowArray();
        $items = $db->table('service')->where('listing_id',$lid)->orderBy('id','ASC')->get()->getResultArray();
        return view('frontend/portal_catalog',[
            'title'=>'Services — '.$listing['name'].' | HiNoida','listing'=>$listing,'items'=>$items,
            'name'=>session()->get('uname'),'saved'=>session()->getFlashdata('saved'),
        ]);
    }
    public function catalogSave(int $lid){
        if (! $this->uid() || ! $this->ownsListing($lid)) { return redirect()->to('/dashboard/listings'); }
        $db = \Config\Database::connect(); $p=$this->request;
        $data=['listing_id'=>$lid,'name'=>trim((string)$p->getPost('name')),'price'=>trim((string)$p->getPost('price')),'description'=>trim((string)$p->getPost('description'))];
        $file=$p->getFile('image');
        if ($file && $file->isValid() && ! $file->hasMoved() && in_array($file->getExtension(),['jpg','jpeg','png','webp','gif'],true)){
            $dir=FCPATH.'uploads'.DIRECTORY_SEPARATOR.'service'.DIRECTORY_SEPARATOR.$lid; if(!is_dir($dir)) @mkdir($dir,0777,true);
            $nm=$file->getRandomName(); $file->move($dir,$nm); $data['image']='uploads/service/'.$lid.'/'.$nm;
        }
        $id=(int)$p->getPost('id');
        if ($data['name']!==''){
            if ($id>0){ $db->table('service')->where('id',$id)->where('listing_id',$lid)->update($data); }
            else { $data['created_at']=date('Y-m-d H:i:s'); $db->table('service')->insert($data); }
            session()->setFlashdata('saved','Service saved.');
        }
        return redirect()->to('/dashboard/catalog/'.$lid);
    }
    public function catalogDelete(int $lid, int $id){
        if (! $this->uid() || ! $this->ownsListing($lid)) { return redirect()->to('/dashboard/listings'); }
        \Config\Database::connect()->table('service')->where('id',$id)->where('listing_id',$lid)->delete();
        session()->setFlashdata('saved','Service removed.');
        return redirect()->to('/dashboard/catalog/'.$lid);
    }

    public function inbox(){ return redirect()->to('/dashboard/leads'); }

    /** Business owner: manage the photos on their own listing. */
    public function photos(int $lid)
    {
        if (! $this->uid()) { return redirect()->to('/login'); }
        if (! $this->ownsListing($lid)) { return redirect()->to('/dashboard/listings'); }
        $db      = \Config\Database::connect();
        $listing = $db->table('listing')->where('id', $lid)->get()->getRowArray();
        $ph      = json_decode($listing['photos'] ?? '[]', true);
        return view('frontend/portal_photos', [
            'title'   => 'Photos — ' . $listing['name'] . ' | HiNoida',
            'listing' => $listing,
            'photos'  => is_array($ph) ? $ph : [],
            'name'    => session()->get('uname'),
            'saved'   => session()->getFlashdata('saved'),
        ]);
    }

    public function photosSave(int $lid)
    {
        if (! $this->uid() || ! $this->ownsListing($lid)) { return redirect()->to('/dashboard/listings'); }
        $db   = \Config\Database::connect();
        $cur  = $db->table('listing')->where('id', $lid)->get()->getRowArray();
        $list = json_decode($cur['photos'] ?? '[]', true);
        $list = is_array($list) ? $list : [];
        $req  = $this->request;

        // unticked photos are dropped
        $keep = $req->getPost('keep');
        $list = is_array($keep) ? array_values(array_intersect($list, $keep)) : [];

        foreach (($req->getFileMultiple('photos') ?? []) as $file) {
            if ($file && $file->isValid() && ! $file->hasMoved()
                && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
                && $file->getSize() <= 5 * 1024 * 1024) {
                $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'listings' . DIRECTORY_SEPARATOR . $lid;
                if (! is_dir($dir)) { @mkdir($dir, 0777, true); }
                $nm = $file->getRandomName();
                $file->move($dir, $nm);
                $list[] = 'uploads/listings/' . $lid . '/' . $nm;
            }
        }

        // manually entered paths / URLs
        $paths = (string) $req->getPost('photo_paths');
        if (trim($paths) !== '') {
            foreach (preg_split('/[\r\n,]+/', $paths) as $x) {
                $x = trim($x);
                if ($x === '') { continue; }
                if (preg_match('~^https?://~i', $x)) { $list[] = $x; continue; }
                $x = ltrim(str_replace('\\', '/', $x), '/');
                if (str_contains($x, '..')) { continue; }
                if (! preg_match('~\.(jpe?g|png|webp|gif|svg)$~i', $x)) { continue; }
                $list[] = $x;
            }
        }

        $list = array_slice(array_values(array_unique($list)), 0, 10);
        $db->table('listing')->where('id', $lid)->update([
            'photos'        => json_encode($list),
            'listing_cover' => $list[0] ?? null,
        ]);
        session()->setFlashdata('saved', 'Photos updated.');
        return redirect()->to('/dashboard/photos/' . $lid);
    }

    /* ===================================================================
       Actions the original site had and the rewrite did not.
       =================================================================== */

    /** Business owner: delete one of their own listings. */
    public function deleteListing(int $lid)
    {
        if (! $this->uid()) { return redirect()->to('/login'); }
        if (! $this->ownsListing($lid)) { return redirect()->to('/dashboard/listings'); }

        $db   = \Config\Database::connect();
        $name = (string) ($db->table('listing')->select('name')->where('id', $lid)->get()->getRowArray()['name'] ?? 'Listing');

        // soft delete when the column exists, so nothing is lost by accident
        if ($db->fieldExists('deleted_at', 'listing')) {
            $db->table('listing')->where('id', $lid)->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'status'     => 'rejected',
            ]);
        } else {
            $db->table('listing')->where('id', $lid)->delete();
        }
        session()->setFlashdata('saved', '“' . $name . '” has been removed.');
        return redirect()->to('/dashboard/listings');
    }

    /** Business owner: accept or decline a booking, and tell the customer. */
    public function bookingDecision(int $id, string $decision)
    {
        if (! $this->uid()) { return redirect()->to('/login'); }
        $decision = $decision === 'approve' ? 'approved' : 'rejected';

        $db = \Config\Database::connect();
        if (! $db->tableExists('booking')) { return redirect()->to('/dashboard/leads'); }

        $myIds = array_map(static fn ($r) => (int) $r['id'],
            $db->table('listing')->select('id')->where('user_id', $this->uid())->get()->getResultArray());
        $bk = $db->table('booking')->where('id', $id)->get()->getRowArray();
        if (! $bk || ! in_array((int) $bk['listing_id'], $myIds, true)) {
            return redirect()->to('/dashboard/leads');
        }

        $db->table('booking')->where('id', $id)->update(['status' => $decision]);

        // let the customer know — the original emailed on approval
        $to = trim((string) ($bk['email'] ?? ''));
        if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $biz  = (string) ($db->table('listing')->select('name')->where('id', (int) $bk['listing_id'])->get()->getRowArray()['name'] ?? 'The business');
            $when = trim((string) ($bk['booking_date'] ?? '') . ' ' . (string) ($bk['booking_time'] ?? ''));
            $ok   = $decision === 'approved';
            (new \App\Libraries\Mailer())->queue($to, (string) ($bk['name'] ?? 'there'),
                ($ok ? 'Your booking is confirmed' : 'About your booking request') . ' — ' . $biz,
                '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;margin:0 auto;color:#33405a">'
                . '<h2 style="color:#101a2c">' . ($ok ? 'Booking confirmed' : 'Booking not available') . '</h2>'
                . '<p>Hi ' . esc((string) ($bk['name'] ?? 'there')) . ',</p>'
                . '<p><b>' . esc($biz) . '</b> has ' . ($ok ? 'confirmed' : 'been unable to accept') . ' your request'
                . ($when !== '' ? ' for <b>' . esc($when) . '</b>' : '') . '.</p>'
                . ($ok
                    ? '<p style="padding:12px 15px;background:#e6f6ec;border-radius:10px;color:#12653a">We look forward to seeing you.</p>'
                    : '<p style="padding:12px 15px;background:#fff3e0;border-radius:10px;color:#8a6d1a">Please try another time, or contact them directly.</p>')
                . '<p style="font-size:13px;color:#7c8aa3">Sent via HiNoida.</p></div>');
        }

        session()->setFlashdata('saved', $decision === 'approved'
            ? 'Booking confirmed — the customer has been emailed.'
            : 'Booking declined — the customer has been emailed.');
        return redirect()->to('/dashboard/leads');
    }

    /** Customer: edit their own review. */
    public function editReview(int $id)
    {
        if (! $this->uid()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect();
        $rv = $db->table('review')->where(hi_review_pk($db), $id)->get()->getRowArray();
        if (! $rv || (int) ($rv['reviewer_id'] ?? 0) !== $this->uid()) {
            return redirect()->to('/dashboard/myreviews');
        }

        $rating = (int) $this->request->getPost('rating');
        $cmt    = trim((string) $this->request->getPost('comment'));
        if ($rating < 1 || $rating > 5) {
            session()->setFlashdata('err', 'Please choose a rating between 1 and 5.');
            return redirect()->to('/dashboard/myreviews');
        }
        if (mb_strlen($cmt) < 10) {
            session()->setFlashdata('err', 'Please write at least 10 characters.');
            return redirect()->to('/dashboard/myreviews');
        }

        $db->table('review')->where(hi_review_pk($db), $id)->update([
            'review_rating'  => $rating,
            'review_comment' => $cmt,
            'timestamp'      => time(),
        ]);
        session()->setFlashdata('saved', 'Your review has been updated.');
        return redirect()->to('/dashboard/myreviews');
    }

    /** Customer: delete their own review. */
    public function deleteReview(int $id)
    {
        if (! $this->uid()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect();
        $rv = $db->table('review')->where(hi_review_pk($db), $id)->get()->getRowArray();
        if ($rv && (int) ($rv['reviewer_id'] ?? 0) === $this->uid()) {
            $db->table('review')->where(hi_review_pk($db), $id)->delete();
            session()->setFlashdata('saved', 'Your review has been deleted.');
        }
        return redirect()->to('/dashboard/myreviews');
    }
}
