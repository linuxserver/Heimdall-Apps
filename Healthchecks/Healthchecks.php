<?php

namespace App\SupportedApps\Healthchecks;

class Healthchecks extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/v3/checks/'), $this->getAttrs());
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
        $res = parent::execute($this->url('api/v3/checks/'), $this->getAttrs());
        if ($res !== null) {
            // Management API returns { "checks": [ ... ] }; each check has a
            // status of new, up, grace, down, or paused. Errors (a bad API
            // key returns 401 with { "error": ... }) carry no "checks" key,
            // so coalesce to null rather than reading the property directly
            // and leave the tile inactive in that case.
            $body = json_decode($res->getBody());
            $checks = $body->checks ?? null;
            if (is_array($checks)) {
                $status = 'active';
                foreach ($checks as $check) {
                    if (!isset($check->status)) {
                        continue;
                    }
                    if ($check->status === 'up') {
                        $data['up']++;
                    } elseif ($check->status === 'down') {
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
            'headers' => [
                'Accept' => 'application/json',
                'X-Api-Key' => ($this->config->apikey ?? ''),
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
