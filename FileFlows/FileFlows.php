<?php namespace App\SupportedApps\FileFlows;

class FileFlows extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    function __construct() {
    }

    public function test()
    {
        $test = parent::appTest($this->url('/api/status'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [];
        $url = $this->url('api/status');
        $res = parent::execute($url);

        $body = $res->getBody();
        $details = json_decode($body);
        if($details)
        {
            $data['queue'] = $details->queue;
            if (strlen($details->time) == 0){
                if($details->processing == 0){
                    $data['second_label'] = 'Processed';
                    $data['second_value'] = $details->processed;
                }
                else{
                    $data['second_label'] = 'Processing';
                    $data['second_value'] = $details->processing;
                }
            }else{
                $data['second_label'] = 'Time';
                $data['second_value'] = $details->time;
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
