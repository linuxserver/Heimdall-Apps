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
            $token = $this->login();
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
                $this->logout($token);
            }
        } catch (\Exception $e) {
            // Any transport/auth failure leaves the tile inactive with zeros.
        }

        return parent::getLiveStats($status, $data);
    }

    // login exchanges the admin username/password for a session bearer token.
    // AudioSilo has no static API key; /admin/stats needs an admin session, so
    // we log in per poll. Returns the token, or null when login is refused.
    private function login()
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
        $body = json_decode($res->getBody());
        return isset($body->token) ? $body->token : null;
    }

    // fetchStats reads the admin overview (catalog totals + who's listening).
    private function fetchStats($token)
    {
        $res = parent::execute($this->url('api/v1/admin/stats'), $this->authAttrs($token));
        return json_decode($res->getBody());
    }

    // logout revokes the per-poll session token. Best effort - failures here
    // must never break the tile, so any error is swallowed.
    private function logout($token)
    {
        try {
            parent::execute($this->url('api/v1/auth/logout'), $this->authAttrs($token), null, "POST");
        } catch (\Exception $e) {
            // ignore
        }
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
