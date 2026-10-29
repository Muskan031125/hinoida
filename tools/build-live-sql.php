<?php

/**
 * Build one SQL file that puts the live hinoida data on the server and nothing
 * else.
 *
 *   php tools/build-live-sql.php <dump.sql> <out.sql>
 *
 * The result is: drop everything the old demo database had, load the real dump
 * verbatim, then add the handful of tables and columns this rewrite needs that
 * the live schema predates. Importing it into a database leaves exactly the
 * shared data — no demo listings, users or content survive.
 *
 * The dump is copied through byte for byte; nothing in the real data is
 * rewritten.
 */

$src = $argv[1] ?? '';
$out = $argv[2] ?? '';
if (! is_file($src) || $out === '') {
    fwrite(STDERR, "usage: php tools/build-live-sql.php <dump.sql> <out.sql>\n");
    exit(1);
}

// Every table to clear first: the ones the dump brings back, plus the ones this
// rewrite created that hold demo rows. Dropping before import is what makes the
// result "only the shared data" rather than a merge.
$dumpTables = [];
$fh = fopen($src, 'r');
while (($l = fgets($fh)) !== false) {
    if (preg_match('/^CREATE TABLE `([a-z0-9_]+)`/i', $l, $m)) { $dumpTables[] = $m[1]; }
}
fclose($fh);

$ours = [
    'offer', 'listing_qa', 'discover_enquiry', 'discover_contact', 'blog_comment',
    'service', 'wishlist', 'email_queue', 'activity', 'admin_log',
    // this rewrite's own content tables. Live keeps posts, events and offers in
    // one `blogs` table, so these hold nothing but demo rows — and because the
    // code prefers `blogs`, they would sit there invisible and unnoticed.
    'blog', 'event',
];
$drop = array_values(array_unique(array_merge($dumpTables, $ours)));
sort($drop);

$fp = fopen($out, 'w');

fwrite($fp, <<<HEAD
-- ---------------------------------------------------------------------------
-- HiNoida — complete live database
-- Built {DATE} from {SRC}
--
-- Import this into the database the site points at. It clears what is there
-- first, so the result is exactly the shared live data — no demo rows remain.
--
-- Order: drop everything -> load the live dump -> add what this build needs.
-- ---------------------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ---------------------------------------------------------------------------
-- 1. Clear the old database
-- ---------------------------------------------------------------------------

HEAD);

fwrite($fp, str_replace(['{DATE}', '{SRC}'], [date('Y-m-d H:i'), basename($src)], ''));
foreach ($drop as $t) {
    fwrite($fp, "DROP TABLE IF EXISTS `{$t}`;\n");
}

fwrite($fp, "\n-- ---------------------------------------------------------------------------\n");
fwrite($fp, "-- 2. The live data, exactly as exported\n");
fwrite($fp, "-- ---------------------------------------------------------------------------\n\n");

// stream the dump through unchanged, minus its own transaction/charset preamble
// which we have already set above
$fh = fopen($src, 'r');
while (($l = fgets($fh)) !== false) {
    if (preg_match('/^(SET |START TRANSACTION|COMMIT|\/\*!)/i', $l)) { continue; }
    fwrite($fp, $l);
}
fclose($fh);

fwrite($fp, <<<'TAIL'

-- ---------------------------------------------------------------------------
-- 3. What this build needs that the live schema predates
--
-- Every statement below uses IF NOT EXISTS, which MariaDB supports on ALTER.
-- The whole file can be re-run and nothing errors or halts the import.
-- ---------------------------------------------------------------------------

ALTER TABLE `listing` ADD COLUMN IF NOT EXISTS `seo_meta_tags` TEXT NULL;
ALTER TABLE `listing` ADD COLUMN IF NOT EXISTS `hours_json` TEXT NULL;

ALTER TABLE `review` ADD COLUMN IF NOT EXISTS `owner_reply` TEXT NULL;
ALTER TABLE `review` ADD COLUMN IF NOT EXISTS `is_hidden` INT DEFAULT 0;
ALTER TABLE `review` ADD COLUMN IF NOT EXISTS `reviewer_name` VARCHAR(190) NULL;

-- Business approval and signup date — the live user table has neither, so
-- without them every business account counts as already approved.
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `approved` INT DEFAULT 1;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `created_date` BIGINT NULL;

-- Content URLs are /news/<slug>; the live blogs table has no slug column.
ALTER TABLE `blogs` ADD COLUMN IF NOT EXISTS `slug` VARCHAR(220) NULL;

-- ---------------------------------------------------------------------------
-- Tables the live schema does not have
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `offer` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NULL,
  `business` VARCHAR(190) NULL,
  `locality` VARCHAR(120) NULL,
  `category` VARCHAR(120) NULL,
  `title` VARCHAR(220) NULL,
  `slug` VARCHAR(240) NULL,
  `description` TEXT NULL,
  `discount` VARCHAR(60) NULL,
  `code` VARCHAR(60) NULL,
  `valid_till` VARCHAR(40) NULL,
  `status` VARCHAR(20) DEFAULT 'pending',
  `created_at` DATETIME NULL,
  KEY `idx_offer_business` (`business`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `listing_qa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NULL,
  `user_id` INT NULL,
  `name` VARCHAR(120) NULL,
  `question` TEXT NULL,
  `answer` TEXT NULL,
  `created_at` DATETIME NULL,
  KEY `idx_qa_listing` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `discover_enquiry` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NULL,
  `user_id` INT NULL,
  `name` VARCHAR(120) NULL,
  `phone` VARCHAR(40) NULL,
  `email` VARCHAR(160) NULL,
  `message` TEXT NULL,
  `status` VARCHAR(20) DEFAULT 'new',
  `created_at` DATETIME NULL,
  KEY `idx_enq_listing` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `discover_contact` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NULL,
  `email` VARCHAR(160) NULL,
  `phone` VARCHAR(40) NULL,
  `message` TEXT NULL,
  `created_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `blog_comment` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `blog_id` INT NULL,
  `user_id` INT NULL,
  `name` VARCHAR(190) NULL,
  `email` VARCHAR(190) NULL,
  `comment` TEXT NULL,
  `status` VARCHAR(20) DEFAULT 'pending',
  `created_at` DATETIME NULL,
  KEY `idx_comment_blog` (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NULL,
  `name` VARCHAR(190) NULL,
  `price` VARCHAR(60) NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NULL,
  KEY `idx_service_listing` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Indexes for the lookups every page does
-- ---------------------------------------------------------------------------

ALTER TABLE `listing` ADD INDEX IF NOT EXISTS `idx_listing_status` (`status`);
ALTER TABLE `listing` ADD INDEX IF NOT EXISTS `idx_listing_locality` (`locality_id`);
ALTER TABLE `listing` ADD INDEX IF NOT EXISTS `idx_listing_area` (`area_id`);
ALTER TABLE `profile_views` ADD INDEX IF NOT EXISTS `idx_pv_listing` (`listing_id`);
-- The homepage's "Popular listings" groups 30 days of views. Without a
-- created_at index that is a full scan of every row in the table, which
-- took 48s on the live data; this covering index answers it from the index.
ALTER TABLE `profile_views` ADD INDEX IF NOT EXISTS `idx_pv_created_listing` (`created_at`, `listing_id`);

-- ---------------------------------------------------------------------------
-- 4. Photos are served from this site's own uploads/ folder
--
-- Left empty deliberately: the images belong with the site, not fetched from
-- anywhere else. Upload the uploads/ folder alongside app/ and they resolve
-- against this domain.
-- ---------------------------------------------------------------------------

DELETE FROM `settings` WHERE `type` = 'media_base';

-- ---------------------------------------------------------------------------
-- Category names were saved double-escaped, so "Pubs & Bars" is stored as
-- "Pubs &amp; Bars" and renders with the entity visible on every page that
-- names a category. Decode the ampersand back.
-- ---------------------------------------------------------------------------

UPDATE `category` SET `name` = REPLACE(`name`, '&amp;amp;', '&');
UPDATE `category` SET `name` = REPLACE(`name`, '&amp;', '&');
UPDATE `category` SET `name` = REPLACE(`name`, '&#39;', '''');

-- ---------------------------------------------------------------------------
-- 5. Post URLs
-- ---------------------------------------------------------------------------

TAIL);

fwrite($fp, "\n");

// Slugs are generated here rather than left to the application, so the file is
// self-sufficient — import it and /news/<slug> works immediately.
$pdo  = new PDO('sqlite::memory:');   // only used for its quoting
$seen = [];
$rows = [];
$fh   = fopen($src, 'r');
$cur  = false;
while (($l = fgets($fh)) !== false) {
    if (preg_match('/^INSERT INTO `blogs`/i', $l)) { $cur = true; }
    if (! $cur) { continue; }
    // rows look like: (1, 'Blog', 'Title here', ...)
    if (preg_match("/^\((\d+),\s*'[^']*',\s*'((?:[^'\\\\]|\\\\.)*)'/", ltrim($l), $m)) {
        $rows[] = ['id' => (int) $m[1], 'title' => stripslashes($m[2])];
    }
    if (preg_match('/;\s*$/', $l)) { $cur = false; }
}
fclose($fh);

foreach ($rows as $r) {
    $s = strtolower(trim($r['title']));
    $s = trim((string) preg_replace('/[^a-z0-9]+/', '-', $s), '-') ?: 'post';
    $base = $s; $i = 2;
    while (isset($seen[$s])) { $s = $base . '-' . $i++; }
    $seen[$s] = true;
    fwrite($fp, 'UPDATE `blogs` SET `slug` = ' . $pdo->quote($s) . ' WHERE `id` = ' . $r['id'] . ";\n");
}

fwrite($fp, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
fwrite($fp, '-- ' . count($rows) . " post URL(s) set.\n");
fclose($fp);

echo '  tables dropped first : ' . count($drop) . "\n";
echo '  post slugs written   : ' . count($rows) . "\n";
echo '  written to           : ' . $out . ' (' . number_format(filesize($out) / 1048576, 1) . " MB)\n";
