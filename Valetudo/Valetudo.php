<?php namespace App\SupportedApps\Valetudo;

class Valetudo extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/v2/valetudo/version'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('api/v2/robot/capabilities/CurrentStatisticsCapability'));
        $details = json_decode($res->getBody());

        $data = [];

        if($details)
        {
                foreach($details as $data_point)
                {
                        if($data_point->type == "time")
                        {
                                $data["last_session_time"] = number_format($data_point->value / 60);
                        }
                        if($data_point->type == "area")
                        {
                                $area = $data_point->value / 10000;
                                $data["last_session_area"] = number_format($area, 2);
                        }
                }
        }

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}