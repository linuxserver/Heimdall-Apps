<?php namespace App\SupportedApps\Jenkins;

class Jenkins extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    //  computer/api/xml?tree=computer[executors[currentExecutable[url]]]&xpath=//url&wrapper=builds
	
    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
		if ($this->config->username != '' || $this->config->password != '') {
            $this->attrs = ['auth'=> [$this->config->username, $this->config->password, 'Basic']];
        }
        $test = parent::appTest($this->url('api/json'),$this->attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
		$data = [];
		$data['TotalRunningJobs'] = 0;
		if ($this->config->username != '' || $this->config->password != '') {
            $this->attrs = ['auth'=> [$this->config->username, $this->config->password, 'Basic']];
        }
        $res = parent::execute($this->url('computer/api/xml?tree=computer[executors[currentExecutable[url]]]&depth=1&xpath=//url&wrapper=buildUrls'), $this->attrs);
		
		try{
			$value = simplexml_load_string($res->getBody());
			$data['TotalRunningJobs'] = count($value->url) ?? 0;
		} catch(\ErrorException $e) {
			$data['TotalRunningJobs'] = 0;
		}
		
		
		/*
		$details = json_decode($res->getBody());
        if($details) {
            $data['TotalRunningJobs'] = count($details->computer[0]->executors) ?? 5;
            $status = 'active';
        } else 
		{
			$data['TotalRunningJobs'] = 99;
		}
		*/
		
        return parent::getLiveStats($status, $data);
        
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}
