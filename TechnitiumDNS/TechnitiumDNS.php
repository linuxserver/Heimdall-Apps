<?php

namespace App\SupportedApps\TechnitiumDNS;

class TechnitiumDNS extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->url("api/dashboard/stats/get"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];

        $res = parent::execute($this->url("api/dashboard/stats/get"));
        $details = json_decode($res->getBody());

        if ($details && isset($details->response) && isset($details->response->stats)) {
            $stats = $details->response->stats;
            $data["queries_blocked"] = number_format($stats->totalBlocked);

            // Calculate percentage blocked
            $percent = 0;
            if ($stats->totalQueries > 0) {
                $percent = ($stats->totalBlocked / $stats->totalQueries) * 100;
            }
            $data["percent_blocked"] = number_format($percent, 1);
            $status = "active";
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        $api_url .= "?token=" . $this->config->apikey . "&type=LastDay";
        return $api_url;
    }
}
