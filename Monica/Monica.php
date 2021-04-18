<?php namespace App\SupportedApps\Monica;

class Monica extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/contacts'));
        echo $test->status;
    }

    public function livestats()
    {
      $status = 'inactive';
      $res = parent::execute($this->url('api/contacts'), $this->attrs());
      $details = json_decode($res->getBody(), True);

      $data = [];

      if($details) {
        $data['contacts'] = $details['meta']['total'] ?? 0;
      }

      return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }

    public function attrs()
    {
        $apikey = $this->config->apikey;
        $attrs = [
          'headers'  => ['content-type' => 'application/json', 'Authorization' => 'Bearer '.$apikey]
        ];
        return $attrs;
    }
}
