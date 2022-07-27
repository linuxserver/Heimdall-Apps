<?php namespace App\SupportedApps\Bazarr;

class Bazarr extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	//protected $login_first = true; // Uncomment if api requests need to be authed first
	//protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

	function __construct()
	{
		//$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
	}

	public function test()
	{
		$attrs = [
			"headers" => ["Accept" => "application/json"],
		];
		$test = parent::appTest($this->url("systemstatus"), $attrs);
		echo $test->status;
	}

	public function livestats()
	{
		$status = "inactive";
		$data = [];
		$attrs = [
			"headers" => ["Accept" => "application/json"],
		];

		$badges = json_decode(
			parent::execute($this->url("badges"), $attrs)->getBody()
		);

		$data = [];

		if ($badges) {
			$data["movies"] = $badges->movies ?? 0;
			$data["series"] = $badges->episodes ?? 0;
		}

		return parent::getLiveStats($status, $data);
	}

	public function url($endpoint)
	{
		$api_url =
			parent::normaliseurl($this->config->url) .
			"api/" .
			$endpoint .
			"?apikey=" .
			$this->config->apikey;
		return $api_url;
	}
}
