<?php

namespace App\SupportedApps\PrusaLink;

use Carbon\CarbonInterval;

class PrusaLink extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/v1/status'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('api/v1/status'), $this->getAttrs());
        $details = json_decode($res->getBody());

        // Check if time_remaining exists and convert it
        if (isset($details->job->time_remaining)) {
            $short_time_remaining = $this->secondsToShortTime($details->job->time_remaining);
        } else {
            $short_time_remaining = "N/A";
        }

        if (isset($details->printer->temp_bed)) {
            $temp_bed = $details->printer->temp_bed . " °C";  // Append "°C"
        } else {
            $temp_bed = "N/A";  // Default value
        }

        if (isset($details->printer->temp_nozzle)) {
            $temp_nozzle = $details->printer->temp_nozzle . " °C";  // Append "°C"
        } else {
            $temp_nozzle = "N/A";  // Default value
        }

        $data = [
            "state" => $details->printer->state ?? "OFFLINE", // Default state as "OFFLINE"
            "short_time_remaining" => $short_time_remaining,
            "temp_nozzle" => $temp_nozzle,
            "temp_bed" => $temp_bed
        ];

        return parent::getLiveStats($status, $data);
    }

    private function secondsToShortTime($seconds) {
        return CarbonInterval::seconds($seconds)
            ->cascade()
            ->forHumans([
                'short' => true,
                'join' => ' ', // Use a space instead of "and" or ","
                'maximumUnit' => 2
            ]);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getAttrs()
    {
        return [
            "headers" => [
                "X-Api-Key" => $this->config->apikey
            ],
        ];
    }
}
