<?php

/**
 * Interface phrases, ported from the original multi_language_helper.php.
 *
 * Same contract as the old get_phrase(): a phrase is looked up by its
 * slugified key in a per-language JSON file, and a key that does not exist
 * yet is added on the fly with a readable default. That is what let the
 * original grow its phrase list simply by using it in a view.
 *
 * Files live in writable/language/<code>.json — in CI4 that is the only
 * directory the app is allowed to write to.
 */

if (! function_exists('hi_lang_dir')) {
    function hi_lang_dir(): string
    {
        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
    }
}

if (! function_exists('hi_lang_code')) {
    /** The language currently in force. Matches settings.type = 'language'. */
    function hi_lang_code(): string
    {
        $c = strtolower(trim(hi_setting('language', 'english')));

        // never let a stored value escape the language directory
        return preg_replace('/[^a-z0-9_-]/', '', $c) ?: 'english';
    }
}

if (! function_exists('hi_languages')) {
    /** @return string[] every language file present */
    function hi_languages(): array
    {
        $out = [];
        foreach (glob(hi_lang_dir() . '*.json') ?: [] as $f) {
            $out[] = basename($f, '.json');
        }
        sort($out);

        return $out ?: ['english'];
    }
}

if (! function_exists('hi_phrases')) {
    /** @return array<string,string> */
    function hi_phrases(?string $code = null, bool $fresh = false): array
    {
        static $cache = [];
        $code = $code ?: hi_lang_code();
        if ($fresh) { unset($cache[$code]); }
        if (isset($cache[$code])) { return $cache[$code]; }

        $file = hi_lang_dir() . $code . '.json';
        $arr  = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];

        return $cache[$code] = is_array($arr) ? $arr : [];
    }
}

if (! function_exists('hi_phrase_save')) {
    /** Write one phrase back. Returns false if the file could not be written. */
    function hi_phrase_save(string $code, string $key, string $value): bool
    {
        $code = preg_replace('/[^a-z0-9_-]/', '', strtolower($code));
        if ($code === '') { return false; }

        $dir = hi_lang_dir();
        if (! is_dir($dir)) { @mkdir($dir, 0775, true); }

        $arr       = hi_phrases($code, true);
        $arr[$key] = $value;
        ksort($arr);

        $ok = @file_put_contents(
            $dir . $code . '.json',
            json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        hi_phrases($code, true);

        return $ok !== false;
    }
}

if (! function_exists('hi_phrase_key')) {
    function hi_phrase_key(string $phrase): string
    {
        return strtolower(preg_replace('/\s+/', '_', trim($phrase)));
    }
}

if (! function_exists('hi_t')) {
    /**
     * Translate. Falls back to a readable version of the key, and records the
     * key so it shows up in the editor — the original did the same.
     */
    function hi_t(string $phrase): string
    {
        $key  = hi_phrase_key($phrase);
        $code = hi_lang_code();
        $all  = hi_phrases($code);

        if (array_key_exists($key, $all) && trim((string) $all[$key]) !== '') {
            return (string) $all[$key];
        }

        // unknown key: use the phrase as given, and remember it for the editor
        $fallback = ucfirst(str_replace('_', ' ', $key));
        if (ENVIRONMENT !== 'production') { hi_phrase_save($code, $key, $fallback); }

        return $fallback;
    }
}
