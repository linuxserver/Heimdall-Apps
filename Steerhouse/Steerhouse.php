<?php

namespace App\SupportedApps\Steerhouse;

class Steerhouse extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/stats'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [
            'running' => 0,
            'queued' => 0,
            'failed' => 0,
        ];

        // GET /api/stats returns counts only, e.g. {"queued": 0, "running": 2, "succeeded_24h": 14,
        // "failed_24h": 1, "workers_online": 3}. execute() returns null when the connection fails;
        // a bad key gets a 401 whose body has no counts, so the tile stays at zero and inactive.
        $res = parent::execute($this->url('api/stats'), $this->getAttrs());
        if ($res !== null && $res->getStatusCode() === 200) {
            $body = json_decode($res->getBody());
            if (isset($body->running, $body->queued, $body->failed_24h)) {
                $data['running'] = (int) $body->running;
                $data['queued'] = (int) $body->queued;
                $data['failed'] = (int) $body->failed_24h;
                // Refresh every 5 seconds while runs are waiting or in progress, else every 30.
                if ($data['running'] > 0 || $data['queued'] > 0) {
                    $status = 'active';
                }
            }
        }

        return parent::getLiveStats($status, $data);
    }

    private function getAttrs()
    {
        // A "stats" API key from Projects -> API keys in Steerhouse: it can read these counts only.
        $attrs = [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . ($this->config->apikey ?? ''),
            ],
        ];
        if (!empty($this->config->ignore_tls)) {
            $attrs['verify'] = false;
        }
        return $attrs;
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
