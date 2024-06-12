<?php

namespace App\SupportedApps\Threadfin;

class Threadfin extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    private function getStatus()
    {
        $attrs = [
            "headers" => [
                "Content-Type" => "application/json"
            ],
            "body" => json_encode([
                "cmd" => "status"
            ])
        ];

        $res = parent::execute($this->url("api/"), $attrs);

        switch ($res->getStatusCode()) {
            case 200:
                return json_decode($res->getBody());
            case 400:
                throw new \Exception("Bad command");
            default:
                throw new \Exception("Could not connect to Threadfin");
        }
    }

    public function test()
    {
        try {
            $res = $this->getStatus();
            if($res->status == true) {
                echo "Successfully communicated with the API";
            } else {
                echo "Threadfin is not in a good health";
            }
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];

        $res = $this->getStatus();

        $data["activeChannels"] = $res->{"streams.active"};
        $data["totalChannels"] = $res->{"streams.all"};

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
