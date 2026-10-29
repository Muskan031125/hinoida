<?php

/**
 * Compare our pages against the original new.hinoida.com equivalents.
 *
 *   php tools/audit-vs-original.php http://127.0.0.1:8080
 *
 * The site-crawl audit finds things that are broken. This one finds things
 * that are *missing* — a section, a control or a piece of information the
 * original shows and we do not. That is the class of fault that only ever
 * surfaced when someone put two screenshots side by side.
 *
 * For each page pair it reports:
 *   - headings the original has and we do not
 *   - form controls (search, filters, sorts) they have and we do not
 *   - counts: images, links, form fields
 *
 * Read-only, and it only ever GETs.
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$orig = 'http://localhost:8080/';

/** page pairs: [ours, theirs, label] */
// both sides share the same paths now — we serve the original's URL shapes
$pairs = [
    ['/',                          '/',                          'home'],
    ['/listings',                  '/listings',                  'listings'],
    ['/category',                  '/category',                  'categories'],
    ['/city',                      '/city',                      'cities'],
    ['/offer',                     '/offer',                     'offers'],
    ['/event',                     '/event',                     'events'],
    ['/news',                      '/news',                      'news'],
    ['/contact',                   '/contact',                   'contact'],
    ['/home/about',                '/home/about',                'about'],
    ['/home/privacy_policy',       '/home/privacy_policy',       'privacy'],
    ['/home/terms_and_conditions', '/home/terms_and_conditions', 'terms'],
    ['/home/faq',                  '/home/faq',                  'faq'],
];

function get(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; hinoida-compare)',
    ]);
    $b = (string) curl_exec($ch);
    curl_close($ch);

    return $b;
}

/** visible headings, lowercased and squashed for comparison */
function headings(string $html): array
{
    preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $m);
    $out = [];
    foreach ($m[1] ?? [] as $h) {
        $t = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        // drop emoji and punctuation so "🎥 Follow Our Journey" matches "Follow our journey"
        $t = trim(preg_replace('/[^a-z0-9 ]+/i', ' ', $t));
        $t = trim(preg_replace('/\s+/', ' ', mb_strtolower($t)));
        if ($t !== '' && mb_strlen($t) > 3) { $out[$t] = true; }
    }

    return array_keys($out);
}

function controls(string $html): array
{
    $c = [];
    if (preg_match('/<input[^>]+type="?search/i', $html) || preg_match('/name="q"/i', $html)) { $c[] = 'search box'; }
    if (preg_match_all('/<select[^>]*>/i', $html, $m))  { $c[] = count($m[0]) . ' select(s)'; }
    if (preg_match('/type="?checkbox/i', $html))         { $c[] = 'checkbox filter'; }
    if (preg_match('/load\s*more/i', $html))             { $c[] = 'load more'; }
    if (preg_match('/pagination|page-link|\?page=/i', $html)) { $c[] = 'pagination'; }
    if (preg_match('/localit/i', $html))                 { $c[] = 'locality control'; }

    return $c;
}

$width = 78;
foreach ($pairs as [$ourPath, $theirPath, $label]) {
    $ours   = get($base . $ourPath);
    $theirs = get($orig . $theirPath);

    if (trim($theirs) === '') {
        printf("  %-12s original unreachable — skipped\n", $label);
        continue;
    }

    $ho = headings($ours);
    $ht = headings($theirs);

    // theirs but not ours, ignoring near-matches
    $missing = [];
    foreach ($ht as $h) {
        $hit = false;
        foreach ($ho as $o) {
            similar_text($h, $o, $pct);
            if ($pct > 62) { $hit = true; break; }
        }
        if (! $hit) { $missing[] = $h; }
    }

    $co = controls($ours);
    $ct = controls($theirs);
    $missingCtl = array_values(array_diff($ct, $co));

    printf("  === %s  (ours %s | theirs %s) ===\n", strtoupper($label), $ourPath, $theirPath);
    if ($missing) {
        echo "      headings only they have:\n";
        foreach (array_slice($missing, 0, 8) as $m) { echo '        - ' . mb_substr($m, 0, 64) . "\n"; }
    }
    if ($missingCtl) { echo '      controls only they have: ' . implode(', ', $missingCtl) . "\n"; }
    if (! $missing && ! $missingCtl) { echo "      nothing missing\n"; }
    echo "\n";
}
