<?php

namespace App\SupportedApps\Proxmox;

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

    public function __construct()
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

        // Default data for inactive status (view template requires these variables)
        $inactiveData = [
            "vm_running" => 0,
            "vm_total" => 0,
            "container_running" => 0,
            "container_total" => 0,
            "cpu_percent" => 0,
            "memory_percent" => 0,
        ];

        $nodes = explode(",", $this->getConfigValue("nodes", ""));

        if ($nodes == [""]) {
            $nodeData = $this->apiCall("nodes");
            if ($nodeData === null) {
                return parent::getLiveStats("inactive", $inactiveData);
            }
            $nodes = array_map(function ($v) {
                return $v->node;
            }, $nodeData);
        }

        if (empty($nodes)) {
            return parent::getLiveStats("inactive", $inactiveData);
        }

        $vm_running = 0;
        $vm_total = 0;
        $container_running = 0;
        $container_total = 0;
        $cpu_percent_sum = 0.0;
        $memory_total = 0.0;
        $memory_used = 0.0;
        $valid_nodes = 0;

        foreach ($nodes as $node) {
            $node_status = $this->apiCall("nodes/" . $node . "/status");
            if ($node_status !== null) {
                $valid_nodes++;
                $cpu_percent_sum += $node_status->cpu ?? 0;
                $memory_used += isset($node_status->memory) ? ($node_status->memory->used ?? 0) : 0;
                $memory_total += isset($node_status->memory) ? ($node_status->memory->total ?? 0) : 0;
            }

            $vm_stats = $this->apiCall("nodes/" . $node . "/qemu");
            if ($vm_stats !== null) {
                $vm_total += count($vm_stats);
                $vm_running += count(
                    array_filter($vm_stats, function ($v) {
                        return isset($v->status) && $v->status == "running";
                    })
                );
            }

            $container_stats = $this->apiCall("nodes/" . $node . "/lxc");
            if ($container_stats !== null) {
                $container_total += count($container_stats);
                $container_running += count(
                    array_filter($container_stats, function ($v) {
                        return isset($v->status) && $v->status == "running";
                    })
                );
            }
        }

        if ($valid_nodes === 0) {
            return parent::getLiveStats("inactive", $inactiveData);
        }

        $data = [
            "vm_running" => $vm_running,
            "vm_total" => $vm_total,
            "container_running" => $container_running,
            "container_total" => $container_total,
            "cpu_percent" => ($cpu_percent_sum / $valid_nodes) * 100,
            "memory_percent" => $memory_total > 0 ? (100 / $memory_total) * $memory_used : 0,
        ];
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_stub = "api2/json/";
        $api_url = parent::normaliseurl(
            $this->getConfigValue("override_url", $this->config->url)
        ) .
        $api_stub .
        $endpoint;
        return $api_url;
    }

    public function apiCall($endpoint)
    {
        $res = parent::execute($this->url($endpoint), $this->getRequestAttrs());

        if ($res === null) {
            return null;
        }

        $object = json_decode($res->getBody());

        if (!$object instanceof \stdClass) {
            return null;
        }
        return $object->data;
    }

    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }
}
