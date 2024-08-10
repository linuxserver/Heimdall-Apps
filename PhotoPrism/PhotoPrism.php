<?php

namespace App\SupportedApps\PhotoPrism;

class PhotoPrism extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function getRequestAttrs()
    {
        $attrs["headers"] = ["X-Session-ID" => $this->config->session];
        return $attrs;
    }

    public function test()
    {
        $attrs = $this->getRequestAttrs();
        $test = parent::appTest($this->url("api/v1/config"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $attrs = $this->getRequestAttrs();
        $res = parent::execute($this->url("api/v1/config"), $attrs);
        $details = json_decode($res->getBody(), true);
        $data = [];

        if ($details) {
            $status = "active";
            $data["photos"] = number_format($details["count"]["photos"]) ?? 0;
            $data["videos"] = number_format($details["count"]["videos"]) ?? 0;
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
