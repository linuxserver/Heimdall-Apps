<?php

namespace App\SupportedApps\FreeLLMAPI;

class FreeLLMAPI extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        try {
            $this->providers();
            echo "Successfully communicated with the API";
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function livestats()
    {
        $data = [
            "providers" => null,
            "attention" => null,
        ];
        try {
            $providers = $this->providers();
            $data["providers"] = count($providers);
            $data["attention"] = 0;
            foreach ($providers as $provider) {
                if ($provider->status !== "healthy") {
                    $data["attention"]++;
                }
            }
            return parent::getLiveStats("active", $data);
        } catch (\Throwable $e) {
            return parent::getLiveStats("inactive", $data);
        }
    }

    private function providers()
    {
        $result = $this->request("providers");
        if (!isset($result->providers) || !is_array($result->providers)) {
            throw new \RuntimeException("Failed: Invalid provider response");
        }
        foreach ($result->providers as $provider) {
            if (
                !is_object($provider) ||
                !isset($provider->name, $provider->status) ||
                !is_string($provider->name) ||
                !in_array(
                    $provider->status,
                    ["healthy", "rate_limited", "invalid", "unknown"],
                    true
                )
            ) {
                throw new \RuntimeException("Failed: Invalid provider status");
            }
        }
        return $result->providers;
    }

    private function request($endpoint)
    {
        $key = trim($this->config->api_key ?? "");
        if ($key === "") {
            throw new \RuntimeException(
                "Failed: A unified API key is required"
            );
        }
        $url = $this->url($endpoint);
        try {
            $response = parent::execute($url, [
                "headers" => [
                    "Accept" => "application/json",
                    "Authorization" => "Bearer " . $key,
                ],
                "timeout" => 5,
                "connect_timeout" => 3,
                "allow_redirects" => false,
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed: Could not connect to FreeLLMAPI"
            );
        }
        if ($response === null) {
            throw new \RuntimeException(
                "Failed: Could not connect to FreeLLMAPI"
            );
        }
        $code = $response->getStatusCode();
        if ($code === 401 || $code === 403) {
            throw new \RuntimeException(
                "Failed: Invalid unified API key (client-profile keys are not supported)"
            );
        }
        if ($code !== 200) {
            throw new \RuntimeException(
                "Failed: FreeLLMAPI returned HTTP " .
                    $code .
                    " for " .
                    $endpoint
            );
        }
        $result = json_decode((string) $response->getBody());
        if (!is_object($result)) {
            throw new \RuntimeException(
                "Failed: FreeLLMAPI returned invalid JSON"
            );
        }
        return $result;
    }

    public function url($endpoint)
    {
        $base = rtrim(trim($this->config->url ?? ""), "/");
        $parts = parse_url($base);
        if (
            !$parts ||
            !in_array(
                strtolower($parts["scheme"] ?? ""),
                ["http", "https"],
                true
            ) ||
            empty($parts["host"]) ||
            isset($parts["user"]) ||
            isset($parts["pass"]) ||
            isset($parts["query"]) ||
            isset($parts["fragment"])
        ) {
            throw new \RuntimeException(
                "Failed: Specify the FreeLLMAPI base HTTP or HTTPS URL"
            );
        }
        $base = preg_replace('~/v1$~', "", $base);
        return $base . "/v1/" . $endpoint;
    }
}
