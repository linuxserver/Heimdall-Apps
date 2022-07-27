<?php namespace App\SupportedApps\VuPlus;

class VuPlus extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	public function login()
	{
		// no login required.
	}

	public function test()
	{
		$test = parent::appTest($this->url("api/about"));
		echo $test->status;
	}

	public function livestats()
	{
		$res = parent::execute($this->url("api/about"));
		$content = (string) $res->getBody(true);
		$result_data = json_decode($content);
		if (
			!isset($result_data) ||
			!isset($result_data->service) ||
			!$result_data->service->result
		) {
			return parent::getLiveStats("inactive", ["channel" => "Standby"]);
		}

		$data = [
			"channel" => $result_data->service->name,
		];
		return parent::getLiveStats("active", $data);
	}

	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}
}
