<?php

namespace App\SupportedApps\bitmagnet;

use Illuminate\Support\Facades\Cache;

class bitmagnet extends \App\SupportedApps implements \App\EnhancedApps // phpcs:ignore
{
    public $config;

    // bitmagnet's GraphQL API is served over POST; both the connection test and
    // the live stats send the same query below.
    protected $method = 'POST';

    // The crawled index totals grow slowly relative to the tile's refresh loop,
    // so a successful fetch is reused for 5 minutes before the server is queried
    // again. This spares a DHT-crawler node needless count queries.
    private const CACHE_TTL = 300;

    public function __construct()
    {
    }

    public function test()
    {
        // bitmagnet ships no built-in auth, so a 200 from the GraphQL endpoint is
        // the whole test: it confirms the URL points at a live bitmagnet instance.
        $test = parent::appTest($this->url('graphql'), $this->getAttrs());
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
            'torrents' => '0',
            'movies' => '0',
        ];

        // Single POST carrying the GraphQL query. execute() returns null on a
        // failed connection (it never throws), so guard before reading the body,
        // and guard json_decode plus every field the response should contain.
        $res = parent::execute($this->url('graphql'), $this->getAttrs());
        if ($res !== null) {
            $body = json_decode($res->getBody());
            if ($body !== null && isset($body->data->torrentContent)) {
                $tc = $body->data->torrentContent;
                if (isset($tc->total->totalCount)) {
                    $status = 'active';
                    $data['torrents'] = $this->humanNumber((int) $tc->total->totalCount);
                    if (isset($tc->movies->totalCount)) {
                        $data['movies'] = $this->humanNumber((int) $tc->movies->totalCount);
                    }
                    // Cache only successes, so a transient outage retries on the
                    // next refresh instead of pinning an empty tile for 5 minutes.
                    Cache::put($cacheKey, $data, self::CACHE_TTL);
                }
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes the cached stats to this tile's server so several bitmagnet
    // tiles never share one cache entry (no credentials exist to key on).
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        return 'bitmagnet_livestats_' . md5($url);
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
            ],
            "json" => [
                "query" => $this->query(),
            ],
        ];
    }

    // One request fetches both totals via aliased searches: every indexed torrent
    // and those the classifier tagged as movies. limit:0 returns no rows while
    // totalCount:true still computes the counts, keeping the payload tiny.
    private function query()
    {
        return 'query { torrentContent { '
            . 'total: search(input: { limit: 0, totalCount: true }) { totalCount } '
            . 'movies: search(input: { limit: 0, totalCount: true, '
            . 'facets: { contentType: { filter: [movie] } } }) { totalCount } '
            . '} }';
    }

    // Compact large counts for the tile, e.g. 12,345,678 -> "12.3M".
    private function humanNumber($number)
    {
        if ($number >= 1000000000) {
            return round($number / 1000000000, 1) . 'B';
        }
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return (string) $number;
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
