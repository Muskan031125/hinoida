<?php

/**
 * Result paging.
 *
 * Listings are filtered and sorted in PHP (ratings live in another table and
 * open-now depends on the request time), so paging happens on the final array
 * rather than in SQL. Sets are in the hundreds, not millions — slicing is fine.
 */

if (! function_exists('hi_paginate')) {
    /**
     * @return array{rows:array,page:int,pages:int,total:int,per:int,from:int,to:int}
     */
    function hi_paginate(array $rows, int $per = 24, ?int $page = null): array
    {
        $total = count($rows);
        $pages = max(1, (int) ceil($total / max(1, $per)));

        if ($page === null) {
            $page = (int) (service('request')->getGet('page') ?? 1);
        }
        $page = max(1, min($pages, $page));

        return [
            'rows'  => array_slice($rows, ($page - 1) * $per, $per),
            'page'  => $page,
            'pages' => $pages,
            'total' => $total,
            'per'   => $per,
            'from'  => $total ? ($page - 1) * $per + 1 : 0,
            'to'    => min($total, $page * $per),
        ];
    }
}

if (! function_exists('hi_page_url')) {
    /** Current URL with page= swapped, every other filter kept. */
    function hi_page_url(int $page): string
    {
        $get = service('request')->getGet() ?? [];
        unset($get['page']);
        if ($page > 1) { $get['page'] = $page; }

        $path = trim(service('request')->getUri()->getPath(), '/');

        return base_url($path) . ($get ? '?' . http_build_query($get) : '');
    }
}

if (! function_exists('hi_pager_links')) {
    /**
     * Window of page numbers to show: always first and last, plus two either
     * side of the current one, with 0 standing in for a gap.
     *
     * @return int[]
     */
    function hi_pager_links(int $page, int $pages): array
    {
        if ($pages <= 1) { return []; }

        $want = [1, $pages];
        for ($i = $page - 2; $i <= $page + 2; $i++) {
            if ($i >= 1 && $i <= $pages) { $want[] = $i; }
        }
        $want = array_values(array_unique($want));
        sort($want);

        $out  = [];
        $prev = 0;
        foreach ($want as $n) {
            if ($prev && $n > $prev + 1) { $out[] = 0; }
            $out[] = $n;
            $prev  = $n;
        }

        return $out;
    }
}
