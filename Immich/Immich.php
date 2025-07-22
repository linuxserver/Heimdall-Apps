<?php

namespace App\SupportedApps\Immich;

class Immich extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
                "x-api-key" => $this->config->api_key,
            ],
        ];
        $test = parent::appTest($this->url("server/statistics"), $attrs);
        echo $test->status;
    }

    private function number_format_large($number)
    {
        $suffixes = [ "", "k", "M", "G" ];
        $rank = 0;
        while (abs($number) > 1000 && $rank < count($suffixes)) {
            $number /= 1000;
            $rank++;
        }
        $decimals = $number < 10 && $rank > 0 ? 1 : 0;
        return number_format($number, $decimals) . $suffixes[$rank];
    }

    public function livestats()
    {
        $status = "inactive";
        $attrs = [
            "headers" => [
                "Accept" => "application/json",
                "x-api-key" => $this->config->api_key,
            ],
        ];
        $res = parent::execute($this->url("server/statistics"), $attrs);
        $details = json_decode($res->getBody());

        $data = [];

        if ($details) {
            $status = "active";
            $data["photos"] = $this->number_format_large($details->photos);
            $data["videos"] = $this->number_format_large($details->videos);
            $usageInGiB = number_format($details->usage / 1073741824, 2);
            $data["usage"] = $usageInGiB . 'GiB';
        }

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
            $api_url = parent::normaliseurl($this->config->url) .
            "api/" .
            $endpoint;
        return $api_url;
    }
}
