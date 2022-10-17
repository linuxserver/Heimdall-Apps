<?php namespace App\SupportedApps\Plex;

class Plex extends \App\SupportedApps implements \App\EnhancedApps
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
		$test = parent::appTest(
			$this->url("/library/recentlyAdded"),
			$this->attrs()
		);
		echo $test->status;
	}

	public function livestats()
	{
		$status = "inactive";
		$res = parent::execute(
			$this->url("/library/recentlyAdded"),
			$this->attrs()
		);
		$body = $res->getBody();
        $xml = simplexml_load_string(
        	$body,
			"SimpleXMLElement",
			LIBXML_NOCDATA | LIBXML_NOBLANKS
		);

		$data = [];

		if ($xml) {
			$data["recently_added"] = $xml["size"];
			$status = "active";
		}

		$res = parent::execute($this->url("/library/onDeck"));

		$res = parent::execute($this->url("/library/onDeck"), $this->attrs());

		$body = $res->getBody();
		$xml = simplexml_load_string(
			$body,
			"SimpleXMLElement",
			LIBXML_NOCDATA | LIBXML_NOBLANKS
		);
		if ($xml) {
			$data["on_deck"] = $xml["size"];
			$status = "active";
		}

		return parent::getLiveStats($status, $data);
	}
	public function url($endpoint)
	{
		$url = parse_url(parent::normaliseurl($this->config->url));
		$scheme = $url["scheme"];
		$domain = $url["host"];
		$port = isset($url["port"]) ? $url["port"] : "32400";
		$api_url =
			$scheme .
			"://" .
			$domain .
			":" .
			$port .
			$endpoint .
			"?X-Plex-Token=" .
			$this->config->token;
		return $api_url;
	}

	public function getConfigValue($key, $default = null)
	{
		return isset($this->config) && isset($this->config->$key)
			? $this->config->$key
			: $default;
	}

	public function attrs()
	{
		$ignoreTls = $this->getConfigValue("ignore_tls", false);
		if ($ignoreTls) {
			$attrs["verify"] = false;
		} else {
			$attrs = [];
		}

		return $attrs;
	}
}
