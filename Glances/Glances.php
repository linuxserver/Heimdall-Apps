<?php

namespace App\SupportedApps\Glances;

class Glances extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $test = parent::appTest($this->url("status"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $details = [];

        if (isset($this->config->availablestats) && is_array($this->config->availablestats)) {
            $details = ["visiblestats" => []];
            foreach ($this->config->availablestats as $stat) {
                $newstat = new \stdClass();
                $availableStats = self::getAvailableStats();

                if (isset($availableStats[$stat])) {
                    $newstat->title = $availableStats[$stat];

                    // Fetch CpuTotal
                    if ($stat === "CpuTotal") {
                        $Response = parent::execute($this->url("cpu/total"));
                        $result = json_decode($Response->getBody());
                        if (isset($result->total)) {
                            $newstat->value = $result->total;
                        } else {
                            $newstat->value = null; // or some default value
                        }
                    }

                    // Fetch MemTotal
                    if ($stat === "MemTotal") {
                        $Response = parent::execute($this->url("mem/total"));
                        $result = json_decode($Response->getBody());
                        if (isset($result->total)) {
                            $newstat->value = $this->convertBytesToGigabytes($result->total);
                        } else {
                            $newstat->value = null; // or some default value
                        }
                    }

                    // Fetch MemAvail
                    if ($stat === "MemAvail") {
                        $Response = parent::execute($this->url("mem/available"));
                        $result = json_decode($Response->getBody());
                        if (isset($result->available)) {
                            $newstat->value = $this->convertBytesToGigabytes($result->available);
                        } else {
                            $newstat->value = null; // or some default value
                        }
                    }

                    // Fetch MemAvail
                    if ($stat === "MemUsage") {
                        $Response = parent::execute($this->url("mem/used"));
                        $result = json_decode($Response->getBody());
                        if (isset($result->used)) {
                            $newstat->value = $this->convertBytesToGigabytes($result->used);
                        } else {
                            $newstat->value = null; // or some default value
                        }
                    }

                    $details["visiblestats"][] = $newstat;
                }
            }
        }

        return parent::getLiveStats($status, $details);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . "api/4/" . $endpoint;
        return $api_url;
    }

    public static function getAvailableStats()
    {
        return [
            "CpuTotal" => "CpuTotal",
            "MemTotal" => "MemTotal",
            "MemAvail" => "MemAvail",
            "MemUsage" => "MemUsage",
        ];
    }

    private function convertBytesToGigabytes($bytes)
    {
        $gigabytes = $bytes / (1024 ** 3); // Converts bytes to gigabytes
        return round($gigabytes, 2) . ' GB'; // Rounds to 4 significant digits
    }
}
