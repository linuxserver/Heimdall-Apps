<?php

namespace App\SupportedApps\WatchYourLAN;

class WatchYourLAN extends \App\SupportedApps implements \App\EnhancedApps
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
        $test = parent::appTest($this->url('api/all/'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('api/all/'));
        $hosts = json_decode($res->getBody());
        $unknown_count = 0;

        if (is_array($hosts)) {
          foreach ($hosts as $key => $host) {
            if (isset($host->Known) && $host->Known == 0) {
                $unknown_count += 1;
            }
          }
        }

        $data['unknown_count'] = $unknown_count;
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
