<?php

namespace App\SupportedApps\SnipeIT;

use Illuminate\Support\Facades\Cache;

class SnipeIT extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // How long a successful stats fetch is reused before Snipe-IT is queried
    // again. Asset and user totals change slowly, so a 5-minute cache keeps the
    // tile fresh while sparing the server the tile's frequent refresh loop.
    private const CACHE_TTL = 300;

    public function __construct()
    {
    }

    public function test()
    {
        // /api/v1/hardware is exactly what the tile reads, so testing it
        // validates both the API token and that it can list assets in one call
        // (401 = bad/missing token).
        $test = parent::appTest($this->url('api/v1/hardware?limit=1'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $cacheKey = $this->cacheKey();

        // Serve a recent successful result without touching the server - the tile
        // refresh loop runs far more often than these totals change (CACHE_TTL).
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return parent::getLiveStats('active', $cached);
        }

        $status = 'inactive';
        $data = [
            'assets' => 0,
            'users' => 0,
        ];

        // Both list endpoints return a "total" count in a Laravel datatables
        // shape ({"total": N, "rows": [...]}), so limit=1 keeps the payload tiny.
        // execute() returns null on a failed connection (it never throws), so
        // guard the response and the decoded body before reading fields.
        $assets = $this->fetchTotal('api/v1/hardware?limit=1');
        if ($assets !== null) {
            $status = 'active';
            $data['assets'] = $this->humanNumber($assets);

            $users = $this->fetchTotal('api/v1/users?limit=1');
            if ($users !== null) {
                $data['users'] = $this->humanNumber($users);
            }

            // Cache only successes, so a transient outage retries on the next
            // refresh instead of pinning an empty tile for 5 minutes.
            Cache::put($cacheKey, $data, self::CACHE_TTL);
        }

        return parent::getLiveStats($status, $data);
    }

    // fetchTotal returns the integer "total" from a Snipe-IT list endpoint, or
    // null if the request failed or the body was not the expected shape.
    private function fetchTotal($endpoint)
    {
        $res = parent::execute($this->url($endpoint), $this->getAttrs());
        if ($res === null) {
            return null;
        }

        $json = json_decode($res->getBody());
        if ($json === null || !isset($json->total)) {
            return null;
        }

        return (int) $json->total;
    }

    // humanNumber keeps small counts exact with thousands separators and
    // abbreviates very large totals so two stats stay readable on the tile.
    private function humanNumber($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }
        if ($number >= 100000) {
            return round($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    // cacheKey scopes the cached stats to this tile's server + token so several
    // Snipe-IT tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $key = $this->config->apikey ?? '';
        return 'snipeit_livestats_' . md5($url . '|' . $key);
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "Content-Type" => "application/json",
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
