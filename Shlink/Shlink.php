<?php

namespace App\SupportedApps\Shlink;

use Illuminate\Support\Facades\Cache;

class Shlink extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // Short URL and visit totals move slowly, so a successful fetch is reused for
    // five minutes. This keeps the tile responsive while sparing the Shlink server
    // the tile's frequent refresh loop.
    private const CACHE_TTL = 300;

    public function __construct()
    {
    }

    public function test()
    {
        // Listing short URLs is an authenticated read that the tile also uses, so
        // testing it validates both the base URL and the API key in one call
        // (401 = bad/missing key, 404 = wrong URL). itemsPerPage=1 keeps it light.
        $test = parent::appTest($this->url('rest/v3/short-urls?itemsPerPage=1'), $this->getAttrs());
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
            'shorturls' => 0,
            'visits' => 0,
        ];

        // Total number of short URLs lives in the pagination block; itemsPerPage=1
        // avoids pulling a page of records we do not need. execute() returns null on
        // a failed connection (it never throws), so guard before reading the body.
        $urlsRes = parent::execute($this->url('rest/v3/short-urls?itemsPerPage=1'), $this->getAttrs());
        if ($urlsRes !== null) {
            $urls = json_decode($urlsRes->getBody());
            if ($urls !== null && isset($urls->shortUrls->pagination->totalItems)) {
                $status = 'active';
                $data['shorturls'] = (int) $urls->shortUrls->pagination->totalItems;

                // Only reach for visit totals once the first authenticated call
                // proved the URL and key are good.
                $visitsRes = parent::execute($this->url('rest/v3/visits'), $this->getAttrs());
                if ($visitsRes !== null) {
                    $visits = json_decode($visitsRes->getBody());
                    if ($visits !== null && isset($visits->visits->nonOrphanVisits->total)) {
                        $data['visits'] = (int) $visits->visits->nonOrphanVisits->total;
                    }
                }

                $data['shorturls'] = $this->readable($data['shorturls']);
                $data['visits'] = $this->readable($data['visits']);

                // Cache only successes, so a transient outage retries on the next
                // refresh instead of pinning a stale tile for five minutes.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // Compact large counts (12.3K, 1.2M) so the tile stays legible; smaller values
    // keep thousands separators for exactness.
    private function readable($value)
    {
        if ($value >= 1000000) {
            return round($value / 1000000, 1) . 'M';
        }
        if ($value >= 10000) {
            return round($value / 1000, 1) . 'K';
        }
        return number_format($value);
    }

    // cacheKey scopes the cached stats to this tile's server + key so several Shlink
    // tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $key = $this->config->apikey ?? '';
        return 'shlink_livestats_' . md5($url . '|' . $key);
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "X-Api-Key" => $this->config->apikey ?? '',
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
