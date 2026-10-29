<?php
namespace App\Controllers;

class Events extends BaseController
{
    /** @param string|null $catSlug from /event/category/{slug} */
    public function index(?string $catSlug = null): string
    {
        helper('seo');
        $db = \Config\Database::connect();
        $q    = hi_content_q('event', $db);
        $ord  = ($q && $db->fieldExists('event_date', hi_content_src('event', $db)['table'])) ? 'event_date' : 'added_date';
        $rows = hi_content_rows($q ? $q->orderBy($ord, 'ASC')->get()->getResultArray() : []);
        $q    = trim((string) $this->request->getGet('q'));
        $total= count($rows);
        $cats = hi_content_cats($rows);            // before the category filter
        // what kind of event each one is, read from its title + description
        foreach ($rows as &$_r) { $_r['_tags'] = self::tags((string) ($_r['title'] ?? '') . ' ' . (string) ($_r['description'] ?? '')); }
        unset($_r);
        $types = [];
        foreach ($rows as $_r) { foreach ($_r['_tags'] as $_t) { $types[$_t] = ($types[$_t] ?? 0) + 1; } }
        arsort($types);
        $typeList = [];
        foreach ($types as $_t => $_n) { $typeList[] = ['slug' => self::slug($_t), 'name' => $_t, 'n' => $_n, 'icon' => self::TYPES[$_t][1]]; }
        $type = trim((string) $this->request->getGet('type'));
        $cat  = (int) $this->request->getGet('cat');
        if ($catSlug !== null && $catSlug !== '') {
            $cat = hi_cat_id_by_slug($catSlug, $cats);
            if ($cat === 0) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        }
        if ($cat > 0) {
            $rows = array_values(array_filter($rows, static fn ($r) => in_array($cat, array_map('intval', preg_split('/[,\s]+/', (string) ($r['category_id'] ?? '')) ?: []), true)));
        }
        if ($type !== '') {
            $rows = array_values(array_filter($rows, static fn ($r) => in_array($type, array_map([self::class, 'slug'], $r['_tags']), true)));
        }
        $rows = hi_content_filter($rows, $q);
        $shown = count($rows);
        $pg    = hi_paginate($rows, 12);
        $rows  = $pg['rows'];
        $seo  = hi_page_seo('event');
        return view('frontend/events_list', [
            'title' => $seo['title'] ?? 'Events in Noida — What\'s On | HiNoida',
            'metaDescription' => $seo['metaDescription'] ?? 'Upcoming events in Noida — festivals, expos, concerts, marathons and more. Dates, venues, timings and tickets on HiNoida.',
            'canonical' => hi_post_category_url('event', $catSlug),
            'events' => $rows, 'pg' => $pg, 'shown' => $shown, 'q' => $q, 'cats' => $cats, 'cat' => $cat, 'types' => $typeList, 'type' => $type, 'total' => $total, 'catSlug' => $catSlug,
            'jsonld' => [hi_breadcrumb(['Home' => base_url('/'), 'Events' => hi_page('events')])],
        ]);
    }

    /** event types: label => [pattern, icon] */
    private const TYPES = [
        'New Year'      => ['/new\s*year|\bnye\b|countdown|31st\s*dec/i', 'champagne-glasses'],
        'Bollywood'     => ['/bollywood|filmy/i', 'film'],
        'DJ Night'      => ['/\bdj\b|\bedm\b|techno|house music/i', 'headphones'],
        'Live Music'    => ['/live (?:music|band|performance|act)|concert|singer|\bgig\b|unplugged|qawwali|sufi/i', 'guitar'],
        'Food & Drinks' => ['/food|buffet|drinks|dinner|brunch|cocktail|wine|beer|tequila|bar\b/i', 'utensils'],
        'Family & Kids' => ['/\bkids?\b|family|children/i', 'children'],
        'Comedy'        => ['/comedy|stand-?up/i', 'face-laugh-squint'],
        'Festival'      => ['/festival|\bmela\b|diwali|holi|navratri|carnival|\bfair\b/i', 'star'],
        'Workshop'      => ['/workshop|masterclass|\bclass\b/i', 'chalkboard-user'],
        'Sports'        => ['/marathon|\brun\b|cricket|sports|tournament|yoga/i', 'person-running'],
        'Expo'          => ['/\bexpo\b|exhibition/i', 'building-columns'],
    ];

    /** @return list<string> */
    public static function tags(string $text): array
    {
        $t = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $out = [];
        foreach (self::TYPES as $label => $def) { if (preg_match($def[0], $t)) { $out[] = $label; } }
        return $out;
    }

    public static function slug(string $t): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(str_replace('&', 'and', $t))), '-');
    }

    /** @param string $key the id from /event/post/{id}/{slug}, or a bare slug */
    public function show(string $key): string
    {
        helper('seo');
        $db  = \Config\Database::connect();
        $src = hi_content_src('event', $db);
        $e   = hi_content_find('event', $key, $db);
        if (! $e) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $mq   = hi_content_q('event', $db);
        $mo   = $db->fieldExists('event_date', $src['table']) ? 'event_date' : 'added_date';
        $more = hi_content_rows($mq ? $mq->where('id !=', (int) $e['id'])->orderBy($mo, 'ASC')->limit(3)->get()->getResultArray() : []);
        $url = hi_event_url($e);
        return view('frontend/event_detail', [
            'title' => $e['title'] . (trim((string) ($e['locality'] ?? '')) !== '' ? ' — ' . $e['locality'] . ', Noida' : '') . ' | HiNoida',
            'metaDescription' => $e['title'] . ' on ' . $e['event_date'] . ' at ' . $e['venue'] . ', ' . $e['locality'] . ', Noida. ' . mb_substr(hi_rich_text($e['description']), 0, 100),
            'canonical' => $url, 'ogType' => 'article',
            'e' => $e, 'more' => $more,
            'jsonld' => [
                hi_breadcrumb(['Home' => base_url('/'), 'Events' => hi_page('events'), $e['title'] => $url]),
                [
                    '@context' => 'https://schema.org', '@type' => 'Event', 'name' => $e['title'],
                    'startDate' => $e['event_date'], 'description' => hi_rich_text($e['description']),
                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                    'location' => ['@type' => 'Place', 'name' => $e['venue'], 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $e['locality'], 'addressRegion' => 'Uttar Pradesh', 'addressCountry' => 'IN']],
                    'image' => hi_topic_img('event', (int) $e['id'], 1000, 500),
                ],
            ],
        ]);
    }
}
