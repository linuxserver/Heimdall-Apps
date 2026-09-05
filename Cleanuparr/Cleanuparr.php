<?php

namespace App\SupportedApps\Cleanuparr;

class Cleanuparr extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->url("api/status"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [
            "version" => "N/A",
            "uptime" => "N/A",
            "memory" => "N/A",
            "managers" => 0,
        ];

        $response = parent::execute($this->url("api/status"));
        if (null === $response || 200 !== $response->getStatusCode()) {
            return parent::getLiveStats($status, $data);
        }

        $details = json_decode($response->getBody());
        if (!is_object($details) || !isset($details->application)) {
            return parent::getLiveStats($status, $data);
        }

        $application = $details->application;
        $data["version"] = (string) ($application->version ?? "N/A");
        $data["uptime"] = $this->formatUptime((string) ($application->upTime ?? ""));
        if (isset($application->memoryUsageMB) && is_numeric($application->memoryUsageMB)) {
            $data["memory"] = number_format((float) $application->memoryUsageMB, 1) . " MB";
        }

        foreach (($details->mediaManagers ?? new \stdClass()) as $manager) {
            $data["managers"] += (int) ($manager->instanceCount ?? 0);
        }
        $status = "active";

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    private function formatUptime($uptime)
    {
        if (preg_match('/^(?:(\d+)\.)?(\d+):(\d+):/', $uptime, $matches)) {
            $days = (int) ($matches[1] ?? 0);
            $hours = (int) $matches[2];
            $minutes = (int) $matches[3];
            if ($days > 0) {
                return $days . "d " . $hours . "h";
            }
            if ($hours > 0) {
                return $hours . "h " . $minutes . "m";
            }
            return $minutes . "m";
        }

        return $uptime !== "" ? $uptime : "N/A";
    }
}
