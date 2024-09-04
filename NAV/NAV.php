<?php

namespace App\SupportedApps\NAV;

class NAV extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    protected $stats = [
        "alerts" => [
            "endpoint" => "/api/1/alert/?page_size=1",
            "title" => "Alerts",
            "short" => "<svg class=\"svg-inline--fa fa-exclamation-triangle fa-w-16\" aria-hidden=\"true\" " .
                       "data-prefix=\"fas\" data-icon=\"exclamation-triangle\" role=\"img\" " .
                       "xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 576 512\" data-fa-i2svg=\"\">" .
                       "<path fill=\"currentColor\" d=\"M569.517 440.013C587.975 472.007 564.807 512 527.94 " .
                       "512H48.054c-36.937 0-60-40.055-41.577-71.987L246.423 23.985c18.467-32.01 64.72-31.95 83.154 " .
                       "0zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43." .
                       "673-165.346l7.418 136c.347 6.364 5.61 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 " .
                       "11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654H256.31c-6.884 0-12.356 " .
                       "5.78-11.98 12.654z\"/></svg>",
        ],
        "devices" => [
            "endpoint" => "/api/1/netbox/?page_size=1",
            "title" => "Devices",
            "short" => "Devs",
        ],
        "interfaces" => [
            "endpoint" => "/api/1/interface/?page_size=1",
            "title" => "Interfaces",
            "short" => "Ifaces",
        ],
    ];

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        if (!empty($this->config->apikey)) {
            $test = parent::appTest($this->url('/api/'), ['headers' => ['Authorization' => 'Token ' .
                                                                        $this->config->apikey]]);
            echo $test->status;
        } else {
            echo "API key missing!";
        }
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = ["stats" => []];

        foreach ($this->stats as $stat => $conf) {
            $res = parent::execute($this->url($conf["endpoint"]), ['headers' => ['Authorization' => 'Token ' .
                                                                                 $this->config->apikey]]);
            $details = json_decode($res->getBody());

            if ($details && isset($details->count)) {
                array_push($data["stats"], [
                    "title" => $conf["title"],
                    "short" => $conf["short"],
                    "count" => $details->count,
                ]);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
