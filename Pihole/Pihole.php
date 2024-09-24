<?php

namespace App\SupportedApps\Pihole;

use Illuminate\Support\Facades\Log;

class Pihole extends \App\SupportedApps implements \App\EnhancedApps
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
        $version = $this->config->version;
        if ($version == 5) {
            $test = parent::appTest($this->url("api.php?summaryRaw"));
            echo $test->status;
        }
        if ($version == 6) {
            $test = $this->login();
            if ($test->getStatusCode() === 200 && $test->getBody()) {
                $wk_resp = json_decode($test->getBody());
                echo "Hello " . $wk_resp->data->username;
            } else {
                echo "Failed";
            }
        }
    }
    public function livestats()
    {
        $status = "inactive";
        $version = $this->config->version;
        
        if ($version == 5) {
            $res = parent::execute($this->url("api.php?summaryRaw"));
            $details = json_decode($res->getBody());

            $data = [];

            if ($details) {
                $data["ads_blocked"] = number_format(
                    $details->ads_blocked_today
                );
                $data["ads_percentage"] = number_format(
                    $details->ads_percentage_today,
                    1
                );
                $status = "active";
            }
        }

        if ($version == 6) {
            $auth = $this->login();

            $attrs = [
                "body" => "sid:" . $auth->sid,
                "headers" => [
                    "content-type" => "application/json",
                    "accept" => "application/json",
                ],
            ];
            $result = parent::execute($this->url("api/stats/summary"),$attrs);

            $data["ads_blocked"] = $result->blocked;
            $data["ads_percentage"] = $result->percent_blocked;
        }
        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $version = $this->config->version;
        if ($version == 5){
            $apiKey = $this->config->apikey;
            $api_url = parent::normaliseurl($this->config->url) . $endpoint;

            if ($apiKey) {
                $api_url .= "&auth=" . $apiKey;
            }
        }
        if ($version == 6) {
            $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        }
        return $api_url;
    }
    public function login()
    {
        $attrs = [
            "body" => "password:" . $this->config->apiKey,
            "headers" => [
                "content-type" => "application/json",
                "accept" => "application/json",
            ],
        ];
        return parent::execute(
            $this->url("api/auth"),
            $attrs,
            null,
            "POST"
        );
    }
}
