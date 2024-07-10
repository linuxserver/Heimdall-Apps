<?php

namespace App\SupportedApps\Gotify;

class Gotify extends \App\SupportedApps implements \App\EnhancedApps
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
            "headers" => ["Accept" => "application/json"],
        ];
        $test = parent::appTest($this->url("application"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];
        $attrs = [
            "headers" => ["Accept" => "application/json"],
        ];

        $messages = json_decode(
            parent::execute($this->url("message"), $attrs)->getBody()
        );

        $data = [];

        if ($messages) {
            $data["messages"] = count($messages->messages ?? []);
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url =
            parent::normaliseurl($this->config->url) .
            "application/" .
            $endpoint .
            "?token=" .
            $this->config->apikey;
        return $api_url;
    }
}
