<?php

namespace App\SupportedApps\RomM;

class RomM extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/stats'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $res = parent::execute($this->url('api/stats'));
        $result = json_decode($res->getBody());
        $details = ["visiblestats" => []];
        foreach ($this->config->availablestats as $stat) {
            $newstat = new \stdClass();
            $newstat->title = self::getAvailableStats()[$stat];
            $newstat->value = $result->{$stat};
            $details["visiblestats"][] = $newstat;
        }
        return parent::getLiveStats($status, $details);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    public static function getAvailableStats()
    {
        return [
            "PLATFORMS" => "Platforms",
            "ROMS" => "Total ROMs",
            "SAVES" => "Saves",
            "STATES" => "States",
            "SCREENSHOTS" => "Screenshots",
            "FILESIZE" => "Total Filesize",
        ];
    }
}
