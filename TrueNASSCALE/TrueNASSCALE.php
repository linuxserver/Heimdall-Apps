<?php

namespace App\SupportedApps\TrueNASSCALE;

use App\SupportedApps\TrueNASCORE\TrueNASApiTrait;

class TrueNASSCALE extends \App\SupportedApps implements \App\EnhancedApps
{
    use TrueNASApiTrait;

    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $test = $this->testApi();
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];

        try {
            $systemInfo = $this->apiCall('system.info');
            $seconds = $systemInfo['uptime_seconds'] ?? 0;
            $data["uptime"] = $this->uptime($seconds);

            $alerts = $this->apiCall('alert.list');
            list($data["alert_tot"], $data["alert_crit"]) = $this->alerts($alerts);
        } catch (\Exception $e) {
            $data["uptime"] = "Error";
            $data["alert_tot"] = "?";
            $data["alert_crit"] = "?";
        } finally {
            $this->disconnectWebSocket();
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url =
            parent::normaliseurl($this->config->url) . "api/v2.0/" . $endpoint;
        return $api_url;
    }

    public function attrs()
    {
        $ignoreTls = $this->getConfigValue("ignore_tls", false);
        $apikey = $this->config->apikey;
        $attrs["headers"] = [
            "content-type" => "application/json",
            "Authorization" => "Bearer " . $apikey,
        ];

        if ($ignoreTls) {
            $attrs["verify"] = false;
        }

        return $attrs;
    }

    public function uptime($inputSeconds)
    {
        $res = "";
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        $days = floor($inputSeconds / $secondsInADay);

        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        if ($days > 0) {
            $res =
                strval($days) .
                "d " .
                strval($hours) .
                ":" .
                sprintf("%02d", $minutes);
        } else {
            $res =
                strval($hours) .
                ":" .
                sprintf("%02d", $minutes) .
                ":" .
                sprintf("%02d", $seconds);
        }
        return $res;
    }

    public function alerts($alert)
    {
        $count_total = $count_critical = 0;
        foreach ($alert as $key => $value) {
            if ($value["dismissed"] == false) {
                $count_total += 1;
                if (!in_array($value["level"], array("NOTICE", "INFO"))) {
                    $count_critical += 1;
                }
            }
        }

        return array(strval($count_total), strval($count_critical));
    }

    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }
}
