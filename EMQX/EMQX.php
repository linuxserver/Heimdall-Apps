<?php

namespace App\SupportedApps\EMQX;

class EMQX extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        // /api/v5/stats?aggregate=true is exactly what the tile reads, so testing
        // it validates the API key/secret pair and its access in a single call
        // (401 = bad/missing credentials).
        $test = parent::appTest(
            $this->url('api/v5/stats?aggregate=true'),
            $this->getAttrs()
        );
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [
            'connections' => 0,
            'subscriptions' => 0,
        ];

        // Single authenticated GET using HTTP Basic auth (API Key : Secret).
        // execute() returns null on a failed connection (it never throws), so
        // guard before reading the body, and guard the decoded JSON too.
        $res = parent::execute(
            $this->url('api/v5/stats?aggregate=true'),
            $this->getAttrs()
        );
        if ($res !== null) {
            $stats = json_decode($res->getBody());
            if ($stats !== null && isset($stats->{'connections.count'})) {
                $status = 'active';
                $data['connections'] = $this->humanize($stats->{'connections.count'});
                if (isset($stats->{'subscriptions.count'})) {
                    $data['subscriptions'] = $this->humanize($stats->{'subscriptions.count'});
                }
            }
        }

        return parent::getLiveStats($status, $data);
    }

    private function getAttrs()
    {
        // EMQX authenticates the REST API with an API Key (username) and Secret
        // (password) over HTTP Basic auth. Create the pair in the dashboard under
        // System -> API Key.
        $key = $this->config->username ?? '';
        $secret = $this->config->password ?? '';

        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Basic " . base64_encode($key . ":" . $secret),
            ],
        ];
    }

    // Compact large counts (e.g. 1234 -> 1.2K, 2500000 -> 2.5M) so busy brokers
    // stay readable on the tile.
    private function humanize($number)
    {
        $number = (int) $number;
        if ($number >= 1000000) {
            return rtrim(rtrim(number_format($number / 1000000, 1), '0'), '.') . 'M';
        }
        if ($number >= 1000) {
            return rtrim(rtrim(number_format($number / 1000, 1), '0'), '.') . 'K';
        }
        return number_format($number);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
