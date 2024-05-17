<?php

namespace App\SupportedApps\Jellystat;

class Jellystat extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test() {
        $attrs = $this->getRequestAttrs(false);
        $test = parent::appTest($this->url('stats/getLibraryCardStats'), $attrs);
        echo $test->status;
    }

    public function livestats() {
        $debug = $this->config->debug[0];

        $status = 'inactive';
        $attrs = $this->getRequestAttrs($debug);

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
    /* public function livestats() {
        $debug = $this->config->debug[0];

        $status = 'inactive';
        $attrs = $this->getRequestAttrs($debug);

        $res = parent::execute($this->url('stats/getLibraryCardStats'), $attrs);

        $results = json_decode($res->getBody());



        if ($debug == 1) {


            echo '<pre>';
            print_r(($this->config));
            print_r(($results));
            echo '</pre>';
        }

        $details = ["visiblestats" => []];

        foreach ($results as $lib) {
            if ($lib->Plays > 0)
                $sorted_lib[$lib->CollectionType] = $lib;
        }



        foreach ($this->config->availablestats as $stat) {
            $newstat = new \stdClass();
            $newstat->title = self::getAvailableStats()[$stat];
            $newstat->value = $sorted_lib[$stat]->ItemName;
            $details["visiblestats"][] = $newstat;
        }




        return parent::getLiveStats($status, $details);
    }
 */
    public function url($endpoint) {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getRequestAttrs($debug) {
        $attrs = [
            "headers" => [

                "accept" => "application/json",
                "x-api-token" => $this->config->x_api_token,
            ],
        ];

        $attrs['debug'] = $debug;
        return $attrs;
    }

    public static function getAvailableStats() {
        return [
            "movies" => "Last Movie",
            "tvshows" => "Last TV-Show",
            "mixed" => "Others"
        ];
    }

    public static function getDebugStatus() {
        return [
            "1" => "On",
            "0" => "Off"
        ];
    }


    function secondsToHoursMinutes($seconds) {

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
