<?php namespace App\SupportedApps\Pihole;

class Pihole extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('/api.php'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('/api.php'));
        $details = json_decode($res->getBody());

        $data = [];

        if($details) {
            $domains_being_blocked = $details->domains_being_blocked;
            $ads_blocked_today = $details->ads_blocked_today;
            $data['domains_being_blocked'] = $domains_being_blocked;
            $data['ads_blocked_today'] = $ads_blocked_today;
        }

        return parent::getLiveStats($status, $data);
        
    }
    public function url($endpoint)
    {
        $url = rtrim($this->config->url, '/');
        $api_url = $url.$endpoint;
        return $api_url;
    }
}
