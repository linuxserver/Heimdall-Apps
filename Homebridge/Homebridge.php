<?php namespace App\SupportedApps\Homebridge;

class Homebridge extends \App\SupportedApps implements \App\EnhancedApps
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
		$test = parent::appTest($this->url("status"));
		echo $test->status;
	}

	public function livestats()
	{
		$status = "inactive";
		$attrs = $this->getAttrs();

		$res = parent::execute(
			$this->url("api/status/server-information"),
			$attrs
		);
		$serverInfo = json_decode($res->getBody());
		$res = parent::execute($this->url("api/status/cpu"), $attrs);
		$cpu = json_decode($res->getBody());
		$res = parent::execute($this->url("api/status/ram"), $attrs);
		$memory = json_decode($res->getBody());

		if ($serverInfo->time->uptime > 0) {
			$status = "active";
		}

		$data = [
			"cpu" => intval($cpu->cpuTemperature->main) . " &deg;C",
			"ram" =>
				format_bytes($memory->mem->used) .
				" / " .
				format_bytes($memory->mem->total),
		];

		return parent::getLiveStats($status, $data);
	}

	public function getAttrs()
	{
		if (strlen($this->config->username) == 0) {
			return [];
		}
		$attrs = [
			"body" => json_encode([
				"username" => $this->config->username,
				"password" => $this->config->password,
			]),
			"headers" => ["content-type" => "application/json"],
		];
		$res = parent::execute(
			$this->url("api/auth/login"),
			$attrs,
			null,
			"POST"
		);
		$auth = json_decode($res->getBody());
		return [
			"headers" => ["authorization" => "Bearer " . $auth->access_token],
		];
	}

	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}
}
