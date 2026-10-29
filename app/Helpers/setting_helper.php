<?php
/**
 * Site settings stored in the `settings` table (type => description),
 * read once per request.
 */
function hi_settings(bool $fresh = false): array
{
    static $cache = null;
    if ($fresh) { $cache = null; }
    if ($cache !== null) { return $cache; }

    $cache = [];
    try {
        $db = \Config\Database::connect();
        if ($db->tableExists('settings')) {
            foreach ($db->table('settings')->get()->getResultArray() as $r) {
                $cache[$r['type']] = (string) ($r['description'] ?? '');
            }
        }
    } catch (\Throwable $e) {
        $cache = [];
    }
    return $cache;
}

function hi_setting(string $key, string $default = ''): string
{
    $v = hi_settings()[$key] ?? '';
    return trim($v) !== '' ? $v : $default;
}

/** Drop the cache after a write, so the next read sees the new value. */
function hi_settings_flush(): void
{
    hi_settings(true);
}

/**
 * Is an optional feature switched on?
 *
 * Unset means on — a fresh install has every add-on available, and an admin
 * has to deliberately switch one off.
 */
function hi_addon(string $key): bool
{
    $v = hi_settings()['addon_' . $key] ?? '';

    return trim((string) $v) === '' ? true : (string) $v === '1';
}

/**
 * The current request path, normalized: no leading/trailing slash, and no
 * leading "index.php" segment.
 *
 * CodeIgniter's dev server (`php spark serve`) routes every request through
 * index.php, and current_url() reflects that literally — "/offers" reports
 * as "index.php/offers". Every place in the layout that compared the first
 * path segment against a route name ("offers", "categories", …) was matching
 * against "index.php" instead and silently highlighting the wrong nav item.
 * A production server with a rewrite rule never carries this prefix, which
 * is exactly why it looked fine until it was actually clicked around on.
 */
function hi_current_path(): string
{
    $path = trim((string) parse_url(current_url(), PHP_URL_PATH), '/');
    if ($path === 'index.php') {
        return '';
    }
    if (str_starts_with($path, 'index.php/')) {
        $path = substr($path, strlen('index.php/'));
    }

    return $path;
}
