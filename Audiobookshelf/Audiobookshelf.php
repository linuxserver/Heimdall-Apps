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
        #$res = parent::execute($this->url('api/users/root/listening-sessions'),$this->getAttrs(),NULL,"POST");
        #$res = parent::execute($this->url('api/authorize'),$this->getAttrs(),NULL,"POST");
        $res = parent::execute($this->url('api/me/listening-stats'),$this->getAttrs());
        $details = json_decode($res->getBody());

        #echo '<pre>';
       # print_r($res);
       # print_r($details);

        $data = ['totalTime' => $this->secondsToHoursMinutes($details->totalTime)];


       # echo '</pre>';
        return parent::getLiveStats($status, $data);

    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
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


    function secondsToHoursMinutes($seconds) {

        // Calculate the hours
        $hours = floor($seconds / 3600);

        // Calculate the remaining seconds
        // into minutes
        $minutes = floor(($seconds % 3600) / 60);

        $return_seconds = floor($seconds % 60);

        // Return the result as an
        // associative array
        return $hours . 'h ' . $minutes . 'min ' . $return_seconds. 'sec';
    }
}
