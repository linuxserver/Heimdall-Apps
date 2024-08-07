<?php

namespace App\SupportedApps\Monit;

class Monit extends \App\SupportedApps
{
    public static function getAvailableStats()
    {
        return [
            'running_services' => 'Running',
            'failed_services' => 'Failed',
            'load' => 'Load',
            'cpu' => 'CPU',
            'memory' => 'Memory',
            'swap' => 'Swap'
        ];
    }

    public function test()
    {
        $response = $this->executeCurl($this->url('/_status?format=xml'));
        if ($response['httpcode'] == 200) {
            echo 'Successfully communicated with the API';
        } else {
            echo 'Failed to connect to Monit. HTTP Status: ' . $response['httpcode'];
        }
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = [
            'running_services' => 'N/A',
            'failed_services' => 'N/A',
            'load' => 'N/A',
            'cpu' => 'N/A',
            'memory' => 'N/A',
            'swap' => 'N/A'
        ];

        $response = $this->executeCurl($this->url('/_status?format=xml'));

        if ($response['httpcode'] == 200) {
            $xml = simplexml_load_string($response['response']);
            $json = json_encode($xml);
            $data = json_decode($json, true);

            $running_services = 0;
            $failed_services = 0;

            if (isset($data['service'])) {
                if (isset($data['service'][0])) {
                    foreach ($data['service'] as $service) {
                        if (isset($service['status']) && $service['status'] == 0) {
                            $running_services++;
                        } else {
                            $failed_services++;
                        }
                    }
                } else {
                    if (isset($data['service']['status']) && $data['service']['status'] == 0) {
                        $running_services++;
                    } else {
                        $failed_services++;
                    }
                }
            }

            $status = 'active';
            $data['running_services'] = $running_services;
            $data['failed_services'] = $failed_services;
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

    private function url($endpoint)
    {
        $config = $this->config;
        $url = rtrim($config->url, '/');
        return $url . $endpoint;
    }

    private function executeCurl($url)
    {
        $username = $this->config->username;
        $password = $this->config->password;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'response' => $response,
            'httpcode' => $httpcode
        ];
    }
}
