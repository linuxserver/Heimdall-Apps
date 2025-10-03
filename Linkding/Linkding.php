<?php

namespace App\SupportedApps\Linkding;

class Linkding extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct() {
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/bookmarks?limit=1'), $this->getHeaders());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('api/bookmarks?limit=1000'), $this->getHeaders());
        $details = json_decode($res->getBody());

        $data = [];
        if ($details) {
            $status = 'active';
            $data = [
                "bookmark_count" => $details->count,
            ];
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getHeaders()
    {
        return [
            "headers" => [
                "Authorization" => "Token " . $this->config->access_token,
            ],
        ];
    }
}
