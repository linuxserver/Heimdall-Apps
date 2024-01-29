<?php

namespace App\SupportedApps\PaperlessNgx;

class PaperlessNgx extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    function __construct()
    {
    }

    public function getRequestAttrs()
    {
        $apikey = $this->getConfigValue("apikey", null);

        $attrs["headers"] = [
            "Accept" => "application/json",
            "Authorization" => "Token " .  $apikey,
        ];

        return $attrs;
    }


    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }

    public function test()
    {
        $attrs = $this->getRequestAttrs();
        $test = parent::appTest($this->url("documents"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];
        $attrs = $this->getRequestAttrs();

        $documents = json_decode(
            parent::execute($this->url("documents"), $attrs)->getBody()
        );

        $data = [
            "documentCount" => $documents->count ?? 0,
        ];

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url =
            parent::normaliseurl($this->config->url) .
            "api/" .
            $endpoint .
            "/";
        return $api_url;
    }
}
