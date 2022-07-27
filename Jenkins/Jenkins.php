<?php namespace App\SupportedApps\Jenkins;

class Jenkins extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	function __construct()
	{
	}

	public function test()
	{
		if ($this->config->username != "" || $this->config->password != "") {
			$this->attrs = [
				"auth" => [
					$this->config->username,
					$this->config->password,
					"Basic",
				],
			];
		}
		$test = parent::appTest($this->url("api/json"), $this->attrs);
		echo $test->status;
	}

	public function livestats()
	{
		$status = "inactive";
		$data = [];
		$data["TotalRunningJobs"] = 0;
		if ($this->config->username != "" || $this->config->password != "") {
			$this->attrs = [
				"auth" => [
					$this->config->username,
					$this->config->password,
					"Basic",
				],
			];
		}
		$res = parent::execute(
			$this->url(
				"computer/api/xml?tree=computer[executors[currentExecutable[url]]]&depth=1&xpath=//url&wrapper=buildUrls"
			),
			$this->attrs
		);

		try {
			$value = simplexml_load_string($res->getBody());
			$data["TotalRunningJobs"] = count($value->url) ?? 0;
		} catch (\ErrorException $e) {
			$data["TotalRunningJobs"] = 0;
		}

		return parent::getLiveStats($status, $data);
	}
	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}
}
