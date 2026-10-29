<?php
namespace App\Controllers;

use App\Models\ListingModel;

class Compare extends BaseController
{
    /** how many a visitor may line up in total, across every category */
    private const MAX_PICKS = 12;

    public function index(): string
    {
        if (! hi_addon('compare')) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        helper('seo');

        $ids = array_slice(
            array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $this->request->getGet('ids')))))),
            0,
            self::MAX_PICKS
        );

        $rows = [];
        if ($ids) {
            $db   = \Config\Database::connect();
            $rows = $db->table('listing li')->select('li.*, l.name AS locality_name')
                ->join('locality l', 'l.id = li.locality_id', 'left')
                ->whereIn('li.id', $ids)->where('li.status', 'active')->get()->getResultArray();
            $rows = (new ListingModel())->attachRatings($rows);
            // preserve the order the user added them
            usort($rows, static fn ($a, $b) => array_search((int) $a['id'], $ids, true) <=> array_search((int) $b['id'], $ids, true));

            // primary category, in one query rather than one per row
            $catIds = [];
            foreach ($rows as $r) {
                $cids = json_decode($r['categories'] ?? '[]', true);
                $catIds[(int) $r['id']] = (is_array($cids) && $cids) ? (int) $cids[0] : 0;
            }
            $names = [];
            if ($wanted = array_values(array_filter(array_unique($catIds)))) {
                foreach ($db->table('category')->select('id, name')->whereIn('id', $wanted)->get()->getResultArray() as $c) {
                    $names[(int) $c['id']] = (string) $c['name'];
                }
            }
            foreach ($rows as &$r) {
                $cid          = $catIds[(int) $r['id']] ?? 0;
                $r['_cat_id'] = $cid;
                $r['_cat']    = $names[$cid] ?? '';
            }
            unset($r);
        }

        // Comparing a salon against a cardiologist tells you nothing, so the
        // table only ever holds one category. Anything else the visitor picked
        // is kept and offered as its own group rather than being thrown away.
        // A property groups by what it *is*, not by the category its dealer sits
        // in — every demo flat shares one category, and a plot has nothing in
        // common with a PG bed either.
        helper('property');
        $propLabels = ['home' => 'Flats & houses', 'pg' => 'PG & hostels',
                       'land' => 'Plots & land', 'commercial' => 'Commercial space'];
        $groups = [];
        foreach ($rows as $r) {
            if (hi_is_property($r)) {
                $pr   = hi_property_of((int) $r['id']);
                $grp  = hi_property_group((string) ($pr['kind'] ?? ''));
                $key  = 'p:' . $grp;
                $name = $propLabels[$grp] ?? 'Property';
            } else {
                $key  = (int) $r['_cat_id'];
                $name = $r['_cat'] ?: 'Uncategorised';
            }
            if (! isset($groups[$key])) {
                $groups[$key] = ['id' => $key, 'name' => $name, 'rows' => [],
                                 'isProp' => hi_is_property($r)];
            }
            $groups[$key]['rows'][] = $r;
        }
        // biggest group first — that is the comparison they are most likely after
        uasort($groups, static fn ($a, $b) => count($b['rows']) <=> count($a['rows']));

        // group keys are ints for categories and "p:home" for property sets
        $active = (string) $this->request->getGet('cat');
        if (! isset($groups[$active])) {
            $active = $groups ? (string) array_key_first($groups) : '';
        }

        return view('frontend/compare', [
            'title' => 'Compare businesses in Noida | HiNoida',
            'metaDescription' => 'Compare Noida businesses side by side — ratings, category, area, hours and contact — on HiNoida.',
            'canonical' => base_url('compare'),
            'robots' => 'noindex,follow',
            // the table works on the active group only; the sidebar sees them all
            'rows'    => $groups[$active]['rows'] ?? [],
            'groups'  => $groups,
            'active'  => $active,
            'activeIsProp' => ! empty($groups[$active]['isProp']),
            'allIds'  => $ids,
            'maxPicks' => self::MAX_PICKS,
        ]);
    }
}
