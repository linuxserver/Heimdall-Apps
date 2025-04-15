<?php

namespace App\SupportedApps\Diaspora;

class Diaspora extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;
    private function getBaseDomain($url) {
        $regex = '/^(https?:)/i';
        $baseurl = preg_replace($regex, '', $url);
        $baseurl = str_replace('/', '', $baseurl);
        return $baseurl;
    }
    private function fetchNodeInfo() {
        $podurl = parent::normaliseurl($this->config->url);
        $nodeinfo = $podurl . '/nodeinfo/2.1';
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    "Content-Type: application/json"
                ]
            ],
        ];
        $context = stream_context_create($options);
        $res = file_get_contents($nodeinfo, false, $context);
        $RawDetails = json_decode($res, true);
        return $RawDetails;
    }
    public function test()
    {
        try {
            $this->fetchNodeInfo("/");
            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }
    public function livestats()
    {
        $status = "inactive";
        $RawDetails = $this->fetchNodeInfo();
        $data = [
            "COMMENT_COUNTS" => $RawDetails["usage"]["localComments"],
            "LOCAL_POSTS" => $RawDetails["usage"]["localPosts"],
            "TOTAL_USERS" => $RawDetails["usage"]["users"]["total"],
            "ACTIVE_USERS_MONTHLY" => $RawDetails["usage"]["users"]["activeMonth"],
            "ACTIVE_USERS_HALFYEAR" => $RawDetails["usage"]["users"]["activeHalfyear"],
            "SIGNUP" => $RawDetails["openRegistrations"],
        ];
        foreach ($this->config->availablestats as $stat) {
            $newstat = new \stdClass();
            $newstat->title = self::getAvailableStats()[$stat];
            $newstat->value = number_format($data[strtoupper($stat)]);
            $data["visiblestats"][] = $newstat;
        }
        $status = "active";
        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
    public static function getAvailableStats()
    {
        return [
            "total_users" => "Total",
            "active_users_monthly" => "Monthly",
            "active_users_halfyear" => "HalfYear",
            "local_posts" => "Posts",
            "comment_counts" => "Comments",
            "signup" => "Signup",
        ];
    }
}
