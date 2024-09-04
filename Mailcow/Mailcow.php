<?php

namespace App\SupportedApps\Mailcow;

class Mailcow extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        $attrs = $this->getAttrs();
        $test = parent::appTest($this->url("status/containers"), $attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $data = [];
        $attrs = $this->getAttrs();

        // Fetch mailboxes
        $mailboxesResponse = parent::execute($this->url("mailbox/all"), $attrs);
        $mailboxes = json_decode($mailboxesResponse->getBody());

        // Count mailboxes
        if ($mailboxes) {
            $data["mailboxes"] = count($mailboxes);
        } else {
            $data["mailboxes"] = 0;
        }

        // Fetch clients
        $domainsResponse = parent::execute($this->url("domain/all"), $attrs);
        $domains = json_decode($domainsResponse->getBody());

        // Count clients
        if ($domains) {
            $data["domains"] = count($domains);
        } else {
            $data["domains"] = 0;
        }

        // Fetch messages
        $queueResponse = parent::execute($this->url("mailq/all"), $attrs);
        $queue = json_decode($queueResponse->getBody());

        // Count messages
        if ($queue && isset($queue->messages)) {
            $data["queue"] = count($queue->messages);
        } else {
            $data["queue"] = 0;
        }

        // Determine status based on data
        if ($data["mailboxes"] > 0 || $data["domains"] > 0 || $data["queue"] >= 0) {
            $status = "active";
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . "api/v1/get/" . $endpoint;
        return $api_url;
    }
    private function getAttrs()
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "X-API-Key" => $this->config->apikey
            ],
        ];
    }
}
