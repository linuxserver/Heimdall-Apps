<?php

namespace App\SupportedApps\Autobrr;

class Autobrr extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $headers = [
            'headers' => [
                'X-API-Token' => $this->config->apikey,
            ],
        ];

        $test = parent::appTest($this->url('healthz/liveness'), $headers);
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $headers = [
            'headers' => [
                'X-API-Token' => $this->config->apikey,
            ],
        ];

        $filtersRes = parent::execute($this->url('filters'), $headers);
        $filters = json_decode($filtersRes->getBody(), true);
        $filterCount = is_array($filters) ? count($filters) : 0;

        $ircRes = parent::execute($this->url('irc'), $headers);
        $irc = json_decode($ircRes->getBody(), true);
        $ircCount = 0;

        if (is_array($irc)) {
            foreach ($irc as $conn) {
                if (!empty($conn['connected'])) {
                    $ircCount++;
                }
            }
        }

        $data = [
            'Filters' => $filterCount,
            'IRC'     => $ircCount,
        ];

        if ($filterCount > 0 || $ircCount > 0) {
            $status = 'active';
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . 'api/' . $endpoint;
    }
}
