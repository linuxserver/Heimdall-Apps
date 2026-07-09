<?php

namespace App\SupportedApps\Cup;

use Illuminate\Support\Facades\Cache;

class Cup extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // How long a successful stats fetch is reused before the Cup server is
    // queried again. Cup only recomputes its update counts on its own refresh
    // schedule (minutes/hours apart), so a short cache spares the server the
    // tile's frequent refresh loop without showing stale numbers.
    private const CACHE_TTL = 300;

    public function __construct()
    {
    }

    public function test()
    {
        // The tile reads /api/v3/json, so testing it validates the URL in the
        // same call the livestats use. Cup's server has no authentication, so a
        // reachable server returns 200.
        $test = parent::appTest($this->url('api/v3/json'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $cacheKey = $this->cacheKey();

        // Serve a recent successful result without touching the server - the tile
        // refresh loop runs far more often than Cup recomputes its counts.
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return parent::getLiveStats('active', $cached);
        }

        $status = 'inactive';
        $data = [
            'updates' => 0,
            'monitored' => 0,
        ];

        // Single unauthenticated GET. execute() returns null on a failed
        // connection (it never throws), so guard before reading the body.
        $res = parent::execute($this->url('api/v3/json'), $this->getAttrs());
        if ($res !== null) {
            $json = json_decode($res->getBody());
            if ($json !== null && isset($json->metrics) && isset($json->metrics->monitored_images)) {
                $status = 'active';
                $data['updates'] = (int) ($json->metrics->updates_available ?? 0);
                $data['monitored'] = (int) $json->metrics->monitored_images;
                // Cache only successes, so a transient outage retries on the
                // next refresh instead of pinning an empty tile.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes the cached stats to this tile's server so several Cup
    // tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        return 'cup_livestats_' . md5($url);
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
