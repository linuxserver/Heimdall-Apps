<?php namespace App\SupportedApps\Tdarr;

class Tdarr extends \App\SupportedApps implements \App\EnhancedApps
{

    public $config;

    function __construct()
    {
    }

    public function test()
    {
        $url = $this->url('api/v2/status/');
        $test = parent::appTest($url);

        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';

        $reqData = array(
            'data' => array(
                'collection' => 'StatisticsJSONDB',
                'mode' => 'getById',
                'docID' => 'statistics'
            ) ,
        );

        $url = $this->url('api/v2/cruddb/');
        // Setup cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ) ,
            CURLOPT_POSTFIELDS => json_encode($reqData)
        ));

        // Send the request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false)
        {
            die(curl_error($ch));
        }

        $details = [];
        // Decode the response
        $details = json_decode($response, true);

        $data = [];
        $data['queue'] = '';
        $data['processed'] = '';
        $data['errored'] = '';

        if ($details)
        {
            $data['queue'] = $details['table1Count'] + $details['table4Count'];
            $data['processed'] = $details['table2Count'] + $details['table5Count'];
            $errored = $details['table3Count'] + $details['table6Count'];
            if ($errored > 0)
            {
                $data['errored'] = $errored;
            }
        }

        // Close the cURL handler
        curl_close($ch);

        return parent::getLiveStats($status, $data);
    }
    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this
            ->config
            ->url) . $endpoint;
        return $api_url;
    }
}

