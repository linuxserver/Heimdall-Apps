<?php

namespace App\SupportedApps\Audiobookshelf;

class Audiobookshelf extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct() {
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/me'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('api/me/listening-stats'), $this->getAttrs());
        $details = json_decode($res->getBody());

        if ($details->totalTime > 0) {
            $status = 'active';
            $data = [
                'totalTime' => $this->secondsToHoursMinutes($details->totalTime),
                'bookCount' => $this->getBookCount()
            ];
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getBookCount()
    {
        $res = parent::execute($this->url('api/libraries'), $this->getAttrs());
        $libraries = json_decode($res->getBody())->libraries;

        $bookCount = 0;
        foreach ($libraries as $library) {
            $lib_id = $library->id;
            $res = parent::execute($this->url('api/libraries/' . $lib_id . '/items'), $this->getAttrs());
            $library_details = json_decode($res->getBody());
            $bookCount += $library_details->total;
        }

        return $bookCount;
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
        return $hours . 'h ' . $minutes . 'm';
    }
}
