<?php

namespace App\Controllers;

/**
 * The full category index — every parent with its sub-categories and counts,
 * as the original's Categories page shows. The homepage grid only surfaces the
 * busiest dozen, so there was no way to see the whole tree.
 *
 * Counts respect the header locality picker, so choosing Sector 18 shows how
 * many of each category are actually there rather than city-wide totals.
 */
class Categories extends BaseController
{
    public function index(): string
    {
        helper(['seo', 'taxonomy']);
        $db = \Config\Database::connect();

        // an explicit ?locality= wins, otherwise the header picker applies
        $loc = (int) ($this->request->getGet('locality') ?: session()->get('loc_id'));

        $cats = $db->table('category')->select('id, name, slug, parent')->orderBy('name', 'ASC')->get()->getResultArray();

        // one pass over the listings rather than a count query per category —
        // 118 categories would otherwise mean 118 round trips
        $counts = [];
        $lq = $db->table('listing')->select('categories')->where('status', 'active');
        if ($loc > 0) { $lq->where('locality_id', $loc); }
        foreach ($lq->get()->getResultArray() as $l) {
            foreach ((array) json_decode($l['categories'] ?? '[]', true) as $cid) {
                $cid = (int) $cid;
                if ($cid) { $counts[$cid] = ($counts[$cid] ?? 0) + 1; }
            }
        }

        $byParent = [];
        foreach ($cats as $c) {
            if ((int) $c['parent'] > 0) { $byParent[(int) $c['parent']][] = $c; }
        }

        $tree = [];
        foreach ($cats as $c) {
            if ((int) $c['parent'] !== 0) { continue; }
            $kids = $byParent[(int) $c['id']] ?? [];
            foreach ($kids as &$k) { $k['n'] = (int) ($counts[(int) $k['id']] ?? 0); }
            unset($k);
            // a sub-category with nothing in it is a dead end
            $kids = array_values(array_filter($kids, static fn ($k) => $k['n'] > 0));
            usort($kids, static fn ($a, $b) => $b['n'] <=> $a['n']);

            $own = (int) ($counts[(int) $c['id']] ?? 0);
            $tot = $own + array_sum(array_column($kids, 'n'));
            if ($tot === 0) { continue; }

            $tree[] = ['cat' => $c, 'kids' => $kids, 'total' => $tot];
        }
        usort($tree, static fn ($a, $b) => $b['total'] <=> $a['total']);
        // and Property to the front, whatever its total — the name sits under
        // 'cat' here, not on the row itself
        usort($tree, static fn ($a, $b) =>
            (int) (stripos((string) ($b['cat']['name'] ?? ''), 'propert') === 0)
            <=> (int) (stripos((string) ($a['cat']['name'] ?? ''), 'propert') === 0));

        $locs = $db->table('locality l')
            ->select('l.id, l.name, (SELECT COUNT(*) FROM listing li WHERE li.locality_id = l.id AND li.status = "active") AS n')
            ->orderBy('l.name', 'ASC')->get()->getResultArray();
        $locs = array_values(array_filter($locs, static fn ($x) => (int) $x['n'] > 0));

        $locName = '';
        foreach ($locs as $l) { if ((int) $l['id'] === $loc) { $locName = (string) $l['name']; } }

        $seo   = hi_page_seo('category');
        $where = $locName !== '' ? ' in ' . $locName : ' in Noida';
        $url   = hi_page('categories');

        return view('frontend/categories', [
            'title'           => $seo['title'] ?? ('All Categories' . $where . ' | HiNoida'),
            'metaDescription' => $seo['metaDescription']
                ?? ('Browse every business category' . $where . ' — restaurants, hospitals, salons, shopping, services and more on HiNoida.'),
            'canonical' => $url,
            'tree'      => $tree,
            'locs'      => $locs,
            'loc'       => $loc,
            'locName'   => $locName,
            'jsonld'    => [hi_breadcrumb(['Home' => base_url('/'), 'Categories' => $url])],
        ]);
    }
}
