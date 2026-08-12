<?php

namespace App\SupportedApps\Dockhand;

class Dockhand extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->url("api/environments"), $this->attrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $running = 0;
        $stopped = 0;

        foreach ($this->environmentIds() as $envId) {
            $result = parent::execute(
                $this->url("api/containers?env=" . urlencode($envId)),
                $this->attrs()
            );
            if (null === $result) {
                continue;
            }
            $containers = json_decode($result->getBody());
            if (!is_array($containers)) {
                continue;
            }
            $status = "active";
            foreach ($containers as $container) {
                if (($container->state ?? "") === "running") {
                    $running++;
                } else {
                    $stopped++;
                }
            }
        }

        $data = [
            "running" => $running,
            "stopped" => $stopped,
        ];

        return parent::getLiveStats($status, $data);
    }

    private function environmentIds()
    {
        $configured = trim((string) $this->getConfigValue("environments", ""));
        if ($configured !== "") {
            return array_filter(array_map("trim", explode(",", $configured)));
        }

        $result = parent::execute($this->url("api/environments"), $this->attrs());
        if (null === $result) {
            return [];
        }
        $environments = json_decode($result->getBody());
        if (!is_array($environments)) {
            return [];
        }

        $ids = [];
        foreach ($environments as $environment) {
            if (isset($environment->id)) {
                $ids[] = $environment->id;
            }
        }
        return $ids;
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    public function attrs()
    {
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
            ],
        ];

        // Dockhand instances with authentication disabled accept
        // unauthenticated API requests, so the token is optional.
        $token = $this->getConfigValue("token", null);
        if (!empty($token)) {
            $attrs["headers"]["Authorization"] = "Bearer " . $token;
        }

        if ($this->getConfigValue("ignore_tls", false)) {
            $attrs["verify"] = false;
        }

        return $attrs;
    }

    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }
}
