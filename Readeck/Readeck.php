<?php

namespace App\SupportedApps\Readeck;

class Readeck extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct() {}

    public function test()
    {
        $test = parent::appTest($this->url("api/contacts"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute(
            $this->url('api/bookmarks?is_archived=false'),
            $this->attrs()
        );

        $details = json_decode($res->getBody());

        $data = [];

        if ($details) {
            $status = 'active';
            $data["bookmarks_count"] = count($details);
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    public function attrs()
    {
        $apikey = $this->config->apikey;
        $attrs = [
            "headers" => [
                "content-type" => "application/json",
                "Authorization" => "Bearer " . $apikey,
            ],
        ];
        return $attrs;
    }
}
