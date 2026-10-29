<?php

/**
 * Editable site copy and imagery.
 *
 * Every visible string outside a listing's own data used to be typed straight
 * into a view, so changing the footer blurb or a nav label meant editing PHP
 * and re-uploading. These read the same strings from `settings` instead, with
 * the text that was hard-coded as the fallback — so nothing changes until an
 * admin actually overrides it, and clearing a box restores the original.
 *
 * The catalogue below is the single source of truth: the defaults live here,
 * and the admin editor renders itself from it, so a new editable string needs
 * one line and no new form markup.
 */

if (! function_exists('hi_content_fields')) {
    /**
     * group => [key => [label, type, default, hint]]
     *
     * type is 'text', 'area' or 'image'.
     *
     * @return array<string,array<string,array{0:string,1:string,2:string,3?:string}>>
     */
    function hi_content_fields(): array
    {
        return [
            'Brand' => [
                'brand_name'     => ['Site name', 'text', 'HiNoida', 'Used in the tab title, image alt text and social cards.'],
                'brand_mark_1'   => ['Wordmark — first part', 'text', 'Hi', 'Shown bold. Only used when no logo image is set.'],
                'brand_mark_2'   => ['Wordmark — second part', 'text', 'NOIDA', ''],
                'brand_tagline'  => ['Tagline', 'text', 'Noida Business Directory', 'Follows the site name in the browser tab on the home page.'],
            ],
            'Header' => [
                'nav_property'   => ['Nav — Property', 'text', 'Property', ''],
                'nav_contact'    => ['Nav — Contact', 'text', 'Contact', ''],
            ],
            'Footer' => [
                'footer_blurb'   => ['Blurb under the logo', 'area',
                    'Noida’s local business directory — restaurants, hospitals, shopping, salons, property and more, with the details you need to actually turn up.', ''],
                'footer_col1'    => ['Column 1 heading', 'text', 'Discover Noida', ''],
                'footer_col2'    => ['Column 2 heading', 'text', 'Company', ''],
                'footer_col3'    => ['Column 3 heading', 'text', 'For business', ''],
                'footer_col4'    => ['Column 4 heading', 'text', 'Legal', ''],
                'footer_col5'    => ['Contact heading', 'text', 'Contact', ''],
                'footer_copy'    => ['Copyright line', 'text',
                    'HiNoida — Noida’s local business directory.', 'The year is added automatically in front.'],
            ],
            'Footer links' => [
                'flink_categories' => ['All categories', 'text', 'All categories', ''],
                'flink_property'   => ['Property', 'text', 'Property in Noida', ''],
                'flink_areas'      => ['Areas', 'text', 'All areas', ''],
                'flink_offers'     => ['Offers', 'text', 'Offers & Deals', ''],
                'flink_news'       => ['News', 'text', 'News & Guides', ''],
                'flink_about'      => ['About', 'text', 'About', ''],
                'flink_contact'    => ['Contact', 'text', 'Contact', ''],
                'flink_pricing'    => ['Pricing', 'text', 'Pricing', ''],
                'flink_faq'        => ['FAQ', 'text', 'FAQ', ''],
                'flink_advertise'  => ['Advertise', 'text', 'Advertise with us', ''],
                'flink_list'       => ['List your business', 'text', 'List your business', ''],
                'flink_login'      => ['Owner login', 'text', 'Owner login', ''],
                'flink_dashboard'  => ['Business dashboard', 'text', 'Business dashboard', ''],
                'flink_privacy'    => ['Privacy', 'text', 'Privacy Policy', ''],
                'flink_credits'    => ['Photo credits', 'text', 'Photo Credits', ''],
                'flink_terms'      => ['Terms', 'text', 'Terms & Conditions', ''],
                'flink_cookie'     => ['Cookie policy', 'text', 'Cookie Policy', ''],
            ],
            'Images' => [
                'img_og'         => ['Social share image', 'image', '',
                    'Shown when a page is shared to WhatsApp, Facebook or X. 1200×630 works best. Falls back to the listing or hero image.'],
                'img_placeholder'=> ['Listing placeholder', 'image', '',
                    'Used on a card when a listing has no photo of its own.'],
            ],
        ];
    }
}

if (! function_exists('hi_content_default')) {
    /** The built-in value for a key, or '' if the key is not in the catalogue. */
    function hi_content_default(string $key): string
    {
        static $flat = null;
        if ($flat === null) {
            $flat = [];
            foreach (hi_content_fields() as $fields) {
                foreach ($fields as $k => $f) { $flat[$k] = (string) $f[2]; }
            }
        }

        return $flat[$key] ?? '';
    }
}

if (! function_exists('hi_c')) {
    /**
     * An editable string.
     *
     * Returns the admin's value when set, otherwise the default — so a blank
     * box means "use the original", never an empty page.
     *
     * The default can be passed at the call site:
     *
     *     <h2><?= esc(hi_c('listing.reviews', 'Ratings & reviews')) ?></h2>
     *
     * A key seen for the first time is written to `settings` with that default,
     * which is what puts it in the admin editor. That is how the whole site
     * becomes editable without maintaining a list of several hundred keys by
     * hand: using a string registers it.
     */
    function hi_c(string $key, string $default = ''): string
    {
        $default = $default !== '' ? $default : hi_content_default($key);
        $all     = hi_settings();
        $col     = 'c_' . $key;

        if (! array_key_exists($col, $all)) {
            hi_c_register($key, $default);

            return $default;
        }

        $v = trim((string) $all[$col]);

        return $v !== '' ? $v : $default;
    }
}

if (! function_exists('hi_c_register')) {
    /**
     * Records a newly seen key so it appears in the admin editor.
     *
     * Writing during a page render is unusual, but it happens once per key for
     * the life of the site and never on a key already stored. Any failure is
     * swallowed: an un-editable string is a far smaller problem than a page
     * that will not render because the settings table is read-only.
     */
    function hi_c_register(string $key, string $default): void
    {
        static $done = [];
        if (isset($done[$key])) { return; }
        $done[$key] = true;

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('settings')) { return; }

            $col = 'c_' . $key;
            if ($db->table('settings')->where('type', $col)->countAllResults() > 0) { return; }

            $db->table('settings')->insert(['type' => $col, 'description' => $default]);

            // keep the request's own cache honest, or every later hi_c() for
            // this key would try to insert it again
            hi_settings_flush();
        } catch (\Throwable $e) {
            log_message('debug', 'hi_c_register failed for ' . $key . ': ' . $e->getMessage());
        }
    }
}

if (! function_exists('hi_c_registry')) {
    /**
     * Every registered string, grouped for the editor by the part of its key
     * before the first dot — so 'listing.reviews' files under "listing".
     *
     * @return array<string,array<string,string>>
     */
    function hi_c_registry(): array
    {
        $curated = [];
        foreach (hi_content_fields() as $fields) {
            foreach ($fields as $k => $f) { $curated[$k] = true; }
        }

        $out = [];
        foreach (hi_settings() as $type => $val) {
            if (! str_starts_with($type, 'c_')) { continue; }
            $key = substr($type, 2);
            if (isset($curated[$key])) { continue; }   // shown in its own group

            $grp = str_contains($key, '.') ? explode('.', $key)[0] : 'other';
            $out[$grp][$key] = (string) $val;
        }
        ksort($out);
        foreach ($out as &$g) { ksort($g); }

        return $out;
    }
}

if (! function_exists('hi_c_img')) {
    /**
     * An editable image, as a URL ready for src. Empty when neither an upload
     * nor a default exists, so the caller can fall back to its own artwork.
     */
    function hi_c_img(string $key): string
    {
        $v = trim(hi_setting('c_' . $key, '')) ?: hi_content_default($key);
        if ($v === '') { return ''; }

        return preg_match('#^https?://#i', $v) ? $v : base_url($v);
    }
}
