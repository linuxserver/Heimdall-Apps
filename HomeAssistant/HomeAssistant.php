<?php namespace App\SupportedApps\HomeAssistant;

class HomeAssistant extends \App\SupportedApps implements \App\EnhancedApps
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
			"headers" => [
				"Accept" => "application/json",
				"Authorization" => "Bearer " . $this->config->token,
			],
		];

		$test = parent::appTest($this->url("api/"), $attrs);
		echo $test->status;
	}

	public function livestats()
	{
		$status = "inactive";

		$first_stat_label = isset($this->config->first_stat_label)
			? $this->config->first_stat_label
			: "Total lights";
		$first_stat_template = isset($this->config->first_stat_template)
			? $this->config->first_stat_template
			: "{{ states.light | count}}";
		$second_stat_label = isset($this->config->second_stat_label)
			? $this->config->second_stat_label
			: "Total lights On";
		$second_stat_template = isset($this->config->second_stat_template)
			? $this->config->second_stat_template
			: '{{ states.light | selectattr(\'state\',\'equalto\',\'on\') | list | count }}';

		$first_attrs = [
			"headers" => [
				"Accept" => "application/json",
				"Authorization" => "Bearer " . $this->config->token,
			],
			"body" => json_encode(["template" => $first_stat_template]),
		];
		$first_res = parent::execute(
			$this->url("api/template"),
			$first_attrs,
			null,
			"POST"
		);
		$first_value = $first_res->getBody();

		$second_attrs = [
			"headers" => [
				"Accept" => "application/json",
				"Authorization" => "Bearer " . $this->config->token,
			],
			"body" => json_encode(["template" => $second_stat_template]),
		];
		$second_res = parent::execute(
			$this->url("api/template"),
			$second_attrs,
			null,
			"POST"
		);
		$second_value = $second_res->getBody();

		return parent::getLiveStats($status, [
			"first_stat_label" => $first_stat_label,
			"first_stat_value" => $first_value,
			"second_stat_label" => $second_stat_label,
			"second_stat_value" => $second_value,
		]);
	}
	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}
}
