<?php

/**
 * Decode text the original site saved double-escaped.
 *
 *   php tools/fix-encoding.php --dry     show what would change
 *   php tools/fix-encoding.php           apply it
 *
 * hinoida escaped on the way in and again on the way out, so the database
 * holds "Logistics &amp;amp; Packers" and "&lt;p&gt;About us&lt;/p&gt;".
 * Escaping that once more on output — which is correct behaviour — printed
 * the entities on the page.
 *
 * Fixing it at each read meant remembering every read. This fixes the values
 * themselves, once, so plain columns hold plain text and the HTML ones hold
 * HTML. Decoding is repeated until the value stops changing, because one pass
 * turns "&amp;#39;" into "&#39;" rather than an apostrophe.
 *
 * Idempotent: running it twice changes nothing the second time.
 */

$apply = ! in_array('--dry', $argv, true);
$db    = new PDO('sqlite:' . __DIR__ . '/../writable/hinoida.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// column => is it meant to hold HTML once decoded
$targets = [
    'listing'   => ['name' => false, 'description' => true, 'address' => false, 'seo_title' => false,
                    'meta_description' => false, 'seo_meta_tags' => false, 'tags' => false],
    'blogs'     => ['title' => false, 'blog_text' => true, 'seo_title' => false, 'meta_description' => false],
    'category'  => ['name' => false],
    'locality'  => ['name' => false],
    'area'      => ['name' => false, 'description' => true],
    'amenities' => ['name' => false],
    'offer'     => ['title' => false, 'description' => true, 'business' => false],
    'seo'       => ['seo_title' => false, 'meta_description' => false],
];

$decode = static function (string $v): string {
    for ($i = 0; $i < 4; $i++) {
        $n = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($n === $v) { break; }
        $v = $n;
    }

    return $v;
};

$totalRows = 0;
$totalCols = 0;

foreach ($targets as $table => $cols) {
    try { $db->query('SELECT 1 FROM `' . $table . '` LIMIT 1'); }
    catch (Throwable $e) { continue; }

    $have = array_column($db->query('PRAGMA table_info(`' . $table . '`)')->fetchAll(PDO::FETCH_ASSOC), 'name');

    foreach ($cols as $col => $_isHtml) {
        if (! in_array($col, $have, true)) { continue; }

        $rows = $db->query('SELECT rowid AS rid, `' . $col . '` AS v FROM `' . $table . '`
                            WHERE `' . $col . '` IS NOT NULL AND `' . $col . '` != ""')->fetchAll(PDO::FETCH_ASSOC);
        $changed = [];
        foreach ($rows as $r) {
            $new = $decode((string) $r['v']);
            if ($new !== (string) $r['v']) { $changed[(int) $r['rid']] = $new; }
        }
        if (! $changed) { continue; }

        $totalCols++;
        $totalRows += count($changed);
        printf("  %-10s %-18s %s rows\n", $table, $col, number_format(count($changed)));

        $first = reset($changed);
        printf("      e.g. %s\n", mb_substr(preg_replace('/\s+/', ' ', $first), 0, 72));

        if ($apply) {
            $st = $db->prepare('UPDATE `' . $table . '` SET `' . $col . '` = ? WHERE rowid = ?');
            $db->beginTransaction();
            foreach ($changed as $rid => $v) { $st->execute([$v, $rid]); }
            $db->commit();
        }
    }
}

echo "\n  " . ($apply ? 'updated ' : 'would update ')
    . number_format($totalRows) . ' value(s) across ' . $totalCols . " column(s)\n";
