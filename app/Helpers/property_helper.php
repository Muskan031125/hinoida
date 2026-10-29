<?php

/**
 * property_helper — the real-estate side of the directory.
 *
 * A property (a flat, a PG bed, a plot, an office) is a listing like any other,
 * typed `property`, so it inherits URLs, cards, photos, the map, enquiries and
 * the sitemap for free. What makes it different lives in `listing_property`,
 * one row per listing, because `listing` is already 45 columns wide.
 *
 * A property is browsed like any other listing — it appears in the same grids,
 * counts and search results, just on a card that leads with the price. The two
 * scope helpers below are only for the places where someone deliberately asks
 * for one or the other: the Type filter on /listings and the /property page.
 */

if (! function_exists('hi_property_deals')) {
    /** What the property is offered as. */
    function hi_property_deals(): array
    {
        return ['sale' => 'For sale', 'rent' => 'For rent', 'pg' => 'PG / hostel'];
    }
}

if (! function_exists('hi_property_kinds')) {
    /**
     * Kind => [label, which field group it uses].
     * The group decides what the owner is asked for: a plot has no bathrooms,
     * a PG bed has sharing and meals rather than BHK.
     */
    function hi_property_kinds(): array
    {
        return [
            'flat'       => ['Flat / Apartment', 'home'],
            'villa'      => ['Villa / Independent house', 'home'],
            'floor'      => ['Builder floor', 'home'],
            'pg'         => ['PG / hostel bed', 'pg'],
            'plot'       => ['Plot / land', 'land'],
            'office'     => ['Office space', 'commercial'],
            'shop'       => ['Shop / showroom', 'commercial'],
            'warehouse'  => ['Warehouse / godown', 'commercial'],
            'coworking'  => ['Coworking seat', 'commercial'],
        ];
    }
}

if (! function_exists('hi_property_group')) {
    /** home | pg | land | commercial — which set of fields applies. */
    function hi_property_group(string $kind): string
    {
        return hi_property_kinds()[strtolower(trim($kind))][1] ?? 'home';
    }
}

if (! function_exists('hi_property_fields')) {
    /**
     * The fields each group asks for, in the order they are shown.
     * Every one maps to a column on `listing_property`.
     */
    function hi_property_fields(string $group): array
    {
        $common = ['price', 'price_period', 'deposit', 'available_from', 'project', 'rera'];

        $by = [
            'home' => ['bedrooms', 'bathrooms', 'balconies', 'carpet_area', 'area_unit',
                       'furnishing', 'floor_no', 'total_floors', 'facing', 'age_years', 'possession'],
            'pg'   => ['sharing', 'for_gender', 'meals', 'carpet_area', 'area_unit', 'furnishing', 'notice_days'],
            'land' => ['carpet_area', 'area_unit', 'facing', 'boundary', 'approved_by'],
            'commercial' => ['carpet_area', 'area_unit', 'furnishing', 'floor_no', 'total_floors',
                             'seats', 'washrooms', 'facing'],
        ];

        return array_merge($by[$group] ?? $by['home'], $common);
    }
}

if (! function_exists('hi_property_labels')) {
    /** Label + input type for every property field. */
    function hi_property_labels(): array
    {
        return [
            'bedrooms'      => ['Bedrooms', 'select', ['1' => '1 BHK', '2' => '2 BHK', '3' => '3 BHK', '4' => '4 BHK', '5' => '5+ BHK']],
            'bathrooms'     => ['Bathrooms', 'number', []],
            'balconies'     => ['Balconies', 'number', []],
            'carpet_area'   => ['Area', 'number', []],
            'area_unit'     => ['Unit', 'select', ['sqft' => 'sq ft', 'sqyd' => 'sq yd', 'sqm' => 'sq m', 'acre' => 'acre']],
            'furnishing'    => ['Furnishing', 'select', ['unfurnished' => 'Unfurnished', 'semi' => 'Semi-furnished', 'full' => 'Fully furnished']],
            'floor_no'      => ['Floor', 'number', []],
            'total_floors'  => ['Total floors', 'number', []],
            'facing'        => ['Facing', 'select', ['east' => 'East', 'west' => 'West', 'north' => 'North', 'south' => 'South', 'north-east' => 'North-east', 'north-west' => 'North-west', 'south-east' => 'South-east', 'south-west' => 'South-west']],
            'age_years'     => ['Age of property (years)', 'number', []],
            'possession'    => ['Possession', 'select', ['ready' => 'Ready to move', 'under' => 'Under construction']],
            'sharing'       => ['Sharing', 'select', ['single' => 'Single', 'double' => 'Double', 'triple' => 'Triple', 'four' => 'Four or more']],
            'for_gender'    => ['For', 'select', ['any' => 'Anyone', 'male' => 'Boys / men', 'female' => 'Girls / women']],
            'meals'         => ['Meals included', 'check', []],
            'notice_days'   => ['Notice period (days)', 'number', []],
            'boundary'      => ['Boundary wall', 'check', []],
            'approved_by'   => ['Approved by', 'text', []],
            'seats'         => ['Seats', 'number', []],
            'washrooms'     => ['Washrooms', 'number', []],
            'price'         => ['Price', 'number', []],
            'price_period'  => ['Per', 'select', ['total' => 'Total (sale)', 'month' => 'Month', 'year' => 'Year']],
            'deposit'       => ['Security deposit', 'number', []],
            'available_from'=> ['Available from', 'date', []],
            'project'       => ['Project / society', 'text', []],
            'rera'          => ['RERA registration no.', 'text', []],
        ];
    }
}

if (! function_exists('hi_property_columns')) {
    /** Every column on listing_property, for building and for saving. */
    function hi_property_columns(): array
    {
        return array_merge(['deal', 'kind'], array_keys(hi_property_labels()));
    }
}

if (! function_exists('hi_ensure_property_table')) {
    /**
     * Create listing_property if it is missing. Unlike `listing`, this is our
     * own table with no legacy defaults, so the CREATE is safe to run.
     */
    function hi_ensure_property_table($db = null): bool
    {
        static $known = null;
        if ($known !== null) { return $known; }
        $db = $db ?: \Config\Database::connect();
        if ($db->tableExists('listing_property')) { return $known = true; }

        $inc = $db->DBDriver === 'MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        try {
            $db->query('CREATE TABLE listing_property (
                id INTEGER PRIMARY KEY ' . $inc . ',
                listing_id INT NOT NULL,
                deal VARCHAR(10) NULL, kind VARCHAR(20) NULL,
                bedrooms INT NULL, bathrooms INT NULL, balconies INT NULL,
                carpet_area DECIMAL(12,2) NULL, area_unit VARCHAR(8) NULL,
                furnishing VARCHAR(20) NULL, floor_no INT NULL, total_floors INT NULL,
                facing VARCHAR(20) NULL, age_years INT NULL, possession VARCHAR(20) NULL,
                sharing VARCHAR(12) NULL, for_gender VARCHAR(10) NULL, meals TINYINT NULL,
                notice_days INT NULL, boundary TINYINT NULL, approved_by VARCHAR(120) NULL,
                seats INT NULL, washrooms INT NULL,
                price DECIMAL(14,2) NULL, price_period VARCHAR(10) NULL, deposit DECIMAL(14,2) NULL,
                available_from DATE NULL, project VARCHAR(190) NULL, rera VARCHAR(60) NULL,
                created_at DATETIME NULL, updated_at DATETIME NULL
            )');
            $db->query('CREATE UNIQUE INDEX idx_lp_listing ON listing_property (listing_id)');
            $db->query('CREATE INDEX idx_lp_deal ON listing_property (deal, price)');
        } catch (\Throwable $e) {
            // fall through — the check below reports the truth either way
        }
        $db->resetDataCache();

        return $known = $db->tableExists('listing_property');
    }
}

if (! function_exists('hi_is_property')) {
    /** @param array|string $listing */
    function hi_is_property($listing): bool
    {
        $t = is_array($listing) ? ($listing['listing_type'] ?? '') : $listing;

        return strtolower(trim((string) $t)) === 'property';
    }
}

if (! function_exists('hi_only_business')) {
    /**
     * Public browsing shows businesses. Properties have their own front door,
     * so a search for "cafe" can never turn up a flat.
     *
     * @param object $b query builder, aliased or not
     */
    function hi_only_business($b, string $alias = '')
    {
        $col = ($alias !== '' ? $alias . '.' : '') . 'listing_type';

        return $b->groupStart()->where($col . ' !=', 'property')->orWhere($col, null)->groupEnd();
    }
}

if (! function_exists('hi_only_property')) {
    /** @param object $b query builder */
    function hi_only_property($b, string $alias = '')
    {
        return $b->where(($alias !== '' ? $alias . '.' : '') . 'listing_type', 'property');
    }
}

if (! function_exists('hi_property_of')) {
    /** The property row for a listing, or [] — memoised per request. */
    function hi_property_of(int $listingId): array
    {
        static $cache = [];
        if (isset($cache[$listingId])) { return $cache[$listingId]; }
        $db = \Config\Database::connect();
        if (! $db->tableExists('listing_property')) { return $cache[$listingId] = []; }

        return $cache[$listingId] = $db->table('listing_property')
            ->where('listing_id', $listingId)->get()->getRowArray() ?: [];
    }
}

if (! function_exists('hi_property_save')) {
    /** Insert or update the property row for a listing. */
    function hi_property_save(int $listingId, array $in): void
    {
        $db = \Config\Database::connect();
        if (! hi_ensure_property_table($db)) { return; }

        $labels = hi_property_labels();
        $data   = ['listing_id' => $listingId];
        foreach (hi_property_columns() as $col) {
            if (! array_key_exists($col, $in)) { continue; }
            $v    = $in[$col];
            $type = $labels[$col][1] ?? 'text';
            if ($type === 'check')  { $data[$col] = $v ? 1 : 0; continue; }
            $v = trim((string) $v);
            if ($v === '')          { $data[$col] = null; continue; }
            $data[$col] = in_array($type, ['number'], true) ? (float) preg_replace('/[^0-9.]/', '', $v) : $v;
        }

        $exists = $db->table('listing_property')->where('listing_id', $listingId)->countAllResults() > 0;
        if ($exists) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $db->table('listing_property')->where('listing_id', $listingId)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->table('listing_property')->insert($data);
        }
    }
}

if (! function_exists('hi_inr')) {
    /**
     * 4500000 -> "45 Lakh", 12000000 -> "1.2 Cr", 45000 -> "45,000".
     * Named hi_inr, not hi_money: schema_helper already has an hi_money() that
     * formats a plain rupee figure, and the first definition loaded wins.
     */
    function hi_inr($amount): string
    {
        $n = (float) $amount;
        if ($n <= 0) { return ''; }
        if ($n >= 10000000) { return rtrim(rtrim(number_format($n / 10000000, 2, '.', ''), '0'), '.') . ' Cr'; }
        if ($n >= 100000)   { return rtrim(rtrim(number_format($n / 100000, 2, '.', ''), '0'), '.') . ' Lakh'; }

        return number_format($n, 0, '.', ',');
    }
}

if (! function_exists('hi_property_price')) {
    /** "₹45,000/mo", "₹1.2 Cr", "₹8,500/mo per bed" */
    function hi_property_price(array $p): string
    {
        $amt = hi_inr($p['price'] ?? 0);
        if ($amt === '') { return 'Price on request'; }
        $per = ['month' => '/mo', 'year' => '/yr'][$p['price_period'] ?? ''] ?? '';

        return '₹' . $amt . $per;
    }
}

if (! function_exists('hi_property_summary')) {
    /** The one line under a property's name: "2 BHK · 1,150 sq ft · Semi-furnished" */
    function hi_property_summary(array $p): string
    {
        $labels = hi_property_labels();
        $bits   = [];
        if (! empty($p['bedrooms']))   { $bits[] = (int) $p['bedrooms'] . ' BHK'; }
        if (! empty($p['sharing']))    { $bits[] = ($labels['sharing'][2][$p['sharing']] ?? ucfirst($p['sharing'])) . ' sharing'; }
        if (! empty($p['seats']))      { $bits[] = (int) $p['seats'] . ' seats'; }
        if (! empty($p['carpet_area'])) {
            $unit  = $labels['area_unit'][2][$p['area_unit'] ?? 'sqft'] ?? 'sq ft';
            $bits[] = number_format((float) $p['carpet_area'], 0) . ' ' . $unit;
        }
        if (! empty($p['furnishing'])) { $bits[] = $labels['furnishing'][2][$p['furnishing']] ?? ''; }

        return implode(' · ', array_filter($bits));
    }
}

/* ---------------------------------------------------------------------------
 * "What's nearby" — the everyday essentials around a place.
 *
 * The mockup showed this with invented distances. 1,450 of the active listings
 * carry real coordinates, so these are measured instead: a bounding box first
 * (cheap), then haversine on what it returns.
 * ------------------------------------------------------------------------- */

if (! function_exists('hi_essential_cats')) {
    /** label => [icon, name patterns that identify the category] */
    function hi_essential_cats(): array
    {
        return [
            'Pharmacy'    => ['prescription-bottle-medical', ['pharmac', 'chemist', 'medical store']],
            'Hospital'    => ['hospital', ['hospital', 'clinic', 'nursing home']],
            'Grocery'     => ['basket-shopping', ['grocer', 'kirana', 'supermarket', 'daily needs']],
            'Cafe'        => ['mug-hot', ['cafe', 'coffee']],
            'Restaurant'  => ['utensils', ['restaurant', 'family restaurant']],
            'Gym'         => ['dumbbell', ['gym', 'fitness']],
            'Salon'       => ['scissors', ['salon', 'spa', 'parlour']],
            'School'      => ['graduation-cap', ['school', 'playschool', 'preschool']],
            'Bank / ATM'  => ['building-columns', ['bank', 'atm']],
            'Diagnostics' => ['vial', ['diagnostic', 'pathology', 'lab']],
        ];
    }
}

if (! function_exists('hi_km_between')) {
    /** Great-circle distance in km. */
    function hi_km_between(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

if (! function_exists('hi_nearby_essentials')) {
    /**
     * The closest real listing in each everyday category, measured.
     * Returns rows of ['label','icon','listing','km'] — never invented: a
     * category with nothing within reach is simply left out.
     */
    function hi_nearby_essentials(array $listing, float $radiusKm = 3.0, int $limit = 8): array
    {
        $lat = (float) ($listing['latitude'] ?? 0);
        $lng = (float) ($listing['longitude'] ?? 0);
        if (! $lat || ! $lng) { return []; }

        $db = \Config\Database::connect();
        // a degree of latitude is ~111 km; longitude shrinks with latitude
        $dLat = $radiusKm / 111;
        $dLng = $radiusKm / max(1e-6, 111 * cos(deg2rad($lat)));

        $rows = $db->table('listing li')
            ->select('li.id, li.name, li.categories, li.latitude, li.longitude, li.listing_type, l.name AS locality_name, l.slug AS locality_slug')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->where('li.status', 'active')->where('li.id !=', (int) ($listing['id'] ?? 0))
            ->where('li.latitude >=', $lat - $dLat)->where('li.latitude <=', $lat + $dLat)
            ->where('li.longitude >=', $lng - $dLng)->where('li.longitude <=', $lng + $dLng)
            ->limit(400)->get()->getResultArray();
        if (! $rows) { return []; }

        // category id => label, resolved once
        static $catMap = null;
        if ($catMap === null) {
            $catMap = [];
            foreach (hi_essential_cats() as $label => [$icon, $needles]) {
                foreach ($db->table('category')->select('id, name')->get()->getResultArray() as $c) {
                    $n = strtolower(html_entity_decode((string) $c['name'], ENT_QUOTES | ENT_HTML5));
                    foreach ($needles as $needle) {
                        if (str_contains($n, $needle)) { $catMap[(int) $c['id']] = $label; break 2; }
                    }
                }
            }
        }

        $mine = array_map('intval', (array) json_decode($listing['categories'] ?? '[]', true));
        $best = [];
        foreach ($rows as $r) {
            $cids = array_map('intval', (array) json_decode($r['categories'] ?? '[]', true));
            foreach ($cids as $cid) {
                $label = $catMap[$cid] ?? null;
                if ($label === null || in_array($cid, $mine, true)) { continue; }
                $km = hi_km_between($lat, $lng, (float) $r['latitude'], (float) $r['longitude']);
                if ($km > $radiusKm) { continue; }
                if (! isset($best[$label]) || $km < $best[$label]['km']) {
                    [$icon] = hi_essential_cats()[$label];
                    // the category id travels with it, so the card can link to
                    // the others of its kind nearby rather than to a whole
                    // city-wide category
                    $best[$label] = ['label' => $label, 'icon' => $icon, 'listing' => $r,
                                      'km' => $km, 'cat_id' => $cid];
                }
                break;
            }
        }
        uasort($best, static fn ($a, $b) => $a['km'] <=> $b['km']);

        return array_slice(array_values($best), 0, $limit);
    }
}

if (! function_exists('hi_nearby_localities')) {
    /**
     * Localities closest to this one. There are no coordinates on `locality`,
     * so each one's centre is the average of its listings' positions.
     */
    function hi_nearby_localities(int $localityId, int $limit = 6): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('listing li')
            ->select('li.locality_id, l.name, l.slug, AVG(li.latitude) AS y, AVG(li.longitude) AS x, COUNT(*) AS n')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->where('li.status', 'active')
            ->where('li.latitude IS NOT NULL', null, false)
            ->groupBy('li.locality_id')->having('n >', 2)
            ->get()->getResultArray();

        $me = null;
        foreach ($rows as $r) { if ((int) $r['locality_id'] === $localityId) { $me = $r; break; } }
        if (! $me || ! $me['y']) { return []; }

        $out = [];
        foreach ($rows as $r) {
            if ((int) $r['locality_id'] === $localityId || empty($r['slug'])) { continue; }
            $out[] = $r + ['km' => hi_km_between((float) $me['y'], (float) $me['x'], (float) $r['y'], (float) $r['x'])];
        }
        usort($out, static fn ($a, $b) => $a['km'] <=> $b['km']);

        return array_slice($out, 0, $limit);
    }
}

if (! function_exists('hi_ensure_rera_column')) {
    /** RERA arrived after listing_property shipped, so add it where missing. */
    function hi_ensure_rera_column($db = null): bool
    {
        static $known = null;
        if ($known !== null) { return $known; }
        $db = $db ?: \Config\Database::connect();
        if (! $db->tableExists('listing_property')) { return $known = false; }
        if ($db->fieldExists('rera', 'listing_property')) { return $known = true; }
        try { $db->query('ALTER TABLE listing_property ADD COLUMN rera VARCHAR(60) NULL'); } catch (\Throwable $e) {}
        $db->resetDataCache();

        return $known = $db->fieldExists('rera', 'listing_property');
    }
}

if (! function_exists('hi_property_badges')) {
    /**
     * The trust markers the mockup showed. Every one is read from real data —
     * a listing with no RERA number simply does not get the badge.
     */
    function hi_property_badges(array $p, array $listing = []): array
    {
        $out = [];
        if (($p['possession'] ?? '') === 'ready') { $out[] = ['Ready to move', 'key', 'ok']; }
        if (($p['possession'] ?? '') === 'under') { $out[] = ['New launch', 'helmet-safety', 'new']; }
        if (trim((string) ($p['rera'] ?? '')) !== '') { $out[] = ['RERA registered', 'certificate', 'ok']; }
        if (! empty($listing['is_featured'])) { $out[] = ['Verified owner', 'circle-check', 'ok']; }
        if (! empty($p['meals'])) { $out[] = ['Meals included', 'utensils', 'ok']; }

        return $out;
    }
}

if (! function_exists('hi_nearby_places')) {
    /**
     * Named landmarks near a listing — the malls, societies and blocks in the
     * live `area` table, 291 of them.
     *
     * The mockup faked these ("Nearest metro · 1.2 km"). There is no metro data
     * here to measure against, so rather than invent numbers this uses the real
     * places the directory already knows: each area's position is the average
     * of the listings tagged to it, and the distance is measured from there.
     */
    function hi_nearby_places(array $listing, float $radiusKm = 4.0, int $limit = 6): array
    {
        $lat = (float) ($listing['latitude'] ?? 0);
        $lng = (float) ($listing['longitude'] ?? 0);
        if (! $lat || ! $lng) { return []; }

        $db = \Config\Database::connect();
        if (! $db->tableExists('area')) { return []; }

        // area centres, worked out once per request
        static $centres = null;
        if ($centres === null) {
            $centres = $db->table('listing li')
                ->select('li.area_id, a.name, a.slug, AVG(li.latitude) AS y, AVG(li.longitude) AS x, COUNT(*) AS n')
                ->join('area a', 'a.id = li.area_id')
                ->where('li.status', 'active')->where('li.area_id >', 0)
                ->where('li.latitude IS NOT NULL', null, false)
                ->groupBy('li.area_id')->having('n >=', 2)
                ->get()->getResultArray();
        }

        $mine = (int) ($listing['area_id'] ?? 0);
        $out  = [];
        foreach ($centres as $c) {
            if ((int) $c['area_id'] === $mine || empty($c['name'])) { continue; }
            // some `area` rows are address fragments rather than places —
            // "near Quick mart grocery store" is a direction, not a landmark
            $nm = trim(html_entity_decode((string) $c['name'], ENT_QUOTES | ENT_HTML5));
            if (preg_match('/^(near|opp\.?|opposite|behind|beside|next to)\b/i', $nm)) { continue; }
            if (mb_strlen($nm) < 3) { continue; }
            $c['name'] = $nm;
            $km = hi_km_between($lat, $lng, (float) $c['y'], (float) $c['x']);
            if ($km > $radiusKm) { continue; }
            $out[] = ['name' => $c['name'], 'slug' => (string) ($c['slug'] ?? ''), 'km' => $km, 'n' => (int) $c['n']];
        }
        usort($out, static fn ($a, $b) => $a['km'] <=> $b['km']);

        return array_slice($out, 0, $limit);
    }
}

if (! function_exists('hi_landmark_types')) {
    /**
     * The landmark slots the mockup shows, each matched against the real place
     * names in `area`. A slot with nothing within range is left out — the
     * mockup filled those with random distances, which would make the measured
     * ones untrustworthy too.
     */
    function hi_landmark_types(): array
    {
        return [
            'Metro station' => ['train-subway', ['metro station', 'metro stn'], []],
            'Market'        => ['store', ['market'], ['mall', 'supermarket']],
            'Mall'          => ['bag-shopping', ['mall'], []],
            'Park'          => ['tree', ['park'], ['business park', 'it park', 'tech park', 'industrial park', 'knowledge park']],
            'Hospital'      => ['hospital', ['hospital'], []],
            'School'        => ['graduation-cap', ['school', 'college', 'university'], []],
            'Stadium'       => ['futbol', ['stadium'], []],
        ];
    }
}

if (! function_exists('hi_nearby_landmarks')) {
    /**
     * One nearest real place per landmark type, measured.
     * Returns ['label','icon','name','slug','km'] rows.
     */
    function hi_nearby_landmarks(array $listing, float $radiusKm = 5.0): array
    {
        $places = hi_nearby_places($listing, $radiusKm, 400);   // already sorted by distance
        if (! $places) { return []; }

        $out = [];
        foreach (hi_landmark_types() as $label => [$icon, $needles, $exclude]) {
            foreach ($places as $p) {
                $n = mb_strtolower($p['name']);
                foreach ($exclude as $x) { if (str_contains($n, $x)) { continue 2; } }
                foreach ($needles as $needle) {
                    if (str_contains($n, $needle)) {
                        $nm = hi_place_name($p['name']);
                        if ($nm === '') { continue; }
                        $out[] = ['label' => $label, 'icon' => $icon, 'name' => $nm,
                                  'slug' => $p['slug'], 'km' => $p['km']];
                        continue 3;
                    }
                }
            }
        }
        usort($out, static fn ($a, $b) => $a['km'] <=> $b['km']);

        return $out;
    }
}

if (! function_exists('hi_property_amenities')) {
    /**
     * What a flat, PG or plot actually offers. The shared `amenities` table was
     * built for hotels and hospitals — Mini Bar, Buffets, Valet Parking — none
     * of which mean anything on a 2 BHK. These are the ones a renter or buyer
     * scans for, keyed by name so the rows can be seeded once and matched after.
     */
    function hi_property_amenities(): array
    {
        return [
            'Swimming pool'      => 'person-swimming',
            'Gymnasium'          => 'dumbbell',
            'Lift'               => 'elevator',
            'Power backup'       => 'bolt',
            '24x7 security'      => 'shield-halved',
            'CCTV surveillance'  => 'video',
            'Covered parking'    => 'square-parking',
            'Visitor parking'    => 'car',
            'Club house'         => 'champagne-glasses',
            'Kids play area'     => 'child-reaching',
            'Landscaped garden'  => 'tree',
            'Gated society'      => 'torii-gate',
            'Modular kitchen'    => 'kitchen-set',
            'Piped gas'          => 'fire-flame-simple',
            '24x7 water supply'  => 'droplet',
            'Rainwater harvest'  => 'cloud-rain',
            'Intercom'           => 'phone',
            'Fire safety'        => 'fire-extinguisher',
            'Servant room'       => 'bed',
            'Vaastu compliant'   => 'compass',
            'Wheelchair access'  => 'wheelchair',
            'Waste disposal'     => 'trash-can',
        ];
    }
}

if (! function_exists('hi_ensure_property_amenities')) {
    /**
     * Seeds the property amenities into the shared table once, so the module
     * works on a server where today's SQL has not been run. Existing rows are
     * matched by name and left alone — nothing is ever updated or removed.
     */
    function hi_ensure_property_amenities($db = null): array
    {
        static $ids = null;
        if ($ids !== null) { return $ids; }

        $db = $db ?: \Config\Database::connect();
        if (! $db->tableExists('amenities')) { return $ids = []; }

        $have = [];
        foreach ($db->table('amenities')->select('id, name')->get()->getResultArray() as $r) {
            $have[strtolower(trim(html_entity_decode((string) $r['name'], ENT_QUOTES | ENT_HTML5)))] = (int) $r['id'];
        }

        $ids = [];
        foreach (hi_property_amenities() as $name => $icon) {
            $k = strtolower($name);
            if (! isset($have[$k])) {
                $db->table('amenities')->insert([
                    'name' => $name,
                    'icon' => $icon,
                    'slug' => hi_slug($name),
                ]);
                $have[$k] = (int) $db->insertID();
            }
            $ids[$have[$k]] = $name;
        }

        return $ids;
    }
}

if (! function_exists('hi_amenity_choices')) {
    /**
     * The amenity checkboxes to offer for a listing: the property set for a
     * property, everything else for a business. Without the split a flat was
     * offered Buffets and a restaurant was offered Vaastu.
     */
    function hi_amenity_choices(array $listing, $db = null): array
    {
        $db = $db ?: \Config\Database::connect();
        if (! $db->tableExists('amenities')) { return []; }

        $all = $db->table('amenities')->orderBy('name', 'ASC')->get()->getResultArray();

        if (hi_is_property($listing)) {
            $mine = hi_ensure_property_amenities($db);
            if ($mine) {
                $all  = $db->table('amenities')->orderBy('name', 'ASC')->get()->getResultArray();
                $keep = array_flip(array_keys($mine));

                return array_values(array_filter($all, static fn ($a) => isset($keep[(int) $a['id']])));
            }

            return $all;
        }

        // a business should not be offered the flat-only ones
        $prop = array_map('strtolower', array_keys(hi_property_amenities()));

        return array_values(array_filter($all, static fn ($a) => ! in_array(
            strtolower(trim(html_entity_decode((string) $a['name'], ENT_QUOTES | ENT_HTML5))), $prop, true
        )));
    }
}

if (! function_exists('hi_shows_nearby')) {
    /**
     * Whether "What's nearby" belongs on this page.
     *
     * A flat entered through the property wizard is the obvious case, but the
     * whole Property category tree wants it too — a coworking desk, a PG, a
     * shop or a builder's site is chosen on what surrounds it just as much as
     * a 2 BHK is. Those are ordinary listings with listing_type "general", so
     * hi_is_property() alone left them without the block.
     */
    function hi_shows_nearby(array $listing, $db = null): bool
    {
        if (hi_is_property($listing)) { return true; }

        $cids = json_decode((string) ($listing['categories'] ?? ''), true);
        if (! is_array($cids) || ! $cids) { return false; }
        $cids = array_map('intval', $cids);

        // the Property root and everything under it, resolved once per request
        static $tree = null;
        if ($tree === null) {
            $db   = $db ?: \Config\Database::connect();
            $root = (int) (hi_cat_id_by_slug('property') ?: 0);
            $tree = [];
            if ($root > 0) {
                $tree[$root] = true;
                foreach ($db->table('category')->select('id')->where('parent', $root)->get()->getResultArray() as $c) {
                    $tree[(int) $c['id']] = true;
                }
            }
        }

        foreach ($cids as $cid) {
            if (isset($tree[$cid])) { return true; }
        }

        return false;
    }
}

if (! function_exists('hi_place_name')) {
    /**
     * Tidies a row from `area` into something that reads as a place name.
     *
     * Several were entered as directions rather than names — "Near Metro
     * Station", "opposite Noida Stadium, Block O". Printed beside a measured
     * distance those are nonsense: "Near Metro Station 400 m" tells a buyer
     * nothing. A bare direction is dropped; a real name carrying a direction
     * or a trailing block reference keeps only the name.
     *
     * Returns '' when nothing usable is left, so the caller can skip it.
     */
    function hi_place_name(string $raw): string
    {
        $name = trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5));

        // a trailing ", Block O" / ", Sector 62" is an address, not the name
        $name = (string) preg_replace('/,\s*[a-z0-9\-]{0,3}\s*(block|sector|pocket)\b.*$/i', '', $name);

        // a leading direction word: keep whatever follows it
        $name = (string) preg_replace(
            '/^(near|nearby|opposite|opp\.?|behind|beside|next\s+to|in\s+front\s+of|adjacent\s+to)\s+/i',
            '', $name
        );

        $name = trim($name, " \t\n\r\0\x0B,-");

        // "Metro Station" on its own names no particular station
        if (preg_match('/^(metro\s*(station|stn)?|station|stadium|market|mall|park)$/i', $name)) { return ''; }

        return mb_strlen($name) < 3 ? '' : $name;
    }
}
