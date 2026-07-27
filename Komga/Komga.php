<?php

namespace App\SupportedApps\Komga;

class Komga extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function getHeaders()
    {
        $username = $this->config->username;
        $password = $this->config->password;

        $attrs["headers"] = [
            "Authorization" =>
                "Basic " . base64_encode($username . ":" . $password),
            "Accept" => "application/json",
        ];
        return $attrs;
    }

    public function test()
    {
        $test = parent::appTest(
            $this->url("api/v2/users/me"),
            $this->getHeaders()
        );
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = ["visiblestats" => []];

        $stats = $this->config->availablestats ?? ["series", "books"];

        foreach ($stats as $stat) {
            if (!isset(self::getAvailableStats()[$stat])) {
                continue;
            }

            $res = parent::execute(
                $this->url("api/v1/" . $stat . "?size=1"),
                $this->getHeaders()
            );

            $details = $res ? json_decode($res->getBody()) : null;

            if (!isset($details->totalElements)) {
                continue;
            }

            $status = "active";

            $newstat = new \stdClass();
            $newstat->title = self::getAvailableStats()[$stat];
            $newstat->value = number_format($details->totalElements);

            $data["visiblestats"][] = $newstat;
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
            "series" => "Series",
            "books" => "Books",
            "collections" => "Collections",
        ];
    }
}
