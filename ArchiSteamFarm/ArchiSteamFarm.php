<?php

namespace App\SupportedApps\ArchiSteamFarm;

use Carbon\Carbon;

class ArchiSteamFarm extends \App\SupportedApps implements \App\EnhancedApps
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
        if (!isset($this->config->password)) {
            echo "Invalid password";
            return;
        }

        $test = parent::appTest(
            $this->url("status?password=" . urlencode($this->config->password))
        );
        echo $test->status;
    }

    public function livestats()
    {
        if (!isset($this->config->password)) {
            return parent::getLiveStats("Inactive", []);
        }
        $status = "inactive";
        $res = parent::execute(
            $this->url("api/bot/asf?password=" . urlencode($this->config->password))
        );
        $details = json_decode($res->getBody());

        $totalSecondsLeft = 0;
        $cardToFarmLeft = 0;
        foreach ($details->Result as $bot) {
            foreach ($bot->CardsFarmer->GamesToFarm as $game) {
                $cardToFarmLeft += $game->CardsRemaining;
            }
            if (
                preg_match(
                    "@([0-9].*).([0-9]+):([0-9]+):([0-9]+)@",
                    $bot->CardsFarmer->TimeRemaining,
                    $matches
                )
            ) {
                $totalSecondsLeft += $matches[1] * 24 * 60 * 60; // Days
                $totalSecondsLeft += $matches[2] * 60 * 60; // Hours
                $totalSecondsLeft += $matches[3] * 60; // Minutes
                $totalSecondsLeft += $matches[4]; // Seconds
            } elseif (
                preg_match(
                    "@([0-9].*):([0-9]+):([0-9]+)@",
                    $bot->CardsFarmer->TimeRemaining,
                    $matches
                )
            ) {
                $totalSecondsLeft += $matches[1] * 60 * 60; // Hours
                $totalSecondsLeft += $matches[2] * 60; // Minutes
                $totalSecondsLeft += $matches[3]; // Seconds
            }
        }

        $d = Carbon::now();
        $d->addSeconds($totalSecondsLeft);
        $data = [
            "time_left" => $d->diffForHumans(null, true, true, 3),
            "cards_left" => $cardToFarmLeft,
        ];
        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
