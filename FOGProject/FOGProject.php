<?php

namespace App\SupportedApps\FOGProject;

class FOGProject extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->url("system/status"), $this->attrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [
            "hosts" => 0,
            "tasks" => 0,
        ];

        $hosts = $this->request("host");
        if (!is_object($hosts) || !isset($hosts->count)) {
            return parent::getLiveStats($status, $data);
        }
        $data["hosts"] = (int) $hosts->count;
        $status = "active";

        $tasks = $this->request("task/active");
        if (is_object($tasks) && isset($tasks->count)) {
            $data["tasks"] = (int) $tasks->count;
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    private function attrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "fog-api-token" => base64_encode($this->getSecretConfigValue("api_token")),
                "fog-user-token" => base64_encode($this->getSecretConfigValue("user_token")),
            ],
        ];
    }

    private function request($endpoint)
    {
        $response = parent::execute($this->url($endpoint), $this->attrs());
        if (null === $response || 200 !== $response->getStatusCode()) {
            return null;
        }

        return json_decode($response->getBody());
    }

    private function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }

    private function getSecretConfigValue($key)
    {
        $value = (string) $this->getConfigValue($key, "");
        if (0 !== strpos($value, "enc:")) {
            return $value;
        }

        try {
            return (string) decrypt(substr($value, 4), false);
        } catch (\Throwable) {
            return "";
        }
    }
}
