<?php

namespace App\Controllers;

/**
 * Permanent redirects from the short paths the rebuild used before the live
 * hinoida.com URL shapes were restored. Nothing here renders — every method
 * answers 301 and points at the canonical URL built by hiurl_helper.
 */
class Legacy extends BaseController
{
    /** Keep whatever the visitor was filtering by. */
    private function go(string $url)
    {
        $qs = $this->request->getUri()->getQuery();

        return redirect()->to($url . ($qs !== '' ? '?' . $qs : ''), 301);
    }

    /** /l/28 → /restaurant/burger-king/28 */
    public function listing(int $id)
    {
        return $this->go(hi_listing_url($id));
    }

    /** /c/restaurants → /category/restaurants */
    public function category(string $slug)
    {
        return $this->go(hi_category_url($slug));
    }

    /** /area/sector-63 → /sector-63 */
    public function locality(string $slug)
    {
        return $this->go(hi_locality_url($slug));
    }

    /** /news/some-slug → /news/post/81/some-slug (and the same for events, offers) */
    public function post(string $kind, string $slug)
    {
        $db  = \Config\Database::connect();
        $src = hi_content_src($kind === 'news' ? 'blog' : $kind, $db);
        $row = null;
        if ($src) {
            $q = $db->table($src['table'])->where('slug', $slug);
            if ($src['type'] !== null) { $q->where('type', $src['type']); }
            $row = $q->get()->getRowArray();
        }
        if (! $row && $db->tableExists('offer') && $kind === 'offer') {
            $row = $db->table('offer')->where('slug', $slug)->get()->getRowArray();
        }
        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->go(hi_post_url($kind, $row));
    }

    /** Named static pages: /about → /home/about, /search → /listings … */
    public function page(string $key, ?string $sub = null)
    {
        return $this->go(hi_page($sub !== null ? $key . '/' . $sub : $key));
    }
}
