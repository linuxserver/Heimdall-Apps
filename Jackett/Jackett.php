<?php namespace App\SupportedApps\Jackett;

class Jackett extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    function __construct() {
    }

    public function test()
    {
        $test = parent::appTest($this->url('all'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
		
        $resFailed = parent::execute($this->url('!test:passed'));
        $detailsFailed = json_decode($resFailed->getBody());  
		$failed = count($detailsFailed->Indexers) ?? 0;
		
		$resAll = parent::execute($this->url('all'));
        $detailsAll = json_decode($resAll->getBody());  
		$all = count($detailsAll->Indexers) ?? 0;
		
		$working = $all - $failed;
		$data = []; 
		$data['indexer_status'] = ''.$working.' / '.$all.'';
        return parent::getLiveStats($status, $data);
        
    }
    public function url($endpoint)
    {
		$apikey = $this->config->apikey;
        $api_url = parent::normaliseurl($this->config->url).'api/v2.0/indexers/'.$endpoint.'/results?apikey='.$apikey.'&query=Indexers';
        return $api_url;
    }
}
