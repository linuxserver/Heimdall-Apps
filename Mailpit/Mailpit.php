<?php

namespace App\SupportedApps\Mailpit;

class Mailpit extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        // The messages list is exactly what the tile reads, so testing it
        // validates both connectivity and (if configured) Basic Auth in one
        // call. limit=1 keeps the probe response tiny. 401 = bad/missing creds.
        $test = parent::appTest($this->url('api/v1/messages?limit=1'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [
            'unread' => 0,
            'total' => 0,
        ];

        // Single GET - Mailpit needs no login flow. limit=1 avoids pulling the
        // full message list since total/unread are mailbox-wide counters that
        // are returned regardless of the page size. execute() returns null on a
        // failed connection (it never throws), so guard before reading the body.
        $res = parent::execute($this->url('api/v1/messages?limit=1'), $this->getAttrs());
        if ($res !== null) {
            $stats = json_decode($res->getBody());
            if ($stats !== null && isset($stats->total)) {
                $status = 'active';
                $data['total'] = (int) $stats->total;
                $data['unread'] = (int) $stats->unread;
            }
        }

        return parent::getLiveStats($status, $data);
    }

    private function getAttrs()
    {
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
            ],
        ];

        // Mailpit has no auth by default but optionally supports Basic Auth.
        // Only send the header when a username is set, so an unauthenticated
        // server keeps working with empty credentials.
        $username = $this->config->username ?? '';
        if ($username !== '') {
            $password = $this->config->password ?? '';
            $attrs["headers"]["Authorization"] = "Basic " . base64_encode($username . ":" . $password);
        }

        return $attrs;
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
