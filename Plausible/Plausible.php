<?php

namespace App\SupportedApps\Plausible;

use Illuminate\Support\Facades\Cache;

class Plausible extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // A site's aggregate visitor/pageview totals change slowly relative to the
    // tile's refresh loop, so a short cache keeps the tile current while sparing
    // the instance's 600 requests/hour Stats API budget.
    private const CACHE_TTL = 300;

    // Rolling window used for both metrics shown on the tile.
    private const DATE_RANGE = '30d';

    // The Stats API v2 query endpoint is a POST with a JSON body, so both
    // appTest() and execute() must issue POST rather than the default GET.
    protected $method = 'POST';

    public function __construct()
    {
    }

    public function test()
    {
        // /api/v2/query is exactly what the tile reads, so testing it validates
        // the API key, the site_id and reachability in a single call
        // (401 = bad/missing key, 404 = wrong URL, other = unknown site).
        $test = parent::appTest($this->url('api/v2/query'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $cacheKey = $this->cacheKey();

        // Serve a recent successful result without touching the instance - the
        // tile refresh loop runs far more often than these totals change.
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return parent::getLiveStats('active', $cached);
        }

        $status = 'inactive';
        $data = [
            'visitors' => 0,
            'pageviews' => 0,
        ];

        // execute() returns null on a failed connection (it never throws), so
        // guard the response and the decoded JSON before reading anything. The
        // metrics array mirrors the order requested in getAttrs():
        // [0] = visitors, [1] = pageviews.
        $res = parent::execute($this->url('api/v2/query'), $this->getAttrs());
        if ($res !== null) {
            $stats = json_decode($res->getBody());
            if (
                $stats !== null
                && isset($stats->results[0]->metrics)
                && is_array($stats->results[0]->metrics)
                && count($stats->results[0]->metrics) >= 2
            ) {
                $metrics = $stats->results[0]->metrics;
                $status = 'active';
                $data['visitors'] = $this->humanNumber((int) $metrics[0]);
                $data['pageviews'] = $this->humanNumber((int) $metrics[1]);
                // Cache only successes, so a transient outage retries on the next
                // refresh instead of pinning an empty tile for the full TTL.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes the cached stats to this tile's instance + key + site so
    // several Plausible tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $key = $this->config->apikey ?? '';
        $site = $this->config->siteid ?? '';
        return 'plausible_livestats_' . md5($url . '|' . $key . '|' . $site);
    }

    // humanNumber shortens large counts (1500 -> 1.5K, 2000000 -> 2M) so both
    // metrics stay legible inside the narrow tile.
    private function humanNumber($number)
    {
        if ($number < 1000) {
            return (string) $number;
        }
        if ($number < 1000000) {
            return rtrim(rtrim(number_format($number / 1000, 1), '0'), '.') . 'K';
        }
        return rtrim(rtrim(number_format($number / 1000000, 1), '0'), '.') . 'M';
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . ($this->config->apikey ?? ''),
            ],
            "json" => [
                "site_id" => $this->config->siteid ?? '',
                "metrics" => ["visitors", "pageviews"],
                "date_range" => self::DATE_RANGE,
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
