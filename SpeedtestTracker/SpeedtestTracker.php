<?php

namespace App\SupportedApps\SpeedtestTracker;

use DateTimeZone;

class SpeedtestTracker extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function getRequestAttrs()
    {
        $api_token = $this->config->apikey;

        $attrs = [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . $api_token,
            ],
        ];

        return $attrs;
    }

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $attrs = $this->getRequestAttrs();
        $test = parent::appTest($this->url("api/v1/results/latest/"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $attrs = $this->getRequestAttrs();
        $res = parent::execute($this->url("api/v1/results/latest/"), $attrs);
        $details = json_decode($res->getBody());

        $data = [];

        if ($details) {
            $data["ping"] = number_format($details->data->ping);
            $data["download"] = number_format($details->data->download, 1);
            $data["upload"] = number_format($details->data->upload, 1);
            foreach ($this->config->availablestats as $stat) {
                if (!isset(self::getAvailableStats()[$stat])) {
                    continue;
                }

                $newstat = new \stdClass();
                $newstat->title = self::getAvailableStats()[$stat];
                $newstat->value = self::formatUsingStat(
                    $stat,
                    $details->data->{$stat}
                );

                $data["visiblestats"][] = $newstat;
            }
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    public static function getAvailableStats()
    {
        return [
            "ping" => "Ping",
            "download" => "Down",
            "upload" => "Up",
            "created_at" => "Time",
        ];
    }

    private static function formatUsingStat($stat, $number)
    {
        if (!isset($number)) {
            return "N/A";
        }

        switch ($stat) {
            case "download":
            case "upload":
                return number_format($number * 8 / 1000000) . "<span>Mbit/s</span>";
            case "ping":
                return number_format($number) . "<span>ms</span>";
            case "created_at":
                return (new \DateTime($number))->format("H:i");
            default:
                return $number;
        }
    }
}
