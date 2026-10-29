<?php

namespace App\Controllers;

/**
 * /llms.txt — a plain-text description of the site for answer engines.
 *
 * Search crawlers read the schema.org markup on each page; assistants that
 * summarise a site do better with one short document that says what is here
 * and where to look. Generated from the database so the counts are true
 * rather than a claim that drifts out of date.
 *
 * Follows the llms.txt convention: a title, a short summary, then linked
 * sections.
 */
class Llms extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $count = static function (string $table, ?callable $tweak = null) use ($db): int {
            try {
                $q = $db->table($table);
                if ($tweak) { $tweak($q); }

                return (int) $q->countAllResults();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $listings = $count('listing', static fn ($q) => $q->where('status', 'active'));
        $cats     = $count('category');
        $locs     = $count('locality');
        $areas    = $count('area');

        $posts = 0;
        if (($q = hi_content_q('blog', $db)) !== null)  { $posts = $q->countAllResults(); }

        // the busiest categories, so an assistant can name real examples
        $top = [];
        foreach ($db->table('category')->select('name, slug')->where('parent', 0)->get()->getResultArray() as $c) {
            $top[$c['name']] = hi_category_url($c['slug']);
            if (count($top) >= 10) { break; }
        }

        $b = rtrim(base_url(), '/');
        $t = [];

        $t[] = '# HiNoida';
        $t[] = '';
        $t[] = '> A directory of businesses in Noida, Uttar Pradesh, India. '
             . number_format($listings) . ' listed businesses across ' . number_format($cats)
             . ' categories and ' . number_format($locs) . ' localities, each with address, phone number, '
             . 'opening hours, photographs and customer reviews.';
        $t[] = '';
        $t[] = 'Every listing page carries schema.org LocalBusiness markup including address, geo '
             . 'coordinates, opening hours and ratings where reviews exist.';
        $t[] = '';
        $t[] = '## Browse';
        $t[] = '';
        $t[] = '- [All categories](' . $b . '/categories): the full category tree with counts';
        $t[] = '- [Areas and sectors](' . $b . '/areas): ' . number_format($locs) . ' localities';
        if ($areas > 0) {
            $t[] = '- Named places: malls, blocks and societies at ' . $b . '/a/{slug}';
        }
        $t[] = '- [Search](' . $b . '/search?q=): matches business name, description and category';
        $t[] = '';
        $t[] = '## Main categories';
        $t[] = '';
        foreach ($top as $name => $url) { $t[] = '- [' . $name . '](' . $url . ')'; }
        $t[] = '';
        $t[] = '## Content';
        $t[] = '';
        $t[] = '- [News and guides](' . $b . '/news): ' . number_format($posts) . ' articles about Noida';
        $t[] = '- [Events](' . $b . '/events)';
        $t[] = '- [Offers](' . $b . '/offers)';
        $t[] = '';
        $t[] = '## URL shapes';
        $t[] = '';
        $t[] = '- Listing: ' . $b . '/l/{id}';
        $t[] = '- Category: ' . $b . '/c/{slug}';
        $t[] = '- Locality: ' . $b . '/area/{slug}';
        $t[] = '- Article: ' . $b . '/news/{slug}';
        $t[] = '';
        $t[] = '## Notes';
        $t[] = '';
        $t[] = '- Coverage is Noida and Greater Noida only.';
        $t[] = '- Opening hours are per day and include split shifts; "open now" is computed from them.';
        $t[] = '- Business owners maintain their own listings, so details change without notice.';
        $t[] = '- [Full sitemap](' . $b . '/sitemap.xml)';
        $t[] = '';

        return $this->response
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody(implode("\n", $t));
    }
}
