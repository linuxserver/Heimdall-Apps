<?php

namespace App\SupportedApps\N8n;

// use Barryvdh\Debugbar\Facades\Debugbar;
// Debugbar::info($data);

class N8n extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $attrs = $this->getAttrs();
        $test = parent::appTest($this->url("workflows"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "active";
        $data = [];
        $attrs = $this->getAttrs();

        // Fetch workflows
        $workflowsResponse = parent::execute($this->url("workflows"), $attrs);
        $workflows = json_decode($workflowsResponse->getBody());

        $data["workflows"] = count($workflows->data);

        // Fetch active workflows
        $activeResponse = parent::execute($this->url("workflows?active=true"), $attrs);
        $active = json_decode($activeResponse->getBody());

        $data["active"] = count($active->data);

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . "api/v1/" . $endpoint;
        return $api_url;
    }
    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "X-N8N-API-KEY" => $this->config->password
            ],
        ];
    }
}
