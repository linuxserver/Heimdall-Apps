<?php

namespace App\SupportedApps\ChannelsDVR;

function preg_grep_keys($pattern, $input)
{
    return preg_grep($pattern, array_keys($input));
}

class ChannelsDVR extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $test = parent::appTest($this->url("dvr"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $res = parent::execute($this->url("dvr"));
        $details = json_decode($res->getBody(), true);

        $data = [];

        $activity = $details["activity"];
        $recordings = count(preg_grep_keys("/^0-job-/", $activity));
        $streams = count(preg_grep_keys("/^6-stream-/", $activity));
        $data["recordings"] = number_format($recordings);
        $data["streams"] = number_format($streams);
        $status = "active";

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
