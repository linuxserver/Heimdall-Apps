<?php

namespace App\SupportedApps\Tracearr;

class Tracearr extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->url("api/v2/public/streams?summary=true"), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [
            "streams" => 0,
            "transcodes" => 0,
            "bandwidth" => "0 Mbps",
        ];

        $res = parent::execute($this->url("api/v2/public/streams?summary=true"), $this->getAttrs());
        $summary = $res ? (json_decode($res->getBody())->summary ?? null) : null;

        if ($summary) {
            $data["streams"] = $summary->total;
            $data["transcodes"] = $summary->transcodes;
            $data["bandwidth"] = $summary->total_bitrate;
            if ($summary->total > 0) {
                $status = "active";
            }
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . $this->config->apikey,
            ],
        ];
    }
}
