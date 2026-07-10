<?php

namespace App\SupportedApps\tubearchivist;

use Illuminate\Support\Facades\Cache;

class tubearchivist extends \App\SupportedApps implements \App\EnhancedApps // phpcs:ignore
{
    public $config;

    // The library totals (video count, media size) change only when downloads
    // finish, so a 5-minute cache serves the tile's frequent refresh loop
    // without re-querying Tube Archivist every time.
    private const CACHE_TTL = 300;

    public function __construct()
    {
    }

    public function test()
    {
        // api/stats/video/ is exactly what the tile reads, so testing it
        // validates the API token in one call (200 = success, 401 = bad or
        // missing token).
        $test = parent::appTest($this->url('api/stats/video/'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $cacheKey = $this->cacheKey();

        // Serve a recent successful result without touching the server - the
        // tile refresh loop runs far more often than these totals change.
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return parent::getLiveStats('active', $cached);
        }

        $status = 'inactive';
        $data = [
            'videos' => 0,
            'size' => $this->formatBytes(0),
        ];

        // Single authenticated GET - the token is a header credential, so there
        // is no per-poll login. execute() returns null on a failed connection
        // (it never throws), so guard before reading the body.
        $res = parent::execute($this->url('api/stats/video/'), $this->getAttrs());
        if ($res !== null) {
            $stats = json_decode($res->getBody());
            if ($stats !== null && isset($stats->doc_count)) {
                $status = 'active';
                $data['videos'] = $this->formatNumber((int) $stats->doc_count);
                $data['size'] = $this->formatBytes((int) ($stats->media_size ?? 0));
                // Cache only successes, so a transient outage retries on the
                // next refresh instead of pinning an empty tile for the TTL.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes the cached stats to this tile's server + token so several
    // Tube Archivist tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $key = $this->config->apikey ?? '';
        return 'tubearchivist_livestats_' . md5($url . '|' . $key);
    }

    // media_size is returned in bytes; render it human-readable for the tile.
    private function formatBytes($bytes, $precision = 1)
    {
        $bytes = (float) $bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $pow = (int) floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    // Large video counts read better abbreviated (e.g. 12.3K) on the small tile.
    private function formatNumber($number)
    {
        if ($number < 1000) {
            return (string) $number;
        }

        $units = ['', 'K', 'M', 'B'];
        $pow = (int) floor(log($number, 1000));
        $pow = min($pow, count($units) - 1);
        $value = $number / pow(1000, $pow);

        return round($value, 1) . $units[$pow];
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Token " . ($this->config->apikey ?? ''),
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
