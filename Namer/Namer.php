<?php namespace App\SupportedApps\Namer;

class Namer extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    protected $method = 'POST';

    function __construct() {
    }

    public function test()
    {
        $this->attrs['headers'] = ['accept' => 'application/json'];
        $res = parent::appTest($this->url('api/v1/get_files'), $this->attrs);
    	echo $res->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [];
        $this->attrs['headers'] = ['accept' => 'application/html'];
        $res = parent::execute($this->url('/api/v1/get_files'), $this->attrs);
        if ($res->getStatusCode() == 200) {
           $data['failed'] = count(json_decode($res->getBody(), True));
        } else {
           $data['failed'] = 0;
        }
        $res = parent::execute($this->url('/api/v1/get_queue'), $this->attrs);
        if ($res->getStatusCode() == 200) {
	   $jsonres = json_decode($res->getBody(), True);
           $data['queued'] = $jsonres;
        } else {
           $data['queued'] = 0;
        }
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }

}
