<?php namespace App\SupportedApps\Duplicati;

class Duplicati extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        $this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
	$res = parent::execute($this->url('api/v1/progressstate/'), $this->attrs);
       	echo $res->getReasonPhrase();
    }

    public function livestats()
    {
        //$status = 'inactive';
	$status = 'active';
        $res = parent::execute($this->url('api/v1/progressstate'), $this->attrs);
        $details = $res->getBody();
	// Sanitize the JSON string
	for ($i = 0; $i <= 31; ++$i)
	{ 
		$details = str_replace(chr($i), "", $details); 
	}
	$details = str_replace(chr(127), "", $details);
	if (0 === strpos(bin2hex($details), 'efbbbf'))
	{
		$details = substr($details, 3);
	}
	// ^^[https://stackoverflow.com/a/20845642]^^
	$details = json_decode($details);
	$speed = $details->BackendSpeed;
	$data['speed'] =  format_Bytes($speed, false, ' <span>', '/s</span>'); 
	$remaining = $details->TotalFileSize - $details->ProcessedFileSize;
	$data['remaining'] = format_Bytes($remaining, false, ' <span>', '</span>');
	$status = ($speed > 0 || $remaining > 0) ? 'active' : 'inactive';
        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
	$res = parent::execute($api_url, ['cookies' => $this->jar]);
        $cookie = $this->jar->getCookieByName('xsrf-token');
        $token = urldecode($cookie->getValue());
        $this->attrs = ['headers' => ['X-XSRF-Token' => $token]];
        return $api_url;
    }


    public function formatBytes($bytes, $precision = 2)
    {
	$units = array('B', 'KB', 'MB', 'GB', 'TB'); 
	$bytes = max($bytes, 0); 
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
	$pow = min($pow, count($units) - 1); 
	$bytes /= pow(1024, $pow);
	return round($bytes, $precision) . ' ' . $units[$pow]; 
    }
}
