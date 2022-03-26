<?php namespace App\SupportedApps\Readarr;

class Readarr extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('system/status?'));
        echo $test->status;
    }


    public function livestats()
    {
        $status = 'inactive';
        $data = [];

        $missing = json_decode(parent::execute($this->url('wanted/missing?'))->getBody());

        $nextmonth = date('Y-m-d',mktime(0, 0, 0, date("m")+1, date("d"), date("Y")));
        $tomorrow = date('Y-m-d',mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));

        $queue = json_decode(parent::execute($this->url('calendar?start='.$tomorrow.'&end='.$nextmonth.'&'))->getBody());
        $collect = collect($queue);
        $upcoming = $collect->where('hasFile', false);

        $data = [];
        
        //$date = new DateTime('NOW');
        if($missing || $queue) {
            $data['missing'] = $missing->{"totalRecords"} ?? 0;
            $data['upcoming'] =  $upcoming->count() ?? 0;
        }

        return parent::getLiveStats($status, $data);
        
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).'api/v1/'.$endpoint.'apikey='.$this->config->apikey;
        return $api_url;
    }
}
