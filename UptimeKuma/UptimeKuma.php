<?php

namespace App\SupportedApps\UptimeKuma;

class UptimeKuma extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    private $mapping = [
        0 => "down",
        1 => "up",
        2 => "pending",
        3 => "maintenance",
    ];

    public function __construct()
    {
    }

    private function getAttrs()
    {
        if (empty($this->config->apikey)) {
            return [];
        }

        $basicAuthValue = base64_encode(":" . $this->config->apikey);

        return [
            "headers" => [
                "Authorization" => "Basic " . $basicAuthValue,
            ]
        ];
    }

    public function test()
    {

        $test = parent::appTest($this->url("metrics"), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";

        $response = parent::execute($this->url("metrics"), $this->getAttrs());
        $body = $response->getBody();

        $lines = explode("\n", $body);

        $data = [
            "up" => 0,
            "down" => 0,
            "pending" => 0,
            "maintenance" => 0,
            "unknown" => 0,
        ];

        foreach ($lines as $line) {
            if (strlen($line) === 0 || strpos($line, '#') === 0) {
                // If the line is empty or is a comment we can skip it
                continue;
            }

            if (strpos($line, 'monitor_status') !== 0) {
                // If the line is a metric but not a monitor we can ignore it
                continue;
            }

            // We only really care about the state, which is the integer at the end
            // of the line.
            //
            // This can be 0 (Down), 1 (Up), 2 (Pending), 3 (Maintenance)
            //
            // We only care about the down or up but let's translate all of them.
            $state = intval(substr($line, strrpos($line, ' ')));

            $data[$this->mapping[$state] ?? 'unknown']++;
        }

        if ($data["down"] > 0) {
            $status = "active";
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) .
            $endpoint;

        return $api_url;
    }
}
