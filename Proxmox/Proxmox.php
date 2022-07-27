<?php namespace App\SupportedApps\Proxmox;

class Proxmox extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	public function getRequestAttrs()
	{
		$token_id = $this->getConfigValue("token_id", null);
		$token_value = $this->getConfigValue("token_value", null);
		$ignoreTls = $this->getConfigValue("ignore_tls", false);

		$auth = "PVEAPIToken=" . $token_id . "=" . $token_value;

		$attrs["headers"] = [
			"Accept" => "application/json",
			"Authorization" => $auth,
		];
		if ($ignoreTls) {
			$attrs["verify"] = false;
		}

		return $attrs;
	}

	function __construct()
	{
	}

	public function test()
	{
		$attrs = $this->getRequestAttrs();
		$test = parent::appTest($this->url("version"), $attrs);
		echo $test->status;
	}

	public function livestats()
	{
		$status = "active";
		$attrs = $this->getRequestAttrs();

		$nodes = explode(",", $this->getConfigValue("nodes", ""));

		if ($nodes == [""]) {
			$nodes = array_map(function ($v) {
				return $v->node;
			}, $this->apiCall("nodes"));
		}

		$vm_running = 0;
		$vm_total = 0;
		$container_running = 0;
		$container_total = 0;
		$cpu_percent_sum = 0.0;
		$memory_total = 0.0;
		$memory_used = 0.0;
		foreach ($nodes as $node) {
			$node_status = $this->apiCall("nodes/" . $node . "/status");
			$cpu_percent_sum += $node_status->cpu;
			$memory_used += $node_status->memory->used;
			$memory_total += $node_status->memory->total;

			$vm_stats = $this->apiCall("nodes/" . $node . "/qemu");
			$vm_total += count($vm_stats);
			$vm_running += count(
				array_filter($vm_stats, function ($v) {
					return $v->status == "running";
				})
			);

			$container_stats = $this->apiCall("nodes/" . $node . "/lxc");
			$container_total += count($container_stats);
			$container_running += count(
				array_filter($container_stats, function ($v) {
					return $v->status == "running";
				})
			);
		}

		$res = parent::execute($this->url("version"), $attrs);
		$details = json_decode($res->getBody())->data;

		$data = [
			"vm_running" => $vm_running,
			"vm_total" => $vm_total,
			"container_running" => $container_running,
			"container_total" => $container_total,
			"cpu_percent" => ($cpu_percent_sum / count($nodes)) * 100,
			"memory_percent" => (100 / $memory_total) * $memory_used,
		];
		return parent::getLiveStats($status, $data);
	}
	public function url($endpoint)
	{
		$api_stub = "api2/json/";
		$api_url =
			parent::normaliseurl(
				$this->getConfigValue("override_url", $this->config->url)
			) .
			$api_stub .
			$endpoint;
		return $api_url;
	}

	public function apiCall($endpoint)
	{
		$res = parent::execute($this->url($endpoint), $this->getRequestAttrs());
		return json_decode($res->getBody())->data;
	}

	public function getConfigValue($key, $default = null)
	{
		return isset($this->config) && isset($this->config->$key)
			? $this->config->$key
			: $default;
	}
}
