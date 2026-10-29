<?php

/**
 * Tables the original new.hinoida.com has that this rewrite did not.
 *
 * The original stores listing-type-specific detail in four side tables rather
 * than on the listing row — a restaurant's menu, a hotel's rooms, a salon's
 * services, a shop's products (Crud_model::listing_type_wise_details). It also
 * keeps owner edits in listing_changes until an admin approves them, comments
 * in blog_comments, and homepage configuration in frontend_settings.
 *
 * Columns are taken from the original's insert arrays, not invented.
 * Created on demand and idempotently, matching how the rest of this app
 * creates its tables.
 */
if (! function_exists('hi_pk')) {
    function hi_pk(\CodeIgniter\Database\BaseConnection $db): string
    {
        return 'INTEGER PRIMARY KEY ' . ($db->DBDriver === 'MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT');
    }
}

if (! function_exists('hi_ensure_tables')) {
    /** @return string[] names of tables that were created by this call */
    function hi_ensure_tables(?\CodeIgniter\Database\BaseConnection $db = null): array
    {
        $db = $db ?: \Config\Database::connect();
        $pk = hi_pk($db);

        $defs = [
            // --- listing-type detail, one row per menu item / room / service / product ---
            'food_menu' => "id {$pk}, listing_id INT, name VARCHAR(190), price VARCHAR(60), items TEXT, photo VARCHAR(255), sort_order INT DEFAULT 0",
            'hotel_room_specification' => "id {$pk}, listing_id INT, name VARCHAR(190), description TEXT, price VARCHAR(60), amenities TEXT, photo VARCHAR(255), sort_order INT DEFAULT 0",
            'beauty_service' => "id {$pk}, listing_id INT, name VARCHAR(190), price VARCHAR(60), service_times VARCHAR(120), photo VARCHAR(255), sort_order INT DEFAULT 0",
            'product_details' => "id {$pk}, listing_id INT, name VARCHAR(190), variant VARCHAR(190), price VARCHAR(60), photo VARCHAR(255), sort_order INT DEFAULT 0",

            // Owner-submitted offers. Live keeps editorial offers inside `blogs`
            // as articles with no business link, so owner offers — which belong
            // to a named listing and carry a code and expiry — need their own
            // table. The public page shows both.
            'offer' => "id {$pk}, listing_id INT, business VARCHAR(190), locality VARCHAR(120), category VARCHAR(120), title VARCHAR(220), slug VARCHAR(240), description TEXT, discount VARCHAR(60), code VARCHAR(60), valid_till VARCHAR(40), status VARCHAR(20) DEFAULT 'pending', created_at DATETIME",

            // --- owner edits held for admin approval ---
            'listing_changes' => "id {$pk}, listing_id INT, user_id INT, payload TEXT, status VARCHAR(20) DEFAULT 'pending', note TEXT, created_at DATETIME, reviewed_at DATETIME",

            // Deliberately NOT created:
            //  - blog_comments: comments already exist as blog_comment (singular),
            //    with a form and moderation. A second table would split them.
            //  - time_configuration: booking slots are generated from the
            //    listing's own opening hours, so a static slot table is dead weight.
            //  - frontend_settings: `settings` is already this exact key/value
            //    store, holding hero_title, cookie_notice and 48 others. A second
            //    one would mean two places to look for the same kind of value.

            // --- reference data ---
            'country'  => "id {$pk}, name VARCHAR(120), code VARCHAR(8), status VARCHAR(20) DEFAULT 'active'",
            'currency' => "id {$pk}, name VARCHAR(120), code VARCHAR(8), symbol VARCHAR(8), rate DECIMAL(12,4) DEFAULT 1, status VARCHAR(20) DEFAULT 'active'",
        ];

        // Columns the original has on `listing` that this rewrite lacked.
        // Added here so uploading the code is enough — the SQL script is a
        // convenience, not a prerequisite. Without this the bulk SEO export
        // selects a column that does not exist and the page 500s.
        $addCols = [
            'listing' => ['seo_meta_tags' => 'TEXT', 'hours_json' => 'TEXT'],
            // features this rewrite added that the live review table predates
            'review'  => ['owner_reply' => 'TEXT', 'is_hidden' => 'INTEGER DEFAULT 0', 'reviewer_name' => 'VARCHAR(190)'],
            // business approval and signup dates — the live user table has
            // neither, so without these every business account is treated as
            // already approved and signup trends cannot be measured
            'user'    => ['approved' => 'INTEGER DEFAULT 1', 'created_date' => 'BIGINT'],
        ];
        foreach ($addCols as $tbl => $cols) {
            if (! $db->tableExists($tbl)) { continue; }
            foreach ($cols as $col => $type) {
                if ($db->fieldExists($col, $tbl)) { continue; }
                try { $db->query('ALTER TABLE ' . $tbl . ' ADD COLUMN ' . $col . ' ' . $type); }
                catch (\Throwable $e) { log_message('error', 'hi_ensure_tables: could not add ' . $tbl . '.' . $col . ' — ' . $e->getMessage()); }
            }
        }

        // Live keeps posts, events and offers in one `blogs` table with no slug,
        // while every content URL here is /news/<slug>. Add one and fill it from
        // the title, so deploying the code is enough — on MySQL as well as the
        // local SQLite file the importer handles.
        if ($db->tableExists('blogs') && ! $db->fieldExists('slug', 'blogs')) {
            try {
                $db->query('ALTER TABLE blogs ADD COLUMN slug VARCHAR(220)');
                $seen = [];
                foreach ($db->table('blogs')->select('id, title')->get()->getResultArray() as $r) {
                    $s = strtolower(trim((string) $r['title']));
                    $s = trim((string) preg_replace('/[^a-z0-9]+/', '-', $s), '-') ?: 'post';
                    $base = $s; $i = 2;
                    while (isset($seen[$s])) { $s = $base . '-' . $i++; }   // titles repeat
                    $seen[$s] = true;
                    $db->table('blogs')->where('id', (int) $r['id'])->update(['slug' => $s]);
                }
            } catch (\Throwable $e) {
                log_message('error', 'hi_ensure_tables: could not add blogs.slug — ' . $e->getMessage());
            }
        }

        // profile_views grows fast (290k+ rows); the home "trending" query
        // filters by created_at, and with no index it full-scans the table and
        // makes the home page take ~25 seconds. Ensure the index exists.
        if ($db->tableExists('profile_views')) {
            $hasIdx = false;
            foreach ($db->query('SHOW INDEX FROM profile_views')->getResultArray() as $ix) {
                if (($ix['Key_name'] ?? '') === 'idx_pv_created_listing') { $hasIdx = true; break; }
            }
            if (! $hasIdx) {
                try { $db->query('ALTER TABLE profile_views ADD INDEX idx_pv_created_listing (created_at, listing_id)'); }
                catch (\Throwable $e) { log_message('error', 'hi_ensure_tables: could not index profile_views — ' . $e->getMessage()); }
            }
        }

        $made = [];
        foreach ($defs as $t => $cols) {
            if ($db->tableExists($t)) { continue; }
            try {
                $db->query('CREATE TABLE ' . $t . ' (' . $cols . ')');
                $made[] = $t;
            } catch (\Throwable $e) {
                log_message('error', 'hi_ensure_tables: could not create ' . $t . ' — ' . $e->getMessage());
            }
        }

        return $made;
    }
}

if (! function_exists('hi_detail_table')) {
    /**
     * Which detail table a listing uses, or '' when its type has none.
     * The original branches on these four values exactly.
     */
    function hi_detail_table(string $listingType): string
    {
        return [
            'hotel'      => 'hotel_room_specification',
            'restaurant' => 'food_menu',
            'beauty'     => 'beauty_service',
            'shop'       => 'product_details',
        ][strtolower(trim($listingType))] ?? '';
    }
}

if (! function_exists('hi_detail_meta')) {
    /** Heading, singular noun and editable fields for each detail table. */
    function hi_detail_meta(string $table): array
    {
        $m = [
            'food_menu' => [
                'title' => 'Menu', 'one' => 'dish', 'add' => 'Add a dish',
                'fields' => ['name' => 'Dish', 'price' => 'Price', 'items' => 'What is included'],
            ],
            'hotel_room_specification' => [
                'title' => 'Rooms', 'one' => 'room', 'add' => 'Add a room',
                'fields' => ['name' => 'Room', 'price' => 'Price per night', 'description' => 'Description', 'amenities' => 'Room amenities'],
            ],
            'beauty_service' => [
                'title' => 'Services', 'one' => 'service', 'add' => 'Add a service',
                'fields' => ['name' => 'Service', 'price' => 'Price', 'service_times' => 'How long it takes'],
            ],
            'product_details' => [
                'title' => 'Products', 'one' => 'product', 'add' => 'Add a product',
                'fields' => ['name' => 'Product', 'variant' => 'Variant', 'price' => 'Price'],
            ],
        ];

        return $m[$table] ?? ['title' => 'Details', 'one' => 'item', 'add' => 'Add', 'fields' => ['name' => 'Name', 'price' => 'Price']];
    }
}

if (! function_exists('hi_pv_col')) {
    /**
     * The column profile_views uses to say what kind of view it was.
     *
     * Live new.hinoida.com calls it `type`; this rewrite created it as `view_type`.
     * Both exist in the wild, so ask the database rather than guessing — using
     * the wrong one throws "no such column" on every listing page.
     */
    function hi_pv_col($db = null): string
    {
        static $col = null;
        if ($col !== null) { return $col; }
        $db = $db ?: \Config\Database::connect();
        if (! $db->tableExists('profile_views')) { return $col = 'type'; }

        return $col = $db->fieldExists('type', 'profile_views') ? 'type' : 'view_type';
    }
}

if (! function_exists('hi_rich')) {
    /**
     * Decode admin-authored rich text stored double-encoded.
     *
     * Live hinoida saved descriptions as "&lt;p&gt;…&amp;#39;s…", so escaping
     * on output printed the tags on the page. One decode pass is not enough —
     * "&amp;#39;" only becomes an apostrophe on the second. Decode until it
     * stops changing, capped so a literal "&amp;amp;" cannot loop.
     */
    function hi_rich(?string $v): string
    {
        $v = trim((string) $v);
        for ($i = 0; $i < 4; $i++) {
            $next = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $v) { break; }
            $v = $next;
        }

        return $v;
    }
}

if (! function_exists('hi_rich_text')) {
    /** Same, flattened to plain text — for meta descriptions and card blurbs. */
    function hi_rich_text(?string $v): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags(hi_rich($v))));
    }
}

if (! function_exists('hi_row_for')) {
    /**
     * Drop keys the table does not have.
     *
     * The live `user` table has no `approved` or `created_date` column, so an
     * insert written against this rewrite's own schema failed outright. Filter
     * rather than assume, so the same code works on both.
     */
    function hi_row_for(string $table, array $row, $db = null): array
    {
        $db = $db ?: \Config\Database::connect();
        if (! $db->tableExists($table)) { return $row; }

        static $cols = [];
        if (! isset($cols[$table])) { $cols[$table] = $db->getFieldNames($table); }

        return array_intersect_key($row, array_flip($cols[$table]));
    }
}

if (! function_exists('hi_user_defaults')) {
    /**
     * Values for `user` columns the live schema declares NOT NULL with no
     * default — otp and updated_date. Omitting them made every registration
     * fail against the real database with a constraint violation.
     *
     * Only columns that actually exist and actually need a value are returned,
     * so this is harmless against a schema that does not have them.
     */
    function hi_user_defaults($db = null): array
    {
        $db  = $db ?: \Config\Database::connect();
        $out = [];
        foreach (['otp' => '', 'updated_date' => date('Y-m-d H:i:s'), 'verification_code' => ''] as $col => $val) {
            if ($db->fieldExists($col, 'user')) { $out[$col] = $val; }
        }

        return $out;
    }
}

if (! function_exists('hi_fe')) {
    /**
     * A value from the live frontend_settings table.
     *
     * Live hinoida configures the banner, the social links and the About /
     * FAQ / Privacy / Terms copy here, under its own key names. This rewrite
     * stores the same things in `settings` as fe_*. Read ours first so anything
     * edited in the admin screen wins, then fall back to the live value —
     * otherwise real, already-written page copy sits unused.
     *
     * Body copy is stored HTML-escaped, so it is decoded on the way out; it is
     * admin-authored and rendered as HTML by the caller.
     */
    function hi_fe(string $ourKey, string $liveKey = '', bool $html = false): string
    {
        $mine = trim((string) hi_setting($ourKey));
        if ($mine !== '') { return $mine; }
        if ($liveKey === '') { return ''; }

        static $live = null;
        if ($live === null) {
            $live = [];
            $db   = \Config\Database::connect();
            if ($db->tableExists('frontend_settings')) {
                foreach ($db->table('frontend_settings')->get()->getResultArray() as $r) {
                    $live[(string) $r['type']] = (string) $r['description'];
                }
            }
        }
        $v = trim((string) ($live[$liveKey] ?? ''));
        if (! $html) { return $v; }

        // The live copy is double-encoded — "&amp;ndash;" inside "&lt;h2&gt;" —
        // so one pass leaves &ndash; printed on the page. Decode until it stops
        // changing, capped so a literal "&amp;amp;" cannot loop forever.
        for ($i = 0; $i < 4; $i++) {
            $next = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $v) { break; }
            $v = $next;
        }

        return $v;
    }
}

if (! function_exists('hi_hero')) {
    /**
     * The hero copy the live site already has.
     *
     * frontend_settings.hero_settings holds one JSON blob with the headline,
     * strapline and the three "how it works" cards. This rewrite read them from
     * separate `settings` rows that were never populated, so the hero fell back
     * to placeholder text and the three cards did not render at all.
     *
     * @return array<string,string>
     */
    function hi_hero(): array
    {
        static $h = null;
        if ($h !== null) { return $h; }

        $raw = hi_fe('', 'hero_settings', true);
        $j   = $raw !== '' ? json_decode($raw, true) : null;

        return $h = is_array($j) ? array_map(static fn ($v) => is_string($v) ? trim($v) : '', $j) : [];
    }
}

if (! function_exists('hi_hero_boxes')) {
    /**
     * The three cards, from our settings first and the live blob second.
     * Font Awesome 5 wrote "fas fa-search"; version 6 wants "fa-solid".
     *
     * @return list<array{icon:string,topic:string,sub:string}>
     */
    function hi_hero_boxes(): array
    {
        $hero = hi_hero();
        $out  = [];
        foreach (['one', 'two', 'three'] as $n) {
            $topic = trim((string) hi_setting('hero_topic_' . $n)) ?: ($hero['topic_' . $n] ?? '');
            if ($topic === '') { continue; }
            $icon = trim((string) hi_setting('hero_icon_' . $n)) ?: ($hero['icon_picker_' . $n] ?? '');
            $icon = strtr($icon, ['fas ' => 'fa-solid ', 'far ' => 'fa-regular ', 'fab ' => 'fa-brands ']);
            $out[] = [
                'icon'  => $icon ?: 'fa-solid fa-circle-check',
                'topic' => $topic,
                'sub'   => trim((string) hi_setting('hero_subtopic_' . $n)) ?: ($hero['sub_topic_' . $n] ?? ''),
            ];
        }

        return $out;
    }
}

if (! function_exists('hi_fe_social')) {
    /** Social links: ours first, else the live social_links JSON blob. */
    function hi_fe_social(): array
    {
        $out = [];
        foreach (['facebook', 'instagram', 'linkedin', 'youtube', 'twitter'] as $k) {
            $v = trim((string) hi_setting('fe_' . $k));
            if ($v !== '') { $out[$k === 'twitter' ? 'x-twitter' : $k] = $v; }
        }
        if ($out) { return $out; }

        $raw = hi_fe('', 'social_links');
        $j   = json_decode($raw, true);
        if (is_array($j)) {
            foreach ($j as $k => $v) {
                $v = trim((string) $v);
                if ($v === '') { continue; }
                $out[$k === 'twitter' ? 'x-twitter' : strtolower($k)] = $v;
            }
        }

        return $out;
    }
}

if (! function_exists('hi_page_seo')) {
    /**
     * Per-page SEO written in the live `seo` table (page, seo_title,
     * seo_meta_tags, meta_description). Returns [] when there is nothing for
     * that page, so callers keep their own defaults.
     */
    function hi_page_seo(string $page): array
    {
        static $all = null;
        if ($all === null) {
            $all = [];
            $db  = \Config\Database::connect();
            if ($db->tableExists('seo')) {
                foreach ($db->table('seo')->get()->getResultArray() as $r) {
                    $all[strtolower((string) $r['page'])] = $r;
                }
            }
        }
        $r = $all[strtolower($page)] ?? null;
        if (! $r) { return []; }

        $out = [];
        if (trim((string) ($r['seo_title'] ?? '')) !== '')        { $out['title'] = trim((string) $r['seo_title']); }
        if (trim((string) ($r['meta_description'] ?? '')) !== '') { $out['metaDescription'] = trim((string) $r['meta_description']); }

        return $out;
    }
}

if (! function_exists('hi_content_src')) {
    /**
     * Where blog posts / events / offers live.
     *
     * Live new.hinoida.com keeps all three in one `blogs` table told apart by a
     * `type` column ('Blog', 'Event', 'Offer') with an integer status, while
     * this rewrite created a separate table per kind with status 'active'.
     * Returns the table, the type filter to apply (or null) and the value that
     * means published, so callers work against either shape.
     *
     * @param string $kind one of blog|event|offer
     * @return array{table:string,type:?string,active:mixed}|null
     */
    function hi_content_src(string $kind, $db = null): ?array
    {
        $db   = $db ?: \Config\Database::connect();
        $kind = strtolower($kind);

        if ($db->tableExists('blogs')) {
            // first content request after a deploy adds the slug column; checked
            // once per request, not once per call
            static $slugged = false;
            if (! $slugged) {
                $slugged = true;
                if (! $db->fieldExists('slug', 'blogs')) { hi_ensure_tables($db); }
            }

            return ['table' => 'blogs', 'type' => ucfirst($kind), 'active' => 1];
        }
        $own = ['blog' => 'blog', 'event' => 'event', 'offer' => 'offer'][$kind] ?? null;
        if ($own && $db->tableExists($own)) {
            return ['table' => $own, 'type' => null, 'active' => 'active'];
        }

        return null;
    }
}

if (! function_exists('hi_content_q')) {
    /** A builder over the right content table, already filtered to published rows. */
    function hi_content_q(string $kind, $db = null)
    {
        $db  = $db ?: \Config\Database::connect();
        $src = hi_content_src($kind, $db);
        if ($src === null) { return null; }

        $q = $db->table($src['table'])->where('status', $src['active']);
        if ($src['type'] !== null) { $q->where('type', $src['type']); }

        return $q;
    }
}

if (! function_exists('hi_content_filter')) {
    /**
     * Narrow a set of posts/events/offers by a search term.
     *
     * Done in PHP rather than SQL because the rows have already been fetched
     * and normalised — and because the body lives under a different column
     * depending on which shape the database is in.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    function hi_content_filter(array $rows, string $q): array
    {
        $q = trim($q);
        if ($q === '') { return $rows; }
        $needle = mb_strtolower($q);

        return array_values(array_filter($rows, static function (array $r) use ($needle): bool {
            $hay = mb_strtolower(implode(' ', [
                (string) ($r['title'] ?? ''),
                strip_tags((string) ($r['description'] ?? $r['blog_text'] ?? '')),
                (string) ($r['business'] ?? ''),
                (string) ($r['locality'] ?? ''),
                (string) ($r['venue'] ?? ''),
            ]));

            return str_contains($hay, $needle);
        }));
    }
}

if (! function_exists('hi_content_find')) {
    /**
     * One post, by whatever the URL carried. The live URLs identify a post by
     * id (/news/post/81/some-slug) and treat the slug as decoration, so an id
     * wins; a bare slug still resolves for the short paths we used in between.
     *
     * @param string|int $key
     */
    function hi_content_find(string $kind, $key, $db = null): ?array
    {
        $db  = $db ?: \Config\Database::connect();
        $src = hi_content_src($kind, $db);
        if (! $src) { return null; }

        $find = static function ($field, $value) use ($db, $src) {
            $q = $db->table($src['table'])->where($field, $value);
            if ($src['type'] !== null) { $q->where('type', $src['type']); }

            return $q->get()->getRowArray();
        };

        $row = ctype_digit((string) $key) ? $find('id', (int) $key) : null;
        if (! $row && $db->fieldExists('slug', $src['table'])) { $row = $find('slug', (string) $key); }

        return $row ? hi_content_row($row) : null;
    }
}

if (! function_exists('hi_content_cats')) {
    /**
     * Category breakdown for a set of posts / events / offers.
     *
     * The original lists these beside the results with counts, so you can see
     * what kinds of thing are there before reading any of them.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array{id:int,name:string,n:int}>
     */
    function hi_content_cats(array $rows): array
    {
        $counts = [];
        foreach ($rows as $r) {
            foreach (preg_split('/[,\s]+/', (string) ($r['category_id'] ?? '')) ?: [] as $cid) {
                $cid = (int) $cid;
                if ($cid > 0) { $counts[$cid] = ($counts[$cid] ?? 0) + 1; }
            }
        }
        if (! $counts) { return []; }

        $db    = \Config\Database::connect();
        $names = [];
        $slugs = [];
        foreach ($db->table('category')->select('id, name, slug')->whereIn('id', array_keys($counts))->get()->getResultArray() as $c) {
            $names[(int) $c['id']] = (string) $c['name'];
            $slugs[(int) $c['id']] = (string) ($c['slug'] ?? '');
        }

        $out = [];
        foreach ($counts as $id => $n) {
            if (! isset($names[$id])) { continue; }
            // the slug is what the live /news/category/{slug} links are built from
            $out[] = ['id' => $id, 'name' => $names[$id], 'slug' => $slugs[$id] ?: hi_slug($names[$id]), 'n' => $n];
        }
        usort($out, static fn ($a, $b) => $b['n'] <=> $a['n']);

        return $out;
    }
}

if (! function_exists('hi_content_row')) {
    /**
     * Fill in the keys the views expect but the live table names differently.
     *
     * Live has `type` where the rewrite reads `blog_type`, and neither carries
     * the event/offer extras (event_date, venue, discount, business) that the
     * rewrite's own tables did. Defaulting them here keeps the views free of
     * isset() noise and stops one missing column blanking a whole page.
     */
    function hi_content_row(array $r): array
    {
        $r['blog_type'] = $r['blog_type'] ?? strtolower((string) ($r['type'] ?? 'blog'));
        $r['slug']      = $r['slug'] ?? (string) ($r['id'] ?? '');
        // Titles are encoded too, so esc() on output produced "&amp;" on the
        // page. Decode here and let the view escape once, as it should.
        if (isset($r['title'])) { $r['title'] = hi_rich_text($r['title']); }
        // live calls the body blog_text; the event and offer views read description
        $r['description']      = $r['description'] ?? (string) ($r['blog_text'] ?? '');
        $r['blog_text']        = $r['blog_text'] ?? (string) ($r['description'] ?? '');
        // The event and offer views were written against this rewrite's own
        // tables, which carried structured extras. Live keeps all three kinds in
        // `blogs` as articles, so none of these columns exist — default every
        // one rather than let a single missing key blank the page.
        foreach ([
            'event_date', 'event_time', 'venue', 'price', 'category', 'locality',
            'discount', 'business', 'code', 'valid_till', 'blog_description',
        ] as $k) {
            $r[$k] = $r[$k] ?? '';
        }
        // live stores added_date as a unix timestamp; the views print a date
        if (! empty($r['added_date']) && ctype_digit((string) $r['added_date'])) {
            $r['added_date'] = date('Y-m-d H:i:s', (int) $r['added_date']);
        }

        return $r;
    }
}

if (! function_exists('hi_content_rows')) {
    /** hi_content_row() over a result set. */
    function hi_content_rows(array $rows): array
    {
        return array_map('hi_content_row', $rows);
    }
}

if (! function_exists('hi_review_pk')) {
    /**
     * Live new.hinoida.com's review table is keyed on `review_id`; this rewrite
     * created it with `id`. Ask the database instead of assuming — the wrong
     * one throws "no such column: id" on every listing page.
     */
    function hi_review_pk($db = null): string
    {
        static $pk = null;
        if ($pk !== null) { return $pk; }
        $db = $db ?: \Config\Database::connect();
        if (! $db->tableExists('review')) { return $pk = 'id'; }

        return $pk = $db->fieldExists('id', 'review') ? 'id' : 'review_id';
    }
}

if (! function_exists('hi_change_fields')) {
    /**
     * Fields an owner's edit must be approved for, label => column.
     * Mirrors the original's fieldMap in User.php — the identity of the
     * business. Photos, hours and amenities are not in it and still apply
     * straight away.
     */
    function hi_change_fields(): array
    {
        return [
            'Business name' => 'name',
            'Description'   => 'description',
            'Phone'         => 'phone',
            'Email'         => 'email',
            'Address'       => 'address',
            'Area'          => 'locality_id',
            'Categories'    => 'categories',
        ];
    }
}

if (! function_exists('hi_queue_change')) {
    /**
     * Record an owner's proposed edit instead of applying it.
     *
     * Only what actually differs is stored, as old/new pairs, so the admin
     * queue shows the change rather than the whole row. Returns the number of
     * changed fields — 0 means nothing was queued and the caller may just save.
     */
    function hi_queue_change($db, array $listing, array $new, int $uid): int
    {
        hi_ensure_tables($db);
        $diff = [];
        foreach (hi_change_fields() as $label => $col) {
            if (! array_key_exists($col, $new)) { continue; }
            $old = (string) ($listing[$col] ?? '');
            $nv  = (string) $new[$col];
            if ($old === $nv) { continue; }
            $diff[$col] = ['label' => $label, 'old' => $old, 'new' => $nv];
        }
        if (! $diff) { return 0; }

        // one open request per listing — a second edit replaces the first
        // rather than queueing two conflicting versions
        $open = $db->table('listing_changes')->where('listing_id', (int) $listing['id'])
            ->where('status', 'pending')->get()->getRowArray();
        $row = [
            'listing_id' => (int) $listing['id'],
            'user_id'    => $uid,
            'payload'    => json_encode($diff),
            'status'     => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        if ($open) { $db->table('listing_changes')->where('id', (int) $open['id'])->update($row); }
        else       { $db->table('listing_changes')->insert($row); }

        return count($diff);
    }
}

if (! function_exists('hi_needs_approval')) {
    /**
     * Live listings need approval to change; drafts and rejected ones do not,
     * because nothing public is affected yet. Admins always edit directly.
     */
    function hi_needs_approval(array $listing, int $role): bool
    {
        if ($role === 1) { return false; }

        return strtolower((string) ($listing['status'] ?? '')) === 'active';
    }
}

if (! function_exists('hi_money')) {
    /**
     * Price is free text in the original — "450", "₹450", "From 2500". Add the
     * rupee sign only when it is a bare number, so text like "On request" and
     * prices that already carry a symbol are left exactly as typed.
     */
    function hi_money(?string $v): string
    {
        $v = trim((string) $v);
        if ($v === '') { return ''; }
        if (preg_match('/^\d+(\.\d+)?$/', $v)) {
            return '₹' . number_format((float) $v, (strpos($v, '.') !== false ? 2 : 0));
        }

        return $v;
    }
}

if (! function_exists('hi_detail_table_for')) {
    /**
     * The original switches on listing_type, but every row in this database
     * carries "listing" or "general" — the four real types were never set. So
     * fall back to the listing's category group, which does distinguish a
     * restaurant from a hotel. Without this the detail tables are dead code.
     */
    function hi_detail_table_for(array $listing): string
    {
        $t = hi_detail_table((string) ($listing['listing_type'] ?? ''));
        if ($t !== '') { return $t; }

        helper('facts');
        $byGroup = [
            'food'     => 'food_menu',
            'hotels'   => 'hotel_room_specification',
            'beauty'   => 'beauty_service',
            'shopping' => 'product_details',
        ];

        return $byGroup[hi_group_of($listing)] ?? '';
    }
}

if (! function_exists('hi_details_for')) {
    /** Detail rows for a listing, or [] when its type has no detail table. */
    function hi_details_for(array $listing): array
    {
        $t = hi_detail_table_for($listing);
        if ($t === '') { return []; }
        $db = \Config\Database::connect();
        if (! $db->tableExists($t)) { return []; }

        // sort_order is this rewrite's addition; the live tables predate it
        $q = $db->table($t)->where('listing_id', (int) $listing['id']);
        if ($db->fieldExists('sort_order', $t)) { $q->orderBy('sort_order', 'ASC'); }

        $rows = $q->orderBy('id', 'ASC')->get()->getResultArray();

        // A row with nothing in it rendered as a blank line under a heading
        // that still counted it — "Menu (1)" above an empty item.
        return array_values(array_filter($rows, static function (array $r): bool {
            foreach (['name', 'price', 'items', 'description', 'variant', 'service_times'] as $k) {
                if (trim((string) ($r[$k] ?? '')) !== '') { return true; }
            }

            return false;
        }));
    }
}
