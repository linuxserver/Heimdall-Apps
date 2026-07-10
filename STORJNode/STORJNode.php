<?php

namespace App\SupportedApps\STORJNode;

use Illuminate\Support\Facades\Cache;

class STORJNode extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // The storage node dashboard's /api/sno totals (disk + bandwidth used) grow
    // gradually, so a short cache keeps the tile responsive without hammering the
    // node on the tile's frequent refresh loop.
    private const CACHE_TTL = 120;

    public function __construct()
    {
    }

    public function test()
    {
        // The dashboard API needs no auth, so hitting /api/sno/ validates both the
        // URL and that the node's dashboard is reachable (200 = ok, 404 = wrong URL).
        $test = parent::appTest($this->url('api/sno/'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $cacheKey = $this->cacheKey();

        // Serve a recent successful result without touching the node - the tile
        // refresh loop runs far more often than these totals change (see CACHE_TTL).
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return parent::getLiveStats('active', $cached);
        }

        $status = 'inactive';
        $data = [
            'disk' => '-',
            'bandwidth' => '-',
        ];

        // execute() returns null on a failed connection (it never throws), so guard
        // before reading the body and again after json_decode.
        $res = parent::execute($this->url('api/sno/'), $this->getAttrs());
        if ($res !== null) {
            $stats = json_decode($res->getBody());
            if ($stats !== null && isset($stats->diskSpace->used)) {
                $status = 'active';
                $disk = (int) $stats->diskSpace->used;
                $bandwidth = isset($stats->bandwidth->used) ? (int) $stats->bandwidth->used : 0;
                $data['disk'] = $this->formatBytes($disk);
                $data['bandwidth'] = $this->formatBytes($bandwidth);
                // Cache only successes, so a transient outage retries on the next
                // refresh instead of pinning a stale tile for the full TTL.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes cached stats to this tile's node URL so several STORJ Node
    // tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        return 'storjnode_livestats_' . md5($url);
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
            ],
        ];
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $units = ["B", "KB", "MB", "GB", "TB", "PB"];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . " " . $units[$pow];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
