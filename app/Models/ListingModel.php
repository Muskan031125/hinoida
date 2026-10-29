<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ListingModel — active business listings, with locality name + rating.
 */
class ListingModel extends Model
{
    protected $table      = 'listing';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /** Featured / recent active listings for the home page. */
    public function featured(int $limit = 8): array
    {
        // A property is a listing like any other in these rows — it shows in
        // Featured, Popular and New beside the businesses, on its own card.
        $rows = $this->db->table('listing li')
            ->select('li.*, l.name AS locality_name')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->where('li.status', 'active')
            ->orderBy('li.is_featured', 'DESC')
            ->orderBy('li.id', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
        return $this->attachRatings($rows);
    }

    /** Active listings in one category (by category id in the JSON array). */
    public function inCategory(int $catId, int $limit = 24): array
    {
        $rows = $this->db->table('listing li')
            ->select('li.*, l.name AS locality_name')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->where('li.status', 'active')
            ->groupStart()->like('li.categories', '"' . $catId . '"')->orLike('li.categories', '[' . $catId . ']')->groupEnd()
            ->limit($limit)
            ->get()->getResultArray();
        return $this->attachRatings($rows);
    }

    /** Attach avg rating + count from the review table. */
    public function attachRatings(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }
        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        $map = [];
        if ($this->db->tableExists('review')) {
            $rb = $this->db->table('review')
                ->select('listing_id, AVG(review_rating) AS avg, COUNT(*) AS c')
                ->whereIn('listing_id', $ids);
            // a review an admin hid must not count towards the rating either
            if ($this->db->fieldExists('is_hidden', 'review')) { $rb->groupStart()->where('is_hidden', 0)->orWhere('is_hidden', null)->groupEnd(); }
            $rr = $rb->groupBy('listing_id')->get()->getResultArray();
            foreach ($rr as $r) {
                $map[(int) $r['listing_id']] = ['avg' => round((float) $r['avg'], 1), 'count' => (int) $r['c']];
            }
        }
        // primary category name — drives category-matched imagery + facts
        $catIds = [];
        foreach ($rows as $r) {
            $c = json_decode($r['categories'] ?? '[]', true);
            if (is_array($c)) { foreach ($c as $cid) { $catIds[] = (int) $cid; } }
        }
        $catMap = [];
        if ($catIds && $this->db->tableExists('category')) {
            // c.name plus the parent name, so a leaf like "Chole Bhature"
            // still resolves to its group via "Street Food & Snacks"
            foreach ($this->db->table('category c')
                ->select('c.id, c.name, p.name AS parent_name')
                ->join('category p', 'p.id = c.parent', 'left')
                ->whereIn('c.id', array_unique($catIds))->get()->getResultArray() as $c) {
                $catMap[(int) $c['id']] = ['name' => $c['name'], 'parent' => $c['parent_name'] ?? ''];
            }
        }

        foreach ($rows as &$r) {
            $id           = (int) $r['id'];
            $r['_ravg']   = $map[$id]['avg']   ?? 0;
            $r['_rcount'] = $map[$id]['count'] ?? 0;
            // use the first category that resolves to a real group, so a
            // sub-category like "North Indian" doesn't fall back to generic
            $c    = json_decode($r['categories'] ?? '[]', true);
            $name = ''; $sub = '';
            if (is_array($c)) {
                helper('facts');
                foreach ($c as $cid) {
                    $e = $catMap[(int) $cid] ?? null;
                    if ($e === null) { continue; }
                    if ($name === '') { $name = $e['name']; }   // fallback = first known
                    if (hi_group_key_or_blank($e['name']) !== '') { $name = $e['name']; break; }
                    if ($e['parent'] !== '' && hi_group_key_or_blank($e['parent']) !== '') { $name = $e['parent']; break; }
                }
            }
            // the specific kind (e.g. PG/Hostel) — looked at separately, the loop above stops early
            if (is_array($c)) { foreach ($c as $cid) { $e = $catMap[(int) $cid] ?? null; if ($e && $e['parent'] !== '') { $sub = $e['name']; break; } } }
            $r['_cat_name'] = $name;
            $r['_sub_name'] = html_entity_decode((string) $sub, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $rows;
    }
}
