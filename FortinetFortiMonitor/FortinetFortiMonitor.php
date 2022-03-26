<?php namespace App\SupportedApps\FortinetFortiMonitor;

use Illuminate\Support\Facades\Log;

class FortinetFortiMonitor extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('outage/active?limit=1&offset=0&full=false'),$this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';

        $instancesList = json_decode(parent::execute($this->url('server?limit=0&offset=0&full=false&status=active&tag_filter_mode=or'), $this->getAttrs())->getBody());
        $outagesList = json_decode(parent::execute($this->url('outage/active?limit=0&offset=0&full=false'), $this->getAttrs())->getBody());

        $data = [];
        if($instancesList || $outagesList) {
            $data['instances'] = count($instancesList->server_list) ?? 0;
            $data['outages'] = count($outagesList->outage_list) ?? 0;
        }
        return parent::getLiveStats($status, $data);
        
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).'v2/'.$endpoint;
        return $api_url;
    }

    private function getAttrs() 
    {
        return [
            'headers' => [
                'Authorization' => 'ApiKey '.$this->config->apikey
            ]
        ];
    }
}
