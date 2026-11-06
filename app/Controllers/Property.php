<?php
namespace App\Controllers;

use App\Models\ListingModel;

/**
 * The property front door. Businesses live at /listings; flats, PGs, plots and
 * offices live here, because they are found by budget and BHK rather than by
 * name and rating.
 */
class Property extends BaseController
{
    public function index(): string
    {
        helper(['seo', 'property']);
        $db = \Config\Database::connect();
        hi_ensure_property_table($db);
        hi_ensure_rera_column($db);

        $in   = fn (string $k) => trim((string) ($this->request->getGet($k) ?? ''));
        $deal = $in('deal');
        $kind = $in('kind');
        $bhk  = (int) $in('bhk');
        $loc  = (int) ($in('locality') ?: session()->get('loc_id'));
        $furn = $in('furnishing');
        $q    = $in('q');
        $sort = $in('sort') ?: 'newest';

        // "30000-60000" — an open end on either side is allowed
        [$lo, $hi] = array_pad(array_map('intval', explode('-', $in('budget'))), 2, 0);

        $b = $db->table('listing li')
            ->select('li.*, l.name AS locality_name')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->join('listing_property lp', 'lp.listing_id = li.id')
            ->where('li.status', 'active');
        hi_only_property($b, 'li');

        // the mockup's quick filters: Buy · Rent · PG · Commercial · Plots · New
        $quick = $in('for');
        if (in_array($quick, ['sale', 'rent', 'pg'], true) && $deal === '') { $deal = $quick; }

        /* Which kind of property this page is about. Buy and Rent are for
           HOMES only — flats, villas, builder floors. Offices and shops live
           under Commercial, land under Plots, beds under PG. Each mode only
           accepts the types and filters that make sense for it. */
        $kindsOf = static function (string $group): array {
            return array_keys(array_filter(hi_property_kinds(), static fn ($k) => $k[1] === $group));
        };
        $mode = match (true) {
            $quick === 'commercial'                   => 'commercial',
            $quick === 'plots'                        => 'plots',
            $deal === 'pg' || $quick === 'pg'         => 'pg',
            in_array($deal, ['sale', 'rent'], true),
            $quick === 'new'                          => 'home',
            default                                   => 'all',
        };
        $allowedKinds = match ($mode) {
            'home'       => $kindsOf('home'),
            'commercial' => $kindsOf('commercial'),
            'plots'      => ['plot'],
            'pg'         => ['pg'],
            default      => array_keys(hi_property_kinds()),
        };
        if ($kind !== '' && ! in_array($kind, $allowedKinds, true)) { $kind = ''; }   // e.g. "office" left over from Commercial
        if (! in_array($mode, ['home', 'all'], true)) { $bhk = 0; }                   // BHK is for homes
        if ($mode === 'plots') { $furn = ''; }                                          // land is never furnished
        if ($mode === 'plots' && $deal === 'pg') { $deal = ''; }

        if ($mode === 'pg') {
            // a PG bed is either typed as one or listed under the PG deal
            $b->groupStart()->where('lp.kind', 'pg')->orWhere('lp.deal', 'pg')->groupEnd();
            $deal = 'pg'; $dealFilter = '';
        } elseif ($mode !== 'all') {
            $b->whereIn('lp.kind', $allowedKinds);
        }
        if ($quick === 'new') { $b->where('lp.possession', 'under'); }
        if (($dealFilter ?? $deal) !== '') { $b->where('lp.deal', $dealFilter ?? $deal); }
        if ($kind !== '') { $b->where('lp.kind', $kind); }
        if ($bhk > 0)     { $bhk >= 4 ? $b->where('lp.bedrooms >=', 4) : $b->where('lp.bedrooms', $bhk); }
        if ($loc > 0)     { $b->where('li.locality_id', $loc); }
        if ($furn !== '') { $b->where('lp.furnishing', $furn); }
        if ($lo > 0)      { $b->where('lp.price >=', $lo); }
        if ($hi > 0)      { $b->where('lp.price <=', $hi); }
        if ($q !== '') {
            $b->groupStart()->like('li.name', $q)->orLike('li.address', $q)
              ->orLike('lp.project', $q)->orLike('li.description', $q)->groupEnd();
        }

        match ($sort) {
            'low'   => $b->orderBy('lp.price', 'ASC'),
            'high'  => $b->orderBy('lp.price', 'DESC'),
            'area'  => $b->orderBy('lp.carpet_area', 'DESC'),
            default => $b->orderBy('li.is_featured', 'DESC')->orderBy('li.id', 'DESC'),
        };

        $total = $b->countAllResults(false);

        // nothing matched (say, no homes in that sector yet): show what this
        // tab does have instead of an empty page
        $suggest = [];
        if ($total === 0) {
            $sb = $db->table('listing li')->select('li.*, l.name AS locality_name')
                ->join('locality l', 'l.id = li.locality_id', 'left')
                ->join('listing_property lp', 'lp.listing_id = li.id')
                ->where('li.status', 'active');
            hi_only_property($sb, 'li');
            if ($mode === 'pg') { $sb->groupStart()->where('lp.kind', 'pg')->orWhere('lp.deal', 'pg')->groupEnd(); }
            elseif ($mode !== 'all') { $sb->whereIn('lp.kind', $allowedKinds); }
            if (in_array($deal, ['sale', 'rent'], true)) { $sb->where('lp.deal', $deal); }
            $suggest = $sb->orderBy('li.is_featured', 'DESC')->orderBy('li.id', 'DESC')->limit(8)->get()->getResultArray();
        }
        $rows  = $b->limit(600)->get()->getResultArray();
        if ($mode === 'pg' && $kind === '' && $lo === 0 && $hi === 0 && $furn === '') {
            // Most PGs and hostels are listed as businesses in the "PG / Hostel"
            // categories, not with property details. Show them here too, or the
            // PG tab says "0 listings" while the site has a dozen.
            $pgCats = $db->table('category')->select('id')->groupStart()
                ->like('slug', 'pg-hostel')->orLike('slug', 'hostel')->orLike('name', 'PG/Hostel')->groupEnd()
                ->get()->getResultArray();
            if ($pgCats) {
                $cb = $db->table('listing li')->select('li.*, l.name AS locality_name')
                    ->join('locality l', 'l.id = li.locality_id', 'left')
                    ->where('li.status', 'active')->groupStart();
                foreach ($pgCats as $i => $c) { $cid = (int) $c['id'];
                    $cb->orLike('li.categories', '"' . $cid . '"')->orLike('li.categories', '[' . $cid . ']'); }
                $cb->groupEnd();
                if ($loc > 0)   { $cb->where('li.locality_id', $loc); }
                if ($q !== '')  { $cb->groupStart()->like('li.name', $q)->orLike('li.address', $q)->groupEnd(); }
                $seen = array_flip(array_map(static fn ($r) => (int) $r['id'], $rows));
                foreach ($cb->orderBy('li.is_featured', 'DESC')->orderBy('li.id', 'DESC')->limit(300)->get()->getResultArray() as $r) {
                    if (! isset($seen[(int) $r['id']])) { $rows[] = $r; }
                }
                $total = count($rows);
                if ($total > 0) { $suggest = []; }
            }
        }
        $pg    = hi_paginate($rows, 24);
        $rows  = $pg['rows'];

        $anyFilter = ($quick !== '' || $deal !== '' || $kind !== '' || $bhk > 0 || $furn !== ''
            || $q !== '' || $lo > 0 || $hi > 0 || $pg['page'] > 1);

        $heading = match ($mode) {
            'home'       => trim(($bhk ? $bhk . ' BHK ' : '') . (['sale' => 'flats & homes for sale', 'rent' => 'flats & homes for rent'][$deal] ?? 'flats & homes')),
            'commercial' => 'commercial property' . (['sale' => ' for sale', 'rent' => ' for rent'][$deal] ?? ''),
            'plots'      => 'plots & land',
            'pg'         => 'PG accommodation',
            default      => trim(($bhk ? $bhk . ' BHK ' : '') . 'property'),
        };

        return view('frontend/property', [
            'title' => ucfirst($heading) . ' in Noida — ' . number_format($total) . ' listings | HiNoida',
            'metaDescription' => 'Find ' . $heading . ' in Noida — flats, PGs, plots and offices with rent, area, floor and furnishing, on HiNoida.',
            'canonical' => base_url('property'),
            // a filtered view is one of hundreds of combinations of the same
            // stock — index the front door, follow through to the listings
            $anyFilter ? 'robots' : '_unused' => 'noindex,follow',
            'rows' => $rows, 'suggest' => $suggest, 'pg' => $pg, 'total' => $total, 'heading' => $heading,
            'f' => ['mode' => $mode, 'kinds' => $allowedKinds, 'for' => $quick, 'deal' => $deal, 'kind' => $kind, 'bhk' => $bhk, 'locality' => $loc,
                    'furnishing' => $furn, 'budget' => $in('budget'), 'q' => $q, 'sort' => $sort],
            'localities' => $db->table('locality')->select('id, name')->orderBy('name', 'ASC')->get()->getResultArray(),
            'jsonld' => [hi_breadcrumb(['Home' => base_url('/'), 'Property' => base_url('property')])],
        ]);
    }

    /** POST /property/alert — save a "notify me" request and go back. */
    public function alert()
    {
        helper('property');
        $in    = fn (string $k) => trim((string) $this->request->getPost($k));
        $phone = preg_replace('/\D+/', '', $in('phone'));
        if (strlen($phone) === 12 && str_starts_with($phone, '91')) { $phone = substr($phone, 2); }
        $page  = $in('page');
        if ($page === '' || ! str_starts_with($page, base_url())) { $page = base_url('property'); }
        $back  = $page . (str_contains($page, '#') ? '' : '#notify');

        if (strlen($phone) !== 10) {
            session()->setFlashdata('alert_err', 'Please enter a 10-digit mobile number.');
            return redirect()->to($back);
        }
        if (! hi_ensure_alert_table()) {
            session()->setFlashdata('alert_err', 'Sorry, that did not save. Please try again.');
            return redirect()->to($back);
        }
        $deal = in_array($in('deal'), ['sale', 'rent', 'pg', 'commercial', 'plots'], true) ? $in('deal') : '';
        \Config\Database::connect()->table('property_alert')->insert([
            'name'   => mb_substr(strip_tags($in('name')), 0, 120),
            'phone'  => $phone,
            'deal'   => $deal,
            'kind'   => mb_substr(preg_replace('/[^a-z_]/', '', $in('kind')), 0, 30),
            'bhk'    => min(9, max(0, (int) $in('bhk'))),
            'budget' => mb_substr(preg_replace('/[^0-9\-]/', '', $in('budget')), 0, 40),
            'area'   => mb_substr(strip_tags($in('area')), 0, 120),
            'page'   => mb_substr($page, 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        session()->setFlashdata('alert_ok', substr($phone, 0, 2) . '******' . substr($phone, -2));

        return redirect()->to($back);
    }

    /** "Save this search" — keeps the exact filters so the user can come back to them. */
    public function saveSearch()
    {
        $url = (string) $this->request->getPost('url');
        // only our own search pages, never an outside address
        if (! preg_match('~^/(property|search/search|category/[a-z0-9-]+|[a-z0-9-]+)(\?[^\s<>"]*)?$~i', $url)) { $url = '/property'; }
        $uid = (int) session()->get('uid');
        if (! $uid) { return redirect()->to('/login?redirect=' . rawurlencode($url)); }
        $db = \Config\Database::connect();
        hi_ensure_saved_search($db);
        $label = mb_substr(trim(strip_tags((string) $this->request->getPost('label'))) ?: 'My search', 0, 190);
        if ($db->table('saved_search')->where(['user_id' => $uid, 'url' => $url])->countAllResults() === 0) {
            $db->table('saved_search')->insert(['user_id' => $uid, 'label' => $label, 'url' => $url, 'created_at' => date('Y-m-d H:i:s')]);
        }
        session()->setFlashdata('saved_search', 'Search saved. Find it under Saved in your account.');
        return redirect()->to($url);
    }

    public function deleteSearch(int $id)
    {
        $uid = (int) session()->get('uid');
        if ($uid) {
            $db = \Config\Database::connect();
            hi_ensure_saved_search($db);
            $db->table('saved_search')->where(['id' => $id, 'user_id' => $uid])->delete();
        }
        return redirect()->to('/dashboard/saved');
    }
}
