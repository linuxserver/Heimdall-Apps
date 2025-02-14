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
        // https://github.com/duplicati/duplicati/blob/master/Duplicati/WebserverCore/Endpoints/V1/Auth.cs
        // https://github.com/duplicati/duplicati/blob/master/Duplicati/WebserverCore/Middlewares/JWTProvider.cs
        $body = json_encode(["password" => $this->config->password]);


        $vars = [
            "http_errors" => false,
            "timeout" => 5,
            "body" => $body,
            "cookies" => $this->jar,  // Store cookies for session handling
            "headers" => [
                "Content-Type" => "application/json",
            ],
        ];


        $result = parent::execute(
            $this->url("api/v1/auth/login"),
            [],
            $vars,
            "POST"
        );

        if ($result === null) {
            throw new Exception("Could not connect to Duplicati");
        }

        $responseBody = $result->getBody()->getContents();


        $response = json_decode($responseBody, true);

        if (null === $response || $result->getStatusCode() !== 200 || !isset($response['AccessToken'])) {
            throw new Exception("Error logging in");
        }

        $this->config->jwt = $response['AccessToken']; // Store the token correctly
        return $response['AccessToken'];
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


    private function getServerState()
    {
        $attrs = [
            "cookies" => $this->jar,
            "headers" => [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $this->config->jwt,  // Add JWT token
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
            list($time1, $time2) = array($time2, $time1);
        }

        // Set up intervals and diffs arrays
        $intervals = array('year', 'month', 'day', 'hour', 'minute', 'second');
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
            $diffs[$interval] = $looped;
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
