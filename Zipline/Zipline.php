<?php

namespace App\SupportedApps\Zipline;

use Illuminate\Support\Facades\Cache;

class Zipline extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // Per-user totals (files uploaded, storage used) change slowly, so a short
    // cache serves the frequent tile refresh loop without re-querying Zipline
    // every time.
    private const CACHE_TTL = 300;

    public function __construct()
    {
    }

    public function test()
    {
        // /api/user/stats is exactly what the tile reads, so testing it validates
        // the token in one call (Zipline v4 returns 200 on success, 401 when the
        // Authorization token is missing/invalid).
        $test = parent::appTest($this->url('api/user/stats'), $this->getAttrs());
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
            'files' => 0,
            'storage' => $this->formatBytes(0),
        ];

        // Zipline v4 uses a raw user token in the Authorization header (no Bearer
        // scheme). execute() returns null on a failed connection (never throws),
        // so guard before reading the body.
        $res = parent::execute($this->url('api/user/stats'), $this->getAttrs());
        if ($res !== null) {
            $stats = json_decode($res->getBody());
            if ($stats !== null && isset($stats->filesUploaded)) {
                $status = 'active';
                $data['files'] = (int) $stats->filesUploaded;
                $data['storage'] = $this->formatBytes((int) ($stats->storageUsed ?? 0));
                // Cache only successes, so a transient outage retries on the next
                // refresh instead of pinning an empty tile for the full TTL.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes the cached stats to this tile's server + token so several
    // Zipline tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $key = $this->config->apikey ?? '';
        return 'zipline_livestats_' . md5($url . '|' . $key);
    }

    // storageUsed is returned in bytes; render it human-readable for the tile.
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

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => $this->config->apikey ?? '',
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
