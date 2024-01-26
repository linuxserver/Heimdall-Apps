<?php namespace App\SupportedApps\Jellyseerr;

class Jellyseerr extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $attrs = $this->getRequestAttrs();
        $test = parent::appTest($this->url("auth/me"), $attrs);

        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];
        $attrs = $this->getRequestAttrs();
        $requestsType = $this->getConfigValue("requests", "pending");
        $requestsCount = json_decode(
            parent::execute($this->url("request/count"), $attrs)->getBody()
        );
        $issuesType = $this->getConfigValue("issues", "open");
        $issuesCount = json_decode(
            parent::execute($this->url("issue/count"), $attrs)->getBody()
        );

        if ($requestsCount || $issuesCount)
        {
            $data["requests"] = $requestsCount->$requestsType ?? 0;
            $data["issues"] = $issuesCount->$issuesType ?? 0;
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url =
            parent::normaliseurl($this->config->url) .
            "api/v1/" .
            $endpoint;

        return $api_url;
    }

    public function getRequestAttrs()
    {
        $attrs["headers"] = [
            "accept" => "application/json",
            "X-Api-Key" => $this->config->apikey,
        ];

        return $attrs;
    }

    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }
}
