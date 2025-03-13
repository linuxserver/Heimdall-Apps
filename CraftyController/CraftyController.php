<?php

namespace App\SupportedApps\CraftyController;

class CraftyController extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    private $token = null;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    private function getToken()
    {
        $res = parent::execute($this->url('api/v2/auth/login'), $this->authAttrs());
        $data = json_decode($res->getBody());
        if ($data->status == 'ok') {
            $this->token = $data->data->token;
        }
    }

    public function test()
    {
        try {
            $this->getToken();
            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }

    public function livestats()
    {
        $status = "inactive";
        $this->getToken();

        $res = parent::execute(
            $this->url('api/v2/servers'),
            attrs: $this->attrs(),
            overridemethod: 'GET'
        );
        $details = json_decode($res->getBody());

        $vars['servers_total'] = count($details->data);

        $servers_online = 0;
        $players_online = 0;
        $mem = 0;

        foreach ($details->data as $server) {
            $server_res = parent::execute(
                $this->url('api/v2/servers/' . $server->server_id . '/stats'),
                attrs: $this->attrs(),
                overridemethod: 'GET'
            );
            $server_details = json_decode($server_res->getBody());
            $players_online += $server_details->data->online;
            $mem += $this->decodeSizeToGB($server_details->data->mem);
            if ($server_details->data->running == true) {
                $servers_online++;
            }
        }

        $vars['servers_online'] = $servers_online;
        $vars['players_online'] = $players_online;
        $vars['mem'] = $mem;

        return parent::getLiveStats($status, $vars);
    }

    private function authAttrs()
    {
        $ignoreTls = $this->getConfigValue("ignore_tls", false);
        return [
            "body" => json_encode([
                "username" => $this->getConfigValue("username", null),
                "password" => $this->getConfigValue("password", null)
            ]),
            "verify" => ($ignoreTls ? false : true)
        ];
    }


    public function attrs()
    {
        $ignoreTls = $this->getConfigValue("ignore_tls", false);
        return [
            "headers" => [
                "content-type" => "application/json",
                "Authorization" => "Bearer " . $this->token,
            ],
            "verify" => ($ignoreTls ? false : true)
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }

    private function decodeSizeToGB($size)
    {
        $units = [
            'B'  => 1 / (1024 * 1024 * 1024),  // Convert bytes to GB
            'KB' => 1 / (1024 * 1024),        // Convert KB to GB
            'MB' => 1 / 1024,                 // Convert MB to GB
            'GB' => 1,                        // GB is the base unit
            'TB' => 1024,                     // Convert TB to GB
            'PB' => 1024 * 1024,              // Convert PB to GB
        ];

        if (preg_match('/^([\d\.]+)\s*([KMGTP]?B)$/i', strtoupper($size), $matches)) {
            $value = (float)$matches[1];
            $unit = $matches[2];

            return round($value * ($units[$unit] ?? 1), 1);
        }

        return false; // Invalid format
    }
}
