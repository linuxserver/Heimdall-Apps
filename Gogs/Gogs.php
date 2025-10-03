<?php

namespace App\SupportedApps\Gogs;

class Gogs extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    private function fetchApi($path) {
        $token = $this->config->apikey;
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "token " . $token
            ],
        ];
        $res = parent::execute($this->url($path), $attrs);
        switch ($res->getStatusCode()) {
            case 200:
                return json_decode($res->getBody());
            case 401:
            case 403:
                throw new \Exception("Invalid token");
            default:
                throw new \Exception("Could not connect to Gogs");
        }
    }

    public function test()
    {
        try {
            $this->fetchApi("api/v1/user/repos");
            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }

    public function livestats()
    {
        $status = "inactive";

        $reposDetails = $this->fetchApi("api/v1/user/repos");
        $orgsDetails = $this->fetchApi("api/v1/user/orgs");

        $data = [
            "repositories" => count($reposDetails),
            "organizations" => count($orgsDetails),
        ];
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;

        return $api_url;
    }
}
