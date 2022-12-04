<?php namespace App\SupportedApps\openmediavault;
use Exception;
use GuzzleHttp\Cookie\CookieJar;

class openmediavault extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	private $cookie;

	function __construct()
	{
		$this->cookie = new \GuzzleHttp\Cookie\CookieJar();
	}

	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . "rpc.php";
		return $api_url;
	}

	private function request($service, $method, $params = [])
	{
		$attrs = [
			"json" => [
				"service" => $service,
				"method" => $method,
				"params" => $params,
			],
			"cookies" => $this->cookie,
		];

		// @see \App\SupportedApps\execute($url, $attrs = [], $overridevars=false, $overridemethod=false)
		$result = parent::execute($this->url(false), $attrs, null, "POST");
		if (null === $result) {
			throw new Exception("OMV error: Could not connect");
		}

		$response = json_decode($result->getBody());

		if (is_null($response->response) && isset($response->error->message)) {
			throw new Exception(
				sprintf("OMV error: %s", $response->error->message)
			);
		} elseif (is_null($response->response)) {
			throw new Exception("OMV error: Empty response");
		}
		return $response->response;
	}

	private function auth()
	{
		$result = $this->request("session", "login", [
			"username" => $this->config->username,
			"password" => $this->config->password,
		]);
		return $result->authenticated;
	}

	public function test()
	{
		try {
			$token = $this->auth();
			echo "Successfully communicated with the API";
		} catch (Exception $err) {
			echo $err->getMessage();
		}
	}

	private function symbol($bool)
	{
		return true === $bool ? "&#10003;" : "&#10007;";
	}

	public function livestats()
	{
		$status = "inactive";
		$token = $this->auth();
		$data = ["visiblestats" => []];

		$info = $this->request("system", "getInformation");
		$data["CPU"] = sprintf("%.1f%%", $info->cpuUsage);
		$data["RAM"] = sprintf(
			"%.1f%%",
			($info->memUsed / $info->memTotal) * 100
		);
		$data["Pkgs"] = $this->symbol(!$info->pkgUpdatesAvailable);

		$services = $this->request("services", "getStatus");
		foreach ($services->data as $service) {
			$k = explode(" ", $service->title)[0];
			$data[$k] = sprintf(
				"%s | %s",
				$this->symbol($service->enabled),
				$this->symbol($service->running)
			);
		}

		foreach ($this->config->availablestats as $stat) {
			$newstat = new \stdClass();
			$newstat->title = self::getAvailableStats()[$stat];
			$newstat->value = $data[$stat];
			$data["visiblestats"][] = $newstat;
		}
		$status = "active";
		return parent::getLiveStats($status, $data);
	}

	public static function getAvailableStats()
	{
		return [
			"CPU" => "CPU",
			"RAM" => "RAM",
			"NFS" => "NFS",
			"FTP" => "FTP",
			"RSync" => "RSync",
			"SMB/CIFS" => "SMB/CIFS",
			"SSH" => "SSH",
			"Pkgs" => "Pkgs",
		];
	}
}
