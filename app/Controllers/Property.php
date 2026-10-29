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
        if ($quick === 'commercial') { $b->whereIn('lp.kind', ['office', 'shop', 'warehouse', 'coworking']); }
        elseif ($quick === 'plots')  { $b->where('lp.kind', 'plot'); }
        elseif ($quick === 'new')    { $b->where('lp.possession', 'under'); }
        elseif (in_array($quick, ['sale', 'rent', 'pg'], true)) { $b->where('lp.deal', $quick); }

        if ($deal !== '') { $b->where('lp.deal', $deal); }
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
        $rows  = $b->limit(600)->get()->getResultArray();
        $pg    = hi_paginate($rows, 24);
        $rows  = $pg['rows'];

        $anyFilter = ($quick !== '' || $deal !== '' || $kind !== '' || $bhk > 0 || $furn !== ''
            || $q !== '' || $lo > 0 || $hi > 0 || $pg['page'] > 1);

        $heading = trim(($bhk ? $bhk . ' BHK ' : '')
            . (['sale' => 'property for sale', 'rent' => 'property for rent', 'pg' => 'PG accommodation'][$deal] ?? 'property'));

        return view('frontend/property', [
            'title' => ucfirst($heading) . ' in Noida — ' . number_format($total) . ' listings | HiNoida',
            'metaDescription' => 'Find ' . $heading . ' in Noida — flats, PGs, plots and offices with rent, area, floor and furnishing, on HiNoida.',
            'canonical' => base_url('property'),
            // a filtered view is one of hundreds of combinations of the same
            // stock — index the front door, follow through to the listings
            $anyFilter ? 'robots' : '_unused' => 'noindex,follow',
            'rows' => $rows, 'pg' => $pg, 'total' => $total, 'heading' => $heading,
            'f' => ['for' => $quick, 'deal' => $deal, 'kind' => $kind, 'bhk' => $bhk, 'locality' => $loc,
                    'furnishing' => $furn, 'budget' => $in('budget'), 'q' => $q, 'sort' => $sort],
            'localities' => $db->table('locality')->select('id, name')->orderBy('name', 'ASC')->get()->getResultArray(),
            'jsonld' => [hi_breadcrumb(['Home' => base_url('/'), 'Property' => base_url('property')])],
        ]);
    }
}
