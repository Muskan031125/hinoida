<?php
namespace App\Controllers;

use App\Models\ListingModel;

class Search extends BaseController
{
    public function index(): string
    {
        $db  = \Config\Database::connect();
        // `search_string` and `selected_locality_id` are the parameter names the
        // live /listings page uses — links and bookmarks built against it keep
        // working alongside our own shorter ones. That form was a POST
        // (/search/search), so read either way.
        $in     = fn (string $k) => $this->request->getGet($k) ?? $this->request->getPost($k);
        $q      = trim((string) ($in('q') ?: $in('search_string')));
        $area   = (int) ($in('area')
            ?: $in('selected_locality_id')
            ?: session()->get('loc_id'));
        $minRat = (float) $this->request->getGet('rating');
        $openNow= $this->request->getGet('open') === '1';
        $sort   = (string) $this->request->getGet('sort') ?: 'relevance';

        // Categories matching the query. Searching "Restaurants" only looked at
        // name/address/description, so it found the one place with the word in
        // its name and missed every listing actually filed under Restaurants —
        // which read as a broken area filter.
        $catIds = [];
        if ($q !== '') {
            foreach ($db->table('category')->select('id')->like('name', $q)->get()->getResultArray() as $c) {
                $catIds[] = (int) $c['id'];
            }
        }
        // applied to both the result query and the area-count query
        $applyQ = static function ($b) use ($q, $catIds) {
            if ($q === '') { return $b; }
            $b->groupStart()->like('li.name', $q)->orLike('li.address', $q)->orLike('li.description', $q);
            // categories is a JSON array of quoted ids — ["65","75"] — so
            // matching on "65" with the quotes included cannot also hit 165
            foreach ($catIds as $cid) {
                $b->orLike('li.categories', '"' . $cid . '"');
            }

            return $b->groupEnd();
        };

        $builder = $db->table('listing li')
            ->select('li.*, l.name AS locality_name')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->where('li.status', 'active');
        // Property shows here alongside businesses, with a Type filter to narrow
        // to one or the other. /property stays the dedicated front door, where
        // budget and BHK filters live.
        helper('property');
        $show = $in('show');                       // '' | business | property
        if ($show === 'business') { hi_only_business($builder, 'li'); }
        elseif ($show === 'property') { hi_only_property($builder, 'li'); }
        $applyQ($builder);
        if ($area) { $builder->where('li.locality_id', $area); }
        // open-now is resolved in PHP below — per-day hours live in a JSON column
        if ($sort === 'featured') { $builder->orderBy('li.is_featured', 'DESC'); }
        elseif ($sort === 'newest') { $builder->orderBy('li.date_added', 'DESC'); }

        // true DB-side match count (rating is filtered in PHP below, so when a
        // rating filter is active we report the post-filter count instead)
        $dbTotal = $builder->countAllResults(false);
        // fetched whole because rating, open-now and paging are all resolved in
        // PHP below; the active set is ~2k rows, so this stays cheap
        $rows = $builder->limit(3000)->get()->getResultArray();

        // attach ratings, then apply rating filter + rating/name sort in PHP
        $rows = (new ListingModel())->attachRatings($rows);
        if ($minRat > 0) { $rows = array_values(array_filter($rows, static fn ($r) => (float) ($r['_ravg'] ?? 0) >= $minRat)); }
        if ($openNow)    { $rows = array_values(array_filter($rows, static fn ($r) => hi_is_open($r))); }
        if ($sort === 'rating')      { usort($rows, static fn ($a, $b) => ($b['_ravg'] ?? 0) <=> ($a['_ravg'] ?? 0)); }
        elseif ($sort === 'name')    { usort($rows, static fn ($a, $b) => strcasecmp($a['name'], $b['name'])); }
        elseif ($sort === 'reviews') { usort($rows, static fn ($a, $b) => ($b['_rcount'] ?? 0) <=> ($a['_rcount'] ?? 0)); }
        $matched = ($minRat > 0 || $openNow) ? count($rows) : $dbTotal;
        $pg      = hi_paginate($rows, 24);
        $rows    = $pg['rows'];

        // Area options counted against the query but NOT against the area
        // filter itself, so the list never offers a sector with no matches.
        $areaCounts = [];
        if ($q !== '') {
            $ab = $db->table('listing li')->select('li.locality_id, COUNT(*) AS n')->where('li.status', 'active');
            $applyQ($ab);
            foreach ($ab->groupBy('li.locality_id')->get()->getResultArray() as $r) {
                $areaCounts[(int) $r['locality_id']] = (int) $r['n'];
            }
        }
        $areas = $db->table('locality')->select('id, name')->orderBy('name', 'ASC')->get()->getResultArray();
        if ($areaCounts) {
            foreach ($areas as &$_a) { $_a['n'] = $areaCounts[(int) $_a['id']] ?? 0; }
            unset($_a);
            $areas = array_values(array_filter($areas, static fn ($a) => $a['n'] > 0 || (int) $a['id'] === $area));
            usort($areas, static fn ($x, $y) => $y['n'] <=> $x['n']);
        }

        // Property is searched at /property. Rather than mixing flats into a
        // business search, say how many matched and let them cross over.
        $propMatches = 0;
        if ($db->tableExists('listing_property')) {
            $pq = $db->table('listing li')->join('listing_property lp', 'lp.listing_id = li.id')
                ->where('li.status', 'active');
            hi_only_property($pq, 'li');
            if ($q !== '') {
                $pq->groupStart()->like('li.name', $q)->orLike('li.address', $q)
                   ->orLike('lp.project', $q)->groupEnd();
            }
            if ($area) { $pq->where('li.locality_id', $area); }
            $propMatches = $pq->countAllResults();
        }

        return view('frontend/search', [
            'propMatches' => $propMatches,
            'propQ' => $q,
            // with no term this is the whole directory, so it gets a directory
            // title rather than a search one
            'title' => ($q !== ''
                ? $q . ' in Noida — ' . $matched . ' Results'
                : ($show === 'property' ? 'Property in Noida' : 'All Listings in Noida') . ' — ' . number_format($matched)) . ' | HiNoida',
            'metaDescription' => $q !== ''
                ? 'Search results for "' . esc($q) . '" in Noida — ' . $matched . ' businesses with address, phone, timings, photos & reviews on HiNoida.'
                : 'Browse all ' . number_format($matched) . ' businesses and properties listed in Noida — filter by area, rating and open-now, with address, phone, timings, photos and reviews.',
            'canonical' => hi_page('listings') . ($q !== '' ? '?search_string=' . urlencode($q) : ''),
            'robots'  => $q !== '' ? 'noindex,follow' : 'index,follow',
            'q' => $q, 'results' => $rows, 'areas' => $areas, 'matched' => $matched, 'pg' => $pg,
            'f' => ['area' => $area, 'rating' => $minRat, 'open' => $openNow, 'sort' => $sort, 'show' => $show],
            'popular' => ['PG', 'Coworking', 'Property dealers', 'Restaurants', 'Hospitals', 'Gyms', 'Schools', 'Cafes'],
        ]);
    }
}
