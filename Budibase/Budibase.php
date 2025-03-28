<?php

namespace App\SupportedApps\Budibase;

class Budibase extends \App\SupportedApps implements \App\EnhancedApps
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
                "x-budibase-api-key" => $this->config->apikey
            ],
        ];
        $test = parent::appTest($this->url('metrics'), $attrs);
        echo $test->status;
    }

    public function decodeResponse($response)
    {
        $response = explode(PHP_EOL, $response);
        $data = [];
        foreach ($response as $x) {
            $y = explode(' ', $x);
            if (is_array($y) && count($y) == 2) {
                $data[$y[0]] = $y[1];
            }
        }
        return $data;
    }

    public function livestats()
    {
        $attrs = [
            "headers" => [
                "x-budibase-api-key" => $this->config->apikey
            ],
        ];
        $status = 'inactive';
        $res = parent::execute($this->url('metrics'), $attrs);
        $info = $this->decodeResponse($res->getBody());

        $data = [
            "total" => $info['budibase_tenant_app_count'],
            "active" => $info['budibase_tenant_production_app_count']
        ];
        if ($info['budibase_quota_limit_automations'] > 0) {
            $status = "active";
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url, true) . "api/public/v1/" . $endpoint;
        return $api_url;
    }
}
