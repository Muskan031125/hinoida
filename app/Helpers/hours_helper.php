<?php

/**
 * Opening hours.
 *
 * The original schema had a single opened_minutes/closed_minutes pair applied
 * to every day, which is wrong for anywhere that shuts on Sunday or opens late
 * at the weekend. Owners can now set each day in hours_json; listings that
 * have not been touched keep using the single pair, so nothing regresses.
 *
 * hours_json shape: {"mon":[[600,780],[900,1320]],"sun":null} — a list of
 * open ranges per day, null means closed all day. A single flat pair
 * [600,1320] is still read, so anything written before this stays valid.
 */

if (! function_exists('hi_days')) {
    /** @return array<string,string> key => label, Monday first */
    function hi_days(): array
    {
        return ['mon'=>'Monday','tue'=>'Tuesday','wed'=>'Wednesday','thu'=>'Thursday',
                'fri'=>'Friday','sat'=>'Saturday','sun'=>'Sunday'];
    }
}

if (! function_exists('hi_today_key')) {
    function hi_today_key(): string
    {
        return strtolower(date('D'));   // app timezone is Asia/Kolkata
    }
}

if (! function_exists('hi_hours')) {
    /**
     * Every day's hours for a listing.
     *
     * @return array<string,?array{0:int,1:int}> null = closed that day
     */
    function hi_hours(array $l): array
    {
        $out = [];
        $raw = json_decode((string) ($l['hours_json'] ?? ''), true);

        // one range: [open, close]. Two ranges (a lunch closing, as the
        // original's second_<day> columns allowed): [[o,c],[o,c]].
        $ranges = static function ($v): ?array {
            if (! is_array($v) || ! $v) { return null; }
            $pairs = is_array($v[0] ?? null) ? $v : [$v];
            $ok = [];
            foreach ($pairs as $p) {
                if (! is_array($p) || count($p) !== 2) { continue; }
                [$o, $c] = [(int) $p[0], (int) $p[1]];
                if ($o !== $c) { $ok[] = [$o, $c]; }
            }

            return $ok ?: null;
        };

        if (is_array($raw) && $raw) {
            foreach (array_keys(hi_days()) as $d) { $out[$d] = $ranges($raw[$d] ?? null); }

            return $out;
        }

        // Live hinoida keeps real per-day hours in time_configuration, one row
        // per listing, as "9-22:30" strings with second_<day> for a split shift.
        // Without this every listing fell back to a single pair applied to all
        // seven days, so closed days and lunch closings vanished and "Open now"
        // was wrong for anything that varies by day.
        if (($tc = hi_time_config((int) ($l['id'] ?? 0))) !== null) {
            foreach (array_keys(hi_days()) as $d) { $out[$d] = $tc[$d] ?? null; }

            return $out;
        }

        // fall back to the one pair that applies to every day
        $om = (int) ($l['opened_minutes'] ?? 0);
        $cm = (int) ($l['closed_minutes'] ?? 0);
        $pair = $om === $cm ? null : [[$om, $cm]];
        foreach (array_keys(hi_days()) as $d) { $out[$d] = $pair; }

        return $out;
    }
}

if (! function_exists('hi_tc_minutes')) {
    /**
     * One cell of time_configuration -> minutes past midnight.
     *
     * Values look like "9-22:30", "10-16", "closed-closed" or "-". The hour may
     * carry no minutes at all, so "9" means 09:00.
     *
     * @return array{0:int,1:int}|null null when that day is shut or blank
     */
    function hi_tc_minutes(?string $cell): ?array
    {
        $cell = strtolower(trim((string) $cell));
        if ($cell === '' || $cell === '-' || str_contains($cell, 'closed')) { return null; }

        $parts = explode('-', $cell);
        if (count($parts) !== 2) { return null; }

        $toMin = static function (string $t): ?int {
            $t = trim($t);
            if ($t === '') { return null; }
            $bits = explode(':', $t);
            $h    = (int) $bits[0];
            $m    = isset($bits[1]) ? (int) $bits[1] : 0;
            if ($h < 0 || $h > 24 || $m < 0 || $m > 59) { return null; }

            return $h * 60 + $m;
        };

        $o = $toMin($parts[0]);
        $c = $toMin($parts[1]);
        if ($o === null || $c === null || $o === $c) { return null; }

        return [$o, $c];
    }
}

if (! function_exists('hi_time_config')) {
    /**
     * Per-day hours for a listing from the live time_configuration table.
     *
     * Loaded once for the whole request rather than per listing — a category
     * page renders 24 cards and each asks about its own hours.
     *
     * @return array<string,?array<array{0:int,1:int}>>|null null when the table
     *         is absent or has no row for this listing
     */
    function hi_time_config(int $listingId): ?array
    {
        static $cache = null;

        if ($listingId <= 0) { return null; }

        if ($cache === null) {
            $cache = [];
            $db    = \Config\Database::connect();
            if (! $db->tableExists('time_configuration')) { return null; }
            foreach ($db->table('time_configuration')->get()->getResultArray() as $r) {
                $week = [];
                foreach (hi_days() as $key => $_label) {
                    $full = [
                        'mon' => 'monday', 'tue' => 'tuesday', 'wed' => 'wednesday', 'thu' => 'thursday',
                        'fri' => 'friday', 'sat' => 'saturday', 'sun' => 'sunday',
                    ][$key] ?? $key;

                    $ranges = [];
                    if (($a = hi_tc_minutes($r[$full] ?? null)) !== null)          { $ranges[] = $a; }
                    if (($b = hi_tc_minutes($r['second_' . $full] ?? null)) !== null) { $ranges[] = $b; }
                    $week[$key] = $ranges ?: null;
                }
                $cache[(int) $r['listing_id']] = $week;
            }
        }

        return $cache[$listingId] ?? null;
    }
}

if (! function_exists('hi_is_open')) {
    /** Open right now, judged against today's hours. */
    function hi_is_open(array $l, ?int $nowMinutes = null): bool
    {
        $today = hi_hours($l)[hi_today_key()] ?? null;
        if (! $today) { return false; }

        $now = $nowMinutes ?? ((int) date('G') * 60 + (int) date('i'));

        foreach ($today as [$om, $cm]) {
            // a closing time before the opening time means it runs past midnight
            $in = $cm > $om ? ($now >= $om && $now < $cm) : ($now >= $om || $now < $cm);
            if ($in) { return true; }
        }

        return false;
    }
}

if (! function_exists('hi_hhmm')) {
    function hi_hhmm(int $minutes): string
    {
        $minutes = (($minutes % 1440) + 1440) % 1440;

        return date('g:i A', mktime(0, $minutes, 0, 1, 1, 2000));
    }
}

if (! function_exists('hi_hours_label')) {
    /** "9:00 AM – 10:00 PM", or with a break "9 AM – 1 PM, 3 PM – 8 PM". */
    function hi_hours_label(?array $ranges): string
    {
        if (! $ranges) { return 'Closed'; }
        if (count($ranges) === 1 && (int) $ranges[0][0] === 0 && (int) $ranges[0][1] === 1440) { return 'Open 24 hours'; }

        $parts = [];
        foreach ($ranges as [$o, $c]) { $parts[] = hi_hhmm((int) $o) . ' – ' . hi_hhmm((int) $c); }

        return implode(', ', $parts);
    }
}

if (! function_exists('hi_hours_same_all_week')) {
    /** True when every open day shares one set of hours — worth collapsing. */
    function hi_hours_same_all_week(array $hours): bool
    {
        $seen = [];
        foreach ($hours as $p) { $seen[$p ? json_encode($p) : 'closed'] = true; }

        return count($seen) === 1;
    }
}

if (! function_exists('hi_hours_schema')) {
    /** openingHoursSpecification for schema.org LocalBusiness. */
    function hi_hours_schema(array $l): array
    {
        $names = ['mon'=>'Monday','tue'=>'Tuesday','wed'=>'Wednesday','thu'=>'Thursday',
                  'fri'=>'Friday','sat'=>'Saturday','sun'=>'Sunday'];
        $out = [];
        foreach (hi_hours($l) as $d => $ranges) {
            if (! $ranges) { continue; }
            foreach ($ranges as [$o, $c]) {
                $out[] = [
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => 'https://schema.org/' . $names[$d],
                    'opens'     => sprintf('%02d:%02d', intdiv($o, 60) % 24, $o % 60),
                    'closes'    => sprintf('%02d:%02d', intdiv($c, 60) % 24, $c % 60),
                ];
            }
        }

        return $out;
    }
}

if (! function_exists('hi_ensure_hours_column')) {
    /**
     * Make sure listing.hours_json exists, and say whether it does.
     *
     * The plain ALTER fails on this database: `listing.update_at` carries a
     * '0000-00-00 00:00:00' default, which strict mode rejects when the table
     * is rebuilt — so the column was never added, and every write that
     * included hours_json failed silently (DBDebug is off in production),
     * taking the whole listing update down with it. Relaxing sql_mode for the
     * one statement gets the column added without touching that legacy default.
     */
    function hi_ensure_hours_column($db = null): bool
    {
        static $known = null;
        if ($known !== null) { return $known; }
        $db = $db ?: \Config\Database::connect();

        if ($db->fieldExists('hours_json', 'listing')) { return $known = true; }

        foreach (['', "SET SESSION sql_mode=''"] as $prep) {
            try {
                if ($prep !== '') { $db->query($prep); }
                $db->query('ALTER TABLE listing ADD COLUMN hours_json TEXT');
                $db->resetDataCache();
                if ($db->fieldExists('hours_json', 'listing')) { return $known = true; }
            } catch (\Throwable $e) {
                // try the next approach, then give up quietly
            }
        }
        $db->resetDataCache();

        return $known = $db->fieldExists('hours_json', 'listing');
    }
}
