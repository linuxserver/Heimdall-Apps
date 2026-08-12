<?php

namespace App\SupportedApps\CashPilot;

class CashPilot extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->url('api/earnings/summary'), $this->attrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [];

        $res = parent::execute($this->url('api/earnings/summary'), $this->attrs());
        $details = json_decode($res->getBody());

        if ($details) {
            $status = 'active';

            // CashPilot separates "nothing has been read yet" from "read, and it
            // is zero". has_readings is false on a fresh install and on one whose
            // collection has stopped, so a figure there would state a
            // measurement that was never taken.
            $data['earnings'] = (isset($details->has_readings) && $details->has_readings)
                ? '$' . number_format((float) ($details->total_adjusted ?? 0), 2)
                : '—';

            // active_services is null when the count could not be taken. Showing
            // 0 would read as "nothing is running" while containers are running.
            $data['running'] = (isset($details->active_services) && $details->active_services !== null)
                ? $details->active_services
                : '—';
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    public function attrs()
    {
        return [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $this->config->access_token,
            ],
        ];
    }
}
