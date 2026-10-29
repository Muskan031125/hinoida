<?php

/**
 * Package entitlements.
 *
 * A purchase row in package_purchased_history is what grants a plan; the row
 * with the furthest expiry that has not passed wins. Owners with no purchase
 * fall back to the free allowance so the site keeps working for everyone who
 * signed up before packages existed.
 */

if (! function_exists('hi_free_plan')) {
    function hi_free_plan(): array
    {
        return [
            'id'                 => 0,
            'name'               => 'Free',
            'price'              => 0,
            // How many listings someone with no paid package may post.
            // 0 means unlimited, which is the default: a person should be able
            // to post their shop, their flat and their PG without being sold
            // anything first. Set `free_listing_limit` in admin to cap it.
            'number_of_listings' => (int) hi_setting('free_listing_limit', '0'),
            'featured'           => 0,
            'ability_to_add_video' => 0,
            'expired_date'       => 0,   // never
            'is_free'            => true,
        ];
    }
}

if (! function_exists('hi_plan')) {
    /** The plan currently in force for a user. */
    function hi_plan(int $uid): array
    {
        static $cache = [];
        if (isset($cache[$uid])) { return $cache[$uid]; }
        if ($uid <= 0) { return hi_free_plan(); }

        $db = \Config\Database::connect();
        if (! $db->tableExists('package_purchased_history') || ! $db->tableExists('package')) {
            return $cache[$uid] = hi_free_plan();
        }

        $row = $db->table('package_purchased_history h')
            ->select('h.expired_date, h.purchase_date, h.amount_paid, p.*')
            ->join('package p', 'p.id = h.package_id', 'left')
            ->where('h.user_id', $uid)
            ->where('h.expired_date >', time())
            ->orderBy('h.expired_date', 'DESC')
            ->get()->getRowArray();

        if (! $row || empty($row['id'])) { return $cache[$uid] = hi_free_plan(); }

        $row['is_free'] = false;

        return $cache[$uid] = $row;
    }
}

if (! function_exists('hi_plan_limit')) {
    /** How many listings this user's plan allows. 0 means unlimited. */
    function hi_plan_limit(int $uid): int
    {
        return (int) (hi_plan($uid)['number_of_listings'] ?? 1);
    }
}

if (! function_exists('hi_listing_count')) {
    function hi_listing_count(int $uid): int
    {
        $db = \Config\Database::connect();
        $b  = $db->table('listing')->where('user_id', $uid)->whereNotIn('status', ['rejected']);
        if ($db->fieldExists('deleted_at', 'listing')) { $b->where('deleted_at', null); }
        // Property is exempt from the plan limit on the way in, so counting it
        // here made the exemption a trap: three flats used up the one business
        // listing the plan covers, and the next shop was refused.
        if ($db->fieldExists('listing_type', 'listing')) { $b->where('listing_type !=', 'property'); }

        return $b->countAllResults();
    }
}

if (! function_exists('hi_can_add_listing')) {
    /** Admins are never limited; everyone else is checked against their plan. */
    function hi_can_add_listing(int $uid, int $roleId = 0): bool
    {
        if ($roleId === 1) { return true; }
        $limit = hi_plan_limit($uid);

        return $limit <= 0 || hi_listing_count($uid) < $limit;
    }
}

if (! function_exists('hi_plan_expiry')) {
    /** Days left on the plan, or null when it never expires. */
    function hi_plan_expiry(int $uid): ?int
    {
        $exp = (int) (hi_plan($uid)['expired_date'] ?? 0);
        if ($exp <= 0) { return null; }

        return (int) max(0, ceil(($exp - time()) / 86400));
    }
}

if (! function_exists('hi_can_add_video')) {
    /**
     * Does this owner's plan allow a video?
     *
     * The original gated the player on has_package_feature('ability_to_add_video');
     * the column exists on `package` and was never read.
     */
    function hi_can_add_video(int $uid, int $roleId = 0): bool
    {
        if ($roleId === 1) { return true; }

        return ! empty(hi_plan($uid)['ability_to_add_video']);
    }
}

if (! function_exists('hi_video_provider')) {
    /** youtube / vimeo / '' — matches the original's video_provider column. */
    function hi_video_provider(string $url): string
    {
        if ($url === '') { return ''; }
        if (preg_match('~youtu\.?be~i', $url)) { return 'youtube'; }
        if (preg_match('~vimeo~i', $url)) { return 'vimeo'; }

        return 'other';
    }
}

if (! function_exists('hi_video_embed')) {
    /** A watch/share URL turned into something an iframe can load. */
    function hi_video_embed(string $url): string
    {
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return '';
    }
}
