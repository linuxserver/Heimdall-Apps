<?php

namespace App\SupportedApps\KuvaszUptime;

class KuvaszUptime extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        // Listing monitors is exactly what the tile reads, so testing it
        // validates the API key in one call (401 = bad/missing key). The
        // health endpoint is unauthenticated, so it would not verify the key.
        $test = parent::appTest($this->url('api/v2/http-monitors'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [
            'up' => 0,
            'down' => 0,
        ];

        // Single authenticated GET. execute() returns null on a failed
        // connection (it never throws), so guard before reading the body.
        $res = parent::execute($this->url('api/v2/http-monitors'), $this->getAttrs());
        if ($res !== null) {
            $monitors = json_decode($res->getBody());
            // The endpoint returns a plain JSON array of monitor objects, each
            // carrying an "uptimeStatus" of UP or DOWN.
            if (is_array($monitors)) {
                $status = 'active';
                foreach ($monitors as $monitor) {
                    if (!isset($monitor->uptimeStatus)) {
                        continue;
                    }
                    if ($monitor->uptimeStatus === 'UP') {
                        $data['up']++;
                    } elseif ($monitor->uptimeStatus === 'DOWN') {
                        $data['down']++;
                    }
                }
            }
        }

        return parent::getLiveStats($status, $data);
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                // Kuvasz accepts the API key via the X-API-KEY header on every
                // API call; this is the mechanism available in all versions.
                "X-API-KEY" => ($this->config->apikey ?? ''),
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
