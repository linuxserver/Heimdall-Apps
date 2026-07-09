<?php

namespace App\SupportedApps\WGDashboard;

use Illuminate\Support\Facades\Cache;

class WGDashboard extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // How long a successful stats fetch is reused before WGDashboard is queried
    // again. Configuration and peer counts change moderately, so a short cache
    // keeps the tile fresh while sparing the dashboard the frequent refresh loop.
    private const CACHE_TTL = 60;

    public function __construct()
    {
    }

    public function test()
    {
        // getWireguardConfigurations is exactly what the tile reads, so testing
        // it validates both the API key and that data is reachable in one call.
        // WGDashboard's before_request hook returns 401 for a missing/invalid key.
        $test = parent::appTest($this->url('api/getWireguardConfigurations'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $cacheKey = $this->cacheKey();

        // Serve a recent successful result without touching the server - the tile
        // refresh loop runs more often than these counts change (see CACHE_TTL).
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return parent::getLiveStats('active', $cached);
        }

        $status = 'inactive';
        $data = [
            'configs' => 0,
            'peers' => 0,
        ];

        // Single authenticated GET - the API key is sent as a header, so there is
        // no per-poll login/logout. execute() returns null on a failed connection
        // (it never throws), so guard before reading the body.
        $res = parent::execute($this->url('api/getWireguardConfigurations'), $this->getAttrs());
        if ($res !== null) {
            $body = json_decode($res->getBody());
            if (
                $body !== null && isset($body->status) && $body->status === true
                && isset($body->data) && is_array($body->data)
            ) {
                $status = 'active';
                $data['configs'] = count($body->data);
                $connected = 0;
                foreach ($body->data as $conf) {
                    if (isset($conf->ConnectedPeers)) {
                        $connected += (int) $conf->ConnectedPeers;
                    }
                }
                $data['peers'] = $connected;
                // Cache only successes, so a transient outage retries on the next
                // refresh instead of pinning an empty tile for the whole TTL.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes the cached stats to this tile's server + key so several
    // WGDashboard tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $key = $this->config->apikey ?? '';
        return 'wgdashboard_livestats_' . md5($url . '|' . $key);
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "Content-Type" => "application/json",
                "wg-dashboard-apikey" => $this->config->apikey ?? '',
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
