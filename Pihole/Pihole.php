<?php

namespace App\SupportedApps\Pihole;

use Illuminate\Support\Facades\Log;

class Pihole extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url("api.php?summaryRaw"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $res = parent::execute($this->url("api.php?summaryRaw"));
        $details = json_decode($res->getBody());

        $data = [];

        if ($details) {
            $data["ads_blocked_today"] = number_format(
                $details->ads_blocked_today
            );
            $data["ads_percentage_today"] = number_format(
                $details->ads_percentage_today,
                1
            );
        }

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $apiKey = $this->config->apikey;
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;

        if ($apiKey) {
            $api_url .= "&auth=" . $apiKey;
        }

        return $api_url;
    }
}
