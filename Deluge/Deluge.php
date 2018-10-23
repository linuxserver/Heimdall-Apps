<?php namespace App\SupportedApps\Deluge;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class Deluge extends \App\SupportedApps implements \App\EnhancedApps {

    public function test()
    {
        return parent::appTest($this->apiUrl('status'));
    }

    public function livestats()
    {
        return false;        
    }

    public function apiUrl($endpoint)
    {
        return false;        
    }

}
