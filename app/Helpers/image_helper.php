<?php
/**
 * Real uploaded photos when present, else a CATEGORY-MATCHED placeholder.
 * Previously every listing fell back to picsum, which serves random photos —
 * restaurants showed mountains. Now the keyword is derived from the listing's
 * category so the imagery is always relevant.
 */

/** keyword pools per taxonomy group — single word each (multi-word 500s upstream) */
function hi_img_keywords(): array
{
    return [
        'food'          => ['restaurant', 'cafe', 'food', 'dining', 'kitchen', 'bakery'],
        'medical'       => ['hospital', 'clinic', 'doctor', 'pharmacy', 'medical', 'healthcare'],
        'education'     => ['school', 'classroom', 'library', 'students', 'campus', 'study'],
        'beauty'        => ['salon', 'spa', 'beauty', 'haircut', 'massage', 'makeup'],
        'realestate'    => ['apartment', 'building', 'property', 'house', 'architecture', 'interior'],
        'hotels'        => ['hotel', 'resort', 'bedroom', 'lobby', 'banquet', 'suite'],
        'automobile'    => ['car', 'garage', 'workshop', 'motorcycle', 'automobile', 'showroom'],
        'sports'        => ['gym', 'fitness', 'sports', 'workout', 'stadium', 'swimming'],
        'shopping'      => ['shop', 'store', 'retail', 'boutique', 'shopping', 'clothing'],
        'services'      => ['tools', 'repair', 'plumber', 'workshop', 'electrician', 'carpenter'],
        'proservices'   => ['office', 'business', 'meeting', 'desk', 'workspace', 'consulting'],
        'entertainment' => ['party', 'concert', 'cinema', 'event', 'celebration', 'nightlife'],
        'pets'          => ['dog', 'cat', 'pets', 'puppy', 'veterinary', 'kitten'],
        'spiritual'     => ['temple', 'mandir', 'worship', 'prayer', 'shrine', 'religious'],
        'govt'          => ['government', 'office', 'building', 'civic', 'park', 'public'],
        'everyday'      => ['grocery', 'market', 'vegetables', 'store', 'produce', 'dairy'],
    ];
}

/** pick the image keyword for a listing from its category name */
function hi_img_kw(array $l, int $variant = 0): string
{
    $gk = 'proservices';
    if (function_exists('hi_group_key')) {
        $cat = $l['_cat_name'] ?? ($l['category_name'] ?? '');
        if ($cat !== '') { $gk = hi_group_key((string) $cat); }
    }
    $pool = hi_img_keywords()[$gk] ?? hi_img_keywords()['proservices'];
    $id   = (int) ($l['id'] ?? 0);
    return $pool[($id + $variant) % count($pool)];
}

/** Optional locally-hosted stock photos: assets/img/cat/<group>-1..6.jpg */
function hi_local_pool(string $gk): array
{
    static $cache = [];
    if (isset($cache[$gk])) { return $cache[$gk]; }
    $dir   = FCPATH . 'assets/img/cat/';
    $found = [];
    for ($i = 1; $i <= 6; $i++) {
        if (is_file($dir . $gk . '-' . $i . '.jpg')) { $found[] = 'assets/img/cat/' . $gk . '-' . $i . '.jpg'; }
    }
    return $cache[$gk] = $found;
}

/** brand-tinted gradient per group — stays inside the hinoida blue family */
function hi_group_tint(string $gk): array
{
    return [
        'food'          => ['#0b3f8f', '#1867c9'], 'medical'    => ['#0a4a7a', '#1580b8'],
        'education'     => ['#123a8f', '#2a6ad4'], 'beauty'     => ['#3a2a7a', '#6d4fc0'],
        'realestate'    => ['#0a2a6b', '#1450a8'], 'hotels'     => ['#0d3566', '#1c6aa8'],
        'automobile'    => ['#14304f', '#2f6f9e'], 'sports'     => ['#0b4a5e', '#159bb0'],
        'shopping'      => ['#2b2f7a', '#5560cc'], 'services'   => ['#1d3557', '#3d7ea6'],
        'proservices'   => ['#0f2a52', '#2a5f9e'], 'entertainment' => ['#43206b', '#8a3fb0'],
        'pets'          => ['#1a4a52', '#2f9c8a'], 'spiritual'  => ['#5a3212', '#c07a2a'],
        'govt'          => ['#22304a', '#4a6a94'], 'everyday'   => ['#12503a', '#2a9464'],
    ][$gk] ?? ['#0a2a6b', '#1450a8'];
}

/** simple, recognisable glyph per group (single SVG path, 24x24 viewBox) */
function hi_group_glyph(string $gk): string
{
    return [
        // fork+knife
        'food' => 'M7 2v8a2 2 0 0 0 2 2v10h2V12a2 2 0 0 0 2-2V2h-1.5v7h-1V2h-1v7h-1V2H7zm10 0c-1.5 1-2.5 3-2.5 6 0 2 .8 3.2 2 3.8V22h2V2h-1.5z',
        // cross / medical
        'medical' => 'M10 2h4v6h6v4h-6v10h-4V12H4V8h6V2z',
        // graduation cap
        'education' => 'M12 3 1 9l11 6 9-4.9V17h2V9L12 3zM5 13.2V17c0 1.7 3.1 3 7 3s7-1.3 7-3v-3.8l-7 3.8-7-3.8z',
        // scissors
        'beauty' => 'M9.6 6.6 14 12l-4.4 5.4a3 3 0 1 0 1.5 1.3L15 14l3.5 4.3a3 3 0 1 0 1.5-1.3L11.1 5.3a3 3 0 1 0-1.5 1.3zM7 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm0 15a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z',
        // building
        'realestate' => 'M4 21V7l8-4 8 4v14h-6v-5h-4v5H4zm4-8h2v-2H8v2zm6 0h2v-2h-2v2zM8 9h2V7H8v2zm6 0h2V7h-2v2z',
        // bed
        'hotels' => 'M3 6h2v7h7V8h5a4 4 0 0 1 4 4v6h-2v-3H5v3H3V6z',
        // car
        'automobile' => 'M5 11l1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11h1v6h-2v2h-3v-2H9v2H6v-2H4v-6h1zm2.2-.5h9.6l-1-3H8.2l-1 3zM7 15a1.2 1.2 0 1 0 0-2.4A1.2 1.2 0 0 0 7 15zm10 0a1.2 1.2 0 1 0 0-2.4 1.2 1.2 0 0 0 0 2.4z',
        // dumbbell
        'sports' => 'M4 9h2v6H4V9zm3 1h2v4H7v-4zm8 0h2v4h-2v-4zm3-1h2v6h-2V9zm-9 2h6v2H9v-2z',
        // shopping bag
        'shopping' => 'M6 8h12l1 13H5L6 8zm3 0V6a3 3 0 1 1 6 0v2h-2V6a1 1 0 1 0-2 0v2H9z',
        // wrench
        'services' => 'M20 6.5A5 5 0 0 1 13.2 13L7 19.2a2.5 2.5 0 1 1-3.5-3.5L9.7 9.5A5 5 0 0 1 16.2 3l-2.9 2.9 1.9 1.9L18.1 5c.6.4 1.1 1 1.4 1.6z',
        // briefcase
        'proservices' => 'M9 4h6a2 2 0 0 1 2 2v1h4v13H3V7h4V6a2 2 0 0 1 2-2zm0 3h6V6H9v1z',
        // music/star burst
        'entertainment' => 'M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4L12 2z',
        // paw
        'pets' => 'M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm8 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4zM5 13a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm14 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm-7 1c3 0 5 2 5 4a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2c0-2 2-4 5-4z',
        // temple
        'spiritual' => 'M12 2l1.5 3h-3L12 2zm-1 4h2v2h-2V6zM5 10h14l1 2H4l1-2zm1 3h12v8h-4v-5h-4v5H6v-8z',
        // classical building
        'govt' => 'M12 3l9 4v2H3V7l9-4zM5 11h2v7H5v-7zm4 0h2v7H9v-7zm4 0h2v7h-2v-7zm4 0h2v7h-2v-7zM3 19h18v2H3v-2z',
        // basket
        'everyday' => 'M7 8l2-5h1.6L9 8h6l-1.6-5H15l2 5h4v2l-1.6 9H4.6L3 10V8h4zm2 4v4h1.5v-4H9zm4 0v4h1.5v-4H13z',
    ][$gk] ?? 'M4 21V7l8-4 8 4v14H4z';
}

/**
 * A clean, always-correct category tile as an inline SVG data URI.
 * Guaranteed relevant (icon = the actual category), no external request,
 * no licensing risk, and it can never show something misleading.
 */
function hi_tile(array $l, int $variant = 0, int $w = 800, int $h = 600, ?string $forceGroup = null): string
{
    $gk        = $forceGroup ?? hi_group_of($l);
    [$c1, $c2] = hi_group_tint($gk);
    $glyph     = hi_group_glyph($gk);
    $id        = (int) ($l['id'] ?? 0) + $variant;

    // Monogram from the business name — this is what makes each card unique.
    // Without it every listing in a category renders an identical icon, which
    // reads as "image failed to load".
    $mono = '';
    $name = trim((string) ($l['name'] ?? ''));
    if ($name !== '') {
        $skip = ['the', 'a', 'an', 'of', 'and', 'shop', 'store', 'new'];
        foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($name), -1, PREG_SPLIT_NO_EMPTY) as $wd) {
            if (in_array($wd, $skip, true)) { continue; }
            $mono .= mb_strtoupper(mb_substr($wd, 0, 1));
            if (mb_strlen($mono) >= 2) { break; }
        }
        if ($mono === '') { $mono = mb_strtoupper(mb_substr($name, 0, 1)); }
    }

    // per-listing hue shift keeps a wall of cards from looking like one image
    $shift = static function (string $hex, int $deg): string {
        $r = hexdec(substr($hex, 1, 2)) / 255; $g = hexdec(substr($hex, 3, 2)) / 255; $b = hexdec(substr($hex, 5, 2)) / 255;
        $mx = max($r, $g, $b); $mn = min($r, $g, $b); $d = $mx - $mn; $hh = 0; $s = 0; $ll = ($mx + $mn) / 2;
        if ($d > 0) {
            $s = $ll > 0.5 ? $d / (2 - $mx - $mn) : $d / ($mx + $mn);
            if ($mx === $r)      { $hh = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6; }
            elseif ($mx === $g)  { $hh = (($b - $r) / $d + 2) / 6; }
            else                 { $hh = (($r - $g) / $d + 4) / 6; }
        }
        $hh = fmod($hh + $deg / 360 + 1, 1);
        $q  = $ll < 0.5 ? $ll * (1 + $s) : $ll + $s - $ll * $s; $p = 2 * $ll - $q;
        $f  = static function ($t) use ($p, $q) {
            $t = fmod($t + 1, 1);
            if ($t < 1 / 6) { return $p + ($q - $p) * 6 * $t; }
            if ($t < 1 / 2) { return $q; }
            if ($t < 2 / 3) { return $p + ($q - $p) * (2 / 3 - $t) * 6; }
            return $p;
        };
        return sprintf('#%02x%02x%02x', (int) round($f($hh + 1 / 3) * 255), (int) round($f($hh) * 255), (int) round($f($hh - 1 / 3) * 255));
    };
    $deg = (($id * 37) % 23) - 11;                  // ±11° — same family, visibly different
    $c1  = $shift($c1, $deg); $c2 = $shift($c2, $deg);
    $angle = 110 + ($id % 6) * 12;

    $gid = 'g' . $id;                                 // unique id per tile
    $mfs = round($h * 0.30, 1);                       // monogram size
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '">'
        . '<defs><linearGradient id="' . $gid . '" gradientTransform="rotate(' . $angle . ')">'
        . '<stop offset="0%" stop-color="' . $c1 . '"/><stop offset="100%" stop-color="' . $c2 . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="' . $w . '" height="' . $h . '" fill="url(#' . $gid . ')"/>'
        . '<circle cx="' . round($w * (0.68 + ($id % 3) * 0.07), 1) . '" cy="' . round($h * 0.2, 1) . '" r="' . round($w * 0.4, 1) . '" fill="#ffffff" opacity="0.10"/>'
        . '<circle cx="' . round($w * 0.14, 1) . '" cy="' . round($h * 0.9, 1) . '" r="' . round($w * 0.3, 1) . '" fill="#000000" opacity="0.12"/>';

    if ($mono !== '') {
        // large, light monogram in the clear upper area (text scrim covers the bottom)
        $svg .= '<text x="' . round($w / 2, 1) . '" y="' . round($h * 0.44, 1) . '" text-anchor="middle"'
            . ' font-family="Inter,Segoe UI,Helvetica,Arial,sans-serif" font-size="' . $mfs . '" font-weight="800"'
            . ' fill="#ffffff" fill-opacity="0.90" letter-spacing="' . round($mfs * 0.02, 1) . '">' . htmlspecialchars($mono, ENT_XML1) . '</text>';
    }
    // small category badge, top-left — keeps the category cue without dominating
    $bs = round($h * 0.115, 1);
    $svg .= '<g transform="translate(' . round($w * 0.055, 1) . ',' . round($h * 0.07, 1) . ') scale(' . round($bs / 24, 4) . ')"'
        . ' fill="#ffffff" opacity="0.72"><path d="' . $glyph . '"/></g>'
        . '</svg>';

    return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}

/** group key for a listing row */
function hi_group_of(array $l): string
{
    if (! function_exists('hi_group_key')) { return 'proservices'; }
    $cat = $l['_cat_name'] ?? ($l['category_name'] ?? '');
    return $cat !== '' ? hi_group_key((string) $cat) : 'proservices';
}

/**
 * Deterministic, category-relevant placeholder.
 * Serves a locally hosted, licence-cleared stock photo when one exists;
 * otherwise falls back to the keyword-matched remote service.
 */
function hi_placeholder(array $l, int $variant = 0, int $w = 800, int $h = 600): string
{
    $gk   = hi_group_of($l);
    $pool = hi_local_pool($gk);
    if ($pool) {                                   // real photos, if ever added
        $idx = ((int) ($l['id'] ?? 0) + $variant) % count($pool);
        return base_url($pool[$idx]);
    }
    return hi_tile($l, $variant, $w, $h);          // designed category tile
}

/**
 * Avatar initial for a person or business.
 * Uses the first letter of the first real name word (skipping titles like
 * "Mr"/"Dr" and any leading punctuation), falling back to the email, then '?'.
 */
function hi_initial(?string $name, ?string $email = null): string
{
    $skip = ['mr', 'mrs', 'ms', 'miss', 'dr', 'the', 'm/s'];
    $name = trim((string) $name);

    if ($name !== '') {
        // \p{M} keeps Indic vowel signs attached to their consonant
        $words = preg_split('/[^\p{L}\p{M}\p{N}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
        $n     = count($words);
        foreach ($words as $i => $w) {
            if (in_array(mb_strtolower($w), $skip, true)) { continue; }
            // "M/s Gupta Traders" -> M,s,Gupta,Traders: skip a stray ASCII
            // initial when a longer word follows
            if ($i < $n - 1 && mb_strlen($w) === 1 && preg_match('/^[A-Za-z]$/', $w)) { continue; }
            return mb_strtoupper(mb_substr($w, 0, 1));
        }
        if ($words) { return mb_strtoupper(mb_substr($words[0], 0, 1)); }
        // name was only punctuation — fall through to the email below
    }
    $email = trim((string) $email);
    if ($email !== '' && preg_match('/[\p{L}\p{N}]/u', $email, $m)) {
        return mb_strtoupper($m[0]);
    }
    return '?';
}

/** Stable avatar colour so different people don't all look identical. */
function hi_avatar_bg(?string $seed): string
{
    $seed = trim((string) $seed);
    if ($seed === '') { return 'linear-gradient(135deg,#0d5bd8,#1741a6)'; }
    $palette = [
        ['#0d5bd8', '#1741a6'], ['#7a4fd0', '#5a2fb0'], ['#0b8a3d', '#0a6e31'],
        ['#c87c0a', '#a35f05'], ['#1580b8', '#0f6392'], ['#c0446b', '#9c2f52'],
        ['#2f9c8a', '#1f7a6b'], ['#5560cc', '#3b45a8'],
    ];
    [$a, $b] = $palette[crc32(mb_strtolower($seed)) % count($palette)];
    return 'linear-gradient(135deg,' . $a . ',' . $b . ')';
}

/** Image for a CATEGORY tile — keyword from the category's own group. */
function hi_cat_img(array $c, int $w = 360, int $h = 260): string
{
    if (! empty($c['thumbnail'])) {
        return str_starts_with($c['thumbnail'], 'http') ? $c['thumbnail'] : base_url($c['thumbnail']);
    }
    $gk = 'proservices';
    if (function_exists('hi_group_key_or_blank')) {
        $gk = hi_group_key_or_blank($c['name'] ?? '')
           ?: (hi_group_key_or_blank($c['parent_name'] ?? '') ?: 'proservices');
    }
    $id = (int) ($c['id'] ?? 0);
    if ($local = hi_local_pool($gk)) {
        return base_url($local[$id % count($local)]);
    }
    return hi_tile(['id' => $id, '_cat_name' => $c['name'] ?? ''], 0, $w, $h);
}

/**
 * CSS background for a CATEGORY card.
 * These cards are short and use background-size:cover, which crops a centred
 * glyph and hides it under the text scrim — so use a clean brand gradient
 * instead of a tile image. The card's own icon badge carries the category cue.
 */
function hi_cat_bg(array $c): string
{
    if (! empty($c['thumbnail'])) {
        // stored as a bare filename, so base_url() alone produced "/abc.jpg"
        // and the real category photograph 404'd behind a generated tile
        $t = hi_media($c['thumbnail'], 'category');
        return "linear-gradient(to top,rgba(6,14,30,.88),rgba(6,14,30,.15)),url('" . $t . "')";
    }
    $gk = 'proservices';
    if (function_exists('hi_group_key_or_blank')) {
        $gk = hi_group_key_or_blank($c['name'] ?? '')
           ?: (hi_group_key_or_blank($c['parent_name'] ?? '') ?: 'proservices');
    }
    $id = (int) ($c['id'] ?? 0);

    // real category photograph when we have one, behind a scrim so the label stays readable
    if ($pool = hi_local_pool($gk)) {
        $img = base_url($pool[$id % count($pool)]);
        return "linear-gradient(to top,rgba(6,14,30,.92) 0%,rgba(6,14,30,.62) 45%,rgba(6,14,30,.28) 100%),url('" . $img . "')";
    }

    [$c1, $c2] = hi_group_tint($gk);
    $ang = 120 + ($id % 5) * 15;
    return 'linear-gradient(' . $ang . 'deg,' . $c1 . ',' . $c2 . ')';
}

/**
 * Image for editorial content (blog / news / events / offers).
 *
 * $text is the headline. Without it every post in a section fell back to one
 * fixed group and rendered as the same flat gradient with no monogram —
 * six identical dark blocks that read as "images failed to load". The headline
 * usually names its own subject ("Rooftop Restaurants", "Off Gym Membership"),
 * so resolve the group from it and use one of the real photos in
 * assets/img/cat/. The per-topic group stays as the fallback.
 */
function hi_topic_img(string $topic, int $id, int $w = 440, int $h = 300, string $text = ''): string
{
    helper('facts');
    $asGroup = ['blog' => 'proservices', 'news' => 'govt', 'event' => 'entertainment', 'offer' => 'shopping'];

    $gk = '';
    if ($text !== '') {
        // Discount vocabulary collides with the category keywords — "Flat 40%
        // Off on Dining" resolved to real estate on the word "flat". Drop the
        // deal words first so only the subject is matched against.
        $clean = preg_replace(
            '/\b(flat|off|upto|up ?to|buy|get|free|save|deal|deals|combo|offer|offers|discount|coupon|only|on|at|the|and|in|for|with|your|new|noida)\b|[0-9%₹]+/i',
            ' ',
            $text
        );
        // Headline vocabulary is checked BEFORE the business taxonomy, which is
        // tuned for shop names and mis-reads editorial titles — "Live Music
        // Night" matched music *classes* (education), and "Dining" lost to the
        // restaurant's own name ("Spice"). Least ambiguous groups first;
        // entertainment last because its words are the vaguest.
        $editorial = [
            'sports'        => ['marathon', 'cricket', 'tournament', 'yoga', 'fitness'],
            'food'          => ['food', 'dining', 'dine', 'restaurant', 'cafe', 'brunch'],
            'education'     => ['workshop', 'seminar', 'science', 'exam', 'coaching', 'school'],
            'shopping'      => ['mela', 'bazaar', 'expo', 'sale', 'market', 'mall'],
            'entertainment' => ['music', 'concert', 'movie', 'film', 'carnival', 'night', 'live'],
        ];
        foreach ($editorial as $key => $kws) {
            if (preg_match('/\b(' . implode('|', $kws) . ')s?\b/i', $clean)) { $gk = $key; break; }
        }
        if ($gk === '') { $gk = hi_group_key_or_blank($clean); }
    }
    if ($gk === '') { $gk = $asGroup[$topic] ?? 'proservices'; }

    // a real photograph beats a generated gradient wherever one exists
    if ($pool = hi_local_pool($gk)) {
        return base_url($pool[abs($id) % count($pool)]);
    }

    return hi_tile(['id' => $id, 'name' => $text, '_cat_name' => ''], 0, $w, $h, $gk);
}

/** All gallery image URLs for a listing (uploaded first, padded with matched placeholders). */
function hi_photos(array $l, int $min = 5): array
{
    $out = [];
    $ph  = json_decode($l['photos'] ?? '[]', true);
    if (is_array($ph)) {
        foreach ($ph as $p) {
            if (is_string($p) && $p !== '') { $out[] = hi_media($p, 'listing'); }
        }
    }
    // the cover and thumbnail are real photographs of the place too, and are
    // often set on listings whose photos array is empty
    foreach ([['listing_cover', 'cover'], ['listing_thumbnail', 'thumb']] as [$col, $kind]) {
        if (! empty($l[$col])) {
            $u = hi_media($l[$col], $kind);
            if ($u !== '' && ! in_array($u, $out, true)) { $out[] = $u; }
        }
    }

    // Only pad when there is nothing at all. A business with one real photo
    // used to get four generated tiles beside it, which reads as four more
    // photographs of the place that do not exist.
    if (! $out) {
        for ($i = 0; $i < max(1, $min); $i++) { $out[] = hi_placeholder($l, $i, 800, 600); }
    }

    return $out;
}

/** Single cover image for cards. */
if (! function_exists('hi_cat_icon')) {
    /**
     * A Font Awesome glyph for a category name.
     *
     * Reuses the group table rather than a second mapping — every group already
     * names its icon, so a category inherits whichever group it resolves to.
     */
    function hi_cat_icon(string $catName): string
    {
        helper(['taxonomy', 'facts']);
        $key = hi_group_key_or_blank($catName);
        if ($key !== '') {
            $g = hi_groups()[$key] ?? null;
            if ($g && ! empty($g[1])) { return (string) $g[1]; }
        }

        return 'shop';
    }
}

/**
 * Resolve a stored image filename to a URL.
 *
 * Live hinoida stores bare filenames — "32b97c61….jpg" — and keeps each kind in
 * its own folder. base_url() alone produced "/32b97c61….jpg", which resolves to
 * nothing, so every real photo in the database rendered as a placeholder.
 *
 * The folder is chosen by $kind. Where the files are served from is the
 * `media_base` setting: leave it empty once the uploads folder has been copied
 * onto this server, or point it at the site that already has them.
 */
function hi_media(?string $file, string $kind = 'listing'): string
{
    $file = trim((string) $file);
    if ($file === '') { return ''; }
    if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) { return $file; }

    $dirs = [
        'listing'   => 'uploads/listing_images/',
        'cover'     => 'uploads/listing_cover_photo/',
        'thumb'     => 'uploads/listing_thumbnails/',
        'blog'      => 'uploads/blog_thumbnails/',
        'blogcover' => 'uploads/blog_cover_images/',
        'category'  => 'uploads/category_thumbnails/',
        'area'      => 'uploads/area_images/',
        'user'      => 'uploads/user_image/',
        'menu'      => 'uploads/restaurant_menu_images/',
        'room'      => 'uploads/hotel_room_images/',
        'service'   => 'uploads/beauty_service_images/',
        'product'   => 'uploads/product_images/',
    ];

    // a value that already carries a path is left as it is — that is what this
    // rewrite's own uploader writes
    $path = str_contains($file, '/') ? $file : ($dirs[$kind] ?? $dirs['listing']) . $file;

    $base = rtrim((string) hi_setting('media_base'), '/');

    return $base !== '' ? $base . '/' . ltrim($path, '/') : base_url($path);
}

function hi_cover(array $l): string
{
    if (! empty($l['listing_cover'])) {
        return hi_media($l['listing_cover'], 'cover');
    }
    if (! empty($l['listing_thumbnail'])) {
        return hi_media($l['listing_thumbnail'], 'thumb');
    }
    $ph = json_decode($l['photos'] ?? '[]', true);
    if (is_array($ph) && ! empty($ph[0]) && is_string($ph[0])) {
        return hi_media($ph[0], 'listing');
    }
    return hi_placeholder($l, 0, 600, 560);
}
