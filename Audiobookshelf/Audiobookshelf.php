<?php

namespace App\SupportedApps\Audiobookshelf;

class Audiobookshelf extends \App\SupportedApps implements \App\EnhancedApps
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
        $test = parent::appTest($this->url('status'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('api/me/listening-stats'), $this->getAttrs());
        $details = json_decode($res->getBody());
        $data = ['totalTime' => $this->secondsToHoursMinutes($details->totalTime)];
        return parent::getLiveStats($status, $data);
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
                "accept" => "application/json",
                "Authorization" =>
                "Bearer " . $this->config->apikey
            ],
        ];
    }


    private function secondsToHoursMinutes($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $return_seconds = floor($seconds % 60);
        return $hours . 'h ' . $minutes . 'min ' . $return_seconds . 'sec';
    }
}
