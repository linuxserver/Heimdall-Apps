<?php namespace App\SupportedApps\Traefik;

class Traefik extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('health'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('health'));
        $details = json_decode($res->getBody());

        $data = [];

        $avg_response_time = $details->average_response_time_sec ?? 0;
        $time = $avg_response_time*1000;
        $data['time_output'] = ($time > 0) ? number_format($time, 2).'<span>ms</span>' : 'Unknown';

        return parent::getLiveStats($status, $data);
        
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}
