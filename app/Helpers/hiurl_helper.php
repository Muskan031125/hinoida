<?php

/**
 * hiurl_helper — canonical public URLs, in the shape the live site (hinoida.com)
 * has always used.
 *
 * The rebuild started out with short internal paths (/l/28, /c/restaurants,
 * /area/sector-63). Those have no history behind them. Every link Google, every
 * WhatsApp forward and every business card points at the original shapes:
 *
 *   /restaurant/burger-king/28          listing  ({listing_type}/{slug}/{id})
 *   /category/restaurants               a category
 *   /category                           all categories
 *   /listings                           browse / search results
 *   /sector-63                          a locality (bare, at the root)
 *   /city                               localities index
 *   /news  /news/post/81/{slug}  /news/category/{slug}
 *   /event /event/post/200/{slug} /event/category/{slug}
 *   /offer /offer/post/187/{slug} /offer/category/{slug}
 *   /home/about, /home/login, /home/privacy_policy …
 *
 * So this file is the single place that knows what a public URL looks like, and
 * the short paths now 301 here (see Config/Routes.php). The id is always the
 * part that identifies the row — the slug is decoration, exactly as on the
 * original, which is why an old link with a stale slug still resolves.
 */

if (! function_exists('hi_slug')) {
    /**
     * The original ran CodeIgniter 3's url_title() over the (HTML-escaped)
     * name. Entities are decoded first so "Domino&#039;s" becomes "domino-s",
     * which is what the live URLs actually contain.
     */
    function hi_slug(?string $text, string $fallback = ''): string
    {
        $t = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');   // double-escaped rows
        $t = trim(strip_tags($t));
        // "Café" → "Cafe", "Women’s" → "Women's". intl does this cleanly; iconv's
        // TRANSLIT is the fallback and leaves an apostrophe behind ("'e"), which
        // the strip below turns into a separator either way.
        static $tr = null;
        if ($tr === null) {
            $tr = class_exists('Transliterator')
                ? (\Transliterator::create('Any-Latin; Latin-ASCII') ?: false)
                : false;
        }
        if ($tr) {
            $ascii = $tr->transliterate($t);
            if (is_string($ascii) && $ascii !== '') { $t = $ascii; }
        } elseif (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
            if ($ascii !== false && $ascii !== '') { $t = $ascii; }
        }
        $t = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $t) ?? '');
        $t = trim(preg_replace('/-+/', '-', $t) ?? '', '-');

        return $t !== '' ? $t : $fallback;
    }
}

if (! function_exists('hi_listing_type')) {
    /** The first URL segment of a listing. The live values are these five. */
    function hi_listing_type(?string $raw): string
    {
        $t = strtolower(trim((string) $raw));

        return in_array($t, ['restaurant', 'hotel', 'beauty', 'shop', 'property', 'general'], true) ? $t : 'general';
    }
}

if (! function_exists('hi_listing_row_for_url')) {
    /**
     * Name and type for a listing we only know the id of (saved lists, leads,
     * bookings). One query per id per request, memoised — the pages that hit
     * this show a handful of rows, not a page of them.
     */
    function hi_listing_row_for_url(int $id): array
    {
        static $cache = [];
        if (isset($cache[$id])) { return $cache[$id]; }
        $row = [];
        try {
            $db  = \Config\Database::connect();
            $row = $db->table('listing')->select('id, name, listing_type')
                ->where('id', $id)->get()->getRowArray() ?: [];
        } catch (\Throwable $e) {
            $row = [];
        }

        return $cache[$id] = $row;
    }
}

if (! function_exists('hi_listing_url')) {
    /**
     * /{listing_type}/{slug}/{id} — pass the row when you have it, the id when
     * you don't.
     *
     * @param array|int|string $listing
     */
    function hi_listing_url($listing, string $suffix = ''): string
    {
        if (! is_array($listing)) {
            $id      = (int) $listing;
            $listing = hi_listing_row_for_url($id) ?: ['id' => $id];
        }
        $id = (int) ($listing['id'] ?? 0);
        // a partial row (a select that left the name or the type out) still has
        // to produce the same URL, so fill the gaps from the table
        if ($id > 0 && (! isset($listing['name']) || ! array_key_exists('listing_type', $listing))) {
            $listing = (hi_listing_row_for_url($id) ?: []) + $listing;
        }
        $type = hi_listing_type($listing['listing_type'] ?? null);
        $slug = hi_slug($listing['name'] ?? '', 'listing');

        return base_url($type . '/' . $slug . '/' . $id . $suffix);
    }
}

if (! function_exists('hi_category_url')) {
    function hi_category_url(?string $slug): string
    {
        $slug = trim((string) $slug);

        return $slug === '' ? base_url('category') : base_url('category/' . $slug);
    }
}

if (! function_exists('hi_locality_url')) {
    /** A locality sits at the root on the live site: /sector-63, /bhangel. */
    function hi_locality_url(?string $slug): string
    {
        $slug = trim((string) $slug, '/ ');

        return $slug === '' ? base_url('city') : base_url($slug);
    }
}

if (! function_exists('hi_post_url')) {
    /**
     * /news/post/{id}/{slug}, and the same shape for events and offers.
     *
     * @param array|int $post
     */
    function hi_post_url(string $kind, $post, string $titleField = 'title'): string
    {
        $seg  = ['blog' => 'news', 'news' => 'news', 'event' => 'event', 'offer' => 'offer'][$kind] ?? 'news';
        $id   = is_array($post) ? (int) ($post['id'] ?? 0) : (int) $post;
        $name = is_array($post) ? (string) ($post[$titleField] ?? $post['title'] ?? $post['name'] ?? '') : '';
        $slug = hi_slug($name, is_array($post) ? hi_slug((string) ($post['slug'] ?? ''), 'post') : 'post');

        return base_url($seg . '/post/' . $id . '/' . $slug);
    }
}

if (! function_exists('hi_news_url')) {
    /** @param array|int $post */
    function hi_news_url($post): string { return hi_post_url('news', $post); }
}

if (! function_exists('hi_event_url')) {
    /** @param array|int $post */
    function hi_event_url($post): string { return hi_post_url('event', $post); }
}

if (! function_exists('hi_offer_url')) {
    /** @param array|int $post */
    function hi_offer_url($post): string { return hi_post_url('offer', $post); }
}

if (! function_exists('hi_post_category_url')) {
    /** /news/category/{slug} — the live per-topic filters. */
    function hi_post_category_url(string $kind, ?string $slug): string
    {
        $seg  = ['blog' => 'news', 'news' => 'news', 'event' => 'event', 'offer' => 'offer'][$kind] ?? 'news';
        $slug = trim((string) $slug);

        return $slug === '' ? base_url($seg) : base_url($seg . '/category/' . $slug);
    }
}

if (! function_exists('hi_cat_id_by_slug')) {
    /**
     * Category id behind a /news/category/{slug} style URL. The category table
     * carries the same slugs the live site links to ("hotels-amp-banquets"),
     * with a name match as a backstop for rows that never got one.
     *
     * @param array $known rows from hi_content_cats(), used before touching the DB
     */
    function hi_cat_id_by_slug(string $slug, array $known = []): int
    {
        $slug = trim($slug);
        if ($slug === '') { return 0; }
        foreach ($known as $c) {
            if ((string) ($c['slug'] ?? '') === $slug || hi_slug((string) ($c['name'] ?? '')) === $slug) {
                return (int) $c['id'];
            }
        }
        $db  = \Config\Database::connect();
        $row = $db->table('category')->select('id')->where('slug', $slug)->get()->getRowArray();
        if ($row) { return (int) $row['id']; }
        foreach ($db->table('category')->select('id, name')->get()->getResultArray() as $c) {
            if (hi_slug((string) $c['name']) === $slug) { return (int) $c['id']; }
        }

        return 0;
    }
}

if (! function_exists('hi_page')) {
    /**
     * Static pages, by the short name the rebuild used internally. Anything not
     * listed keeps its own path (the pages the original never had — compare,
     * vcards, the wizard, the dashboard, admin).
     */
    function hi_page(string $key): string
    {
        static $map = [
            'home'             => '/',
            'search'           => 'listings',
            'listings'         => 'listings',
            'categories'       => 'category',
            'areas'            => 'city',
            'city'             => 'city',
            'news'             => 'news',
            'events'           => 'event',
            'offers'           => 'offer',
            'contact'          => 'contact',
            'about'            => 'home/about',
            'faq'              => 'home/faq',
            'privacy'          => 'home/privacy_policy',
            'terms'            => 'home/terms_and_conditions',
            'pricing'          => 'home/pricing',
            'login'            => 'home/login',
            'register'         => 'home/sign_up',
            'register/business' => 'directories/sign_up',
            'forgot'           => 'home/forgot_password',
        ];

        return base_url($map[$key] ?? $key);
    }
}

/* ---------------------------------------------------------------------------
 * Field validation shared by the admin form and the business wizard. Both
 * used to accept anything at all — "99581" as a phone number, an address with
 * no @ in it as an email — and it went straight onto the public page.
 * ------------------------------------------------------------------------- */

if (! function_exists('hi_valid_phone')) {
    /**
     * Indian numbers as the directory actually holds them: a 10-digit mobile,
     * optionally +91/0 prefixed, or a landline with its STD code, or a 1800
     * toll-free line. Spaces, dashes and brackets are ignored, and two numbers
     * may be given separated by a comma or a slash.
     */
    function hi_valid_phone(string $raw): bool
    {
        foreach (preg_split('~[,/]+~', $raw) as $one) {
            $d = preg_replace('/[^0-9+]/', '', $one);
            if ($d === '') { continue; }
            $d = preg_replace('/^(\+91|91|0)/', '', $d);
            // mobile: 10 digits starting 6-9 · landline: 8-11 digits with STD
            // · toll-free: 1800-xxx-xxxx
            if (! preg_match('/^[6-9][0-9]{9}$/', $d)
                && ! preg_match('/^[1-9][0-9]{7,10}$/', $d)) { return false; }
        }

        return trim($raw) !== '';
    }
}

if (! function_exists('hi_valid_email')) {
    function hi_valid_email(string $raw): bool
    {
        return (bool) filter_var(trim($raw), FILTER_VALIDATE_EMAIL);
    }
}

if (! function_exists('hi_normalise_url')) {
    /** "example.com" is what people type; a link needs the scheme. */
    function hi_normalise_url(string $raw): string
    {
        $u = trim($raw);
        if ($u === '') { return ''; }

        return preg_match('~^https?://~i', $u) ? $u : 'https://' . ltrim($u, '/');
    }
}

if (! function_exists('hi_valid_website')) {
    function hi_valid_website(string $raw): bool
    {
        $u = hi_normalise_url($raw);
        if (! filter_var($u, FILTER_VALIDATE_URL)) { return false; }
        $host = parse_url($u, PHP_URL_HOST) ?: '';

        // a bare word is not a site — require a dot and a plausible TLD
        return (bool) preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $host);
    }
}

/* hi_video_provider() lives in plan_helper.php, next to hi_video_embed(). */
