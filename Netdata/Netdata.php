<?php

namespace App\SupportedApps\Netdata;

class Netdata extends \App\SupportedApps implements \App\EnhancedApps
{
    public static function getAvailableStats()
    {
        return [
            'cpu' => 'CPU',
            'memory_free' => 'Mem Free',
            'memory_used' => 'Mem Used',
            'load1' => 'Load 1',
            'load5' => 'Load 5',
            'load15' => 'Load 15',
            'disk_in' => 'Disk In',
            'disk_out' => 'Disk Out',
            'network_in' => 'Net In',
            'network_out' => 'Net Out'
        ];
    }

    public function test()
    {
        $response = $this->executeCurl($this->url('/api/v1/allmetrics?format=json'));
        if ($response['httpcode'] == 200) {
            echo 'Successfully communicated with the API';
        } else {
            echo 'Failed to connect to Netdata. HTTP Status: ' . $response['httpcode'];
        }
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [];
        $response = $this->executeCurl($this->url('/api/v1/allmetrics?format=json'));

        if ($response['httpcode'] == 200) {
            $json = json_decode($response['response'], true);

            $cpu = isset($json['system.cpu']['dimensions']['idle']['value'])
                ? number_format(100 - $json['system.cpu']['dimensions']['idle']['value'], 1) . '%'
                : 'N/A';

            $memoryFree = isset($json['system.ram']['dimensions']['free']['value'])
                ? number_format($json['system.ram']['dimensions']['free']['value'], 1) . 'MB'
                : 'N/A';
            $memoryUsed = isset($json['system.ram']['dimensions']['used']['value'])
                ? number_format($json['system.ram']['dimensions']['used']['value'], 1) . 'MB'
                : 'N/A';

            $load1 = isset($json['system.load']['dimensions']['load1']['value'])
                ? $json['system.load']['dimensions']['load1']['value']
                : 'N/A';
            $load5 = isset($json['system.load']['dimensions']['load5']['value'])
                ? $json['system.load']['dimensions']['load5']['value']
                : 'N/A';
            $load15 = isset($json['system.load']['dimensions']['load15']['value'])
                ? $json['system.load']['dimensions']['load15']['value']
                : 'N/A';

            $diskIn = isset($json['system.io']['dimensions']['in']['value'])
                ? number_format($json['system.io']['dimensions']['in']['value'], 1) . 'KB/s'
                : 'N/A';
            $diskOut = isset($json['system.io']['dimensions']['out']['value'])
                ? number_format($json['system.io']['dimensions']['out']['value'], 1) . 'KB/s'
                : 'N/A';

            $networkIn = isset($json['system.net']['dimensions']['InOctets']['value'])
                ? number_format($json['system.net']['dimensions']['InOctets']['value'], 1) . 'KB/s'
                : 'N/A';
            $networkOut = isset($json['system.net']['dimensions']['OutOctets']['value'])
                ? number_format($json['system.net']['dimensions']['OutOctets']['value'], 1) . 'KB/s'
                : 'N/A';

            $status = 'active';
            $data = [
                'cpu' => $cpu,
                'memory_free' => $memoryFree,
                'memory_used' => $memoryUsed,
                'load1' => $load1,
                'load5' => $load5,
                'load15' => $load15,
                'disk_in' => $diskIn,
                'disk_out' => $diskOut,
                'network_in' => $networkIn,
                'network_out' => $networkOut
            ];
        } else {
            $data = [
                'error' => 'Failed to connect to Netdata. HTTP Status: ' . $response['httpcode']
            ];
        }

        $visiblestats = [];
        if (isset($this->config->availablestats)) {
            foreach ($this->config->availablestats as $stat) {
                $visiblestats[] = [
                    'title' => self::getAvailableStats()[$stat],
                    'value' => $data[$stat] ?? 'N/A'
                ];
            }
        }

        return parent::getLiveStats($status, ['visiblestats' => $visiblestats]);
    }

    public function url($endpoint)
    {
        $config = $this->config;
        $url = rtrim($config->url, '/');
        return $url . $endpoint;
    }

    public function executeCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'response' => $response,
            'httpcode' => $httpcode
        ];
    }
}
