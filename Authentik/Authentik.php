<?php

namespace App\SupportedApps\Authentik;

class Authentik extends \App\SupportedApps implements \App\EnhancedApps
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
                "Authorization" => "Bearer " . $token
            ],
        ];
        $res = parent::execute($this->url($path), $attrs);
        switch ($res->getStatusCode()) {
            case 200:
                return json_decode($res->getBody());
            case 403:
                throw new \Exception("Invalid token");
            default:
                throw new \Exception("Could not connect to Authentik");
        }
    }

    public function test()
    {
        try {
            $this->fetchApi("api/v3/core/applications/");
            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }

    public function livestats()
    {
        $status = "inactive";

        $applicationDetails = $this->fetchApi("api/v3/core/applications/");
        $usersDetails = $this->fetchApi("api/v3/core/users/");
        $internalUsers = $filteredObjects = array_filter($usersDetails->results, function ($user) {
            return isset($user->type) && $user->type === 'internal';
        });

        $data = [
            "applications" => $applicationDetails->pagination->count,
            "users" => count($internalUsers),
        ];

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;

        return $api_url;
    }
}
