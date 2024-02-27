<?php

namespace App\SupportedApps\Scrutiny;

class Scrutiny extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $attrs = [
            "headers" => ["Accept" => "application/json"],
        ];
        $test = parent::appTest($this->url("health"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [
            "passed" => 0,
            "failed" => 0,
        ];

        $attrs = [
            "headers" => ["Accept" => "application/json"],
        ];

        $response = json_decode(
            parent::execute($this->url("summary"), $attrs)->getBody()
        );

        $summary = $response->data->summary;

        foreach ($summary as $entry) {
            $device_status = $entry->device->device_status;

            if ($device_status == 0) {
                $data["passed"]++;
            } else {
                $status = "active";
                $data["failed"]++;
            }
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url =
            parent::normaliseurl($this->config->url) .
            "api/" .
            $endpoint;

        return $api_url;
    }
}
