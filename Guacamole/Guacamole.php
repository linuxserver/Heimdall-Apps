<?php

namespace App\SupportedApps\Guacamole;

class Guacamole extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    private function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }

    private function getToken()
    {
        $username = urlencode($this->config->username);
        $password = urlencode($this->config->password);

        $attrs = [
            "body" => "username=" . $username . "&password=" . $password,
            "headers" => [
                "Content-Type" => "application/x-www-form-urlencoded"
            ],
        ];

        $res = parent::execute($this->url("api/tokens"), $attrs, null, "POST");

        switch ($res->getStatusCode()) {
            case 200:
                $details = json_decode($res->getBody());
                return $details->authToken;
            case 400:
                throw new \Exception("Invalid username format");
            case 401:
            case 403:
                throw new \Exception("Invalid username/password");
            default:
                throw new \Exception("Could not connect to Guacamole");
        }
    }

    private function getConnections($token)
    {
        $dataSource = $this->getConfigValue('dataSource', 'postgresql');

        $url = $this->url("api/session/data/" . $dataSource . "/connections?token=" . $token);
        $res = parent::execute($url);
        if ($res->getStatusCode() == 404) {
            throw new \Exception("Invalid data source " . $token);
        }

        return json_decode($res->getBody());
    }

    public function test()
    {
        try {
            $token = $this->getToken();
            $this->getConnections($token);
            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }

    public function livestats()
    {
        $status = "inactive";

        $token = $this->getToken();
        $details = $this->getConnections($token);

        $data = [];
        if ($details != null) {
            $activeConnections = 0;
            foreach ($details as $item) {
                $activeConnections += $item->activeConnections;
            }

            $data["connections"] = count((array)$details);
            $data["active_connections"] = $activeConnections;
        }
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;

        return $api_url;
    }
}
