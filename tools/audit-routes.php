<?php

/**
 * Which routes write to the database without checking who is asking.
 *
 *   php tools/audit-routes.php
 *
 * A route that inserts, updates or deletes should establish identity first —
 * an admin guard, a session user, an ownership check, or a shared secret for
 * the cron endpoints. This lists any that do not, so an open endpoint is
 * something you find deliberately rather than by accident.
 */

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/app/Config/Routes.php');

preg_match_all(
    '/\$routes->(get|post|match)\(\s*(?:\[[^\]]*\]\s*,\s*)?[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([A-Za-z]+)::([a-zA-Z0-9_]+)/',
    $routes,
    $m,
    PREG_SET_ORDER
);

$open = [];
$safe = 0;

foreach ($m as [$_, $verb, $path, $ctrl, $meth]) {
    $file = $root . '/app/Controllers/' . $ctrl . '.php';
    if (! is_file($file)) { continue; }
    $src = file_get_contents($file);

    // the method body, up to the next method at the same indent
    if (! preg_match('/(?:public|protected|private)?\s*function\s+' . preg_quote($meth, '/') . '\s*\(.*?(?=\n    (?:public|protected|private|\}))/s', $src, $mm)) {
        continue;
    }
    $body = $mm[0];

    $writes  = (bool) preg_match('/->(insert|update|delete)\s*\(/', $body);
    // identity can be established by an admin guard, a role check, a session
    // user, an ownership test, or a shared secret on the cron endpoints
    $guarded = (bool) preg_match(
        '/guard\(\)|\$this->uid\(\)|session\(\)->get\(\s*[\'"](uid|role)[\'"]|hash_equals|->owns\(|ownsListing/',
        $body
    );

    if (! $writes) { continue; }
    if ($guarded) { $safe++; continue; }
    $open[] = sprintf('%-5s %-38s %s::%s', strtoupper($verb), $path, $ctrl, $meth);
}

echo "  routes that write and DO check identity : " . $safe . "\n";
echo "  routes that write and do NOT            : " . count($open) . "\n";
foreach (array_unique($open) as $o) { echo '    ' . $o . "\n"; }
