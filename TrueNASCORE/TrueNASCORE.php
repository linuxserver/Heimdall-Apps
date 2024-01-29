<?php

namespace App\SupportedApps\TrueNASCORE;

class TrueNASCORE extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url("core/ping"), $this->attrs());
        if ($test->code === 200) {
            $data = $test->response;
            if ($test->response != '"pong"') {
                $test->status = "Failed: " . $data;
            }
        }
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];

        $res = parent::execute($this->url("system/info"), $this->attrs());
        $details = json_decode($res->getBody());
        $seconds = $details->uptime_seconds ?? 0;
        $data["uptime"] = $this->uptime($seconds);

        $res = parent::execute($this->url("alert/list"), $this->attrs());
        $details = json_decode($res->getBody(), true);
        list($data["alert_tot"], $data["alert_crit"]) = $this->alerts($details);

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
        // Adapted from https://stackoverflow.com/questions/8273804/convert-seconds-into-days-hours-minutes-and-seconds

        $res = "";
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        // extract days
        $days = floor($inputSeconds / $secondsInADay);

        // extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        //$res = strval($days).'d '.strval($hours).':'.sprintf('%02d', $minutes).':'.sprintf('%02d', $seconds);
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
