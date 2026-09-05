<?php

namespace App\SupportedApps\Unmanic;

class Unmanic extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    protected $method = "POST";

    public function test()
    {
        $test = parent::appTest($this->url("api/v2/pending/tasks"), $this->postAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [
            "pending" => 0,
            "workers" => 0,
            "completed" => 0,
        ];

        $pending = $this->request("api/v2/pending/tasks", "POST", $this->postAttrs());
        if (!is_object($pending) || !isset($pending->recordsTotal)) {
            return parent::getLiveStats($status, $data);
        }
        $data["pending"] = (int) $pending->recordsTotal;
        $status = "active";

        $workers = $this->request("api/v2/workers/status", "GET");
        if (is_object($workers) && is_array($workers->workers_status ?? null)) {
            $data["workers"] = count($workers->workers_status);
        }

        $history = $this->request("api/v2/history/tasks", "POST", $this->postAttrs());
        if (is_object($history) && isset($history->recordsTotal)) {
            $data["completed"] = (int) $history->recordsTotal;
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    private function postAttrs()
    {
        return [
            "headers" => ["Accept" => "application/json"],
            "json" => new \stdClass(),
        ];
    }

    private function request($endpoint, $method, $attrs = [])
    {
        $response = parent::execute($this->url($endpoint), $attrs, null, $method);
        if (null === $response || 200 !== $response->getStatusCode()) {
            return null;
        }

        return json_decode($response->getBody());
    }
}
