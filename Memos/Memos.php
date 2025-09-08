<?php

namespace App\SupportedApps\Memos;

class Memos extends \App\SupportedApps implements \App\EnhancedApps
{

    public $config;

    public function __construct() {}

    public function test()
    {
        $test = parent::appTest(
            $this->url('api/v1/auth/sessions/current'),
            $this->attrs()
        );
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute(
            $this->url('api/v1/memos'),
            $this->attrs()
        );
        $details = json_decode($res->getBody());

        $data = [];

        if ($details) {
            $status = 'active';
            $data['memo_count'] = count($details->memos);
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
        $access_token = $this->config->access_token;
        $attrs = [
            "headers" => [
                "content-type" => "application/json",
                "Authorization" => "Bearer " . $access_token,
            ],
        ];
        return $attrs;
    }
}
