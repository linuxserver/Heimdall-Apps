<?php namespace App\SupportedApps\LinkAce;

class LinkAce extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/v1/links'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';

        $res_links = parent::execute($this->url('api/v1/links'), $this->attrs());
        $links = json_decode($res_links->getBody(), True);

        $res_tags = parent::execute($this->url('api/v1/tags'), $this->attrs());
        $tags = json_decode($res_tags->getBody(), True);

        $data = [];

        if($links) {
          $data['links'] = $links['total'] ?? 0;
        }

        if($tags) {
          $data['tags'] = $tags['total'] ?? 0;
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
