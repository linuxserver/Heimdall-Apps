<?php

namespace App\SupportedApps\FileBrowser;

class FileBrowser extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    private function getToken()
    {
        $username = $this->config->username;
        $password = $this->config->password;
        $authHeader = $this->config->authHeader;

        $attrs = [
            "headers" => [
                "Content-Type" => "application/json"
            ],
            "body" => json_encode([
                "username" => $username,
                "password" => $password
            ])
        ];
        if ($authHeader !== null) {
            $attrs["headers"][$authHeader] = $username;
        }

        $res = parent::execute($this->url("api/login"), $attrs, null, "POST");

        switch ($res->getStatusCode()) {
            case 200:
                return $res->getBody()->getContents();
            case 403:
                throw new \Exception("Invalid username/password");
            default:
                throw new \Exception("Could not connect to FileBrowser");
        }
    }

    public function test()
    {
        try {
            $token = $this->getToken();
            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }

    public function livestats()
    {
        $status = "inactive";

        $token = $this->getToken();
        $attrs = [
            "headers" => [
                "X-AUTH" => $token
            ],
        ];
        $res = parent::execute($this->url("api/usage"), $attrs);
        $details = json_decode($res->getBody());

        if ($details != null) {
            $data["used"] = number_format($details->used / 1073741824, 0) . " GiB";
            $data["total"] =number_format($details->total / 1073741824, 0) . " GiB";
        }
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;

        return $api_url;
    }
}
