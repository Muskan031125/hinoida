<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Bring a database up to what this application expects, then report on it.
 *
 *   php spark db:prepare
 *
 * Run once after pointing .env at a freshly imported hinoida database. The same
 * work happens lazily on the first page load, but doing it here means a failure
 * is visible immediately rather than showing up as a broken page later.
 *
 * Safe to run repeatedly: it only ever adds what is missing.
 */
class DbPrepare extends BaseCommand
{
    protected $group       = 'HiNoida';
    protected $name        = 'db:prepare';
    protected $description = 'Add any tables and columns this app needs, then report what the database holds.';

    public function run(array $params)
    {
        helper(['schema', 'facts', 'taxonomy']);
        $db = Database::connect();

        CLI::write('Database: ' . $db->getDatabase() . ' (' . $db->DBDriver . ')', 'yellow');
        CLI::newLine();

        // --- what is missing before we touch anything -----------------------
        $wanted = [
            'food_menu', 'hotel_room_specification', 'beauty_service', 'product_details',
            'offer', 'listing_changes', 'country', 'currency',
        ];
        $before = [];
        foreach ($wanted as $t) {
            if (! $db->tableExists($t)) { $before[] = $t; }
        }
        $needSlug = $db->tableExists('blogs') && ! $db->fieldExists('slug', 'blogs');

        // --- do the work ----------------------------------------------------
        try {
            $made = hi_ensure_tables($db);
        } catch (\Throwable $e) {
            CLI::error('Could not prepare the database: ' . $e->getMessage());
            CLI::write('Nothing was changed. Check the database user has CREATE and ALTER rights.', 'yellow');

            return EXIT_ERROR;
        }

        if ($made) {
            CLI::write('Created ' . count($made) . ' table(s):', 'green');
            foreach ($made as $t) { CLI::write('    ' . $t); }
        } else {
            CLI::write('No new tables needed.', 'green');
        }

        // hi_ensure_tables() adds these; say so plainly rather than leaving the
        // operator to guess whether they landed
        foreach ([
            'listing.seo_meta_tags', 'listing.hours_json',
            'review.owner_reply', 'review.is_hidden', 'review.reviewer_name',
        ] as $col) {
            [$t, $c] = explode('.', $col);
            if ($db->tableExists($t) && ! $db->fieldExists($c, $t)) {
                CLI::error('  MISSING still: ' . $col);
            }
        }

        if ($needSlug) {
            if ($db->fieldExists('slug', 'blogs')) {
                $n = $db->table('blogs')->where('slug IS NOT NULL', null, false)->where('slug !=', '')->countAllResults();
                CLI::write('Added blogs.slug and filled ' . $n . ' post URL(s).', 'green');
            } else {
                CLI::error('blogs.slug could not be added — /news/<slug> pages will 404.');
            }
        }

        // --- report ---------------------------------------------------------
        CLI::newLine();
        CLI::write('Contents:', 'yellow');

        $counts = [];
        foreach (['listing', 'user', 'category', 'locality', 'review', 'booking'] as $t) {
            if ($db->tableExists($t)) { $counts[$t] = $db->table($t)->countAllResults(); }
        }
        foreach ($counts as $t => $n) {
            CLI::write(sprintf('    %-12s %s', $t, number_format($n)));
        }

        if ($db->tableExists('listing')) {
            $live = $db->table('listing')->where('status', 'active')->countAllResults();
            CLI::write(sprintf('    %-12s %s live, %s not', 'of which', number_format($live), number_format(($counts['listing'] ?? 0) - $live)));
        }

        // Content is the one people are most likely to think is broken, because
        // unpublished rows are invisible with no explanation.
        $src = hi_content_src('blog', $db);
        if ($src) {
            CLI::newLine();
            CLI::write('Content in `' . $src['table'] . '`:', 'yellow');
            foreach (['blog' => 'news', 'event' => 'events', 'offer' => 'offers'] as $kind => $page) {
                $q = hi_content_q($kind, $db);
                $shown = $q ? $q->countAllResults() : 0;
                $all   = $src['type'] !== null
                    ? $db->table($src['table'])->where('type', ucfirst($kind))->countAllResults()
                    : $db->table($src['table'])->countAllResults();
                $note  = ($all > $shown) ? '   (' . number_format($all - $shown) . ' unpublished, so not shown)' : '';
                CLI::write(sprintf('    /%-8s %s of %s%s', $page, number_format($shown), number_format($all), $note));
            }
        }

        CLI::newLine();
        CLI::write('Done. The site is ready to serve this database.', 'green');

        return EXIT_SUCCESS;
    }
}
