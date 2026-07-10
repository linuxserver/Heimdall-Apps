<?php

namespace App\SupportedApps\Matomo;

use Illuminate\Support\Facades\Cache;

class Matomo extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // Today's visit totals accumulate gradually, so a short cache keeps the tile
    // responsive without querying Matomo on every dashboard refresh loop.
    private const CACHE_TTL = 120;

    public function __construct()
    {
    }

    public function test()
    {
        // Hitting the real VisitsSummary.get endpoint confirms the URL resolves and
        // the Matomo instance answers. Matomo returns 200 for API calls, a bad URL
        // yields 404, and a dead host is caught as a connection failure upstream.
        $test = parent::appTest($this->url($this->apiEndpoint()), []);
        echo $test->status;
    }

    public function livestats()
    {
        $cacheKey = $this->cacheKey();

        // Serve a recent successful result without touching the server - the tile
        // refresh loop runs more often than these totals change (see CACHE_TTL).
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return parent::getLiveStats('active', $cached);
        }

        $status = 'inactive';
        $data = [
            'visits' => 0,
            'actions' => 0,
        ];

        // execute() is Guzzle with http_errors=false; it returns null on a failed
        // connection (it never throws), so guard before reading the response body.
        $res = parent::execute($this->url($this->apiEndpoint()), []);
        if ($res !== null) {
            $stats = json_decode($res->getBody());
            // A valid report is a JSON object carrying nb_visits. On an auth or URL
            // error Matomo returns {"result":"error",...} instead, so requiring
            // nb_visits also rejects those responses and keeps the tile inactive.
            if (is_object($stats) && isset($stats->nb_visits)) {
                $status = 'active';
                $data['visits'] = $this->humanNumber((int) $stats->nb_visits);
                $data['actions'] = $this->humanNumber(
                    isset($stats->nb_actions) ? (int) $stats->nb_actions : 0
                );
                // Cache only successes, so a transient error retries on the next
                // refresh instead of pinning a stale/empty tile for the full TTL.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // apiEndpoint builds today's VisitsSummary.get request. idSite defaults to 1
    // (the first site) when the optional Site ID field is left blank.
    private function apiEndpoint()
    {
        $idSite = trim((string) ($this->config->idsite ?? ''));
        if ($idSite === '') {
            $idSite = '1';
        }

        return 'index.php?' . http_build_query([
            'module' => 'API',
            'method' => 'VisitsSummary.get',
            'idSite' => $idSite,
            'period' => 'day',
            'date' => 'today',
            'format' => 'json',
            'token_auth' => $this->config->apikey ?? '',
        ]);
    }

    // Compact large counts so both stats always fit the tile (e.g. 1234 -> 1.2K).
    private function humanNumber($value)
    {
        if ($value >= 1000000) {
            return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . 'M';
        }
        if ($value >= 1000) {
            return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'K';
        }
        return number_format($value);
    }

    // cacheKey scopes cached stats to this tile's URL + token + site so multiple
    // Matomo tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $key = $this->config->apikey ?? '';
        $site = $this->config->idsite ?? '';
        return 'matomo_livestats_' . md5($url . '|' . $key . '|' . $site);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
