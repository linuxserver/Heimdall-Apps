<?php

namespace App\SupportedApps\Jellystat;

class Jellystat extends \App\SupportedApps implements \App\EnhancedApps
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
        $attrs = $this->getRequestAttrs();
        $test = parent::appTest($this->url('stats/getLibraryCardStats'), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $attrs = $this->getRequestAttrs();

        $res = parent::execute($this->url('stats/getAllUserActivity'), $attrs);

        $results = json_decode($res->getBody());

        $details = ["visiblestats" => []];

        $newstat = new \stdClass();
        $newstat->TotalPlays = new \stdClass();
        $newstat->TotalPlays->title = 'Total Plays';

        $newstat->TotalWatchTime = new \stdClass();
        $newstat->TotalWatchTime->title = 'Total Watchtime';

        if (isset($results[0])) {
            $result = $results[0];
            $newstat->TotalPlays->value = $result->TotalPlays;
            $newstat->TotalWatchTime->value = $this->secondsToHoursMinutes($result->TotalWatchTime);
        }

        $details["visiblestats"][] = $newstat;

        return parent::getLiveStats($status, $details);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getRequestAttrs()
    {
        $attrs = [
            "headers" => [

                "accept" => "application/json",
                "x-api-token" => $this->config->x_api_token,
            ],
        ];

        return $attrs;
    }

    private function secondsToHoursMinutes($seconds)
    {

        // Calculate the hours
        $hours = floor($seconds / 3600);

        // Calculate the remaining seconds
        // into minutes
        $minutes = floor(($seconds % 3600) / 60);

        // Return the result as an
        // associative array
        return $hours . 'h ' . $minutes . 'min';
    }
}
