<?php namespace App\SupportedApps\Deluge;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class Deluge extends \App\SupportedApps implements \App\EnhancedApps {

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    function __construct() {
        $this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function login()
    {
        $password = $this->config->password;
        $attrs = [
            'body' => '{"method": "auth.login", "params": ["'.$password.'"], "id": 1}',
            'cookies' => $this->jar,
            'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json']
        ];
        return parent::appTest($this->url('json'), $attrs);
    }

    public function test()
    {
        $test = $this->login();
        if($test->code === 200) {
            $data = json_decode($test->response);
            if(!isset($data->result) || is_null($data->result) || $data->result == false) {
                $test->status = 'Failed: Invalid Credentials';
            } 
        } 
        echo $test->status;

    }

    public function livestats()
    {
        $test = $this->login();
        $status = 'inactive';
        $attrs = [
            'body' => '{"method": "web.update_ui", "params": [["none"], {}], "id": 1}',
            'cookies' => $this->jar,
            'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json']
        ];
        $res = parent::execute($this->url('json'), $attrs);
        $details = json_decode($res->getBody());

        $data = [];

        if($details) {
            $download_rate = $details->result->stats->download_rate ?? 0;
            $upload_rate = $details->result->stats->upload_rate ?? 0;
            $data['download_rate'] = format_bytes($download_rate, false, ' <span>', '/s</span>');
            $data['upload_rate'] = format_bytes($upload_rate, false, ' <span>', '/s</span>');
            $data['seed_count'] = $details->result->filters->state[2][1] ?? 0;
            $data['leech_count'] = $details->result->filters->state[1][1] ?? 0;  
            $status = ($data['leech_count'] > 0) ? 'active' : 'inactive';  
        }

        return parent::getLiveStats($status, $data);
       
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }

}
