<?php namespace App\SupportedApps\Radarr;

class Radarr extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('system/status'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [];

        $movies = json_decode(parent::execute($this->url('movie?'))->getBody());
        $missing = collect($movies)->where('hasFile', false)->where('isAvailable', true);

        $today = date('Y-m-d',mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $nextmonth = date('Y-m-d',mktime(0, 0, 0, date("m")+1, date("d"), date("Y")));
        $queue = json_decode(parent::execute($this->url('calendar?start='.$today.'&end='.$nextmonth.'&'))->getBody());
        $upcoming = collect($queue)->where('hasFile', false)->where('isAvailable', false)->whereNotNull('digitalRelease');


        $data = [];
        if($missing || $upcoming) {
            $data['missing'] = $missing->count() ?? 0;
            $data['upcoming'] = $upcoming->count() ?? 0;
        }

        return parent::getLiveStats($status, $data);
        
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).'api/v3/'.$endpoint.'apikey='.$this->config->apikey;
        return $api_url;
    }
}
