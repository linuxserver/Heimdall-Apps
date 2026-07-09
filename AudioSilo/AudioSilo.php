<?php

namespace App\SupportedApps\AudioSilo;

use Illuminate\Support\Facades\Cache;

class AudioSilo extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // How long a successful stats fetch is reused before the AudioSilo server is
    // queried again. Catalog totals change rarely, so a 5-minute cache keeps the
    // tile fresh while sparing the server the tile's frequent refresh loop.
    private const CACHE_TTL = 300;

    public function __construct()
    {
    }

    public function test()
    {
        // /admin/stats is exactly what the tile reads, so testing it validates
        // both the API key and that it carries admin access in one call
        // (401 = bad/missing key, 403 = valid key but not an admin).
        $test = parent::appTest($this->url('api/v1/admin/stats'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $cacheKey = $this->cacheKey();

        // Serve a recent successful result without touching the server - the tile
        // refresh loop runs far more often than these totals change (see CACHE_TTL).
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return parent::getLiveStats('active', $cached);
        }

        $status = 'inactive';
        $data = [
            'books' => 0,
            'users' => 0,
        ];

        // Single authenticated GET - the API key is a bearer token, so there is
        // no per-poll login/logout to do. execute() returns null on a failed
        // connection (it never throws), so guard before reading the body.
        $res = parent::execute($this->url('api/v1/admin/stats'), $this->getAttrs());
        if ($res !== null) {
            $stats = json_decode($res->getBody());
            if ($stats !== null && isset($stats->total_books)) {
                $status = 'active';
                $data['books'] = (int) $stats->total_books;
                $data['users'] = (int) $stats->total_users;
                // Cache only successes, so a transient outage retries on the
                // next refresh instead of pinning an empty tile for 5 minutes.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes the cached stats to this tile's server + key so several
    // AudioSilo tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $key = $this->config->apikey ?? '';
        return 'audiosilo_livestats_' . md5($url . '|' . $key);
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . ($this->config->apikey ?? ''),
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
