<?php namespace App\SupportedApps\Jeedom;

class Jeedom extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }
	
	

    public function test()
    {
		$test = $this->url("ping");
        if($test->code === 200) {
            $data = json_decode($test->response);
            if(!isset($data->result) || is_null($data->result) || $data->result == false) {
                $test->status = 'Failed: Invalid Credentials';
            } 
        } 
        echo $test->status;
    }

    public function livestats()
    {
	
		$status = "active";
		$test = $this->url("update::nbNeedUpdate");
		$detailsUpdates = json_decode($test->response);
		if($detailsUpdates) {		
			$data['updates'] = $detailsUpdates->result;
		} else {
			$data['updates'] = 0;
			
		}
		
		$test = $this->url("message::all");
		$detailsMessages = json_decode($test->response);
		if($detailsMessages) {		
			$data['messages'] = count($detailsMessages->result);
		} else {
			$data['messages'] = 0;
			
		}
		
        return parent::getLiveStats($status, $data);
        
    }
	
	 public function url($entrypoint)
    {        
		$apikey = $this->config->apikey;
        $attrs = [
		
            'body' => '{"jsonrpc": "2.0","method":"'.$entrypoint.'","params":{"apikey":"'.$this->config->apikey.'"}}',
            'cookies' => $this->jar,
            'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json']
        ];
        return parent::appTest($this->config->url.'/core/api/jeeApi.php', $attrs);
    }
	

	
}