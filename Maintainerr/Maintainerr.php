<?php

namespace App\SupportedApps\Maintainerr;

class Maintainerr extends \App\SupportedApps implements \App\EnhancedApps
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
        $test = parent::appTest($this->url(""));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];

        $rules = json_decode(parent::execute($this->url("api/rules"))->getBody());
        $activeRules = $filteredObjects = array_filter($rules, function ($rule) {
            return isset($rule->isActive) && $rule->isActive;
        });

        $data["rules"] = count($rules);
        $data["activeRules"] = count($activeRules);

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
