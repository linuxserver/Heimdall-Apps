<?php

namespace App\SupportedApps\UniFi;

/**
 * Implementation based on
 * https://ubntwiki.com/products/software/unifi-controller/api
 */
class UniFi extends \App\SupportedApps
{
    public $config;

    protected $method = 'POST';

    public function __construct()
    {
        $this->jar = new \GuzzleHttp\Cookie\CookieJar();
    }

    public function test()
    {
        $urls = $this->getAPIURLs();
        $test = parent::appTest(
            $this->url($urls['loginURL']),
            $this->getLoginAttributes(),
        );

        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $urls = $this->getAPIURLs();
        parent::execute(
            $this->url($urls['loginURL']),
            $this->getLoginAttributes(),
            null,
            'POST'
        );

        $res = parent::execute(
            $this->url($urls['statsURL']),
            $this->getAttributes(),
            null,
            'GET'
        );

        $details = json_decode($res->getBody());

        $data = [];

        if (isset($details->data)) {
            $data['error'] = false;
            foreach ($details->data as $key => $detail) {
                if ($detail->subsystem === 'wlan') {
                    // Handle lack of APs
                    // TODO: Update UI to adapt to lack of APs
                    $data['wlan_users'] = isset($detail->num_user) ? $detail->num_user : 0;
                    $data['wlan_ap'] = isset($detail->num_ap) ? $detail->num_ap : 0;
                    $data['wlan_dc'] = isset($detail->num_disconnected) ? $detail->num_disconnected : 0;
                    $data['num_ap'] = isset($detail->num_ap) ? $detail->num_ap : 0;
                }

                if ($detail->subsystem === 'lan') {
                    // Handle lack of Switches
                    // TODO: Update UI to adapt to lack of Switches
                    $data['lan_users'] = isset($detail->num_user) ? $detail->num_user : 0;
                    $data['num_sw'] = isset($detail->num_sw) ? $detail->num_sw : 0;
                }

                if ($detail->subsystem === 'wan') {
                    // Handle lack of GW
                    // TODO: Update UI to adapt to lack of GW
                    $data['wan_avail'] = isset($detail->uptime_stats->WAN->availability)
                                       ? number_format($detail->uptime_stats->WAN->availability, 0)
                                       : 0;
                    $data['num_gw'] = isset($detail->num_gw) ? $detail->num_gw : 0;
                }
            }
        } else {
            $data['error'] = true;
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $url = parse_url(parent::normaliseurl($this->config->url));
        $scheme = $url["scheme"];
        $domain = $url["host"];
        $port = isset($url["port"]) ? $url["port"] : "443";

        $api_url =
            $scheme .
            "://" .
            $domain .
            ":" .
            $port .
            $endpoint;

        return $api_url;
    }

    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }

    public function getLoginAttributes()
    {
        $ignoreTls = $this->getConfigValue("ignore_tls", false);
        $username = $this->config->username;
        $password = $this->config->password;

        $body = [
            "username" => $username,
            "password" => $password,
        ];

        $attrs = [
            "body" => json_encode($body),
            "cookies" => $this->jar,
            "headers" => [
                "Content-Type" => "application/json"
            ]
        ];

        if ($ignoreTls) {
            $attrs["verify"] = false;
        }

        return $attrs;
    }

    public function getAttributes()
    {
        $attrs = [
            "cookies" => $this->jar,
        ];

        $ignoreTls = $this->getConfigValue("ignore_tls", false);

        if ($ignoreTls) {
            $attrs["verify"] = false;
        }

        return $attrs;
    }

    public function getAPIURLs() {
        $self_hosted = $this->getConfigValue("self_hosted", false);
        // Default to UDM URLs
        $urls = [
            "loginURL" => "/api/auth/login",
            "statsURL" => "/proxy/network/api/s/default/stat/health",
        ];
        if ($self_hosted) {
            // Self hosted URLs
            $urls = [
                "loginURL" => "/api/login",
                "statsURL" => "/api/s/default/stat/health",
            ];
        }
        return $urls;
    }
}
