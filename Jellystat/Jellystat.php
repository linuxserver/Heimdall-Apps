<?php

namespace App\SupportedApps\Jellystat;

class Jellystat extends \App\SupportedApps implements \App\EnhancedApps
{

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $attrs = $this->getRequestAttrs();
        $test = parent::appTest($this->url('/stats/getLibraryCardStats'),$attrs);
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
		$attrs = $this->getRequestAttrs();
		
		
		
        $res = parent::execute($this->url('/stats/getLibraryCardStats'),$attrs);
        
		$result = json_decode($res->getBody());
        $details = ["visiblestats" => []];
        foreach ($this->config->availablestats as $stat) {
            $newstat = new \stdClass();
            $newstat->title = self::getAvailableStats()[$stat];
            $newstat->value = $result->CollectionType->ItemName;
            $details["visiblestats"][] = $newstat;
        }
		
		
        return parent::getLiveStats($status, $data);
        
    }

	public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }

	private function getRequestAttrs()
    {
        $attrs["headers"] = ["X-API-Key" => $this->config->apikey];
        return $attrs;
    }

	public static function getAvailableStats()
    {
        return [
            "movies" => "Movies",
            "tvshows" => "Series",
            "mixed" => "Others"
        ];
    }
}
