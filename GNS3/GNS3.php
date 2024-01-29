<?php

namespace App\SupportedApps\GNS3;

class GNS3 extends \App\SupportedApps implements \App\EnhancedApps // phpcs:ignore
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
        $test = parent::appTest($this->url('/v2/version'), ['auth' => [$this->config->username,
                                                                       $this->config->password]]);
        $details = json_decode($test->response);
        if ($details && isset($details->version)) {
            echo $test->status . "\nServer version: " . $details->version;
        } else {
            echo $test->status;
        }
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('/v2/projects'), ['auth' => [$this->config->username,
                                                                       $this->config->password]]);
        $details = json_decode($res->getBody());

        $data = [];
        $data["opened"] = 0;
        $data["closed"] = 0;

        foreach ($details as $project) {
            if ($project->status == "opened") {
                $data["opened"]++;
            } else {
                $data["closed"]++;
            }
        }

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url, false) . $endpoint;
        return $api_url;
    }
}
