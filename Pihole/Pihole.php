<?php

namespace App\SupportedApps\Pihole;

use Illuminate\Support\Facades\Log;

class Pihole extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        $this->jar = new \GuzzleHttp\Cookie\CookieJar(); // Uncomment if cookies need to be set
    }

    public function test()
    {
        $version = $this->config->version;

        if ($version == 5) {
            $test = parent::appTest($this->url("api.php?summaryRaw"));
            echo $test->status;
        }
        if ($version == 6) {
            $test = $this->getInfo();
            if ($test["valid"]) {
                echo "Successfully communicated with the API";
            } else {
                echo "Error while communicating with the API";
            }
        }
    }
    public function livestats()
    {
        $version = $this->config->version;

        if ($version == 5) {
            $res = parent::execute($this->url("api.php?summaryRaw"));
            $details = json_decode($res->getBody());
            if ($details) {
                $data["ads_blocked"] = number_format(
                    $details->ads_blocked_today
                );
                $data["ads_percentage"] = number_format(
                    $details->ads_percentage_today,
                    1
                );
                $data["gravity"] = number_format(
                    $details->domains_being_blocked,
                    0,
                    '',
                    '.'
                );

                $status = "active";
            }
        }

        if ($version == 6) {
            $results = $this->getInfo();

            $data["ads_blocked"] = $results["queries"];
            $data["ads_percentage"] = $results["percent"];
            $data["gravity"] = $results["gravity"];

            $status = "active";
        }
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $version = $this->config->version;
        if ($version == 5) {
            $apikey = $this->config->apikey;
            $api_url = parent::normaliseurl($this->config->url) . $endpoint;

            if ($apikey) {
                $api_url .= "&auth=" . $apikey;
            }
        }
        if ($version == 6) {
            $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        }
        return $api_url;
    }

    public function getInfo()
    {
        $ignoreTls = $this->getConfigValue("ignore_tls", false);
        if ($ignoreTls) {
            $attrs = [
                "body" => json_encode(['password' => $this->config->apikey]),
                "cookies" => $this->jar,
                "verify" => false,
                "headers" => [
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ],
            ];
            $attrsid["verify"] = false;
        } else {
            $attrs = [
                "body" => json_encode(['password' => $this->config->apikey]),
                "cookies" => $this->jar,
                "headers" => [
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ],
            ];
        }

        // Create session and retreave data
        $response = parent::execute($this->url("api/auth"), $attrs, null, "POST");
        $auth = json_decode($response->getBody());

        if ($ignoreTls) {
            $attrsid = [
                "body" => json_encode(['sid' => $auth->session->sid]),
                "cookies" => $this->jar,
                "verify" => false,
                "headers" => [
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ],
            ];
        } else {
            $attrsid = [
                "body" => json_encode(['sid' => $auth->session->sid]),
                "cookies" => $this->jar,
                "headers" => [
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ],
            ];
        }

        // Get queries data
        $responsesummary = parent::execute($this->url("api/stats/summary"), $attrsid, null, "GET");
        $datasummary = json_decode($responsesummary->getBody());

        // After retrieving the data the session is closed to declutter
        parent::execute($this->url("api/auth"), $attrsid, null, "DELETE");

        // Extract data from the response
        $valid = $auth->session->valid;
        $validity = $auth->session->validity;
        $message = $auth->session->message;
        $queriesblocked = $datasummary->queries->blocked;
        $percentblocked = round($datasummary->queries->percent_blocked, 2);
        $gravity = number_format($datasummary->gravity->domains_being_blocked, 0, '', '.');

        $data = [
            'valid'    => $valid,
            'validity' => $validity,
            'message'  => $message,
            'queries'  => $queriesblocked,
            'percent'  => $percentblocked,
            'gravity'  => $gravity
        ];
        return $data;
    }
    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }
}
