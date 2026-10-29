<?php

namespace App\Controllers;

/**
 * Public profile for a member or a business owner.
 *
 * Deliberately narrow: a name, what they have contributed, and — for an owner
 * — the places they run. Email, phone and address are never shown, whatever
 * the account holds.
 */
class Profile extends BaseController
{
    public function show(int $id)
    {
        $db = \Config\Database::connect();
        $u  = $db->table('user')->select('id, name, role_id, social, is_verified')
            ->where('id', $id)->get()->getRowArray();
        if (! $u) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $name = trim((string) $u['name']) ?: 'HiNoida member';
        $isBiz = (int) $u['role_id'] === 2;

        // their listings — active only, so a pending or rejected one stays private
        $listings = [];
        if ($isBiz || (int) $u['role_id'] === 1) {
            $lm = new \App\Models\ListingModel();
            $q  = $db->table('listing li')->select('li.*, l.name AS locality_name')
                ->join('locality l', 'l.id = li.locality_id', 'left')
                ->where('li.user_id', $id)->where('li.status', 'active');
            if ($db->fieldExists('deleted_at', 'listing')) { $q->where('li.deleted_at', null); }
            $listings = $lm->attachRatings($q->orderBy('li.is_featured', 'DESC')->limit(24)->get()->getResultArray());
        }

        // their reviews — hidden ones excluded, same as everywhere else
        $reviews = [];
        if ($db->tableExists('review')) {
            $rq = $db->table('review r')->select('r.*, li.name AS listing_name, li.id AS lid')
                ->join('listing li', 'li.id = r.listing_id', 'left')
                ->where('r.reviewer_id', $id);
            if ($db->fieldExists('is_hidden', 'review')) {
                $rq->groupStart()->where('r.is_hidden', 0)->orWhere('r.is_hidden', null)->groupEnd();
            }
            $reviews = $rq->orderBy('r.' . hi_review_pk($db), 'DESC')->limit(10)->get()->getResultArray();
        }

        $reviewCount = $db->tableExists('review')
            ? $db->table('review')->where('reviewer_id', $id)->countAllResults() : 0;

        $social = [];
        foreach ((array) json_decode((string) ($u['social'] ?? ''), true) as $k => $v) {
            if (is_string($v) && trim($v) !== '' && preg_match('~^https?://~i', $v)) { $social[$k] = $v; }
        }

        $what = $isBiz ? 'business owner' : 'member';
        $desc = $name . ' on HiNoida — ' . $what
            . ($listings ? ', running ' . count($listings) . ' listed business' . (count($listings) === 1 ? '' : 'es') : '')
            . ($reviewCount ? ' with ' . $reviewCount . ' review' . ($reviewCount === 1 ? '' : 's') . ' written' : '') . '.';

        return view('frontend/profile', [
            'title'           => $name . ' | HiNoida',
            'metaDescription' => $desc,
            'canonical'       => base_url('u/' . $id),
            // thin pages with nothing on them should not be indexed
            'robots'          => ($listings || $reviewCount) ? 'index,follow' : 'noindex,follow',
            'u'               => $u,
            'name'            => $name,
            'isBiz'           => $isBiz,
            'listings'        => $listings,
            'reviews'         => $reviews,
            'reviewCount'     => $reviewCount,
            'social'          => $social,
            'jsonld'          => [[
                '@context' => 'https://schema.org',
                '@type'    => 'Person',
                'name'     => $name,
                'url'      => base_url('u/' . $id),
            ]],
        ]);
    }
}
