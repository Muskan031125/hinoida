<?php

namespace App\Controllers;

/**
 * The small XHR endpoints the original scattered across Home, User, Admin and
 * Listing: cascading place dropdowns, the visitor's chosen location, and the
 * footer's category list.
 *
 * Home::set_filter is not here — it is commented out in the original.
 */
class Ajax extends BaseController
{
    /** Cities in a state — Admin/Home/User::get_city_list_by_country_id. */
    public function cities()
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('city')) { return $this->response->setJSON([]); }

        $b = $db->table('city')->select('id, name')->orderBy('name', 'ASC');
        $state = (int) ($this->request->getPost('state_id') ?? $this->request->getGet('state_id'));
        if ($state) { $b->where('state_id', $state); }

        return $this->response->setJSON($b->get()->getResultArray());
    }

    /** Areas in a city — User::get_localities_by_city. */
    public function localities()
    {
        $db  = \Config\Database::connect();
        $b   = $db->table('locality')->select('id, name, slug')->orderBy('name', 'ASC');
        $city = (int) ($this->request->getPost('city_id') ?? $this->request->getGet('city_id'));
        if ($city && $db->fieldExists('city_id', 'locality')) { $b->where('city_id', $city); }

        return $this->response->setJSON($b->get()->getResultArray());
    }

    /**
     * Categories that actually have a listing in this area —
     * Home::get_categories_by_locality. Offering a category with nothing in it
     * is a dead end, so empty ones are dropped.
     */
    public function categoriesByLocality()
    {
        $db  = \Config\Database::connect();
        $lid = (int) ($this->request->getPost('locality_id') ?? $this->request->getGet('locality_id'));
        if (! $lid) { return $this->response->setJSON([]); }

        $counts = [];
        foreach ($db->table('listing')->select('categories')
            ->where('locality_id', $lid)->where('status', 'active')->get()->getResultArray() as $r) {
            foreach ((array) json_decode($r['categories'] ?? '[]', true) as $cid) {
                $cid = (int) $cid;
                if ($cid) { $counts[$cid] = ($counts[$cid] ?? 0) + 1; }
            }
        }
        if (! $counts) { return $this->response->setJSON([]); }

        arsort($counts);
        $rows = $db->table('category')->select('id, name, slug')
            ->whereIn('id', array_keys($counts))->get()->getResultArray();

        $out = [];
        foreach ($rows as $c) { $out[(int) $c['id']] = $c + ['n' => $counts[(int) $c['id']] ?? 0]; }

        // keep the busiest first
        $ordered = [];
        foreach (array_keys($counts) as $cid) { if (isset($out[$cid])) { $ordered[] = $out[$cid]; } }

        return $this->response->setJSON($ordered);
    }

    /** Areas for a locality picker — User::get_areas_by_locality. */
    public function areas()
    {
        return $this->localities();
    }

    /**
     * Remember where the visitor said they are — Home::set_location.
     * Only ever stored in their own session.
     */
    /**
     * The header locality filter.
     *
     * Remembers one locality for the session and sends the visitor back where
     * they were. Everything that lists businesses reads it, so choosing
     * "Sector 62" once narrows browsing until it is cleared.
     */
    public function setLocality(int $id = 0)
    {
        $db = \Config\Database::connect();
        if ($id > 0) {
            $l = $db->table('locality')->select('id, name, slug')->where('id', $id)->get()->getRowArray();
            if ($l) {
                session()->set(['loc_id' => (int) $l['id'], 'loc_name' => (string) $l['name']]);
            }
        } else {
            session()->remove(['loc_id', 'loc_name']);
        }

        // back to the page they were on, but never off-site
        $back = (string) ($this->request->getGet('r') ?? '');
        $path = $back !== '' ? parse_url($back, PHP_URL_PATH) : '';
        if (! is_string($path) || $path === '' || str_starts_with($path, '//')) { $path = '/'; }
        $q = $back !== '' ? parse_url($back, PHP_URL_QUERY) : '';

        return redirect()->to($path . ($q ? '?' . $q : ''));
    }

    public function setLocation()
    {
        $place = trim((string) $this->request->getPost('suburb'));
        if ($place === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'No place given']);
        }

        $lat = $this->request->getPost('latitude');
        $lng = $this->request->getPost('longitude');

        session()->set([
            'current_location' => mb_substr($place, 0, 120),
            'latitude'         => is_numeric($lat) ? (float) $lat : null,
            'longitude'        => is_numeric($lng) ? (float) $lng : null,
        ]);

        return $this->response->setJSON(['ok' => true, 'location' => session()->get('current_location')]);
    }

    /** The footer's "more categories" list — Home::footer_more_category. */
    public function footerCategories()
    {
        $db    = \Config\Database::connect();
        $limit = max(1, min(200, (int) ($this->request->getGet('limit') ?: 40)));
        $rows  = $db->table('category')->select('id, name, slug')
            ->where('parent', 0)->orderBy('name', 'ASC')->limit($limit)->get()->getResultArray();

        return $this->response->setJSON($rows);
    }

    /**
     * Everything the search box's suggestion dropdown needs, in one small
     * JSON file: categories (with counts), areas and active listing names.
     * Built at most once every 10 minutes and cached by the browser for an
     * hour, so typing in the box never queries the database.
     */
    public function searchIndex()
    {
        $cache = \Config\Services::cache();
        $data  = $cache->get('search_index_v1');
        if ($data === null) {
            $db  = \Config\Database::connect();
            $dec = static fn ($s) => html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $rows = $db->table('listing li')
                ->select('li.id, li.name, li.listing_type, li.categories, li.locality_id, l.name AS loc')
                ->join('locality l', 'l.id = li.locality_id', 'left')
                ->where('li.status', 'active')->get()->getResultArray();

            $catCount = []; $locIds = [];
            $L = [];
            foreach ($rows as $r) {
                $cids = json_decode((string) $r['categories'], true);
                $cids = is_array($cids) ? array_map('intval', $cids) : [];
                foreach ($cids as $c) { $catCount[$c] = ($catCount[$c] ?? 0) + 1; }
                if ($r['locality_id']) { $locIds[(int) $r['locality_id']] = true; }
                // [name, url, area, category ids]
                $L[] = [$dec($r['name']), hi_listing_url($r), self::areaName($dec($r['loc'] ?? '')), $cids];
            }

            $C = [];
            foreach ($db->table('category')->select('id, name, slug, parent')->get()->getResultArray() as $c) {
                $n = $catCount[(int) $c['id']] ?? 0;
                if ($n === 0) { continue; }
                // [id, name, url, count, parent id]
                $C[] = [(int) $c['id'], $dec($c['name']), hi_category_url($c['slug']), $n, (int) $c['parent']];
            }

            $A = [];
            if ($locIds) {
                foreach ($db->table('locality')->select('id, name, slug')->whereIn('id', array_keys($locIds))->get()->getResultArray() as $a) {
                    // [id, name, url]
                    $A[] = [(int) $a['id'], self::areaName($dec($a['name'])), hi_locality_url($a['slug'] ?? '')];
                }
            }

            $data = ['c' => $C, 'a' => $A, 'l' => $L, 'search' => hi_page('search')];
            $cache->save('search_index_v1', $data, 600);
        }

        return $this->response->setHeader('Cache-Control', 'public, max-age=3600')->setJSON($data);
    }

    /** "Sector - 66" and "sector 66" both read as "Sector 66". */
    private static function areaName(string $n): string
    {
        $n = trim($n);

        return preg_match('/^sector\s*-?\s*(\d+\w?)$/i', $n, $m) ? 'Sector ' . $m[1] : $n;
    }
}
