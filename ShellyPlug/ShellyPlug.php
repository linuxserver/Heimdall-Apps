<?php

namespace App\SupportedApps\ShellyPlug;

class ShellyPlug extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    function __construct()
    {
    }

    public static function getAvailableStats()
    {
        return [
            "cloud" => "Cloud",
            "mqtt" => "MQTT",
            "ws" => "Websocket",
            "sysUptime" => "Uptime",
            "state" => "State",
            "power" => "Watts",
            "tempC" => "Temperature in °C",
            "tempF" => "Temperature in °F",
        ];
    }

    private static function toTime($timestamp)
    {
        $hours = floor($timestamp / 3600);
        $minutes = floor($timestamp % 3600 / 60);
        $seconds = $timestamp % 60;

        $hourDuration = sprintf('%02dh', $hours);
        $minDuration =  sprintf('%02dm', $minutes);
        $secDuration =  sprintf('%02ds', $seconds);
        $HourMinSec = $hourDuration . $minDuration . $secDuration;

        if ($hourDuration > 0) {
            $hourDuration = $hourDuration;
        } else {
            $hourDuration = '00h';
        }

        if ($minDuration > 0) {
            $minDuration = $minDuration;
        } else {
            $minDuration = '00m';
        }

        if ($secDuration > 0) {
            $secDuration = $secDuration;
        } else {
            $secDuration = '00s';
        }

        $HourMinSec = $hourDuration . $minDuration . $secDuration;

        return $HourMinSec;
    }

    private static function formatValueUsingStat($stat, $value)
    {
        if (!isset($value)) {
            return "N/A";
        }

        switch ($stat) {
            case "cloud":
            case "mqtt":
            case "ws":
                return (bool)$value ? "Connected" : "Disconnected";
            case "sysUptime":
                return self::toTime((int)$value);
            case "state":
                return (bool)$value ? "On" : "Off";
            case "power":
                return "{$value} W";
            case "tempC":
                return "{$value} °C";
            case "tempF":
                return "{$value} °F";
            default:
                return "{$value}";
        }
    }

    public function test()
    {
        $test = parent::appTest(
            $this->url("Shelly.GetStatus")
        );
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('Shelly.GetStatus'));
        $details = json_decode($res->getBody());
        $res = parent::execute($this->url('Switch.GetStatus?id=0'));
        $switch = json_decode($res->getBody());

        $data = ["visiblestats" => []];
        if ($details) {
            foreach ($this->config->availablestats as $stat) {
                if (!isset(self::getAvailableStats()[$stat])) {
                    continue;
                }

                $newstat = new \stdClass();

                switch ($stat) {
                    case "cloud":
                        $newstat->title = self::getAvailableStats()[$stat];
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $details->cloud->connected
                        );
                        break;
                    case "mqtt":
                        $newstat->title = self::getAvailableStats()[$stat];
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $details->mqtt->connected
                        );
                        break;
                    case "ws":
                        $newstat->title = self::getAvailableStats()[$stat];
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $details->ws->connected
                        );
                        break;
                    case "sysUptime":
                        $newstat->title = self::getAvailableStats()[$stat];
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $details->sys->uptime
                        );
                        break;
                    case "state":
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $switch->output
                        );
                        break;
                    case "power":
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $switch->apower
                        );
                        break;
                    case "tempC":
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $switch->temperature->tC
                        );
                        break;
                    case "tempF":
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $switch->temperature->tF
                        );
                        break;
                }
                $data["visiblestats"][] = $newstat;
            }
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . "rpc/" . $endpoint;
        return $api_url;
    }
}
