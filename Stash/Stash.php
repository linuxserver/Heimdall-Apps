<?php namespace App\SupportedApps\Stash;

class Stash extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	//protected $login_first = true; // Uncomment if api requests need to be authed first
	protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

	function __construct()
	{
		//$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
	}

	public function test()
	{
		$apiKey = $this->config->apikey;

		$attrs = [];
		$data = [];
		$vars = [
			"body" => '{ "query": "{ systemStatus { status } }" }',
			"headers" => [
				"Content-Type" => "application/json",
			],
		];

		if ($apiKey) {
			$vars["headers"]["ApiKey"] = $apiKey;
		}

		$res = parent::execute(
			$this->url("graphql"),
			$attrs,
			$vars
		);

		if ($res->getStatusCode() == 200) {
			$details = json_decode($res->getBody());

			if ($details != null && $details->data->systemStatus->status == "OK") {
				echo "Welcome! You are connected to API.";
				return true;
			}
		}
	}


	public function livestats()
	{
		$status = "inactive";
		$apiKey = $this->config->apikey;

		$attrs = [];
		$data = [];
		$vars = [
			"body" => '{ "query": "{ stats { scene_count scenes_size } }" }',
			"headers" => [
				"Content-Type" => "application/json",
			],
		];

		if ($apiKey) {
			$vars["headers"]["ApiKey"] = $apiKey;
		}

		$res = parent::execute(
			$this->url("graphql"),
			$attrs,
			$vars
		);

		if ($res->getStatusCode() == 200) {
			$status = "active";
			$details = json_decode($res->getBody());

			if ($details) {
				$data["scene_count"] = number_format($details->data->stats->scene_count);
				$data["scenes_size"] = $this->formatBytes($details->data->stats->scenes_size);
			}
		}

		return parent::getLiveStats($status, $data);
	}
	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}

	public function formatBytes($bytes, $precision = 2)
	{
		$units = ["B", "KB", "MB", "GB", "TB"];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . " " . $units[$pow];
	}
}
