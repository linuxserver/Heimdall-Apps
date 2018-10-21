<?php namespace App\SupportedApps\Nzbget;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class Nzbget extends \App\SupportedApps implements \App\EnhancedApps {

    //public function test()
    //public function execute()


    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->apiUrl('status'));
        $data = json_decode($res->getBody());

        if($data) {
            $size = $data->result->RemainingSizeMB;
            $rate = $data->result->DownloadRate;
            $queue_size = format_bytes($size*1000*1000, false, ' <span>', '</span>');
            $current_speed = format_bytes($rate, false, ' <span>');
            $status = ($size > 0 || $rate > 0) ? 'active' : 'inactive';
        }

        return parent::livestats($status, $data);
        
    }


    public function apiUrl($endpoint)
    {
        $config = $this->config;
        $url = $config->url;
        $username = $config->username;
        $password = $config->password;
        $rebuild_url = str_replace('http://', 'http://'.$username.':'.$password.'@', $url);
        $rebuild_url = str_replace('https://', 'https://'.$username.':'.$password.'@', $rebuild_url);
        $rebuild_url = rtrim($rebuild_url, '/');
        $api_url = $rebuild_url.'/jsonrpc/'.$endpoint;
        return $api_url;
    }
}
