<?php
/**
 * Download every referenced media file from the live site into public/uploads/
 * so the local install no longer depends on the remote host.
 *
 * Usage:
 *   php tools/download-media.php            # download missing files
 *   php tools/download-media.php --count    # just report totals, download nothing
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

const LIVE_BASE = 'https://new.hinoida.com';
const CONCURRENCY = 20;

$destRoot = __DIR__ . '/../public/uploads';
$countOnly = in_array('--count', $argv, true);

// ---------------------------------------------------------------------------
// Database (local XAMPP)
// ---------------------------------------------------------------------------
$db = new mysqli('localhost', 'root', '', 'u504377054_hiosob');
if ($db->connect_error) {
    fwrite(STDERR, "DB connection failed: " . $db->connect_error . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');

// ---------------------------------------------------------------------------
// Column -> folder mapping (bare filenames live in these folders on the server)
// ---------------------------------------------------------------------------
$sources = [
    ['listing',                  'photos',            'listing',   true ],
    ['listing',                  'listing_thumbnail', 'thumb',     false],
    ['listing',                  'listing_cover',     'cover',     false],
    ['blogs',                    'blog_thumbnail',    'blog',      false],
    ['blogs',                    'blog_cover',        'blogcover', false],
    ['category',                 'thumbnail',         'category',  false],
    ['area',                     'image',             'area',      false],
    ['food_menu',                'photo',             'menu',      true ],
    ['hotel_room_specification', 'photo',             'room',      true ],
    ['beauty_service',           'photo',             'service',   true ],
    ['product_details',          'photo',             'product',   true ],
];

$dirs = [
    'listing'   => 'listing_images',
    'cover'     => 'listing_cover_photo',
    'thumb'     => 'listing_thumbnails',
    'blog'      => 'blog_thumbnails',
    'blogcover' => 'blog_cover_images',
    'category'  => 'category_thumbnails',
    'area'      => 'area_images',
    'menu'      => 'restaurant_menu_images',
    'room'      => 'hotel_room_images',
    'service'   => 'beauty_service_images',
    'product'   => 'product_images',
];

// ---------------------------------------------------------------------------
// Collect unique filenames per folder
// ---------------------------------------------------------------------------
$files = []; // folder => [name => true]

foreach ($sources as [$table, $col, $kind, $isJson]) {
    $folder = $dirs[$kind];
    if (!isset($files[$folder])) { $files[$folder] = []; }

    $res = $db->query("SELECT `$col` FROM `$table` WHERE `$col` IS NOT NULL AND `$col` <> ''");
    if ($res === false) { fwrite(STDERR, "Query failed for $table.$col\n"); continue; }

    while ($row = $res->fetch_row()) {
        $val = (string) $row[0];
        if ($val === '') { continue; }

        $names = [];
        if ($isJson) {
            $arr = json_decode($val, true);
            if (is_array($arr)) {
                $names = $arr;
            } else {
                // defensive: comma-separated fallback
                $names = array_map('trim', explode(',', $val));
            }
        } else {
            $names = [$val];
        }

        foreach ($names as $n) {
            $n = trim((string) $n);
            if ($n === '') { continue; }
            if (preg_match('#^https?://#i', $n)) { continue; } // absolute URL
            if (strpos($n, '/') !== false) { continue; }       // already a path (new-style upload)
            if (!preg_match('/\.(jpe?g|png|gif|webp|avif|svg|mp4|webm|pdf)$/i', $n)) { continue; }
            $files[$folder][$n] = true;
        }
    }
    $res->free();
}

$total = 0;
foreach ($files as $names) { $total += count($names); }

echo "Folders / unique files:\n";
foreach ($files as $folder => $names) {
    echo sprintf("  %-24s %d\n", $folder . '/', count($names));
}
echo "TOTAL unique files: $total\n";

if ($countOnly) {
    echo "(count only — no files downloaded)\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// Build flat task list
// ---------------------------------------------------------------------------
$tasks = [];
foreach ($files as $folder => $names) {
    $dir = $destRoot . '/' . $folder;
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    foreach (array_keys($names) as $name) {
        $local = $dir . '/' . $name;
        if (is_file($local) && filesize($local) > 0) { continue; } // already have it
        $tasks[] = [
            'url'   => LIVE_BASE . '/uploads/' . $folder . '/' . rawurlencode($name),
            'local' => $local,
            'id'    => "$folder/$name",
        ];
    }
}

$pending = count($tasks);
echo "\nTo download now: $pending (skipping " . ($total - $pending) . " already present)\n";
if ($pending === 0) { echo "Nothing to do.\n"; exit(0); }

// ---------------------------------------------------------------------------
// Download with a rolling curl_multi window
// ---------------------------------------------------------------------------
$ok = 0; $fail = 0; $failed = [];
$handles = []; // task index => curl handle
$mh = curl_multi_init();

function startTask(array $task, $mh, &$handles, int $idx): void
{
    $ch = curl_init($task['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_REFERER        => 'https://new.hinoida.com/',
    ]);
    $handles[$idx] = $ch;
    curl_multi_add_handle($mh, $ch);
}

// seed the initial window
$queue = array_keys($tasks);
$next = 0;
for ($i = 0; $i < min(CONCURRENCY, count($queue)); $i++) {
    startTask($tasks[$queue[$next]], $mh, $handles, $queue[$next]);
    $next++;
}

$processed = 0;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh, 0.2);

    while ($info = curl_multi_info_read($mh)) {
        $ch = $info['handle'];
        $idx = array_search($ch, $handles, true);
        if ($idx === false) { continue; }

        $task = $tasks[$idx];
        $data = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($data !== null && $data !== false && strlen($data) > 0 && $code >= 200 && $code < 400) {
            if (@file_put_contents($task['local'], $data) !== false) {
                $ok++;
            } else {
                $fail++; $failed[] = $task['id'] . " (write failed)";
            }
        } else {
            $fail++; $failed[] = $task['id'] . " (HTTP $code)";
        }

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        unset($handles[$idx]);

        // refill the window
        if ($next < count($queue)) {
            startTask($tasks[$queue[$next]], $mh, $handles, $queue[$next]);
            $next++;
        }

        $processed++;
        if ($processed % 200 === 0) {
            echo sprintf("  %d/%d done — ok=%d fail=%d\n", $processed, $pending, $ok, $fail);
        }
    }
} while ($running > 0 || count($handles) > 0);

curl_multi_close($mh);

echo "\nDone. downloaded=$ok failed=$fail\n";
if ($failed) {
    $log = __DIR__ . '/download-media-failures.log';
    file_put_contents($log, implode("\n", $failed));
    echo "Failures written to tools/download-media-failures.log (" . count($failed) . " lines)\n";
}
