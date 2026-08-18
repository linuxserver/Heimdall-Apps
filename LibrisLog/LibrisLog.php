<?php

namespace App\SupportedApps\LibrisLog;

class LibrisLog extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $test = parent::appTest(
            $this->url("api/books/stats"),
            $this->getAttrs()
        );
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $res = parent::execute($this->url("api/books/stats"), $this->getAttrs());
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

    private function getAttrs()
    {
        return [
            "headers" => [
                "X-API-Key" => $this->config->password,
            ],
        ];
    }

    public static function getAvailableStats()
    {
        return [
            "books_read" => "Read",
            "books_reading" => "Reading",
            "books_want_to_read" => "Want to read",
            "total_books" => "Total",
        ];
    }
}
