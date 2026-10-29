<?php

namespace App\Libraries;

/**
 * Reels pulled from the site's own Instagram account.
 *
 * Meta retired the old Basic Display API, so the only supported way to read an
 * account's own media is a long-lived token issued to an Instagram Business or
 * Creator account. The token is entered once in Admin → Frontend.
 *
 * Two things matter here and both are easy to get wrong:
 *
 *  1. The strip renders in the site layout, so it is on *every* page. Calling
 *     Instagram per request would put a network round trip in front of every
 *     page load and burn the rate limit within a day. Results are cached, and
 *     a failed call is cached too (briefly) so an outage cannot turn into a
 *     request storm.
 *
 *  2. A long-lived token dies after 60 days. Meta lets you exchange one for a
 *     fresh 60 days as long as it is at least 24 hours old, so the refresh is
 *     done here automatically once the token passes 30 days. Without that the
 *     section silently empties two months after setup and nobody knows why.
 *
 * Nothing here ever throws: if Instagram is unreachable, the token has expired
 * or the account has no video posts, this returns an empty array and the view
 * falls back to the pasted reel links, then to local clips, then to the plain
 * follow button.
 */
class Instagram
{
    /** How long a good result is reused. */
    private const TTL_OK = 3600;        // 1 hour

    /** How long a failure is remembered, so an outage is not hammered. */
    private const TTL_FAIL = 600;       // 10 minutes

    /** Refresh the token once it is older than this. Meta's ceiling is 60 days. */
    private const REFRESH_AFTER = 30 * 86400;

    private const CACHE_KEY = 'hi_ig_reels';

    /**
     * @return list<array{permalink:string,thumb:string,caption:string,video:string}>
     */
    public function reels(int $limit = 4): array
    {
        helper('setting');
        $token = trim((string) hi_setting('fe_ig_token'));
        if ($token === '') {
            return [];
        }

        $cache  = \Config\Services::cache();
        $cached = $cache->get(self::CACHE_KEY);
        if (is_array($cached)) {
            // a cached failure is stored as an empty list, and is honoured too
            return array_slice($cached, 0, $limit);
        }

        $this->refreshTokenIfStale($token);
        // re-read: refreshTokenIfStale may have replaced it
        $token = trim((string) hi_setting('fe_ig_token', $token));

        $items = $this->fetch($token);

        $cache->save(self::CACHE_KEY, $items, $items === [] ? self::TTL_FAIL : self::TTL_OK);

        return array_slice($items, 0, $limit);
    }

    /** Drop the cache — called after the token is changed in admin. */
    public function forget(): void
    {
        \Config\Services::cache()->delete(self::CACHE_KEY);
    }

    /**
     * @return list<array{permalink:string,thumb:string,caption:string,video:string}>
     */
    private function fetch(string $token): array
    {
        $url = 'https://graph.instagram.com/me/media?fields='
             . rawurlencode('id,caption,media_type,media_url,permalink,thumbnail_url,timestamp')
             . '&limit=25&access_token=' . rawurlencode($token);

        $body = $this->get($url);
        if ($body === null) {
            return [];
        }

        $json = json_decode($body, true);
        if (! is_array($json) || ! isset($json['data']) || ! is_array($json['data'])) {
            // Meta puts the reason in `error.message` — worth having in the log,
            // because "expired token" and "wrong account type" look identical
            // from the outside.
            $why = $json['error']['message'] ?? 'unexpected response';
            log_message('error', 'Instagram: ' . $why);

            return [];
        }

        $videos = [];
        $others = [];
        foreach ($json['data'] as $m) {
            $link = (string) ($m['permalink'] ?? '');
            if ($link === '') {
                continue;
            }
            $row = [
                'permalink' => $link,
                // videos carry a poster in thumbnail_url; images use media_url
                'thumb'     => (string) ($m['thumbnail_url'] ?? $m['media_url'] ?? ''),
                'caption'   => trim((string) ($m['caption'] ?? '')),
                'video'     => ((string) ($m['media_type'] ?? '')) === 'VIDEO'
                    ? (string) ($m['media_url'] ?? '') : '',
            ];
            if ($row['thumb'] === '') {
                continue;
            }
            if ($row['video'] !== '') {
                $videos[] = $row;
            } else {
                $others[] = $row;
            }
        }

        // reels first; fall back to stills so a photo-only account still shows
        return array_values(array_merge($videos, $others));
    }

    /**
     * Exchange the token for a fresh 60 days once it is past 30 days old.
     * Meta refuses tokens younger than 24 hours, which is why this is not run
     * on the very first call.
     */
    private function refreshTokenIfStale(string $token): void
    {
        helper('setting');
        $stamp = (int) hi_setting('fe_ig_token_at');

        if ($stamp === 0) {
            // first sight of this token — record when we started counting
            $this->putSetting('fe_ig_token_at', (string) time());

            return;
        }
        if (time() - $stamp < self::REFRESH_AFTER) {
            return;
        }

        $body = $this->get('https://graph.instagram.com/refresh_access_token'
            . '?grant_type=ig_refresh_token&access_token=' . rawurlencode($token));
        if ($body === null) {
            return;
        }

        $json = json_decode($body, true);
        $new  = is_array($json) ? trim((string) ($json['access_token'] ?? '')) : '';
        if ($new === '') {
            log_message('error', 'Instagram: token refresh failed — '
                . ($json['error']['message'] ?? 'no token returned')
                . '. It expires 60 days after it was issued; re-issue it in Admin → Frontend.');

            return;
        }

        $this->putSetting('fe_ig_token', $new);
        $this->putSetting('fe_ig_token_at', (string) time());
        log_message('info', 'Instagram: access token refreshed for another 60 days.');
    }

    /** GET with a short timeout — this sits in front of a page render. */
    private function get(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'HiNoida/1.0',
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $body === '') {
            log_message('error', 'Instagram: request failed — ' . ($err ?: 'empty response'));

            return null;
        }

        return (string) $body;
    }

    /** Same upsert the admin controller uses, without reaching into it. */
    private function putSetting(string $key, string $value): void
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('settings')) {
            return;
        }
        $exists = $db->table('settings')->where('type', $key)->countAllResults() > 0;
        if ($exists) {
            $db->table('settings')->where('type', $key)->update(['description' => $value]);
        } else {
            $db->table('settings')->insert(['type' => $key, 'description' => $value]);
        }
        if (function_exists('hi_settings_flush')) {
            hi_settings_flush();
        }
    }
}
