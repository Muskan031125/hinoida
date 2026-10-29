<?php
namespace App\Controllers;

class Offers extends BaseController
{
    /** @param string|null $catSlug from /offer/category/{slug} */
    public function index(?string $catSlug = null): string
    {
        helper('seo');
        $db = \Config\Database::connect();
        $q    = hi_content_q('offer', $db);
        $rows = hi_content_rows($q ? $q->orderBy('id', 'DESC')->get()->getResultArray() : []);
        // Owner-submitted offers live in their own table with a business link.
        // When `blogs` is the editorial source these are a separate set, so show
        // both rather than silently dropping everything owners published.
        $src = hi_content_src('offer', $db);
        if ($src && $src['table'] !== 'offer' && $db->tableExists('offer')) {
            $own = $db->table('offer')->where('status', 'active')->orderBy('id', 'DESC')->get()->getResultArray();
            $rows = array_merge(hi_content_rows($own), $rows);
        }
        $q    = trim((string) $this->request->getGet('q'));
        $total= count($rows);
        $cats = hi_content_cats($rows);            // before the category filter
        $cat  = (int) $this->request->getGet('cat');
        if ($catSlug !== null && $catSlug !== '') {
            $cat = hi_cat_id_by_slug($catSlug, $cats);
            if ($cat === 0) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        }
        if ($cat > 0) {
            $rows = array_values(array_filter($rows, static fn ($r) => in_array($cat, array_map('intval', preg_split('/[,\s]+/', (string) ($r['category_id'] ?? '')) ?: []), true)));
        }
        $rows = hi_content_filter($rows, $q);
        $shown = count($rows);
        $pg    = hi_paginate($rows, 12);
        $rows  = $pg['rows'];
        $seo  = hi_page_seo('offer');
        return view('frontend/offers_list', [
            'title' => $seo['title'] ?? 'Offers & Deals in Noida — Save Big | HiNoida',
            'metaDescription' => $seo['metaDescription'] ?? 'Latest offers, deals and discount coupons from Noida businesses — dining, salons, gyms, shopping and more on HiNoida.',
            'canonical' => hi_post_category_url('offer', $catSlug),
            'offers' => $rows, 'pg' => $pg, 'shown' => $shown, 'q' => $q, 'cats' => $cats, 'cat' => $cat, 'total' => $total, 'catSlug' => $catSlug,
            'jsonld' => [hi_breadcrumb(['Home' => base_url('/'), 'Offers & Deals' => hi_page('offers')])],
        ]);
    }

    /** @param string $key the id from /offer/post/{id}/{slug}, or a bare slug */
    public function show(string $key): string
    {
        helper('seo');
        $db  = \Config\Database::connect();
        $src = hi_content_src('offer', $db);
        $o   = hi_content_find('offer', $key, $db);
        // an owner-submitted offer lives in its own table, not in blogs
        if (! $o && $src && $src['table'] !== 'offer' && $db->tableExists('offer')) {
            $own = $db->table('offer');
            $row = ctype_digit($key)
                ? $own->where('id', (int) $key)->get()->getRowArray()
                : $own->where('slug', $key)->get()->getRowArray();
            if ($row) { $o = hi_content_row($row); }
        }
        if (! $o) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $mq   = hi_content_q('offer', $db);
        $more = hi_content_rows($mq ? $mq->where('id !=', (int) $o['id'])->orderBy('id', 'DESC')->limit(3)->get()->getResultArray() : []);
        $url = hi_offer_url($o);
        return view('frontend/offer_detail', [
            'title' => $o['title'] . ' — ' . $o['business'] . ', Noida | HiNoida',
            'metaDescription' => $o['title'] . ' at ' . $o['business'] . ', ' . $o['locality'] . ', Noida. Use code ' . $o['code'] . '. Valid till ' . $o['valid_till'] . '.',
            'canonical' => $url,
            'o' => $o, 'more' => $more,
            'jsonld' => [hi_breadcrumb(['Home' => base_url('/'), 'Offers' => hi_page('offers'), $o['title'] => $url])],
        ]);
    }
}
