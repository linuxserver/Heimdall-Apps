<?php

namespace App\SupportedApps\Transmission;

class Transmission extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;
    public $attrs = [];
    public $vars;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    protected $method = "POST"; // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
        $body["method"] = "torrent-get";
        $body["arguments"] = [
            "fields" => ["percentDone", "status", "rateDownload", "rateUpload"],
        ];
        $this->vars = [
            "http_errors" => false,
            "timeout" => 5,
            "body" => json_encode($body),
        ];
    }

    public function test()
    {
        $test = $this->sendTest();

        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $res = $this->sendRequest();
        if ($res == null) {
            //Log::debug('Transmission connection failed');
            return "";
        }

        $details = json_decode($res->getBody());
        if (!isset($details->arguments)) {
            //Log::debug('Failed to fetch data from Transmission');
            return "";
        }

        $data = [];

        $torrents = $details->arguments->torrents;
        $seeding_torrents = 0;
        $leeching_torrents = 0;
        $rateDownload = $rateUpload = 0;

        foreach ($torrents as $thisTorrent) {
            $rateDownload += $thisTorrent->rateDownload;
            $rateUpload += $thisTorrent->rateUpload;
            if ($thisTorrent->status == 6) {
                $seeding_torrents += 1;
            }
            if ($thisTorrent->status == 4) {
                $leeching_torrents += 1;
            }
        }

        if ($leeching_torrents > 0) {
            $status = "active";
        }

        $data["download_rate"] = format_bytes(
            $rateDownload,
            false,
            " <span>",
            "/s</span>"
        );
        $data["upload_rate"] = format_bytes(
            $rateUpload,
            false,
            " <span>",
            "/s</span>"
        );
        $data["seed_count"] = $seeding_torrents;
        $data["leech_count"] = $leeching_torrents;

        return parent::getLiveStats($status, $data);
    }

    private function sendTest()
    {
        $this->setClientOptions();
        $test = parent::appTest(
            $this->url("transmission/rpc"),
            $this->attrs,
            $this->vars
        );
        if ($test->code === 409) {
            $this->setClientOptions();
            $test = parent::appTest(
                $this->url("transmission/rpc"),
                $this->attrs,
                $this->vars
            );
        }
        return $test;
    }

    private function sendRequest()
    {
        $this->setClientOptions();
        $res = parent::execute(
            $this->url("transmission/rpc"),
            $this->attrs,
            $this->vars
        );
        if ($res->getStatusCode() === 409) {
            $this->setClientOptions();
            $res = parent::execute(
                $this->url("transmission/rpc"),
                $this->attrs,
                $this->vars
            );
        }
        return $res;
    }

    private function setClientOptions()
    {
        if ($this->config->username != "" || $this->config->password != "") {
            $this->attrs = [
                "auth" => [
                    $this->config->username,
                    $this->config->password,
                    "Basic",
                ],
            ];
        }
        $res = parent::execute(
            $this->url("transmission/rpc"),
            $this->attrs,
            $this->vars
        );

        try {
            //print_r($res);
            $xtId = $res->getHeaderLine("X-Transmission-Session-Id");
            if ($xtId != null) {
                $this->attrs["headers"] = [
                    "X-Transmission-Session-Id" => $xtId,
                ];
            } else {
                //Log::error("Unable to get Transmission session information");
                //Log::debug("Status Code: ".$res->getStatusCode());
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            //Log::error("Failed connection to Transmission");
            return false;
        }
        return true;
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
