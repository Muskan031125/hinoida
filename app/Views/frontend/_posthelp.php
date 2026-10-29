<?php
/**
 * Helpers for pages whose content is a free-text post (events, offers,
 * news). Live posts often leave the structured fields empty and type the
 * details into the text, and some emoji were saved with the wrong encoding
 * ("Ôæí", "ðŸŽ‰"). These read and clean that text.
 */

if (! function_exists('hi_valid_ts')) {
/** A real timestamp, or null for empty / "0000-00-00" / 1970 values. */
function hi_valid_ts($d): ?int
{
    $t = strtotime((string) $d);
    return ($t && $t > strtotime('2000-01-01')) ? $t : null;
}
}

if (! function_exists('hi_repair_text')) {
/**
 * Turn text that was saved with the wrong encoding back into the real
 * characters: "ÔÇô" -> "–", "ÔÇÖ" -> "’", "ðŸŽ‰" -> "🎉".
 * Each broken character is 2–4 odd letters in a row; we try to read them
 * back as the original bytes and keep the result only if it is valid.
 */
function hi_repair_text(string $s): string
{
    if ($s === '' || ! preg_match('/[\x{00A0}-\x{FFFF}]/u', $s)) return $s;
    return preg_replace_callback('/[^\x00-\x7F]{2,}/u', static function ($m) {
        $ch = preg_split('//u', $m[0], -1, PREG_SPLIT_NO_EMPTY);
        $out = ''; $n = count($ch);
        for ($i = 0; $i < $n;) {
            $done = false;
            for ($len = min(4, $n - $i); $len >= 2; $len--) {
                $piece = implode('', array_slice($ch, $i, $len));
                foreach (['CP850', 'Windows-1252'] as $cs) {
                    $b = @iconv('UTF-8', $cs, $piece);
                    if ($b !== false && strlen($b) === $len && mb_check_encoding($b, 'UTF-8') && mb_strlen($b, 'UTF-8') === 1) {
                        $out .= $b; $i += $len; $done = true; break 2;
                    }
                }
            }
            if (! $done) { $out .= $ch[$i]; $i++; }
        }
        return $out;
    }, $s) ?? $s;
}
}

if (! function_exists('hi_fix_mojibake')) {
/** Repair broken characters, then drop any that cannot be repaired. */
function hi_fix_mojibake(string $s): string
{
    $s = hi_repair_text($s);
    $s = preg_replace('/[\x{00C0}-\x{00FF}][\x{0080}-\x{00BF}\x{0152}-\x{2122}]+/u', '', $s) ?? $s;
    $s = preg_replace('/(^|[\s>])[\x{00C0}-\x{00FF}]{2,4}(?=\s)/mu', '$1', $s) ?? $s;
    return $s;
}
}

if (! function_exists('hi_post_lines')) {
/** The post as clean plain-text lines. */
function hi_post_lines(string $html): array
{
    $flat = preg_replace('~<\s*(br|/p|/div|/li|/h[1-6]|/tr)\s*/?>~i', "\n", $html);
    $txt  = html_entity_decode(strip_tags((string) $flat), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $out  = [];
    foreach (preg_split('/\R/u', $txt) as $ln) {
        $ln = trim(preg_replace('/\s+/u', ' ', hi_fix_mojibake($ln)));
        $ln = trim(preg_replace('/^[•·▪►✓✔\-–—*]+\s*/u', '', $ln));   // leading bullets
        if ($ln !== '') { $out[] = $ln; }
    }
    return $out;
}
}

if (! function_exists('hi_text_date')) {
/** First date written in the text ("31st December 2025", "Dec 31, 2025"). */
function hi_text_date(string $txt): ?int
{
    if (preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?,?\s+(\d{4})\b/i', $txt, $m)) { return hi_valid_ts("$m[1] $m[2] $m[3]"); }
    if (preg_match('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+(\d{4})\b/i', $txt, $m)) { return hi_valid_ts("$m[2] $m[1] $m[3]"); }
    return null;
}
}

if (! function_exists('hi_post_link')) {
/** First outside link in the HTML (a "Book now" / "Grab deal" target). */
function hi_post_link(string $html): string
{
    return preg_match('~href=["\'](https?://[^"\']+)["\']~i', $html, $m) ? $m[1] : '';
}
}

if (! function_exists('hi_split_points')) {
/** Short lines become chips, long lines become paragraphs. */
function hi_split_points(array $lines, array $skip = [], int $short = 45): array
{
    $intro = []; $points = [];
    foreach ($lines as $ln) {
        if (in_array($ln, $skip, true)) continue;
        if (preg_match('/^(about (the|this) (event|offer|deal)|offer details?|event details?|terms( & conditions)?|t&c)$/i', $ln)) continue;
        if (preg_match('/^(book|grab|claim|buy) (now|your|the)/i', $ln)) continue;
        mb_strlen($ln) <= $short ? $points[] = $ln : $intro[] = $ln;
    }
    return [$intro, $points];
}
}

if (! function_exists('hi_read_mins')) {
/** Rough reading time in minutes. */
function hi_read_mins(string $html): int
{
    $w = str_word_count(strip_tags($html));
    return max(1, (int) round($w / 200));
}
}

/** Decode names saved as "&amp;" before they are escaped again. */
if (! function_exists('hi_dec')) {
function hi_dec($s): string
{
    return hi_repair_text(html_entity_decode((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}
}

if (! function_exists('hi_fix_entities')) {
/**
 * Names saved as "Domino&#039;s" get escaped again on the way out and show
 * the code on screen. Undo exactly one extra layer of escaping so the page
 * shows "Domino's". Text stays escaped once, so this is still safe HTML.
 */
function hi_fix_entities(string $html): string
{
    return preg_replace('/&amp;(#\d{1,6}|#x[0-9a-fA-F]{1,6}|[a-zA-Z]{2,8});/', '&$1;', $html) ?? $html;
}
}
