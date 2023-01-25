<?php namespace App\SupportedApps\Portainer;
use Exception;

class Portainer extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	function __construct()
	{
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

	public function auth()
	{
		$body["username"] = $this->config->username;
		$body["password"] = $this->config->password;
		$vars = [
			"http_errors" => false,
			"timeout" => 5,
			"body" => json_encode($body),
		];

		$result = parent::execute(
			$this->url("api/auth"),
			$this->getAttrs(),
			$vars,
			"POST"
		);
		if (null === $result) {
			throw new Exception("Could not connect to Portainer");
		}

		$response = json_decode($result->getBody());

		if (!isset($response->jwt)) {
			throw new Exception("Invalid credentials");
		}

		return $response->jwt;
	}

	public function livestats()
	{
		$status = "inactive";
		$running = 0;
		$stopped = 0;

		$token = $this->auth();
		$headers = [
			"Authorization" => "Bearer " . $token,
			"Accept" => "application/json",
		];

		$attrs = $this->getAttrs();

		$attrs["headers"] = $headers;

		$result = parent::execute(
			$this->url("api/endpoints?limit=100&start=0"),
			$attrs,
			[]
		);
		if (null === $result) {
			throw new Exception("Could not connect to Portainer");
		}

		$response = json_decode($result->getBody());
		if (count($response) === 0) {
			throw new Exception("No endpoints");
		}

		foreach ($response as $endpoint) {
			if (count($endpoint->Snapshots) === 0) {
				throw new Exception("No snapshots");
			}

			$snapshot = $endpoint->Snapshots[0];
			$data = [
				($running += $snapshot->RunningContainerCount),
				($stopped += $snapshot->StoppedContainerCount),
			];
		}

		$data = [
			"running" => $running,
			"stopped" => $stopped,
		];
		if ($running || $stopped) {
			$status = "active";
		}
		return parent::getLiveStats($status, $data);
	}

	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}

	public function getConfigValue($key, $default = null)
	{
		return isset($this->config) && isset($this->config->$key)
			? $this->config->$key
			: $default;
	}

	public function getAttrs()
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
