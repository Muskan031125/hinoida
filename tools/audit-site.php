<?php

/**
 * Crawl the site and report the kinds of fault that keep turning up one
 * screenshot at a time.
 *
 *   php tools/audit-site.php http://127.0.0.1:8080
 *
 * Checks every page reachable from the homepage plus a sample of each
 * generated URL type, and reports:
 *
 *   - status codes that are not 200
 *   - HTML entities visible as text ("&lt;p&gt;", "&amp;amp;")
 *   - images whose src cannot resolve
 *   - internal links that 404
 *   - stray punctuation from an empty field (", " with nothing before it)
 *   - pages with no <h1>, or a duplicated one
 *
 * Read-only: it never posts anything.
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$db   = new PDO('sqlite:' . __DIR__ . '/../writable/hinoida.db');

/**
 * The site writes links with app.baseURL, which is rarely the host we are
 * crawling. Rewriting them to $base is what makes link and image checking
 * work at all — without it every URL looked external and was skipped.
 */
$siteBase = '';
foreach ([__DIR__ . '/../.env'] as $envFile) {
    if (! is_file($envFile)) { continue; }
    foreach (file($envFile) as $line) {
        if (preg_match('/^\s*app\.baseURL\s*=\s*[\'"]?([^\'"\s]+)/', $line, $m)) { $siteBase = rtrim($m[1], '/'); }
    }
}
$localise = static function (string $url) use ($siteBase, $base): string {
    return ($siteBase !== '' && str_starts_with($url, $siteBase)) ? $base . substr($url, strlen($siteBase)) : $url;
};

/** @return array{code:int,body:string} */
function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => 'hinoida-audit',
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => $body];
}

// a representative URL of every kind the site generates
// the live URL shapes — see app/Helpers/hiurl_helper.php
$urls = ['/', '/category', '/city', '/news', '/event', '/offer', '/listings?search_string=cafe',
         '/home/about', '/contact', '/home/privacy_policy', '/home/terms_and_conditions',
         '/home/faq', '/home/pricing', '/compare',
         '/home/login', '/home/sign_up', '/advertise', '/credits', '/cookie'];

$pick = static function (string $sql) use ($db): array {
    try { return $db->query($sql)->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) { return []; }
};
$pickRows = static function (string $sql) use ($db): array {
    try { return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { return []; }
};
foreach ($pickRows('SELECT id, name, listing_type FROM listing WHERE status="active" ORDER BY RANDOM() LIMIT 6') as $l) {
    $type = in_array(strtolower(trim((string) $l['listing_type'])), ['restaurant','hotel','beauty','shop','general'], true)
        ? strtolower(trim((string) $l['listing_type'])) : 'general';
    $slug = trim(preg_replace('/-+/', '-', strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', html_entity_decode((string) $l['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')))), '-');
    $urls[] = '/' . $type . '/' . ($slug ?: 'listing') . '/' . $l['id'];
}
foreach ($pick('SELECT slug FROM category WHERE slug IS NOT NULL AND slug!="" ORDER BY RANDOM() LIMIT 6') as $s) { $urls[] = '/category/' . $s; }
foreach ($pick('SELECT slug FROM locality WHERE slug IS NOT NULL AND slug!="" ORDER BY RANDOM() LIMIT 4') as $s) { $urls[] = '/' . $s; }
foreach ($pick('SELECT slug FROM area WHERE slug IS NOT NULL AND slug!="" ORDER BY RANDOM() LIMIT 4') as $s) { $urls[] = '/a/' . $s; }
foreach ($pick('SELECT id FROM blogs WHERE type="Blog"  AND status=1 LIMIT 3') as $s) { $urls[] = '/news/post/' . $s; }
foreach ($pick('SELECT id FROM blogs WHERE type="Event" AND status=1 LIMIT 3') as $s) { $urls[] = '/event/post/' . $s; }
foreach ($pick('SELECT id FROM blogs WHERE type="Offer" AND status=1 LIMIT 3') as $s) { $urls[] = '/offer/post/' . $s; }

$faults = [];
$note   = static function (string $url, string $kind, string $detail) use (&$faults): void {
    $faults[$kind][] = $url . '  —  ' . $detail;
};

$imgCache = [];
$checked  = 0;

foreach ($urls as $u) {
    $r = fetch($base . $u);
    $checked++;

    if (! in_array($r['code'], [200, 301, 302], true)) {
        $note($u, 'status', 'HTTP ' . $r['code']);
        continue;
    }
    $html = $r['body'];

    // entities showing as text — the double-encoding fault
    foreach (['&amp;amp;', '&amp;lt;', '&lt;p&gt;', '&lt;strong&gt;', '&amp;#39;', '&amp;nbsp;'] as $ent) {
        if (str_contains($html, $ent)) { $note($u, 'encoding', 'visible "' . $ent . '"'); break; }
    }

    // images that cannot resolve
    preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $im);
    foreach (array_slice(array_unique($im[1] ?? []), 0, 8) as $src) {
        $src = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (str_starts_with($src, 'data:')) { continue; }
        $abs = $localise(str_starts_with($src, 'http') ? $src : $base . '/' . ltrim($src, '/'));
        // only local ones — an off-site image is not ours to fix
        if (! str_starts_with($abs, $base)) { continue; }
        if (! isset($imgCache[$abs])) { $imgCache[$abs] = fetch($abs)['code']; }
        if ($imgCache[$abs] !== 200) { $note($u, 'image', $imgCache[$abs] . ' on ' . $src); }
    }

    // a field printed with its separator when the field itself is empty
    if (preg_match('/<\/i>\s*,\s*</', $html)) { $note($u, 'empty-field', 'separator with nothing before it'); }

    // headings
    $h1 = preg_match_all('/<h1[\s>]/i', $html);
    if ($h1 === 0) { $note($u, 'heading', 'no <h1>'); }
    if ($h1 > 1)   { $note($u, 'heading', $h1 . ' <h1> tags'); }

    // a card or row rendered with no text at all
    if (preg_match('/<b>\s*<\/b>|<h3>\s*<\/h3>/', $html)) { $note($u, 'empty-field', 'empty heading element'); }
}

// internal links, from the homepage and the main indexes
$linkPages = ['/', '/category', '/city', '/news', '/event', '/offer'];
$seen = [];
foreach ($linkPages as $p) {
    $html = fetch($base . $p)['body'];
    preg_match_all('/<a[^>]+href="([^"]+)"/i', $html, $lm);
    foreach (array_unique($lm[1] ?? []) as $href) {
        $href = $localise(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (! str_starts_with($href, $base)) { continue; }
        if (str_contains($href, '#') || isset($seen[$href])) { continue; }
        $seen[$href] = true;
        if (count($seen) > 90) { break 2; }
        $c = fetch($href)['code'];
        if (! in_array($c, [200, 301, 302], true)) { $note($p, 'link', $c . ' -> ' . substr($href, strlen($base))); }
    }
}

echo "  pages checked : " . $checked . "\n";
echo "  links checked : " . count($seen) . "\n";
echo "  images checked: " . count($imgCache) . "\n\n";

if (! $faults) { echo "  no faults found\n"; exit(0); }

foreach ($faults as $kind => $list) {
    echo '  === ' . strtoupper($kind) . ' (' . count($list) . ") ===\n";
    foreach (array_slice(array_unique($list), 0, 12) as $l) { echo '    ' . $l . "\n"; }
    if (count(array_unique($list)) > 12) { echo '    … and ' . (count(array_unique($list)) - 12) . " more\n"; }
    echo "\n";
}
