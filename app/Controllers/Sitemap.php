<?php
namespace App\Controllers;

class Sitemap extends BaseController
{
    public function index()
    {
        $db  = \Config\Database::connect();
        helper('taxonomy');
        $urls = [];

        $urls[] = [base_url('/'), '1.0', 'daily'];
        // named pages, each at the URL the live site uses
        $urls[] = [base_url('property'), '0.9', 'daily'];
        foreach (['listings','areas','about','contact','news','pricing','events','offers','faq','privacy','terms'] as $p) $urls[] = [hi_page($p), '0.6', 'weekly'];
        foreach (['advertise','cookie'] as $p) $urls[] = [base_url($p), '0.5', 'weekly'];

        // Blog posts, events and offers. Live keeps all three in one
        // `blogs` table, so asking for blog/event/offer by name found
        // nothing and every post was missing from the sitemap.
        foreach (['blog', 'event', 'offer'] as $kind) {
            $q = hi_content_q($kind, $db);
            if (! $q) { continue; }
            foreach ($q->select('id, title')->get()->getResultArray() as $r) {
                $urls[] = [hi_post_url($kind, $r), '0.7', 'weekly'];
            }
        }

        // the category index, and named places inside a locality
        $urls[] = [hi_page('categories'), '0.9', 'weekly'];
        if ($db->tableExists('area')) {
            foreach ($db->table('area')->select('slug')->get()->getResultArray() as $a) {
                if (! empty($a['slug'])) { $urls[] = [base_url('a/' . $a['slug']), '0.7', 'weekly']; }
            }
        }

        // groups
        foreach (array_keys(hi_groups()) as $k) $urls[] = [base_url('g/' . $k), '0.8', 'weekly'];

        // categories
        foreach ($db->table('category')->select('slug')->get()->getResultArray() as $c) {
            if (! empty($c['slug'])) $urls[] = [hi_category_url($c['slug']), '0.7', 'weekly'];
        }
        // areas
        foreach ($db->table('locality')->select('slug')->get()->getResultArray() as $a) {
            if (! empty($a['slug'])) $urls[] = [hi_locality_url($a['slug']), '0.6', 'weekly'];
        }
        // listings
        foreach ($db->table('listing')->select('id, name, listing_type')->where('status', 'active')->get()->getResultArray() as $l) {
            $urls[] = [hi_listing_url($l), '0.7', 'monthly'];
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url><loc>" . htmlspecialchars($u[0]) . "</loc><changefreq>{$u[2]}</changefreq><priority>{$u[1]}</priority></url>\n";
        }
        $xml .= '</urlset>';

        return $this->response->setHeader('Content-Type', 'application/xml')->setBody($xml);
    }
}
