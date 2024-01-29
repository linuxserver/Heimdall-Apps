<?php

namespace App\SupportedApps\Clash;

class Clash extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $attrs = $this->getRequestAttrs();
        $test = parent::appTest($this->url("version"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "active";
        $attrs = $this->getRequestAttrs();

        // Outbound mode
        $res = $this->apiCall("configs");
        $outbound_mode = ucfirst($res->mode);

        // Selector value
        $sel_name = $this->getConfigValue("sel_name", "");
        $res = $this->apiCall("proxies");
        $sel_value =
            isset($res) &&
            isset($res->proxies) &&
            isset($res->proxies->{$sel_name})
                ? $res->proxies->{$sel_name}->now
                : "";

        $data = [
            "outbound_mode" => $outbound_mode,
            "sel_name" => $sel_name,
            "sel_value" => $sel_value,
        ];

        return parent::getLiveStats($status, $data);
    }

    // Utils

    public function getRequestAttrs()
    {
        $token_value = $this->getConfigValue("password", "");
        $auth = "Bearer " . $token_value;

        $attrs["headers"] = [
            "Accept" => "application/json",
            "Authorization" => $auth,
        ];

        return $attrs;
    }

    public function apiCall($endpoint)
    {
        $res = parent::execute($this->url($endpoint), $this->getRequestAttrs());
        return json_decode($res->getBody());
    }

    public function url($endpoint)
    {
        $api_url =
            parent::normaliseurl(
                $this->getConfigValue("override_url", $this->config->url)
            ) . $endpoint;
        return $api_url;
    }

    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }
}
