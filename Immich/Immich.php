<?php

namespace App\SupportedApps\Immich;

class Immich extends \App\SupportedApps implements \App\EnhancedApps
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
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
                "x-api-key" => $this->config->api_key,
            ],
        ];
        $test = parent::appTest($this->url("server/statistics"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
                "x-api-key" => $this->config->api_key,
            ],
        ];
        $res = parent::execute($this->url("server/statistics"), $attrs);
        $details = json_decode($res->getBody());

        $data = [];

        if ($details) {
            $status = "active";
            $data["photos"] = number_format($details->photos);
            $data["videos"] = number_format($details->videos);
            $usageInGiB = number_format($details->usage / 1073741824, 2);
            $data["usage"] = $usageInGiB . 'GiB';
        }

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
            $api_url = parent::normaliseurl($this->config->url) .
            "api/" .
            $endpoint;
        return $api_url;
    }
}
