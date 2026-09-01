<?php

namespace App\SupportedApps\Slskd;

class Slskd extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->url("api/v0/application"), $this->attrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [
            "health" => "Offline",
            "downloads" => 0,
            "uploads" => 0,
            "down_rate" => "0 B/s",
            "up_rate" => "0 B/s",
        ];

        $application = $this->request("api/v0/application");
        if (!is_object($application) || !isset($application->server)) {
            return parent::getLiveStats($status, $data);
        }

        $connected = (bool) ($application->server->isConnected ?? false);
        $loggedIn = (bool) ($application->server->isLoggedIn ?? false);
        $data["health"] = $connected && $loggedIn ? "Online" : "Degraded";
        $status = $connected ? "active" : "inactive";

        [$data["downloads"], $downloadRate] = $this->activeTransfers(
            $this->request("api/v0/transfers/downloads")
        );
        [$data["uploads"], $uploadRate] = $this->activeTransfers(
            $this->request("api/v0/transfers/uploads")
        );
        $data["down_rate"] = $this->formatRate($downloadRate);
        $data["up_rate"] = $this->formatRate($uploadRate);

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
                "X-API-Key" => $this->getSecretConfigValue("apikey"),
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

    private function activeTransfers($users)
    {
        if (!is_array($users)) {
            return [0, 0];
        }

        $count = 0;
        $rate = 0;
        foreach ($users as $user) {
            foreach (($user->directories ?? []) as $directory) {
                foreach (($directory->files ?? []) as $file) {
                    if (0 !== strcasecmp((string) ($file->state ?? ""), "InProgress")) {
                        continue;
                    }
                    $count++;
                    $rate += max(0, (float) ($file->averageSpeed ?? 0));
                }
            }
        }

        return [$count, $rate];
    }

    private function formatRate($bytes)
    {
        $units = ["B/s", "KB/s", "MB/s", "GB/s", "TB/s"];
        $value = max(0, (float) $bytes);
        $unit = 0;
        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        $precision = $value >= 100 || 0 === $unit ? 0 : 1;
        return number_format($value, $precision) . " " . $units[$unit];
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
