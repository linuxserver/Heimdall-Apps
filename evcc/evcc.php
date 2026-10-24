<?php

namespace App\SupportedApps\evcc;

class evcc extends \App\SupportedApps implements \App\EnhancedApps // phpcs:ignore
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        // /api/state is exactly what the tile reads and needs no authentication,
        // so a 200 here confirms the URL points at a reachable evcc instance.
        $test = parent::appTest($this->url('api/state'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [
            'pv' => '0.0 kW',
            'charging' => '0.0 kW',
        ];

        // Single GET - evcc read endpoints are public, so no login/logout per poll.
        // execute() returns null on a failed connection (it never throws), so guard
        // before reading the body.
        $res = parent::execute($this->url('api/state'), $this->getAttrs());
        if ($res !== null) {
            $json = json_decode($res->getBody());
            // The evcc /api/state response is a flat JSON object - there is no
            // "result" wrapper. Fields vary by configuration, so read each one
            // defensively.
            if ($json !== null && isset($json->apiReady) && $json->apiReady === true) {
                $status = 'active';

                // pvPower is only present when a PV meter is configured (grid-only
                // setups omit it); default to 0 so the tile still renders.
                $pvWatts = isset($json->pvPower) ? (float) $json->pvPower : 0.0;
                $data['pv'] = $this->formatKw($pvWatts);

                // Sum chargePower across all loadpoints - loadpoints is evcc's core
                // config and effectively always present.
                $chargeWatts = 0.0;
                if (isset($json->loadpoints) && is_array($json->loadpoints)) {
                    foreach ($json->loadpoints as $lp) {
                        if (isset($lp->chargePower)) {
                            $chargeWatts += (float) $lp->chargePower;
                        }
                    }
                }
                $data['charging'] = $this->formatKw($chargeWatts);
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // evcc reports power in watts; the tile shows kW with one decimal.
    private function formatKw($watts)
    {
        return number_format($watts / 1000, 1) . ' kW';
    }

    private function getAttrs()
    {
        $headers = [
            "Accept" => "application/json",
        ];
        // Reads need no auth, but recent evcc builds accept an API token for
        // password-protected instances - send it only when the user supplies one.
        if (!empty($this->config->apikey)) {
            $headers["Authorization"] = "Bearer " . $this->config->apikey;
        }

        return [
            "headers" => $headers,
        ];
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
