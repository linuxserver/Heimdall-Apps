<?php namespace App\SupportedApps\UniFi;

/**
 * Implementation based on
 * https://ubntwiki.com/products/software/unifi-controller/api
 */
class UniFi extends \App\SupportedApps
{
	public $config;

	protected $method = 'POST';

	function __construct()
	{
		$this->jar = new \GuzzleHttp\Cookie\CookieJar;
	}

	public function test()
	{
		$test = parent::appTest(
			$this->url("/api/auth/login"),
			$this->getLoginAttributes(),
        );

		echo $test->status;
	}

	public function livestats()
	{
		$status = "inactive";

		parent::execute(
			$this->url("/api/auth/login"),
			$this->getLoginAttributes(),
			null,
			'POST'
		);

		$res = parent::execute(
			$this->url("/proxy/network/api/s/default/stat/health"),
			$this->getAttributes(),
			null,
			'GET'
		);

		$details = json_decode($res->getBody());

		$data = [];

		if (isset($details->data)) {
			$data['error'] = false;
			foreach ($details->data as $key => $detail) {
				if ($detail->subsystem === 'wlan') {
					$data['wlan_users'] = $detail->num_user;
					$data['wlan_ap'] = $detail->num_ap;
					$data['wlan_dc'] = $detail->num_disconnected;
				}

				if ($detail->subsystem === 'lan') {
					$data['lan_users'] = $detail->num_user;
				}

				if ($detail->subsystem === 'wan') {
					$data['wan_avail'] = $detail->uptime_stats->WAN->availability;
				}
			}
		} else {
			$data['error'] = true;
		}

		return parent::getLiveStats($status, $data);
	}

	public function url($endpoint)
	{
		$url = parse_url(parent::normaliseurl($this->config->url));
		$scheme = $url["scheme"];
		$domain = $url["host"];
		$port = isset($url["port"]) ? $url["port"] : "443";

		$api_url =
			$scheme .
			"://" .
			$domain .
			":" .
			$port .
			$endpoint;

		return $api_url;
	}

	public function getConfigValue($key, $default = null)
	{
		return isset($this->config) && isset($this->config->$key)
			? $this->config->$key
			: $default;
	}

	public function getLoginAttributes()
	{
		$ignoreTls = $this->getConfigValue("ignore_tls", false);
		$username = $this->config->username;
		$password = $this->config->password;

		$body = [
			"username" => $username,
			"password" => $password,
		];

		$attrs = [
			"body" => json_encode($body),
			"cookies" => $this->jar,
			"headers" => [
				"Content-Type" => "application/json"
			]
		];

		if ($ignoreTls) {
			$attrs["verify"] = false;
		}

		return $attrs;
	}

	public function getAttributes()
	{
		$attrs = [
			"cookies" => $this->jar,
		];

		$ignoreTls = $this->getConfigValue("ignore_tls", false);

		if ($ignoreTls) {
			$attrs["verify"] = false;
		}

		return $attrs;
	}
}
