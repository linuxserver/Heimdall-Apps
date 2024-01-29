<?php

namespace App\SupportedApps\DuetWebControl;

class DuetWebControl extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url(""));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $res = parent::execute($this->url("rr_status?type=3"));
        $printer_status = $job_progress = 0;

        $details = json_decode($res->getBody());

        // Get current printer status (only three basic supported (at least for now))
        switch ($details->status) {
            case "I":
                $printer_status = "Idle";
                break;
            case "P":
                $printer_status = "Printing";
                break;
            case "S":
                $printer_status = "Stopped";
                break;
            case "B":
                $printer_status = "Busy";
                break;

            default:
                $printer_status = "Unknown";
        }

        // Get current job percentage
        $job_progress = $details->fractionPrinted;

        $data["printer_status"] = $printer_status ?? "Off";
        $data["job_progress"] = $job_progress . "%" ?? "Off";

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
