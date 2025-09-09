<?php

namespace App\SupportedApps\MySpeed;

class MySpeed extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct() {}

    public function test()
    {
        $test = parent::appTest($this->url('api/info/version'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('api/speedtests/statistics'));
        $details = json_decode($res->getBody());

        $data = [];

        if ($details) {
            $status = 'active';
            $data = [
                'avg_down' => floor($details->download->avg),
                'avg_up' => floor($details->upload->avg)

            ];
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
