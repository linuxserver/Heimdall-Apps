<?php

namespace App\SupportedApps\Gitea;

class Gitea extends \App\SupportedApps implements \App\EnhancedApps // phpcs:ignore
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
                $data = json_decode($res->getBody());
                if (gettype($data) == "array") {
                    return $data;
                }
                if (property_exists($data, "data")) {
                    return $data->data;
                }
                throw new Exception("Invalid response");
            case 401:
            case 403:
                throw new \Exception("Invalid token");
            default:
                throw new \Exception("Could not connect to Gitea");
        }
    }

    public function test()
    {
        try {
            $this->fetchApi("api/v1/users/search");
            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }

    public function livestats()
    {
        $status = "inactive";

        $reposDetails = $this->fetchApi("api/v1/repos/search");
        $orgsDetails = $this->fetchApi("api/v1/orgs");
        $usersDetails = $this->fetchApi("api/v1/users/search");

        $data = [
            "Repos" => count($reposDetails),
            "Orgs" => count($orgsDetails),
            "Users" => count($usersDetails),
        ];

        foreach ($this->config->availablestats as $stat) {
            $newstat = new \stdClass();
            $newstat->title = self::getAvailableStats()[$stat];
            $newstat->value = $data[$stat];
            $data["visiblestats"][] = $newstat;
        }
        $status = "active";
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;

        return $api_url;
    }

    public static function getAvailableStats()
    {
        return [
            "Repos" => "Repos",
            "Orgs" => "Orgs",
            "Users" => "Users",
        ];
    }
}
