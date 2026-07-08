<?php

namespace App\SupportedApps\AudioSilo;

class AudioSilo extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        // The public server-info endpoint needs no auth and always answers on a
        // healthy AudioSilo server, so it is the reliable reachability check.
        // Credentials are exercised by the live tile (see livestats).
        $test = parent::appTest($this->url('api/v1/server'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [
            'books' => 0,
            'libraries' => 0,
            'users' => 0,
            'listening' => 0,
        ];

        try {
            $token = $this->getSessionToken();
            if ($token !== null) {
                $stats = $this->fetchStats($token);
                if ($stats !== null && isset($stats->total_books)) {
                    $status = 'active';
                    $data['books'] = (int) $stats->total_books;
                    $data['libraries'] = (int) $stats->total_libraries;
                    $data['users'] = (int) $stats->total_users;
                    $data['listening'] = $this->countListening($stats);
                }
                // Revoke the short-lived session we minted for this poll so
                // tokens don't accumulate on the server. Best effort.
                $this->revokeSession($token);
            }
        } catch (\Throwable $e) {
            // Any transport/auth failure leaves the tile inactive with zeros.
        }

        return parent::getLiveStats($status, $data);
    }

    // getSessionToken exchanges the admin username/password for a session bearer
    // token. AudioSilo has no static API key; /admin/stats needs an admin
    // session, so we log in per poll. Returns the token, or null when login is
    // refused or the server can't be reached. (Named to avoid the base class's
    // public login() used by the login-first flow.)
    private function getSessionToken()
    {
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ],
            "body" => json_encode([
                "username" => $this->config->username,
                "password" => $this->config->password,
                "device_name" => "Heimdall",
            ]),
        ];
        $res = parent::execute(
            $this->url('api/v1/auth/login'),
            $attrs,
            null,
            "POST"
        );
        if ($res === null) {
            return null;
        }
        $body = json_decode($res->getBody());
        return isset($body->token) ? $body->token : null;
    }

    // fetchStats reads the admin overview (catalog totals + who's listening).
    // Returns the decoded object, or null when the request fails.
    private function fetchStats($token)
    {
        $res = parent::execute($this->url('api/v1/admin/stats'), $this->authAttrs($token));
        if ($res === null) {
            return null;
        }
        return json_decode($res->getBody());
    }

    // revokeSession logs out the per-poll session token. Best effort - the
    // response is ignored, and execute() swallows connection errors itself.
    private function revokeSession($token)
    {
        parent::execute($this->url('api/v1/auth/logout'), $this->authAttrs($token), null, "POST");
    }

    // countListening reports how many distinct users have a book in progress in
    // the cross-user listening feed (finished books are excluded), matching the
    // server's "who's listening" framing.
    private function countListening($stats)
    {
        if (!isset($stats->listening) || !is_array($stats->listening)) {
            return 0;
        }
        $users = [];
        foreach ($stats->listening as $row) {
            if (empty($row->finished) && isset($row->user_id)) {
                $users[$row->user_id] = true;
            }
        }
        return count($users);
    }

    private function authAttrs($token)
    {
        return [
            "headers" => [
                "Accept" => "application/json",
                "Authorization" => "Bearer " . $token,
            ],
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
