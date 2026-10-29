<?php
/** SEO/AEO/GEO schema builders shared across the site. */

function hi_breadcrumb(array $crumbs): array
{
    $items = [];
    $i = 1;
    foreach ($crumbs as $name => $url) {
        $items[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $name, 'item' => $url];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

/** WebSite schema with sitelinks search box (SearchAction). */
function hi_website_schema(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => 'HiNoida',
        'url'      => base_url('/'),
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => hi_page('search') . '?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function hi_org_schema(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => 'HiNoida',
        'url'      => base_url('/'),
        'logo'     => base_url('assets/img/logo.png'),
        'description' => 'HiNoida is Noida\'s local business directory — find restaurants, hospitals, shopping, salons, real estate and more.',
        'areaServed' => ['@type' => 'City', 'name' => 'Noida'],
    ];
}

/** CollectionPage + embedded ItemList for category/area listing pages. */
function hi_collection_schema(string $name, string $desc, string $url, array $items): array
{
    $li = [];
    $i = 1;
    foreach ($items as $it) {
        $li[] = [
            '@type'    => 'ListItem',
            'position' => $i++,
            'name'     => $it['name'],
            'url'      => hi_listing_url($it),
        ];
    }
    return [
        '@context'    => 'https://schema.org',
        '@type'       => 'CollectionPage',
        'name'        => $name,
        'description' => $desc,
        'url'         => $url,
        'mainEntity'  => ['@type' => 'ItemList', 'numberOfItems' => count($li), 'itemListElement' => $li],
    ];
}
