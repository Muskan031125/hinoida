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
}
