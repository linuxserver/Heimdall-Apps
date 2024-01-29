<?php

namespace App\SupportedApps\NginxProxyManager;

class NginxProxyManager extends \App\SupportedApps implements \App\EnhancedApps
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
        $test = parent::appTest($this->url("api"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $auth_attrs = [
            "headers" => [
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ],
            "body" => json_encode([
                "identity" => $this->config->email,
                "secret" => $this->config->password,
            ]),
        ];
        $auth_res = parent::execute(
            $this->url("api/tokens"),
            $auth_attrs,
            null,
            "POST"
        );
        $auth_data = json_decode($auth_res->getBody(), true);
        $token = $auth_data["token"];

        $attrs = [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . $token,
            ],
        ];
        $res = parent::execute($this->url("api/reports/hosts"), $attrs);
        $data = json_decode($res->getBody(), true);

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
