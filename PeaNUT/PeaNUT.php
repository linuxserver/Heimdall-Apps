<?php

namespace App\SupportedApps\PeaNUT;

class PeaNUT extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        // /api/v1/devices is exactly what the tile reads, so testing it validates
        // the URL and any Basic-auth credentials in one call (200 = ok, 401 = bad
        // credentials, 404 = wrong URL). Auth is optional in PeaNUT (it can be
        // disabled server-side), so an empty username simply sends no auth header.
        $test = parent::appTest($this->url('api/v1/devices'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [
            'charge' => '-',
            'load' => '-',
        ];

        // Single GET of the live device list. execute() returns null on a failed
        // connection (it never throws), so guard before reading the body. These
        // are live telemetry readings, so there is deliberately no caching.
        $res = parent::execute($this->url('api/v1/devices'), $this->getAttrs());
        if ($res !== null) {
            $devices = json_decode($res->getBody());
            // The endpoint returns an array of flat device objects whose keys are
            // NUT variables (e.g. "battery.charge", "ups.load"). Read the first UPS.
            if (is_array($devices) && count($devices) > 0 && is_object($devices[0])) {
                $ups = $devices[0];
                $status = 'active';
                if (isset($ups->{'battery.charge'})) {
                    $data['charge'] = $this->percent($ups->{'battery.charge'});
                }
                if (isset($ups->{'ups.load'})) {
                    $data['load'] = $this->percent($ups->{'ups.load'});
                }
            }
        }

        return parent::getLiveStats($status, $data);
    }

    // percent renders a NUT numeric reading as a whole-number percentage.
    private function percent($value)
    {
        if (!is_numeric($value)) {
            return '-';
        }
        return round((float) $value) . '%';
    }

    private function getAttrs()
    {
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
            ],
        ];
        // PeaNUT uses HTTP Basic auth when enabled; only add credentials when a
        // username is supplied so auth-disabled instances keep working. Guzzle's
        // "auth" option builds the Authorization: Basic header.
        if (!empty($this->config->username)) {
            $attrs["auth"] = [$this->config->username, $this->config->password ?? ''];
        }
        return $attrs;
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
