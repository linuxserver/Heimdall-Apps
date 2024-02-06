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
        $test = parent::appTest($this->url("api/server-info/statistics"));
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
        $res = parent::execute($this->url("api/server-info/statistics"), $attrs);
        $data = json_decode($res->getBody());

        $details = [];

        if ($data) {
            $status = "active";
            $details["photos"] = number_format($data->photos);
            $details["videos"] = number_format($data->videos);
        }

        return parent::getLiveStats($status, $details);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
