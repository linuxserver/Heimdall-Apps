<?php

namespace App\SupportedApps\Gotify;

class Gotify extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $attrs = $this->getAttrs();
        $test = parent::appTest($this->url("health"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];
        $attrs = $this->getAttrs();

        // Fetch applications
        $applicationsResponse = parent::execute($this->url("application"), $attrs);
        $applications = json_decode($applicationsResponse->getBody());

        // Count applications
        if ($applications) {
            $data["applications"] = count($applications);
        } else {
            $data["applications"] = 0;
        }

        // Fetch clients
        $clientsResponse = parent::execute($this->url("client"), $attrs);
        $clients = json_decode($clientsResponse->getBody());

        // Count clients
        if ($clients) {
            $data["clients"] = count($clients);
        } else {
            $data["clients"] = 0;
        }

        // Fetch messages
        $messagesResponse = parent::execute($this->url("message"), $attrs);
        $messages = json_decode($messagesResponse->getBody());

        // Count messages
        if ($messages && isset($messages->messages)) {
            $data["messages"] = count($messages->messages);
        } else {
            $data["messages"] = 0;
        }

        // Determine status based on data
        if ($data["applications"] > 0 || $data["clients"] > 0 || $data["messages"] > 0) {
            $status = "active";
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "X-Gotify-Key" => $this->config->apikey
            ],
        ];
    }
}
