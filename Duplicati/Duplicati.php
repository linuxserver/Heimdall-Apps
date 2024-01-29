<?php

namespace App\SupportedApps\Duplicati;

use Exception;

class Duplicati extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
        $this->jar = new \GuzzleHttp\Cookie\CookieJar();
    }

    public function test()
    {
        try {
            $authResponse = $this->auth();
            $serverState = $this->getServerState();

            //var_dump($serverState);

            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo "Error connecting to Duplicati: " . $err->getMessage();
        }
    }

    public function auth()
    {
        // Auth flow references
        // https://github.com/duplicati/duplicati/blob/master/Duplicati/Server/webroot/login/login.js
        // https://github.com/Pectojin/duplicati-client/blob/master/auth.py

        $noncedPassword = $this->getNoncedPassword($this->config->password);

        $passAttrs = [
            "body" => "password=" . urlencode($noncedPassword),
            "cookies" => $this->jar,
            "headers" => [
                "content-type" => "application/x-www-form-urlencoded"
            ],
        ];

        $passResponse = parent::execute(
            $this->url("login.cgi"),
            $passAttrs,
            null,
            "POST"
        );

        if (null === $passResponse || $passResponse->getStatusCode() !== 200) {
            throw new Exception("Error logging in");
        }

        return $passResponse;
    }

    public function livestats()
    {
        $status = "inactive";

        $authResponse = $this->auth();

        $serverState = $this->getServerState();

        $nextTime = $this->getNextTime($serverState);

        if ($nextTime == "Running" || str_contains($nextTime, "second")) {
            $status = "active";
        }

        $data = [
            "error" => $serverState["HasWarning"] || $serverState["HasError"] ? "Yes" : "No",
            "nextBackup" => $nextTime
        ];

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getNoncedPassword($password)
    {

        $nonceDetails = $this->getNonce();

        $nonce = $nonceDetails["Nonce"];
        $salt = $nonceDetails["Salt"];

        // Prepare nonced+salted password
        $encodedPassword = mb_convert_encoding($password, 'UTF-8', 'ISO-8859-1');

        $saltedPasssword =  $encodedPassword . base64_decode($salt);

        $hashedSaltedPassword = hash('sha256', $saltedPasssword, true);

        $nonceAndPass = base64_decode($nonce) . $hashedSaltedPassword;

        $hashedNoncedPassword = hash('sha256', $nonceAndPass, true);

        $encodedHashedNoncedPassword = base64_encode($hashedNoncedPassword);

        return $encodedHashedNoncedPassword;
    }

    private function getNonce()
    {
        $nonceAttrs = [
            "body" => "get-nonce=1",
            "cookies" => $this->jar,
            "headers" => [
                "content-type" => "application/x-www-form-urlencoded"
            ],
        ];

        $nonceResponse = parent::execute(
            $this->url("login.cgi"),
            $nonceAttrs,
            null,
            "POST"
        );

        if (null === $nonceResponse || $nonceResponse->getStatusCode() !== 200) {
            throw new Exception("Error getting nonce");
        }

        $nonceBody = $nonceResponse->getBody();
        $nonceBodyData = $nonceBody->read(50 * 1024);
        $nonceDetails = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $nonceBodyData), true);

        return $nonceDetails;
    }

    private function getServerState()
    {
        $attrs = [
            "cookies" => $this->jar,
            "headers" => [
                "content-type" => "application/json",
                "X-XSRF-Token" => urldecode($this->jar->getCookieByName("xsrf-token")->getValue())
            ],
        ];
        $result = parent::execute(
            $this->url("api/v1/ServerState"),
            $attrs,
            null
        );

        if (null === $result || $result->getStatusCode() !== 200) {
            throw new Exception("Error retrieving server state");
        }

        $serverStateData = $result->getBody()->read(50 * 1024);
        $serverState = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $serverStateData), true);

        return $serverState;
    }

    private function getNextTime($serverState)
    {
        if ($serverState["ProgramState"] === "Paused") {
            return "Paused";
        }

        if ($serverState["ActiveTask"] !== null) {
            return "Running";
        }

        if (count($serverState["ProposedSchedule"]) === 0) {
            return "None";
        }

        $dates = [];

        foreach ($serverState["ProposedSchedule"] as $scheduleItem) {
            $dates[] = strtotime($scheduleItem["Item2"]);
        }

        return $this->getDateDiff(min($dates), time());
    }

    private function getDateDiff($time1, $time2, $precision = 1)
    {
        // If not numeric then convert timestamps
        if (!is_int($time1)) {
            $time1 = strtotime($time1);
        }
        if (!is_int($time2)) {
            $time2 = strtotime($time2);
        }

        // If time1 > time2 then swap the 2 values
        if ($time1 > $time2) {
            list( $time1, $time2 ) = array( $time2, $time1 );
        }

        // Set up intervals and diffs arrays
        $intervals = array( 'year', 'month', 'day', 'hour', 'minute', 'second' );
        $diffs = array();

        foreach ($intervals as $interval) {
            // Create temp time from time1 and interval
            $ttime = strtotime('+1 ' . $interval, $time1);
            // Set initial values
            $add = 1;
            $looped = 0;
            // Loop until temp time is smaller than time2
            while ($time2 >= $ttime) {
                // Create new temp time from time1 and interval
                $add++;
                $ttime = strtotime("+" . $add . " " . $interval, $time1);
                $looped++;
            }

            $time1 = strtotime("+" . $looped . " " . $interval, $time1);
            $diffs[ $interval ] = $looped;
        }

        $count = 0;
        $times = array();
        foreach ($diffs as $interval => $value) {
            // Break if we have needed precission
            if ($count >= $precision) {
                break;
            }
            // Add value and interval if value is bigger than 0
            if ($value > 0) {
                if ($value != 1) {
                    $interval .= "s";
                }
                // Add value and interval to times array
                $times[] = $value . " " . $interval;
                $count++;
            }
        }

        // Return string with times
        return implode(", ", $times);
    }
}
