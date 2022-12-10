<?php namespace App\SupportedApps\Heimdall;

class Heimdall extends \App\SupportedApps implements \App\EnhancedApps
{

	public $config;

	function __construct()
	{
	}

	public function test()
	{
		$test = parent::appTest($this->url('health'));
		echo $test->status;
	}

	public function livestats()
	{
		$status = 'inactive';
		$res = parent::execute($this->url('health'));

		if ($res->getStatusCode() > 299) {
			$data = [
				'error' => true,
				'statusCode' => $res->getStatusCode(),
				'items' => 0,
				'users' => 0,
			];
			return parent::getLiveStats($status, $data);
		}

		$details = json_decode($res->getBody());

		$data = [
			'error' => false,
			'statusCode' => $res->getStatusCode(),
			'items' => $details->items,
			'users' => $details->users,
		];

		return parent::getLiveStats($status, $data);
	}

	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}
}
