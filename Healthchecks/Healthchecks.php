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
        $data = [
            'up' => 0,
            'down' => 0,
        ];

        // Single authenticated GET. execute() returns null on a failed
        // connection (it never throws), so guard before reading the body.
        $res = parent::execute($this->url('api/v3/checks/'), $this->getAttrs());
        if ($res === null) {
            return parent::getLiveStats('inactive', $data);
        }

        // Management API returns { "checks": [ ... ] }; each check has
        // status of new, up, grace, down, or paused.
        $body = json_decode($res->getBody());
        $checks = is_object($body) ? $body->checks : [];
        if (!is_array($checks)) {
            return parent::getLiveStats('inactive', $data);
        }

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

        return parent::getLiveStats('inactive', $data);
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
