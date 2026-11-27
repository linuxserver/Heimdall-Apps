<?php

namespace App\SupportedApps\HermesAgent;

class HermesAgent extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        try {
            $health = $this->health();
            echo $health === null
                ? "Failed: Check the Hermes API URL and API key"
                : "Successfully communicated with the API";
        } catch (\Throwable $e) {
            echo "Failed: Could not reach the Hermes API";
        }
    }

    public function livestats()
    {
        try {
            $health = $this->health();
            if ($health !== null) {
                return parent::getLiveStats(
                    $health->status === "ok" ? "active" : "inactive",
                    [
                        "status" =>
                            $health->status === "ok" ? "Healthy" : "Degraded",
                        "runs" =>
                            $health->readiness->checks->background_queues
                                ->active_api_runs,
                    ]
                );
            }
        } catch (\Throwable $e) {
            // Keep transient API errors off the dashboard.
        }

        return parent::getLiveStats("inactive", [
            "status" => "Unavailable",
            "runs" => null,
        ]);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    private function health()
    {
        $key = $this->config->apikey ?? "";
        if ($key === "") {
            return null;
        }

        $response = parent::execute($this->url("health/detailed"), [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . $key,
            ],
            "connect_timeout" => 3,
            "timeout" => 5,
            "allow_redirects" => false,
        ]);
        if ($response === null || $response->getStatusCode() !== 200) {
            return null;
        }

        $health = json_decode((string) $response->getBody());
        $runs =
            $health->readiness->checks->background_queues->active_api_runs ??
            null;
        if (
            ($health->platform ?? null) !== "hermes-agent" ||
            !in_array($health->status ?? null, ["ok", "degraded"], true) ||
            !is_int($runs) ||
            $runs < 0
        ) {
            return null;
        }

        return $health;
    }
}
