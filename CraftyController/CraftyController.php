<?php

namespace App\SupportedApps\CraftyController;

class CraftyController extends \App\SupportedApps implements \App\EnhancedApps
{

    public $config;

    private $token = null;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {   
        $test = parent::appTest($this->url('api/v2/auth/login'), $this->auth_attrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";
        $res = parent::execute($this->url('api/v2/auth/login'), $this->auth_attrs());
        $data = json_decode($res->getBody());
        
        if ($data->status == 'ok') {
            $this->token = $data->data->token;
        }

        $res = parent::execute($this->url('api/v2/servers'), attrs: $this->attrs(), overridemethod: 'GET');
        $details = json_decode($res->getBody());

        $vars['servers_total'] = count($details->data);

        $online = 0;

        foreach ($details->data as $server) {
            $server_res = parent::execute($this->url('api/v2/servers/' . $server->server_id . '/stats'), attrs: $this->attrs(), overridemethod: 'GET');
            $server_details = json_decode($server_res->getBody());
            if ($server_details->data->running == True)
                $online++;
        }

        $vars['servers_online'] = $online;

        return parent::getLiveStats($status, $vars);
        
    }

    private function auth_attrs()
    {
        return [
            "body" => json_encode([
                "username" => $this->config->username,
                "password" => $this->config->password
            ]),
            "verify" => false
        ];
    }

    
    public function attrs()
    {
        $attrs["headers"] = [
            "content-type" => "application/json",
            "Authorization" => "Bearer " . $this->token,
        ];
        $attrs["verify"] = false;

        return $attrs;
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}
