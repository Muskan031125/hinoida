<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ListingModel;

class Category extends BaseController
{
    public function show(string $slug): string
    {
        $catModel     = new CategoryModel();
        $listingModel = new ListingModel();

        $cat = $catModel->bySlug($slug);
        if (! $cat) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        helper('seo');
        $children = $catModel->children((int) $cat['id']);
        foreach ($children as &$ch) {
            $ch['listing_count'] = $catModel->countForCategory((int) $ch['id']);
        }
        unset($ch);

        $listings = $listingModel->inCategory((int) $cat['id'], 400);
        $listings = $listingModel->attachRatings($listings);

        // on a category page the browsed category IS the right context for
        // imagery/facts — it beats whichever of the listing's other categories
        // happened to resolve first.
        helper('facts');
        $ctxName = $cat['name'];
        if (hi_group_key_or_blank($ctxName) === '' && ! empty($cat['parent'])) {
            $p = $catModel->find((int) $cat['parent']);
            if ($p && hi_group_key_or_blank($p['name']) !== '') { $ctxName = $p['name']; }
        }
        if (hi_group_key_or_blank($ctxName) !== '') {
            foreach ($listings as &$_l) { $_l['_cat_name'] = $ctxName; }
            unset($_l);
        }
        // Facet counts, computed BEFORE any filter is applied, so each option
        // read the filters first — the counts depend on them
        // the header locality picker applies unless this page sets its own area
        $fArea = (int) ($this->request->getGet('area') ?: session()->get('loc_id'));
        $fRat  = (float) $this->request->getGet('rating');
        $fOpen = $this->request->getGet('open') === '1';
        $fSub  = (int) $this->request->getGet('sub');
        $fAmen = (static function ($r) { return array_values(array_unique(array_filter(array_map('intval', is_array($r) ? $r : preg_split('/[,\s]+/', (string) $r))))); })($this->request->getGet('amenity'));   // several facilities at once
        $fAmen = array_slice($fAmen, 0, 3);              // up to 3
        $sort  = (string) $this->request->getGet('sort');
        $fQ    = trim((string) $this->request->getGet('q'));   // search inside this category
        $nowM  = (int) date('G') * 60 + (int) date('i');
        $childIds = array_column($children, 'id');

        // One test per dimension, so a facet can be counted with every filter
        // applied EXCEPT its own. Counting against the unfiltered set made each
        // dropdown advertise results that vanished once combined — "Sector 62 (1)"
        // and "Pet Friendly (1)" could be different listings and give zero together.
        $tests = [
            'area'   => static fn ($l) => ! $fArea || (int) ($l['locality_id'] ?? 0) === $fArea,
            'sub'    => static fn ($l) => ! $fSub  || in_array($fSub, array_map('intval', (array) json_decode($l['categories'] ?? '[]', true)), true),
            'amen'   => static fn ($l) => ! $fAmen || ! array_diff($fAmen, array_map('intval', (array) json_decode($l['amenities'] ?? '[]', true))),   // has every facility picked
            'rating' => static fn ($l) => ! $fRat  || (float) ($l['_ravg'] ?? 0) >= $fRat,
            'open'   => static fn ($l) => ! $fOpen || hi_is_open($l, $nowM),
            'q'      => static fn ($l) => $fQ === '' || mb_stripos(implode(' ', [(string) ($l['name'] ?? ''), (string) ($l['address'] ?? ''), (string) ($l['head_point'] ?? '')]), $fQ) !== false,
        ];
        $except = static function (array $rows, string $skip) use ($tests) {
            foreach ($tests as $k => $t) {
                if ($k === $skip) { continue; }
                $rows = array_values(array_filter($rows, $t));
            }
            return $rows;
        };

        $facet = ['area' => [], 'rating' => ['4' => 0, '3' => 0], 'open' => 0, 'sub' => [], 'amen' => []];
        foreach ($except($listings, 'area') as $_fl) {
            $aid = (int) ($_fl['locality_id'] ?? 0);
            if ($aid) { $facet['area'][$aid] = ($facet['area'][$aid] ?? 0) + 1; }
        }
        foreach ($except($listings, 'sub') as $_fl) {
            foreach ((array) json_decode($_fl['categories'] ?? '[]', true) as $cid) {
                $cid = (int) $cid;
                if (in_array($cid, $childIds)) { $facet['sub'][$cid] = ($facet['sub'][$cid] ?? 0) + 1; }
            }
        }
        $amenBase = count($except($listings, 'amen'));   // a facility every place here has is no filter
        foreach ($except($listings, 'amen') as $_fl) {
            foreach ((array) json_decode($_fl['amenities'] ?? '[]', true) as $amid) {
                $amid = (int) $amid;
                if ($amid) { $facet['amen'][$amid] = ($facet['amen'][$amid] ?? 0) + 1; }
            }
        }
        foreach ($except($listings, 'rating') as $_fl) {
            $rv = (float) ($_fl['_ravg'] ?? 0);
            if ($rv >= 4) { $facet['rating']['4']++; }
            if ($rv >= 3) { $facet['rating']['3']++; }
        }
        foreach ($except($listings, 'open') as $_fl) {
            if (hi_is_open($_fl, $nowM)) { $facet['open']++; }
        }

        // now the actual result set — every filter applied
        foreach ($tests as $t) { $listings = array_values(array_filter($listings, $t)); }
        if ($sort === 'rating')      { usort($listings, static fn ($a, $b) => ($b['_ravg'] ?? 0) <=> ($a['_ravg'] ?? 0)); }
        elseif ($sort === 'reviews') { usort($listings, static fn ($a, $b) => ($b['_rcount'] ?? 0) <=> ($a['_rcount'] ?? 0)); }
        elseif ($sort === 'name')    { usort($listings, static fn ($a, $b) => strcasecmp($a['name'], $b['name'])); }
        elseif ($sort === 'featured'){ usort($listings, static fn ($a, $b) => ($b['is_featured'] ?? 0) <=> ($a['is_featured'] ?? 0)); }
        $pg       = hi_paginate($listings, 24);
        $listings = $pg['rows'];
        $areas = (new \Config\Database)->connect()->table('locality')->select('id, name')->orderBy('name','ASC')->get()->getResultArray();
        $areas = array_values(array_filter($areas, static fn ($a) => !empty($facet['area'][(int) $a['id']])));
        usort($areas, static fn ($a, $b) => $facet['area'][(int) $b['id']] <=> $facet['area'][(int) $a['id']]);
        // amenity options, most common first, empty ones dropped
        $db2   = (new \Config\Database)->connect();
        $amens = $db2->tableExists('amenities')
            ? $db2->table('amenities')->select('id, name, icon')->get()->getResultArray() : [];
        // only facilities that actually tell places apart: not the basics
        // (toilets, drinking water, lights…) and not ones every place has
        $amens = array_values(array_filter($amens, static function ($a) use ($facet, $amenBase, $fAmen) {
            $id = (int) $a['id']; $n = (int) ($facet['amen'][$id] ?? 0);
            if (in_array($id, $fAmen, true)) { return true; }
            if (! $n || $n >= $amenBase) { return false; }
            return ! preg_match('/toilet|washroom|restroom|bathroom|drinking\s*water|electricit|power\s*back|\bfans?\b|\blight(s|ing)?\b|seating|dustbin|first\s*aid|cctv|security\s*guard|\blifts?\b|elevator/i', html_entity_decode((string) $a['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }));
        usort($amens, static fn ($a, $b) => $facet['amen'][(int) $b['id']] <=> $facet['amen'][(int) $a['id']]);
        // sub-categories that actually have listings, most first
        $subs = array_values(array_filter($children, static fn ($c) => ! empty($facet['sub'][(int) $c['id']])));
        usort($subs, static fn ($a, $b) => $facet['sub'][(int) $b['id']] <=> $facet['sub'][(int) $a['id']]);
        $count    = $catModel->countForCategory((int) $cat['id']);
        $name     = $cat['name'];
        $url      = hi_category_url($slug);

        $intro = "Find the best {$name} in Noida on HiNoida. Browse " . number_format($count) . " verified "
            . strtolower($name) . " listings with address, phone numbers, opening hours, photos, genuine ratings and customer reviews. "
            . "Compare options across every sector of Noida and send an enquiry or booking request directly — all in one place.";

        $faqs = [
            ["How many {$name} are listed in Noida?", "HiNoida lists " . number_format($count) . " {$name} across Noida, each with contact details, timings, ratings and reviews."],
            ["How do I contact a " . rtrim($name, 's') . " in Noida?", "Open any listing to see its phone number, WhatsApp, address and website. You can also send an enquiry directly from the page."],
            ["Are the {$name} on HiNoida verified?", "Featured listings are marked as Verified by HiNoida. All listings show real user ratings and reviews to help you choose."],
            ["Which areas of Noida are covered?", "HiNoida covers all sectors and localities of Noida — you can filter {$name} by area from the Areas page."],
        ];
        $faqLd = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>array_map(
            static fn($f)=>['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]], $faqs)];

        return view('frontend/category', [
            'title'    => $name . ' in Noida — ' . number_format($count) . ' Listings, Reviews & Contact | HiNoida',
            'metaDescription' => "Best {$name} in Noida — " . number_format($count) . " listings with phone, address, timings, photos & reviews. Compare and enquire on HiNoida.",
            'canonical' => $pg['page'] > 1 ? hi_page_url($pg['page']) : $url,
            'cat'      => $cat,
            'children' => $children,
            'listings' => $listings,
            'pg'       => $pg,
            'sort'     => $sort,
            'areas'    => $areas,
            'facet'    => $facet,
            'amens'    => $amens,
            'subs'     => $subs,
            'total'    => $count,
            'f'        => ['area'=>$fArea,'rating'=>$fRat,'open'=>$fOpen,'sub'=>$fSub,'amenity'=>$fAmen,'q'=>$fQ],
            'intro'    => $intro,
            'faqs'     => $faqs,
            'jsonld'   => [
                hi_breadcrumb(['Home'=>base_url('/'), $name=>$url]),
                hi_collection_schema($name . ' in Noida', $intro, $url, $listings),
                $faqLd,
            ],
        ]);
    }
}
