<?php namespace App\SupportedApps;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class Deluge extends SupportedApps implements EnhancedApps {

    //public function test()
    //public function execute()


    public function livestats()
    {
        $html = '';
        $active = 'active';
        $jar = $this->login()[1];
        $res = $this->getDetails($jar);
        $response = json_decode($res->getBody());
        $data['download_rate'] = $this->formatBytes($response->result->stats->download_rate);
        $data['upload_rate'] = $this->formatBytes($response->result->stats->upload_rate);
        $data['seed_count'] = $response->result->filters->state[2][1];
        $data['leech_count'] = $response->result->filters->state[1][1];
        $html = view('SupportedApps::Deluge.livestats')->with('products', $products)->render();
        
        return json_encode(['status' => $active, 'html' => $html]);
    }
    public function getDetails($jar)
    {
        $config = $this->config;
        $url = $config->url;
        $url = rtrim($url, '/');
        $api_url = $url.'/json';
        $client = new Client(['http_errors' => false, 'timeout' => 15, 'connect_timeout' => 15]);
        $res = $client->request('POST', $api_url, [
            'body' => '{"method": "web.update_ui", "params": [["none"], {}], "id": 1}',
            'cookies' => $jar,
            'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json']
        ]);
        return $res;
    }

    function formatBytes($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow] . 'ps'; 
    }
}
