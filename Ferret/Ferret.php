<?php

namespace App\SupportedApps\Ferret;

class Ferret extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $test = parent::appTest($this->url("api/v1/summary"), $this->attrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];
        $res = parent::execute($this->url("api/v1/summary"), $this->attrs());
        if ($res !== null && $res->getStatusCode() === 200) {
            $details = json_decode($res->getBody(), true);
            $data["things"] = number_format($details["things"] ?? 0);
            $data["attention"] = number_format($details["needsAttention"] ?? 0);
            if (($details["urgent"] ?? 0) > 0) {
                $status = "active";
            }
        }
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    private function attrs()
    {
        // A Ferret API key (Settings → Claude & API); a read-only one is enough.
        return [
            "headers" => [
                "Accept" => "application/json",
                "X-Api-Key" => $this->config->apikey,
            ],
        ];
    }
}
