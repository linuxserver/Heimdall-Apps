<?php namespace App\SupportedApps\ruTorrent;

class ruTorrent extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    function __construct() 
    {
    }

    public function test()
    {
        $data = $this->getXMLRPCData('throttle.global_down.rate');
        if( !isset($data) || $data == 'Err' || $data == null || !is_object($data))
        {
           echo 'There is an issue to connect to "' . $this->url('RPC2') . '". Please respect URL format "http(s)://IP:PORT". ' . $data;
        }
        else
        {
           echo 'Connection succeed! ' . 'Your download speed is currently: ' . $this->formatBytes((float)$data->params->param->value->i8); 
        }
    }

    public function livestats()
    {
        $status = 'inactive';

        $data = [];
        $data['down_rate'] = $this->formatBytes((float)$this->getXMLRPCData('throttle.global_down.rate')->params->param->value->i8, 1);
        
        $data['up_rate'] = $this->formatBytes((float)$this->getXMLRPCData('throttle.global_up.rate')->params->param->value->i8, 1);
        
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }

    public function getXMLRPCData($method)
    {
        $value='';

        $body = '<methodCall><methodName>'.$method.'</methodName></methodCall>';

        $this->vars = ['http_errors' => false, 'timeout' => 5, 'body' => $body];
        $this->attrs = [];
        $this->attrs['headers'] = ['Content-Type' => 'text/xml'];
        $res = parent::execute($this->url('RPC2'), $this->attrs, $this->vars);

        if (function_exists('simplexml_load_string')) {
            $value = simplexml_load_string($res->getBody()->getContents());
        } else {
            $value = 'simplexml_load_string doesn\'t exist.';
        }

        return $value;
    }

    public function formatBytes($bytes, $precision = 2) 
    { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        $bytes /= pow(1024, $pow); 

        return round($bytes, $precision) . ' ' . $units[$pow] . '/s'; 
    } 
}
