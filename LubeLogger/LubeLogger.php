<?php

namespace App\SupportedApps\LubeLogger;

use Illuminate\Support\Facades\Cache;

class LubeLogger extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // Vehicle counts and reminder totals change rarely, so a 5-minute cache keeps
    // the tile fresh while sparing the server the tile's frequent refresh loop.
    private const CACHE_TTL = 300;

    public function __construct()
    {
    }

    public function test()
    {
        // /api/vehicles is exactly what the tile reads, so testing it validates
        // both the URL and the Basic Auth credentials in one call
        // (200 = ok, 401 = bad/missing username/password).
        $test = parent::appTest($this->url('api/vehicles'), $this->getAttrs());
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
            'vehicles' => 0,
            'reminders' => 0,
        ];

        // execute() returns null on a failed connection (it never throws), so
        // guard before reading the body, and guard the decoded JSON too.
        $res = parent::execute($this->url('api/vehicles'), $this->getAttrs());
        if ($res !== null) {
            $vehicles = json_decode($res->getBody());
            if (is_array($vehicles)) {
                $status = 'active';
                $data['vehicles'] = count($vehicles);

                // Reminders are scoped per vehicle, so total the due reminders
                // across each vehicle. Few vehicles are typical for this app and
                // the whole result is cached, so the extra calls are cheap.
                $reminders = 0;
                foreach ($vehicles as $vehicle) {
                    if (!isset($vehicle->id)) {
                        continue;
                    }
                    $rRes = parent::execute(
                        $this->url('api/vehicle/reminders?vehicleId=' . rawurlencode($vehicle->id)),
                        $this->getAttrs()
                    );
                    if ($rRes !== null) {
                        $list = json_decode($rRes->getBody());
                        if (is_array($list)) {
                            $reminders += count($list);
                        }
                    }
                }
                $data['reminders'] = $reminders;

                // Cache only successes, so a transient outage retries on the next
                // refresh instead of pinning an empty tile for 5 minutes.
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // cacheKey scopes the cached stats to this tile's server + credentials so
    // several LubeLogger tiles never share one cache entry.
    private function cacheKey()
    {
        $url = $this->config->url ?? '';
        $user = $this->config->username ?? '';
        $pass = $this->config->password ?? '';
        return 'lubelogger_livestats_' . md5($url . '|' . $user . '|' . $pass);
    }

    private function getAttrs()
    {
        $username = $this->config->username ?? '';
        $password = $this->config->password ?? '';

        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Basic " . base64_encode($username . ":" . $password),
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
