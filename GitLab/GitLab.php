<?php namespace App\SupportedApps\GitLab;

class GitLab extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('/-/readiness?token='.$this->config->apikey.'&all=1'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('/-/readiness?token='.$this->config->apikey.'&all=1'));
        $details = json_decode($res->getBody());

        $data = [];
        
        if($details)
        {
            $data['status'] = $details['status'];
        }
        
        return parent::getLiveStats($status, $data);
        
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}
