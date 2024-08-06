<?php

namespace App\SupportedApps\Monit;

class Monit extends \App\SupportedApps
{
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
        $data = [];

        $response = $this->executeCurl($this->url('/_status?format=xml'));

        if ($response['httpcode'] == 200) {
            $xml = simplexml_load_string($response['response']);
            $json = json_encode($xml);
            $data = json_decode($json, true);

            // 计算运行的服务数量和失败的服务数量
            $running_services = 0;
            $failed_services = 0;

            if (isset($data['service'])) {
                if (isset($data['service'][0])) {
                    // 如果是多个服务的情况
                    foreach ($data['service'] as $service) {
                        if (isset($service['status']) && $service['status'] == 0) {
                            $running_services++;
                        } else {
                            $failed_services++;
                        }
                    }
                } else {
                    // 如果是单个服务的情况
                    if (isset($data['service']['status']) && $data['service']['status'] == 0) {
                        $running_services++;
                    } else {
                        $failed_services++;
                    }
                }
            }

            $status = 'active';
            $data = [
                'running_services' => $running_services,
                'failed_services' => $failed_services
            ];
        } else {
            $data = [
                'error' => 'Failed to connect to Monit. HTTP Status: ' . $response['httpcode']
            ];
        }

        // 返回JSON格式数据
        return parent::getLiveStats($status, $data);
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
