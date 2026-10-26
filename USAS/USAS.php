<?php

namespace App\SupportedApps\USAS;

class USAS extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // What the tile may show, short enough for its three columns. Anything else in the answer is
    // ignored, so a spoofed answer can't put markup or other text on the dashboard.
    private const PLAYBOOKS = [
        "build-installer" => "ISO",
        "provision" => "Install",
        "baseline" => "Baseline",
        "site" => "Site",
        "keys" => "Keys",
    ];
    private const RESULTS = [
        "succeeded" => "OK",
        "failed" => "Failed",
        "stopped" => "Stopped",
        "interrupted" => "Stopped",
    ];

    public function __construct()
    {
    }

    public function test()
    {
        $test = parent::appTest($this->url("api/status"), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [
            "run_title" => "Run",
            "run_value" => "-",
            "vms" => "-",
            "pending" => "-",
        ];

        // GET /api/status (docs/interfaces.md#the-status-api in the USAS repository). execute() returns
        // null when the connection fails and never throws for an error status.
        $res = parent::execute($this->url("api/status"), $this->getAttrs());
        if ($res === null || $res->getStatusCode() !== 200) {
            return parent::getLiveStats($status, $data);
        }
        $body = json_decode($res->getBody());
        if (!is_object($body) || ($body->schema ?? null) !== 1) {
            return parent::getLiveStats($status, $data);
        }

        $running = $body->running ?? null;
        $playbook = is_object($running) && is_string($running->playbook ?? null) ? $running->playbook : "";
        if (isset(self::PLAYBOOKS[$playbook])) {
            // Heimdall asks every 5 seconds while the status is active, every 30 otherwise.
            $status = "active";
            $data["run_title"] = "Now";
            $data["run_value"] = self::PLAYBOOKS[$playbook];
        } else {
            $last = $body->last_run ?? null;
            $result = is_object($last) && is_string($last->status ?? null) ? $last->status : "";
            $data["run_value"] = is_object($last) ? (self::RESULTS[$result] ?? "-") : "None";
        }

        $vms = $body->vms ?? null;
        if (is_object($vms) && is_int($vms->running ?? null) && is_int($vms->total ?? null)) {
            $data["vms"] = $vms->running . "/" . $vms->total;
        }
        $servers = $body->servers ?? null;
        if (is_object($servers) && is_int($servers->pending ?? null)) {
            $data["pending"] = (string) $servers->pending;
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    private function getAttrs()
    {
        // The status token, made in USAS on the Account page. It goes only in this header, never in the URL.
        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . trim($this->config->password ?? ""),
            ],
        ];
    }
}
