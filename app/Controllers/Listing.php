<?php

namespace App\Controllers;

use App\Models\ListingModel;

class Listing extends BaseController
{
    public function show(int $id): string
    {
        $db  = \Config\Database::connect();
        $row = $db->table('listing li')
            ->select('li.*, l.name AS locality_name, l.slug AS locality_slug')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->where('li.id', $id)
            ->where('li.status', 'active')
            ->get()->getRowArray();

        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // track a profile view (skip the owner viewing their own)
        if ($db->tableExists('profile_views') && (int) session()->get('uid') !== (int) ($row['user_id'] ?? 0)) {
            $db->table('profile_views')->insert(['listing_id' => $id, 'user_id' => (int) session()->get('uid'), 'ip' => (string) $this->request->getIPAddress(), hi_pv_col($db) => 'profile', 'created_at' => date('Y-m-d H:i:s')]);
        }

        // rating
        $rating = ['avg' => 0, 'count' => 0];
        if ($db->tableExists('review')) {
            // reviews an admin hid are gone from the page and from the average
            $vis = static function ($q) use ($db) {
                if ($db->fieldExists('is_hidden', 'review')) { $q->groupStart()->where('is_hidden', 0)->orWhere('is_hidden', null)->groupEnd(); }
                return $q;
            };
            $r = $vis($db->table('review')->select('AVG(review_rating) avg, COUNT(*) c')->where('listing_id', $id))->get()->getRowArray();
            $rating = ['avg' => round((float) ($r['avg'] ?? 0), 1), 'count' => (int) ($r['c'] ?? 0)];
            $reviews = $vis($db->table('review')->where('listing_id', $id))->orderBy(hi_review_pk($db), 'DESC')->limit(8)->get()->getResultArray();
        }

        // amenities
        $amen = [];
        $aids = json_decode($row['amenities'] ?? '', true);
        if (is_array($aids) && $aids && $db->tableExists('amenities')) {
            $amen = $db->table('amenities')->whereIn('id', array_map('intval', $aids))->get()->getResultArray();
        }

        // similar (same first category) + category names for tags/breadcrumb
        $similar = [];
        $catNames = [];
        $cids = json_decode($row['categories'] ?? '', true);
        if (hi_is_property($row) && $db->tableExists('listing_property')) {
            // "Similar" for a flat means comparable flats — same kind, same
            // deal, nearby — not the dealers filed under the same category.
            $me = hi_property_of($id);
            $sq = $db->table('listing li')->select('li.*, l.name AS locality_name')
                ->join('locality l', 'l.id = li.locality_id', 'left')
                ->join('listing_property lp', 'lp.listing_id = li.id')
                ->where('li.status', 'active')->where('li.id !=', $id)
                ->where('li.listing_type', 'property');
            if (! empty($me['kind'])) { $sq->where('lp.kind', $me['kind']); }
            if (! empty($me['deal'])) { $sq->where('lp.deal', $me['deal']); }
            // closest by price first, so the row is genuinely comparable
            if (! empty($me['price'])) {
                $sq->orderBy('ABS(lp.price - ' . (float) $me['price'] . ')', 'ASC', false);
            }
            $similar = $sq->limit(4)->get()->getResultArray();
            // nothing that close? widen to any property of the same kind
            if (! $similar) {
                $similar = $db->table('listing li')->select('li.*, l.name AS locality_name')
                    ->join('locality l', 'l.id = li.locality_id', 'left')
                    ->where('li.status', 'active')->where('li.id !=', $id)
                    ->where('li.listing_type', 'property')
                    ->orderBy('li.id', 'DESC')->limit(4)->get()->getResultArray();
            }
            $cn = $db->table('category')->select('name, slug')->whereIn('id', array_map('intval', (array) $cids))->get()->getResultArray();
            $catNames = $cn;
        } elseif (is_array($cids) && $cids) {
            $similar = (new ListingModel())->inCategory((int) $cids[0], 4);
            $cn = $db->table('category')->select('name, slug')->whereIn('id', array_map('intval', $cids))->get()->getResultArray();
            $catNames = $cn;
        }
        // drives category-matched imagery + category-specific facts.
        // prefer a category that resolves to a real group; else its parent.
        helper('facts');
        $row['_cat_name'] = $catNames[0]['name'] ?? '';
        foreach ($catNames as $cn) {
            if (hi_group_key_or_blank($cn['name']) !== '') { $row['_cat_name'] = $cn['name']; break; }
        }
        if (hi_group_key_or_blank($row['_cat_name']) === '' && is_array($cids) && $cids) {
            $par = $db->table('category c')->select('p.name AS pn')
                ->join('category p', 'p.id = c.parent', 'left')
                ->whereIn('c.id', array_map('intval', $cids))->get()->getResultArray();
            foreach ($par as $p) {
                if (! empty($p['pn']) && hi_group_key_or_blank($p['pn']) !== '') { $row['_cat_name'] = $p['pn']; break; }
            }
        }

        // social links (decode + keep only real urls)
        $social = json_decode($row['social'] ?? '', true);
        $social = is_array($social) ? array_filter($social, static fn ($v) => is_string($v) && $v !== '' && $v !== '#') : [];

        // opening hours — per day when the owner set them, otherwise one pair for the week
        $week    = hi_hours($row);
        $todayHr = $week[hi_today_key()] ?? null;
        $hours   = null;
        if ($todayHr) {
            $hours = [
                'open'    => hi_hhmm($todayHr[0][0]),
                'close'   => hi_hhmm($todayHr[count($todayHr) - 1][1]),
                'label'   => hi_hours_label($todayHr),
                'is_open' => hi_is_open($row),
            ];
        }
        $sameAllWeek = hi_hours_same_all_week($week);


        // rating distribution (5★ → 1★) for the review-breakdown bars
        $ratingDist = [5=>0,4=>0,3=>0,2=>0,1=>0];
        if ($db->tableExists('review')) {
            $db_ = $db->table('review')->select('review_rating, COUNT(*) c')->where('listing_id', $id);
            if ($db->fieldExists('is_hidden', 'review')) { $db_->groupStart()->where('is_hidden', 0)->orWhere('is_hidden', null)->groupEnd(); }
            foreach ($db_->groupBy('review_rating')->get()->getResultArray() as $rr) {
                $star = max(1, min(5, (int) $rr['review_rating']));
                $ratingDist[$star] = (int) $rr['c'];
            }
        }

        // established / listed-since year
        $established = ! empty($row['date_added']) ? date('Y', strtotime($row['date_added'])) : null;

        // catalog / services with prices
        $catalog = $db->tableExists('service') ? $db->table('service')->where('listing_id', $id)->orderBy('id', 'ASC')->get()->getResultArray() : [];

        // community Q&A
        if (! $db->tableExists('listing_qa')) {
            $db->query('CREATE TABLE listing_qa (id INTEGER PRIMARY KEY ' . ($db->DBDriver==='MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', listing_id INT, user_id INT, name VARCHAR(120), question TEXT, answer TEXT, created_at DATETIME)');
        }
        $qa = $db->table('listing_qa')->where('listing_id', $id)->orderBy('id', 'DESC')->limit(10)->get()->getResultArray();

        // A dealer or builder is a business listing; the flats they posted are
        // property listings under the same account. This is where the two meet.
        $theirProps = [];
        if (! empty($row['user_id']) && ! hi_is_property($row) && $db->tableExists('listing_property')) {
            $theirProps = $db->table('listing li')->select('li.*, l.name AS locality_name')
                ->join('locality l', 'l.id = li.locality_id', 'left')
                ->where('li.user_id', (int) $row['user_id'])
                ->where('li.status', 'active')->where('li.listing_type', 'property')
                ->orderBy('li.id', 'DESC')->limit(6)->get()->getResultArray();
        }

        // also nearby (same locality, excluding self)
        $nearby = [];
        if (! empty($row['locality_id'])) {
            $nearby = $db->table('listing')->select('listing.*')->where('locality_id', (int) $row['locality_id'])
                ->where('status', 'active')->where('id !=', $id)->orderBy('is_featured', 'DESC')
                ->limit(4)->get()->getResultArray();
            $nearby = (new ListingModel())->attachRatings($nearby);
        }

        // SEO/AEO: build FAQ Q&A from the real data (also emitted as FAQPage JSON-LD)
        $bn = $row['name'];
        $area = $row['locality_name'] ?: 'Noida';
        $catLabel = $catNames[0]['name'] ?? 'business';
        $faqs = [];
        $faqs[] = ["Where is {$bn} located?", "{$bn} is located at " . ($addr = ($row['address'] ?? '')) . ($addr ? ', ' : '') . "{$area}, Noida, Uttar Pradesh."];
        if (! empty($row['phone'])) $faqs[] = ["What is the phone number of {$bn}?", "You can contact {$bn} on {$row['phone']}. You can also send an enquiry or booking request directly from this page."];
        if ($hours && ! hi_is_property($row)) $faqs[] = ["What are the timings of {$bn}?", "{$bn} is generally open from {$hours['open']} to {$hours['close']}. Timings may vary on public holidays."];
        $faqs[] = ["Is {$bn} good for " . strtolower($catLabel) . " in {$area}?", "{$bn} is a listed " . strtolower($catLabel) . " in {$area}, Noida" . ($rating['count'] ? ", rated {$rating['avg']} out of 5 by {$rating['count']} users." : ". Be the first to review it.")];
        if (! empty($row['website'])) $faqs[] = ["Does {$bn} have a website?", "Yes, you can visit {$bn} online at {$row['website']}."];
        $faqs[] = ["How do I book or enquire at {$bn}?", "Use the booking form on this page to send a request directly to {$bn}" . (! empty($row['phone']) ? ", or call them on {$row['phone']}" : '') . ". You'll need to be logged in to HiNoida so they can reply to you."];
        $faqs[] = ["What payment methods does {$bn} accept?", "{$bn} generally accepts cash, UPI and major debit/credit cards. We recommend confirming with the business before visiting."];
        if ($amen) $faqs[] = ["What facilities are available at {$bn}?", "{$bn} offers " . count($amen) . " listed amenities including " . implode(', ', array_slice(array_column($amen, 'name'), 0, 4)) . "."];
        if (! empty($catalog)) $faqs[] = ["What services does {$bn} offer and at what price?", "{$bn} lists " . count($catalog) . " services with pricing on this page, starting with " . esc($catalog[0]['name']) . (! empty($catalog[0]['price']) ? " at {$catalog[0]['price']}" : '') . "."];
        // a flat is not "open today" — that question only fits a business
        if (! hi_is_property($row)) $faqs[] = ["Is {$bn} open today?", $hours ? "{$bn} is normally open every day from {$hours['open']} to {$hours['close']}. The live open/closed status is shown at the top of this page." : "Opening hours for {$bn} are available on request — contact them directly to confirm."];
        $faqs[] = ["How do I reach {$bn} in {$area}?", "{$bn} is in {$area}, Noida" . (! empty($row['address']) ? " at {$row['address']}" : '') . ". Use the map and 'Get directions' button on this page for turn-by-turn navigation."];
        $faqs[] = ["Can I leave a review for {$bn}?", "Yes. Log in to your HiNoida account and use the review form on this page to rate {$bn} out of 5 and share your experience with other customers in Noida."];

        $metaDescription = "{$bn} in {$area}, Noida — " . strtolower($catLabel) . "."
            . ($row['phone'] ? " Call {$row['phone']}." : '')
            . ($rating['count'] ? " Rated {$rating['avg']}★ ({$rating['count']} reviews)." : '')
            . ($hours ? " Open {$hours['open']}–{$hours['close']}." : '')
            . " Address, contact, photos, reviews & directions on HiNoida.";

        return view('frontend/listing', [
            // an owner-written title/description wins over the generated one
            'title'    => trim((string) ($row['seo_title'] ?? '')) !== ''
                ? $row['seo_title'] . ' | HiNoida'
                : $row['name'] . ' in ' . $area . ', Noida — ' . $catLabel . ' | HiNoida',
            'metaDescription' => trim((string) ($row['meta_description'] ?? '')) !== ''
                ? $row['meta_description']
                : $metaDescription,
            'metaKeywords' => trim((string) ($row['tags'] ?? '')),
            // the id is what identifies the listing, so a link carrying an old
            // name in its slug still opens the page — this points search engines
            // at the one spelling that counts
            'canonical' => hi_listing_url($row),
            'faqs'     => $faqs,
            'l'        => $row,
            'rating'   => $rating,
            // how many people have looked at this place, from the view log
            'views'    => (static function () use ($db, $id): int {
                if (! $db->tableExists('profile_views')) { return 0; }

                return (int) $db->table('profile_views')->where('listing_id', $id)->countAllResults();
            })(),
            'reviews'  => $reviews ?? [],
            'amenities'=> $amen,
            'similar'  => $similar,
            'nearby'   => $nearby,
            'theirProps' => $theirProps,
            'qa'       => $qa,
            'catalog'  => $catalog,
            'ratingDist' => $ratingDist,
            'established' => $established,
            'catNames' => $catNames,
            'primaryCat' => $catNames[0] ?? null,
            'social'   => $social,
            'hours'    => $hours,
            'week'     => $week,
            'sameAllWeek' => $sameAllWeek,
            'verified' => ! empty($row['is_featured']),
            'isSaved'  => (function () use ($db, $id) {
                if (! session()->get('uid')) return false;
                $u = $db->table('user')->select('wishlists')->where('id', (int) session()->get('uid'))->get()->getRowArray();
                $w = json_decode($u['wishlists'] ?? '[]', true);
                return is_array($w) && in_array($id, array_map('intval', $w), true);
            })(),
            'flash'    => session()->getFlashdata('flash'),
            'err'      => session()->getFlashdata('err'),
        ]);
    }

    public function enquiry(int $id)
    {
        if (! hi_addon('bookings')) { return redirect()->to(hi_listing_url($id)); }
        // Asking a question should not cost an account. Someone arriving from
        // Google, interested in one flat, will not sign up to send one message —
        // they leave, and the owner never hears from them. Name and phone are
        // enough; user_id is simply 0 when they are not signed in.
        $db = \Config\Database::connect();
        if (! $db->tableExists('discover_enquiry')) {
            $db->query('CREATE TABLE discover_enquiry (id INTEGER PRIMARY KEY ' . ($db->DBDriver==='MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', listing_id INT, user_id INT, name VARCHAR(120), phone VARCHAR(40), email VARCHAR(160), message TEXT, created_at DATETIME)');
        }
        $eName  = trim((string) $this->request->getPost('name'));
        $eMsg   = trim((string) $this->request->getPost('message'));
        $ePhone = trim((string) $this->request->getPost('phone'));
        $eMail  = trim((string) $this->request->getPost('email'));
        if ($eName === '' || $eMsg === '') {
            session()->setFlashdata('err', 'Please enter your name and a short message.');
            return redirect()->to(hi_listing_url($id) . '#enquiry');
        }
        // a lead with no way back to the sender is not a lead
        if (! session()->get('uid') && $ePhone === '' && $eMail === '') {
            session()->setFlashdata('err', 'Please leave a phone number or email so they can reply.');
            return redirect()->to(hi_listing_url($id) . '#enquiry');
        }
        if ($ePhone !== '' && ! hi_valid_phone($ePhone)) {
            session()->setFlashdata('err', 'Please enter a valid phone number.');
            return redirect()->to(hi_listing_url($id) . '#enquiry');
        }
        $db->table('discover_enquiry')->insert([
            'listing_id' => $id, 'user_id' => (int) session()->get('uid'),
            'name' => trim((string) $this->request->getPost('name')), 'phone' => trim((string) $this->request->getPost('phone')),
            'email' => trim((string) $this->request->getPost('email')), 'message' => trim((string) $this->request->getPost('message')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->notifyOwner($db, $id, 'enquiry', $eName, $ePhone, $eMail, $eMsg);
        session()->setFlashdata('flash', 'Enquiry sent — the business will get back to you.');
        return redirect()->to(hi_listing_url($id) . '#enquiry');
    }

    public function book(int $id)
    {
        if (! hi_addon('bookings')) { return redirect()->to(hi_listing_url($id)); }
        // Same here: a site visit is a lead, and a login wall loses it.
        $db = \Config\Database::connect();
        if (! $db->tableExists('booking')) {
            $db->query('CREATE TABLE booking (id INTEGER PRIMARY KEY ' . ($db->DBDriver==='MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', listing_id INT, user_id INT, booking_type VARCHAR(30), name VARCHAR(120), phone VARCHAR(40), email VARCHAR(160), booking_date VARCHAR(40), booking_time VARCHAR(40), guests VARCHAR(20), message TEXT, status VARCHAR(20) DEFAULT \'pending\', created_at DATETIME)');
        }
        // server-side validation — never accept an empty request
        $bName  = trim((string) $this->request->getPost('name'));
        $bPhone = trim((string) $this->request->getPost('phone'));
        if ($bName === '' || $bPhone === '') {
            session()->setFlashdata('err', 'Please enter your name and phone number so the business can reach you.');
            return redirect()->to(hi_listing_url($id) . '#enquiry');
        }
        if (! preg_match('/^[0-9+\-\s()]{7,20}$/', $bPhone)) {
            session()->setFlashdata('err', 'Please enter a valid phone number.');
            return redirect()->to(hi_listing_url($id) . '#enquiry');
        }
        if (mb_strlen(trim((string) $this->request->getPost('message'))) < 10) {
            session()->setFlashdata('err', 'Please add a short message (at least 10 characters) so the business knows what you need.');
            return redirect()->to(hi_listing_url($id) . '#enquiry');
        }
        // The live `booking` table has none of the columns this used to write —
        // no name, phone, message or booking_type. Every request submitted on
        // the live site raised "Unknown column 'booking_type'" and was thrown
        // away, while the visitor was told "Booking request sent!".
        //
        // The visitor's details go into additional_information, which is where
        // the original kept the same kind of thing, and hi_row_for() drops any
        // column this particular database does not have.
        $lrow = $db->table('listing')->select('user_id, listing_type')->where('id', $id)->get()->getRowArray() ?: [];
        $when = trim((string) $this->request->getPost('booking_date'));
        $extra = array_filter([
            'name'         => $bName,
            'phone'        => $bPhone,
            'email'        => trim((string) $this->request->getPost('email')),
            'time'         => trim((string) $this->request->getPost('booking_time')),
            'guests'       => trim((string) $this->request->getPost('guests')),
            'message'      => trim((string) $this->request->getPost('message')),
            'booking_type' => trim((string) $this->request->getPost('booking_type')) ?: 'general',
        ], static fn ($v) => trim((string) $v) !== '');

        $db->table('booking')->insert(hi_row_for('booking', [
            'listing_id'   => $id,
            'user_id'      => (int) ($lrow['user_id'] ?? 0),          // the owner
            'requester_id' => (int) session()->get('uid'),            // 0 for a guest
            'listing_type' => (string) ($lrow['listing_type'] ?? ''),
            'booking_date' => $when !== '' ? $when : date('Y-m-d'),
            'additional_information' => json_encode($extra),
            'status'       => 0,
            'created_at'   => date('Y-m-d H:i:s'),
            // only present on installs this rewrite created
            'booking_type' => $extra['booking_type'] ?? 'general',
            'name'         => $bName,
            'phone'        => $bPhone,
            'email'        => $extra['email'] ?? '',
            'booking_time' => $extra['time'] ?? '',
            'guests'       => $extra['guests'] ?? '',
            'message'      => $extra['message'] ?? '',
        ], $db));
        $this->notifyOwner($db, $id, 'booking', $bName, $bPhone,
            trim((string) $this->request->getPost('email')),
            trim((string) $this->request->getPost('message')),
            trim((string) $this->request->getPost('booking_date')) . ' ' . trim((string) $this->request->getPost('booking_time')));
        session()->setFlashdata('flash', 'Booking request sent!');
        return redirect()->to(hi_listing_url($id) . '#enquiry');
    }

    /**
     * Emails the listing owner that someone is trying to reach them.
     *
     * Enquiries and bookings were written to the database and nothing else
     * happened — the owner found out whenever they next signed in, which for
     * most owners is never. A lead that nobody sees is not a lead.
     *
     * The visitor's reply address goes in Reply-To position (first line of the
     * body, since the queue takes no headers), so the owner can just hit reply.
     * A failure here must never lose the enquiry: it is already saved by the
     * time we get here, and any mail problem is logged, not thrown.
     */
    private function notifyOwner($db, int $id, string $kind, string $name,
                                 string $phone, string $email, string $message, string $when = ''): void
    {
        try {
            $l = $db->table('listing')->select('id, name, user_id, listing_type')
                ->where('id', $id)->get()->getRowArray();
            if (! $l || empty($l['user_id'])) { return; }

            $owner = $db->table('user')->select('name, email')
                ->where('id', (int) $l['user_id'])->get()->getRowArray();
            $to = trim((string) ($owner['email'] ?? ''));
            if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) { return; }

            $biz   = esc((string) $l['name']);
            $what  = $kind === 'booking' ? 'booking request' : 'enquiry';
            $url   = hi_listing_url($l);

            $rows = [
                'From'    => $name,
                'Phone'   => $phone,
                'Email'   => $email,
                'When'    => trim($when),
                'Message' => $message,
            ];
            $body = '<p>Hello ' . esc((string) ($owner['name'] ?? 'there')) . ',</p>'
                  . '<p>You have a new ' . $what . ' for <b>' . $biz . '</b>.</p>'
                  . '<table cellpadding="6" style="border-collapse:collapse;font-size:14px">';
            foreach ($rows as $k => $v) {
                if (trim((string) $v) === '') { continue; }
                $body .= '<tr><td style="color:#5a6b86">' . $k . '</td><td><b>' . esc((string) $v) . '</b></td></tr>';
            }
            $body .= '</table>'
                  . ($phone !== '' ? '<p><a href="tel:' . esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') . '">Call ' . esc($phone) . '</a></p>' : '')
                  . '<p><a href="' . esc($url, 'attr') . '">View the listing</a> &middot; '
                  . '<a href="' . esc(base_url('dashboard/' . ($kind === 'booking' ? 'bookings' : 'enquiries')), 'attr') . '">All your leads</a></p>'
                  . '<p style="color:#7c8aa3;font-size:12px">Reply to this email to reach them directly.</p>';

            $m = new \App\Libraries\Mailer();
            $m->queue($to, (string) ($owner['name'] ?? ''), 'New ' . $what . ' for ' . $l['name'], $body);
            $m->processQueue(5);
        } catch (\Throwable $e) {
            // the enquiry is already saved; a mail failure must not undo it
            log_message('error', 'notifyOwner failed for listing ' . $id . ': ' . $e->getMessage());
        }
    }

    public function review(int $id)
    {
        if (! hi_addon('reviews')) { return redirect()->to(hi_listing_url($id)); }
        if (! session()->get('uid')) {
            return redirect()->to('/login');
        }
        $db     = \Config\Database::connect();
        $rating = (int) $this->request->getPost('rating');
        $cmt    = trim((string) $this->request->getPost('comment'));
        if ($rating < 1 || $rating > 5) {
            session()->setFlashdata('err', 'Please choose a star rating between 1 and 5.');
            return redirect()->to(hi_listing_url($id) . '#reviews');
        }
        if (mb_strlen($cmt) < 10) {
            session()->setFlashdata('err', 'Please write at least 10 characters so your review is useful to others.');
            return redirect()->to(hi_listing_url($id) . '#reviews');
        }
        $db->table('review')->insert([
            'listing_id' => $id, 'reviewer_id' => (int) session()->get('uid'),
            'reviewer_name' => session()->get('uname'), 'review_rating' => $rating,
            'review_comment' => $cmt, 'timestamp' => time(),
        ]);
        session()->setFlashdata('flash', 'Thanks for your review!');
        return redirect()->to(hi_listing_url($id) . '#reviews');
    }

    /**
     * Report a listing — wrong details, closed down, spam, offensive content.
     *
     * Ported from Home::report_this_listing / Frontend_model::report_this_listing.
     * The original stored status 0 and a unix date_added; kept the same so the
     * table stays compatible either way.
     */
    public function report(int $id)
    {
        if (! session()->get('uid')) {
            session()->set('auth_redirect', 'l/' . $id);
            return redirect()->to('/login');
        }

        $db = \Config\Database::connect();
        if (! $db->table('listing')->where('id', $id)->countAllResults()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $this->ensureReported($db);

        $report = trim((string) $this->request->getPost('report'));
        if (mb_strlen($report) < 10) {
            session()->setFlashdata('err', 'Please tell us what is wrong, in at least a few words.');
            return redirect()->to(hi_listing_url($id) . '#reviews');
        }

        // one open report per person per listing — stops repeat submissions
        $already = $db->table('reported_listing')
            ->where('listing_id', $id)->where('reporter_id', (int) session()->get('uid'))
            ->where('status', 0)->countAllResults();
        if ($already) {
            session()->setFlashdata('flash', 'You have already reported this listing — we are looking at it.');
            return redirect()->to(hi_listing_url($id) . '#reviews');
        }

        $db->table('reported_listing')->insert([
            'listing_id'  => $id,
            'reporter_id' => (int) session()->get('uid'),
            'report'      => $report,
            'status'      => 0,
            'date_added'  => time(),
        ]);

        session()->setFlashdata('flash', 'Thank you — we will look into it.');
        return redirect()->to(hi_listing_url($id) . '#reviews');
    }

    /** The original never shipped this table in the live database. */
    private function ensureReported($db): void
    {
        if ($db->tableExists('reported_listing')) { return; }
        $inc = $db->DBDriver === 'MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $db->query('CREATE TABLE reported_listing (id INTEGER PRIMARY KEY ' . $inc . ',
            listing_id INT, reporter_id INT, report TEXT, status INT DEFAULT 0, date_added INT)');
    }

    public function ask(int $id)
    {
        if (! hi_addon('qa')) { return redirect()->to(hi_listing_url($id)); }
        if (! session()->get('uid')) { session()->setFlashdata('err', 'Please log in to ask a question.'); return redirect()->to('/login?redirect=' . urlencode(hi_listing_url($id) . '#qa')); }
        $db = \Config\Database::connect();
        if (! $db->tableExists('listing_qa')) {
            $db->query('CREATE TABLE listing_qa (id INTEGER PRIMARY KEY ' . ($db->DBDriver==='MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', listing_id INT, user_id INT, name VARCHAR(120), question TEXT, answer TEXT, created_at DATETIME)');
        }
        $q = trim((string) $this->request->getPost('question'));
        if (mb_strlen($q) < 10) {
            session()->setFlashdata('err', 'Please type your question — at least 10 characters.');
            return redirect()->to(hi_listing_url($id) . '#qa');
        }
        {
            $db->table('listing_qa')->insert([
                'listing_id' => $id, 'user_id' => (int) session()->get('uid'),
                'name' => trim((string) $this->request->getPost('name')) ?: (session()->get('uname') ?: 'HiNoida user'),
                'question' => $q, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            session()->setFlashdata('flash', 'Your question was posted — the business will answer soon.');
        }
        return redirect()->to(hi_listing_url($id) . '#qa');
    }

    /** Simple Save/unsave toggle from a normal link — gated by login, redirects back. */
    public function save(int $id)
    {
        if (! session()->get('uid')) { session()->setFlashdata('err', 'Please log in to save places.'); return redirect()->to('/login?redirect=' . urlencode(hi_listing_url($id))); }
        $db = \Config\Database::connect(); $uid = (int) session()->get('uid');
        $u = $db->table('user')->select('wishlists')->where('id', $uid)->get()->getRowArray();
        $ids = json_decode($u['wishlists'] ?? '[]', true); $ids = is_array($ids) ? array_map('intval', $ids) : [];
        $k = array_search($id, $ids, true);
        if ($k !== false) { unset($ids[$k]); session()->setFlashdata('flash', 'Removed from your saved places.'); }
        else { $ids[] = $id; session()->setFlashdata('flash', 'Saved! Find it in your dashboard → Saved.'); }
        $db->table('user')->where('id', $uid)->update(['wishlists' => json_encode(array_values($ids))]);
        return redirect()->to(hi_listing_url($id));
    }

    public function wishlist()
    {
        $uid = (int) session()->get('uid');
        if (! $uid) {
            return $this->response->setJSON(['ok' => false, 'auth' => false]);
        }
        $db  = \Config\Database::connect();
        $lid = (int) ($this->request->getJSON(true)['listing_id'] ?? $this->request->getPost('listing_id'));
        $u   = $db->table('user')->select('wishlists')->where('id', $uid)->get()->getRowArray();
        $ids = json_decode($u['wishlists'] ?? '[]', true);
        $ids = is_array($ids) ? array_map('intval', $ids) : [];
        $k   = array_search($lid, $ids, true);
        if ($k !== false) {
            unset($ids[$k]);
            $status = 'removed';
        } else {
            $ids[] = $lid;
            $status = 'added';
        }
        $db->table('user')->where('id', $uid)->update(['wishlists' => json_encode(array_values($ids))]);
        return $this->response->setJSON(['ok' => true, 'status' => $status]);
    }

    /**
     * Record an interaction (phone / whatsapp / directions / website).
     * Matches what the original site tracked, so business Insights can show
     * what customers actually did, not just page views.
     */
    public function track(int $id)
    {
        $type = strtolower(trim((string) $this->request->getPost('type')));
        if (! in_array($type, ['phone', 'whatsapp', 'direction', 'website', 'share'], true)) {
            return $this->response->setJSON(['ok' => false]);
        }
        $db = \Config\Database::connect();
        if (! $db->tableExists('profile_views')) { return $this->response->setJSON(['ok' => false]); }
        $ip = (string) $this->request->getIPAddress();
        // one row per listing+type+ip per day, so a repeated tap is not double counted
        $dupe = $db->table('profile_views')->where('listing_id', $id)->where(hi_pv_col($db), $type)
            ->where('ip', $ip)->like('created_at', date('Y-m-d'), 'after')->countAllResults();
        if ($dupe === 0) {
            $db->table('profile_views')->insert([
                'listing_id' => $id, 'user_id' => (int) session()->get('uid'),
                'ip' => $ip, hi_pv_col($db) => $type, 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return $this->response->setJSON(['ok' => true]);
    }
}
