<?php namespace App\SupportedApps\TarTenNinety;

class TarTenNinety extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('data/stats.json'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('data/stats.json'));
        $details = json_decode($res->getBody());

        $data = [];

        if($details) {
            $data['aircaft_with_pos'] = number_format($details->aircaft_with_pos);
            $data['aircraft_without_pos'] = number_format($details->aircraft_without_pos);
        }

        return parent::getLiveStats($status, $data);
        
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}
