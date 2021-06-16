<?php namespace App\SupportedApps\Kodi;

class Kodi extends \App\SupportedApps implements \App\EnhancedApps {
    public $config;

    function __construct() {}

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . 'jsonrpc?request={"id":1,"jsonrpc":"2.0","method":"' . $endpoint . '"}';
        return $api_url;
    }

    private function getAttrs() {
        return [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->config->username . ':' . $this->config->password),
            ]
        ];
    }

    public function test()
    {
        $test = parent::appTest($this->url('JSONRPC.Introspect'), $this->getAttrs());
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = ['visiblestats' => []];
        foreach($this->config->availablestats as $method) {
            $res = parent::execute($this->url($method), $this->getAttrs());
            $result = json_decode($res->getBody());
            if ( isset($result->result) && isset($result->result->limits) ) {
                $stat = new \stdClass();
                $stat->title = self::getAvailableStats()[$method];
                $stat->value = $result->result->limits->total;
                $data['visiblestats'][] = $stat;
            }
        }
        return parent::getLiveStats($status, $data);
    }

    public static function getAvailableStats()
    {
        return [
            'VideoLibrary.GetMovies' => 'Movies',
            'VideoLibrary.GetMovieSets' => 'Movie Sets',
            'VideoLibrary.GetTVShows' => 'TV Shows',
            'VideoLibrary.GetEpisodes' => 'Episodes',
            'PVR.GetRecordings' => 'PVR Rec',
            'AudioLibrary.GetArtists' => 'Artists',
            'AudioLibrary.GetAlbums' => 'Albums',
            'AudioLibrary.GetSongs' => 'Songs',
            'VideoLibrary.GetMusicVideos' => 'Music Vids',
        ];
    }
}
