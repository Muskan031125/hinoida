<?php
namespace App\Controllers;
class Areas extends BaseController
{
    public function index(): string
    {
        $db = \Config\Database::connect();
        helper('seo');
        $rows = $db->table('locality l')
            ->select('l.*, (SELECT COUNT(*) FROM listing li WHERE li.locality_id=l.id AND li.status="active") AS listing_count')
            ->orderBy('listing_count', 'DESC')->get()->getResultArray();
        return view('frontend/areas', [
            'title'=>'Areas & Sectors in Noida — Local Business Directory | HiNoida',
            'metaDescription'=>'Browse businesses by area and sector in Noida. Find shops, services, restaurants and more near you across '.count($rows).' localities on HiNoida.',
            'canonical'=>hi_page('areas'),
            'areas'=>$rows,
            'jsonld'=>[hi_breadcrumb(['Home'=>base_url('/'), 'Areas in Noida'=>hi_page('areas')])],
        ]);
    }
    /**
     * A named place inside a locality — a mall, block or society — from the
     * live `area` table. 1,223 listings carry an area_id that nothing in this
     * rewrite surfaced, so "Gaurcity Mall" and "DLF Mall of India" were not
     * browsable even though the data was there.
     */
    public function place(string $slug): string
    {
        helper(['seo', 'facts']);
        $db = \Config\Database::connect();
        if (! $db->tableExists('area')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $area = $db->table('area')->where('slug', $slug)->get()->getRowArray();
        if (! $area) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $lm       = new \App\Models\ListingModel();
        $listings = $lm->where('status', 'active')->where('area_id', (int) $area['id'])->findAll(400);
        $listings = $lm->attachRatings($listings);

        // the locality this place sits in, for the breadcrumb
        $loc = ! empty($area['locality_id'])
            ? $db->table('locality')->where('id', (int) $area['locality_id'])->get()->getRowArray()
            : null;
        foreach ($listings as &$_l) { $_l['locality_name'] = $loc['name'] ?? ''; }
        unset($_l);

        $fQ = trim((string) $this->request->getGet('q'));
        // popular kinds of business here (before the search narrows them)
        $placeKinds = [];
        foreach ($listings as $_l) { $_k = trim((string) ($_l['_cat_name'] ?? '')); if ($_k !== '') { $placeKinds[$_k] = ($placeKinds[$_k] ?? 0) + 1; } }
        arsort($placeKinds);
        if ($fQ !== '') { $listings = array_values(array_filter($listings, static fn ($l) => mb_stripos(implode(' ', [(string) ($l['name'] ?? ''), (string) ($l['address'] ?? ''), (string) ($l['head_point'] ?? ''), (string) ($l['_cat_name'] ?? '')]), $fQ) !== false)); }
        $sort = (string) $this->request->getGet('sort');
        if ($sort === 'rating')      { usort($listings, static fn ($a, $b) => ($b['_ravg'] ?? 0) <=> ($a['_ravg'] ?? 0)); }
        elseif ($sort === 'reviews') { usort($listings, static fn ($a, $b) => ($b['_rcount'] ?? 0) <=> ($a['_rcount'] ?? 0)); }
        elseif ($sort === 'name')    { usort($listings, static fn ($a, $b) => strcasecmp($a['name'], $b['name'])); }

        $total    = count($listings);
        $pg       = hi_paginate($listings, 24);
        $listings = $pg['rows'];

        $name  = (string) $area['name'];
        $url   = base_url('a/' . $slug);
        $where = $loc ? $name . ', ' . $loc['name'] : $name;
        $intro = 'Businesses at ' . $where . ', Noida — ' . number_format($total)
            . ' listed with address, phone, opening hours, photos and reviews.';

        $crumbs = ['Home' => base_url('/'), 'Areas' => hi_page('areas')];
        if ($loc) { $crumbs[$loc['name']] = hi_locality_url($loc['slug']); }
        $crumbs[$name] = $url;

        return view('frontend/place', [
            'title'           => $name . ', Noida — ' . number_format($total) . ' Businesses | HiNoida',
            'metaDescription' => $intro,
            'canonical'       => $pg['page'] > 1 ? hi_page_url($pg['page']) : $url,
            'area'            => $area,
            'loc'             => $loc,
            'listings'        => $listings,
            'placeKinds'      => array_slice($placeKinds, 0, 8, true),
            'pg'              => $pg,
            'total'           => $total,
            'sort'            => $sort,
            'intro'           => $intro,
            'jsonld'          => [
                hi_breadcrumb($crumbs),
                hi_collection_schema($name . ', Noida', $intro, $url, $listings),
            ],
        ]);
    }

    public function show(string $slug): string
    {
        helper('seo');
        $db = \Config\Database::connect();
        $area = $db->table('locality')->where('slug',$slug)->get()->getRowArray();
        if(!$area){ throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $lm = new \App\Models\ListingModel();
        // true total for this area — NOT the capped page slice
        $total = $db->table('listing')->where('status','active')->where('locality_id',(int)$area['id'])->countAllResults();
        // property here is listed with everything else; this count only drives
        // the shortcut through to the filtered property search
        $propHere = $db->table('listing')->where('status','active')
            ->where('locality_id',(int)$area['id'])->where('listing_type','property')->countAllResults();

        $listings = $lm->where('status','active')->where('locality_id',(int)$area['id'])->findAll(1000);
        $listings = $lm->attachRatings($listings);
        foreach ($listings as &$_l) { $_l['locality_name'] = $area['name']; } unset($_l);

        // filters first — facet counts depend on them
        helper('facts');
        $fCat  = (int) $this->request->getGet('cat');
        $fRat  = (float) $this->request->getGet('rating');
        $fOpen = $this->request->getGet('open') === '1';
        $fAmen = (static function ($r) { return array_values(array_unique(array_filter(array_map('intval', is_array($r) ? $r : preg_split('/[,\s]+/', (string) $r))))); })($this->request->getGet('amenity'));   // several facilities at once
        $fAmen = array_slice($fAmen, 0, 3);              // up to 3
        $sort  = (string) $this->request->getGet('sort');
        $fQ    = trim((string) $this->request->getGet('q'));   // search inside this area
        $nowM  = (int) date('G')*60 + (int) date('i');

        $catNames = [];
        foreach ($db->table('category')->select('id, name')->get()->getResultArray() as $c) { $catNames[(int)$c['id']] = $c['name']; }

        // each facet is counted with every filter applied EXCEPT its own, so a
        // dropdown never offers a combination that returns nothing
        $tests = [
            'cat'    => static fn($l) => ! $fCat  || in_array($fCat, array_map('intval', (array) json_decode($l['categories'] ?? '[]', true)), true),
            'amen'   => static fn ($l) => ! $fAmen || ! array_diff($fAmen, array_map('intval', (array) json_decode($l['amenities'] ?? '[]', true))),   // has every facility picked
            'rating' => static fn($l) => ! $fRat  || (float)($l['_ravg'] ?? 0) >= $fRat,
            'open'   => static fn($l) => ! $fOpen || hi_is_open($l, $nowM),
            'q'      => static fn ($l) => $fQ === '' || mb_stripos(implode(' ', [(string) ($l['name'] ?? ''), (string) ($l['address'] ?? ''), (string) ($l['head_point'] ?? '')]), $fQ) !== false,
        ];
        $except = static function(array $rows, string $skip) use ($tests) {
            foreach ($tests as $k => $t) { if ($k === $skip) { continue; } $rows = array_values(array_filter($rows, $t)); }
            return $rows;
        };

        $facet = ['cat'=>[], 'rating'=>['4'=>0,'3'=>0], 'open'=>0, 'amen'=>[]];
        foreach ($except($listings,'cat') as $_fl) {
            foreach ((array) json_decode($_fl['categories'] ?? '[]', true) as $cid) {
                $cid = (int) $cid;
                if (isset($catNames[$cid])) { $facet['cat'][$cid] = ($facet['cat'][$cid] ?? 0) + 1; }
            }
        }
        $amenBase = count($except($listings, 'amen'));   // a facility every place here has is no filter
        foreach ($except($listings,'amen') as $_fl) {
            foreach ((array) json_decode($_fl['amenities'] ?? '[]', true) as $amid) {
                $amid = (int) $amid;
                if ($amid) { $facet['amen'][$amid] = ($facet['amen'][$amid] ?? 0) + 1; }
            }
        }
        foreach ($except($listings,'rating') as $_fl) {
            $rv = (float)($_fl['_ravg'] ?? 0);
            if ($rv >= 4) { $facet['rating']['4']++; }
            if ($rv >= 3) { $facet['rating']['3']++; }
        }
        foreach ($except($listings,'open') as $_fl) { if (hi_is_open($_fl, $nowM)) { $facet['open']++; } }

        arsort($facet['cat']);
        $facetCats = [];
        foreach (array_slice($facet['cat'], 0, 40, true) as $cid => $n) { $facetCats[] = ['id'=>$cid, 'name'=>$catNames[$cid], 'n'=>$n]; }

        // the real result set
        foreach ($tests as $t) { $listings = array_values(array_filter($listings, $t)); }
        if ($sort==='rating')        { usort($listings, static fn($a,$b)=>($b['_ravg']??0)<=>($a['_ravg']??0)); }
        elseif ($sort==='reviews')   { usort($listings, static fn($a,$b)=>($b['_rcount']??0)<=>($a['_rcount']??0)); }
        elseif ($sort==='name')      { usort($listings, static fn($a,$b)=>strcasecmp($a['name'],$b['name'])); }
        elseif ($sort==='featured')  { usort($listings, static fn($a,$b)=>($b['is_featured']??0)<=>($a['is_featured']??0)); }
        // Named places within this locality — malls, blocks, societies — from
        // the live `area` table. Only ones that actually have listings.
        $places = [];
        if ($db->tableExists('area')) {
            $places = $db->table('area a')
                ->select('a.id, a.name, a.slug, (SELECT COUNT(*) FROM listing li WHERE li.area_id = a.id AND li.status = "active") AS n')
                ->where('a.locality_id', (int) $area['id'])
                ->get()->getResultArray();
            $places = array_values(array_filter($places, static fn ($p) => (int) $p['n'] > 0));
            usort($places, static fn ($x, $y) => (int) $y['n'] <=> (int) $x['n']);
        }

        $amens = $db->tableExists('amenities')
            ? $db->table('amenities')->select('id, name, icon')->get()->getResultArray() : [];
        // only facilities that actually tell places apart: not the basics
        // (toilets, drinking water, lights…) and not ones every place has
        $amens = array_values(array_filter($amens, static function ($a) use ($facet, $amenBase, $fAmen) {
            $id = (int) $a['id']; $n = (int) ($facet['amen'][$id] ?? 0);
            if (in_array($id, $fAmen, true)) { return true; }
            if (! $n || $n >= $amenBase) { return false; }
            return ! preg_match('/toilet|washroom|restroom|bathroom|drinking\s*water|electricit|power\s*back|\bfans?\b|\blight(s|ing)?\b|seating|dustbin|first\s*aid|cctv|security\s*guard|\blifts?\b|elevator/i', html_entity_decode((string) $a['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }));
        usort($amens, static fn($a,$b) => $facet['amen'][(int)$b['id']] <=> $facet['amen'][(int)$a['id']]);

        $shown    = count($listings);
        $pg       = hi_paginate($listings, 24);
        $listings = $pg['rows'];

        $url=hi_locality_url($slug);
        $an=$area['name'];
        $intro="Discover local businesses in {$an}, Noida. HiNoida lists ".number_format($total)." businesses in {$an} — restaurants, shops, clinics, salons, services and more — each with address, phone number, opening hours, photos, ratings and reviews.";
        $faqs=[
            ["How many businesses are listed in {$an}, Noida?","HiNoida lists ".number_format($total)." active businesses in {$an}, across categories like food, shopping, health and services."],
            ["What are the best places in {$an}?","Browse the listings on this page — each shows real user ratings and reviews so you can find the top-rated places in {$an}, Noida."],
        ];
        $faqLd=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>array_map(
            static fn($f)=>['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]],$faqs)];
        return view('frontend/area', [
            'title'=>$an.', Noida — '.number_format($total).' Local Businesses, Reviews & Contact | HiNoida',
            'metaDescription'=>"Local businesses in {$an}, Noida — ".number_format($total)." listings with phone, address, timings, photos & reviews on HiNoida.",
            'canonical'=>$pg['page']>1 ? hi_page_url($pg['page']) : $url,
            'area'=>$area,'listings'=>$listings,'pg'=>$pg,'intro'=>$intro,'faqs'=>$faqs,
            'propRows'=>(static function() use ($area){ helper('property'); return hi_area_property_rows((int)$area['id'], 8); })(),
            'total'=>$total,'propHere'=>$propHere,'shown'=>$shown,'facet'=>$facet,'facetCats'=>$facetCats,'sort'=>$sort,
            'amens'=>$amens,
            'places'=>$places,
            'f'=>['cat'=>$fCat,'rating'=>$fRat,'open'=>$fOpen,'amenity'=>$fAmen,'q'=>$fQ],
            'jsonld'=>[
                hi_breadcrumb(['Home'=>base_url('/'), 'Areas'=>hi_page('areas'), $an=>$url]),
                hi_collection_schema('Businesses in '.$an.', Noida',$intro,$url,$listings),
                $faqLd,
            ],
        ]);
    }
}
