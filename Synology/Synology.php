<?php namespace App\SupportedApps\Synology;

class Synology extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        $this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }
	
	 public function login()
    {
        $password = $this->config->password;
		$user = $this->config->username;
		
        $attrs = [
			'cookies' => $this->jar,			
        ];
        return parent::appTest($this->url('/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=Login&account='.$user.'&passwd='.$password.'&session=SurveillanceStation&format=cookie'),$attrs);
    }
		
	public function test()
    {
		$test = $this->login();
		if($test->code === 200) {
            $data = json_decode($test->response);
			if(!isset($data->success) || is_null($data->success) || $data->success == false) {
                $test->success = 'Failed: Invalid Credentials';                				
            } else {
				$test->success = $data->success;
			}
        } 
        echo "OK";
    }

    public function livestats()
    {
		$test = $this->login();
		$status = 'inactive';
		$attrs = [
			'cookies' => $this->jar,        
            'headers'  => ['Content-Type' => 'application/x-www-form-urlencoded']
        ];

		$request = parent::execute($this->url('/webapi/entry.cgi?stop_when_error=false&mode=parallel&api=SYNO.Entry.Request&version=1&method=request&compound=[{"api":"SYNO.Core.System.Utilization","method":"get","version":1,"type":"current","resource":["cpu","memory","network","lun","disk","space"]}]'), $attrs);
		$response = json_decode($request->getBody());
		if($response->success == 'true') {
			$cpu = $response->data->result[0]->data->cpu->system_load;
			$ram = $response->data->result[0]->data->memory->real_usage;
		} else {
			$cpu = "err.";
			$ram = "err.";
		}
		$data["cpu"] = $cpu.' <span> %</span>';
		$data["ram"] = $ram.' <span> %</span>';			
       
        return parent::getLiveStats($status, $data);
        
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}
