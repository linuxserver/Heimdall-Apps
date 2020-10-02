<?php namespace App\SupportedApps\Fronius;

class Fronius extends \App\SupportedApps implements \App\EnhancedApps {
    public $config;

    function __construct() {
    }

    public function test()
    {
        $test = parent::appTest($this->url('solar_api/GetAPIVersion.cgi'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'active';

        $attrs['query'] = ['Scope' => 'System'];
        $res = parent::execute($this->url('solar_api/v1/GetInverterRealtimeData.cgi'), $attrs);
        $details = json_decode($res->getBody());

        $data = [];
        $data['PAC'] = 0;
        foreach( $details->Body->Data->PAC->Values as $key => $pac){
            $data['PAC'] += $pac;
        }
        $data['PAC_UNIT'] = $details->Body->Data->PAC->Unit;

        $data['DAY_ENERGY'] = 0;
        foreach( $details->Body->Data->DAY_ENERGY->Values as $key => $ev){
            $data['DAY_ENERGY'] += $ev;
        }
        $data['DAY_ENERGY_UNIT'] = $details->Body->Data->DAY_ENERGY->Unit;

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}
