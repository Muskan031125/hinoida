<?php

/**
 * Load a phpMyAdmin MySQL dump of the live hinoida database into the local
 * SQLite file the app develops against.
 *
 *   php tools/import-live-sql.php <dump.sql> [target.db]
 *
 * MySQL and SQLite differ in ways that matter here:
 *   - inline KEY / UNIQUE KEY / CONSTRAINT lines are not valid in SQLite
 *   - int(11) / AUTO_INCREMENT spelling differs
 *   - MySQL escapes quotes with a backslash; SQLite doubles them
 *   - /*! ... *​/ conditional comments and LOCK TABLES have no meaning
 *
 * Nothing is written until every statement has been parsed, and the target is
 * built as a fresh file — the previous database is left on disk untouched.
 */

$src = $argv[1] ?? '';
$dst = $argv[2] ?? __DIR__ . '/../writable/hinoida.db';

if (! is_file($src)) {
    fwrite(STDERR, "usage: php tools/import-live-sql.php <dump.sql> [target.db]\n");
    exit(1);
}

$tmp = $dst . '.building';
@unlink($tmp);

$pdo = new PDO('sqlite:' . $tmp);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA journal_mode = OFF');
$pdo->exec('PRAGMA synchronous = OFF');

/** MySQL column/table DDL -> something SQLite accepts. */
function convert_create(string $sql): string
{
    // trailing table options
    $sql = preg_replace('/\)\s*ENGINE=.*?;/is', ');', $sql);

    $lines = explode("\n", $sql);
    $keep  = [];
    foreach ($lines as $ln) {
        $t = trim($ln);
        // index and constraint definitions live outside the table in SQLite
        if (preg_match('/^(UNIQUE\s+KEY|KEY|PRIMARY\s+KEY|CONSTRAINT|FULLTEXT|INDEX)\b/i', $t)) {
            // keep a single-column PRIMARY KEY by folding it into the column
            continue;
        }
        $keep[] = $ln;
    }
    $sql = implode("\n", $keep);

    // a trailing comma left by a removed KEY line
    $sql = preg_replace('/,(\s*\))/s', '$1', $sql);

    // types
    $sql = preg_replace('/\bint\(\d+\)\s+NOT NULL\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);

    // phpMyAdmin declares the key separately:
    //     `id` int(11) NOT NULL,        <- here
    //     ALTER TABLE x ADD PRIMARY KEY (`id`);
    //     ALTER TABLE x MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
    // Those ALTERs cannot be replayed on SQLite, so without this the id column
    // ends up NOT NULL with no autoincrement and every INSERT that omits an id
    // fails with "NOT NULL constraint failed".
    if (! preg_match('/PRIMARY KEY AUTOINCREMENT/i', $sql)) {
        $sql = preg_replace(
            '/^(\s*`id`\s+)int\(\d+\)\s+NOT NULL\s*,/mi',
            '$1INTEGER PRIMARY KEY AUTOINCREMENT,',
            $sql,
            1
        );
    }
    $sql = preg_replace('/\b(big|small|tiny|medium)?int\(\d+\)/i', 'INTEGER', $sql);
    $sql = preg_replace('/\b(big|small|tiny|medium)?int\b(?!\w)/i', 'INTEGER', $sql);
    $sql = preg_replace('/\bAUTO_INCREMENT\b/i', '', $sql);
    $sql = preg_replace('/\bCOLLATE\s+[a-z0-9_]+/i', '', $sql);
    $sql = preg_replace('/\bCHARACTER SET\s+[a-z0-9_]+/i', '', $sql);
    $sql = preg_replace('/\bunsigned\b/i', '', $sql);
    $sql = preg_replace('/\benum\([^)]*\)/i', 'TEXT', $sql);
    $sql = preg_replace('/\bset\([^)]*\)/i', 'TEXT', $sql);
    $sql = preg_replace("/DEFAULT '0000-00-00 00:00:00'/i", 'DEFAULT NULL', $sql);

    // MySQL-only column clauses
    $sql = preg_replace("/\s+COMMENT\s+'(?:[^'\\\\]|\\\\.)*'/i", '', $sql);
    $sql = preg_replace('/\s+ON UPDATE\s+current_timestamp\(\)/i', '', $sql);
    $sql = preg_replace('/\s+ON UPDATE\s+CURRENT_TIMESTAMP/i', '', $sql);
    $sql = preg_replace('/\bcurrent_timestamp\(\)/i', 'CURRENT_TIMESTAMP', $sql);
    $sql = preg_replace('/\bGENERATED ALWAYS AS[^,\n]*/i', '', $sql);

    return $sql;
}

/** MySQL string escaping -> SQLite. */
function convert_values(string $sql): string
{
    $out = '';
    $len = strlen($sql);
    $in  = false;
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if (! $in) {
            if ($c === "'") { $in = true; $out .= $c; continue; }
            $out .= $c;
            continue;
        }
        // inside a string literal
        if ($c === '\\') {
            $n = $sql[$i + 1] ?? '';
            $i++;
            switch ($n) {
                case "'":  $out .= "''";  break;   // escaped quote -> doubled
                case '"':  $out .= '"';   break;
                case '\\': $out .= '\\';  break;
                case 'n':  $out .= "\n";  break;
                case 'r':  $out .= "\r";  break;
                case 't':  $out .= "\t";  break;
                case '0':  $out .= '';    break;
                default:   $out .= $n;    break;
            }
            continue;
        }
        if ($c === "'") {
            // doubled quote inside MySQL output stays doubled
            if (($sql[$i + 1] ?? '') === "'") { $out .= "''"; $i++; continue; }
            $in = false;
            $out .= $c;
            continue;
        }
        $out .= $c;
    }

    return $out;
}

$fh = fopen($src, 'r');
if (! $fh) { fwrite(STDERR, "cannot read $src\n"); exit(1); }

$buf = '';
$stats = ['tables' => 0, 'rows' => 0, 'skipped' => 0];
$errors = [];

$pdo->beginTransaction();
while (($line = fgets($fh)) !== false) {
    $t = ltrim($line);
    if ($t === '' || str_starts_with($t, '--') || str_starts_with($t, '/*')) { continue; }
    if (preg_match('/^(LOCK TABLES|UNLOCK TABLES|SET |START TRANSACTION|COMMIT|DROP TABLE)/i', $t)) { continue; }

    $buf .= $line;
    if (! preg_match('/;\s*$/', rtrim($line))) { continue; }

    $stmt = trim($buf);
    $buf  = '';

    // live PHP session rows — throwaway, and stored as hex blobs SQLite rejects
    if (preg_match('/^INSERT INTO `ci_sessions`/i', $stmt)) { $stats['skipped']++; continue; }

    try {
        if (preg_match('/^CREATE TABLE/i', $stmt)) {
            $pdo->exec(convert_create($stmt));
            $stats['tables']++;
        } elseif (preg_match('/^INSERT INTO/i', $stmt)) {
            $conv = convert_values($stmt);
            $pdo->exec($conv);
            $stats['rows'] += substr_count($conv, "),\n(") + substr_count($conv, '),(') + 1;
        } elseif (preg_match('/^(ALTER TABLE|CREATE INDEX)/i', $stmt)) {
            $stats['skipped']++;   // indexes are rebuilt below, ALTERs are MySQL-only
        }
    } catch (Throwable $e) {
        $errors[] = substr(preg_replace('/\s+/', ' ', $stmt), 0, 90) . '  ==>  ' . $e->getMessage();
    }
}
$pdo->commit();
fclose($fh);

// indexes the app relies on for its common lookups
foreach ([
    'CREATE INDEX IF NOT EXISTS idx_listing_status   ON listing(status)',
    'CREATE INDEX IF NOT EXISTS idx_listing_locality ON listing(locality_id)',
    'CREATE INDEX IF NOT EXISTS idx_listing_user     ON listing(user_id)',
    'CREATE INDEX IF NOT EXISTS idx_review_listing   ON review(listing_id)',
    'CREATE INDEX IF NOT EXISTS idx_pv_listing       ON profile_views(listing_id)',
    'CREATE INDEX IF NOT EXISTS idx_pv_created_listing ON profile_views(created_at, listing_id)',
] as $ix) {
    try { $pdo->exec($ix); } catch (Throwable $e) { /* table may not exist */ }
}

// Live keeps blog posts, events and offers in one `blogs` table keyed by `type`,
// with no slug — the rewrite addresses content by slug. Add one and fill it from
// the title so the existing URL scheme keeps working.
try {
    $chk = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('blogs') WHERE name='slug'");
    $has = $chk->fetchColumn();
    $chk = null;
    if (! $has) {
        $pdo->exec('ALTER TABLE blogs ADD COLUMN slug TEXT');
        $q    = $pdo->query('SELECT id, title FROM blogs');
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        $q    = null;                       // release before the rename below
        $seen = [];
        $upd  = $pdo->prepare('UPDATE blogs SET slug = ? WHERE id = ?');
        foreach ($rows as $r) {
            $s = strtolower(trim((string) $r['title']));
            $s = preg_replace('/[^a-z0-9]+/', '-', $s);
            $s = trim((string) $s, '-') ?: 'post';
            $base = $s; $i = 2;
            while (isset($seen[$s])) { $s = $base . '-' . $i++; }   // titles repeat
            $seen[$s] = true;
            $upd->execute([$s, $r['id']]);
        }
        $upd = null;                        // as above — a live handle blocks rename()
        echo '  blogs.slug added and filled for ' . count($rows) . " posts\n";
    }
} catch (Throwable $e) {
    echo '  could not add blogs.slug: ' . $e->getMessage() . "\n";
}

// the one column this rewrite adds that the live schema has no equivalent for.
// hi_hours() falls back to opened_minutes/closed_minutes when it is null, so
// existing listings keep their single open/close pair until someone edits them.
try { $pdo->exec('ALTER TABLE listing ADD COLUMN hours_json TEXT'); } catch (Throwable $e) {}

echo "  tables created : {$stats['tables']}\n";
echo "  rows inserted  : " . number_format($stats['rows']) . "\n";
if ($errors) {
    echo "  FAILED statements: " . count($errors) . "\n";
    foreach (array_slice($errors, 0, 12) as $e) { echo "    - $e\n"; }
}

// only replace the live file once the build succeeded
unset($pdo);
if ($errors) {
    echo "  left as $tmp — target NOT replaced\n";
    exit(1);
}

// Windows keeps a lock if anything still has the file open, and rename() then
// fails silently unless checked — which once made this print "written" while
// the old database was still in place.
if (is_file($dst) && ! @unlink($dst)) {
    echo "  CANNOT REPLACE $dst — it is open in another process.\n";
    echo "  Stop any running `php -S` server, then re-run. Build kept at $tmp\n";
    exit(1);
}
if (! @rename($tmp, $dst)) {
    echo "  rename failed — build kept at $tmp\n";
    exit(1);
}
clearstatcache();
$check = new PDO('sqlite:' . $dst);
$n = (int) $check->query('SELECT COUNT(*) FROM listing')->fetchColumn();
echo '  written to ' . $dst . ' — ' . number_format($n) . " listings\n";
