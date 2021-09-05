<?php namespace App\SupportedApps\JDownloader;

class MYJDAPI
{
    private $api_url = "http://api.jdownloader.org";
    private $version = "1.0.29092020";
    private $rid_counter;
    private $appkey = "MYJDAPI_php";
    private $apiVer = 1;
    private $devices;
    private $loginSecret;
    private $deviceSecret;
    private $sessiontoken;
    private $regaintoken;
    private $serverEncryptionToken;
    private $deviceEncryptionToken;
    private $SERVER_DOMAIN = "server";
    private $DEVICE_DOMAIN = "device";
    private $device_name = null;

    public function __construct( $email = "", $password = "", $device_name = null) {
        $this -> rid_counter = time();
        if( ($email != "") && ($password != "")) {
            $res = $this -> connect( $email, $password);
            if( $res === false) {
                return false;
            }
        }

        $this -> setDeviceName( $device_name);
    }

    public function getVersion() {
        return $this -> version;
    }

    //Set device name
    public function setDeviceName( $device_name) {
        if( !is_null( $device_name) && is_string( $device_name)) {
            $this -> device_name = $device_name;
            return true;
        }
        return false;
    }

    //Get device name
    public function getDeviceName() {
        return $this -> device_name;
    }

    // Connect to api.jdownloader.org
    // if success - setup loginSecret, deviceSecret, sessiontoken, regaintoken, serverEncryptionToken, deviceEncryptionToken
    // input: email, password
    // return: true or false
    public function connect( $email, $password) {
        $this -> loginSecret = $this -> createSecret( $email, $password, $this -> SERVER_DOMAIN);
        $this -> deviceSecret = $this -> createSecret( $email, $password, $this -> DEVICE_DOMAIN);
        $query = "/my/connect?email=".urlencode( $email)."&appkey=".urlencode( $this -> appkey);
        $res = $this -> callServer( $query, $this -> loginSecret);
        if( $res === false) {
            return false;
        }
        $content_json = json_decode( $res, true);
        $this -> sessiontoken = $content_json["sessiontoken"];
        $this -> regaintoken = $content_json["regaintoken"];
        $this -> serverEncryptionToken = $this -> updateEncryptionToken( $this -> loginSecret, $this -> sessiontoken);
        $this -> deviceEncryptionToken = $this -> updateEncryptionToken( $this -> deviceSecret, $this -> sessiontoken);
        return true;
    }

    // Reconnect to api.jdownloader.org
    // if success - setup sessiontoken, regaintoken, serverEncryptionToken, deviceEncryptionToken
    // return: true or false
    public function reconnect() {
        $query = "/my/reconnect?appkey=".urlencode( $this -> appkey)."&sessiontoken=".urlencode( $this -> sessiontoken)."&regaintoken=".urlencode( $this -> regaintoken);
        $res = $this -> callServer( $query, $this -> serverEncryptionToken);
        if( $res === false) {
            return false;
        }
        $content_json = json_decode( $res, true);
        $this -> sessiontoken = $content_json["sessiontoken"];
        $this -> regaintoken = $content_json["regaintoken"];
        $this -> serverEncryptionToken = $this -> updateEncryptionToken( $this -> serverEncryptionToken, $this -> sessiontoken);
        $this -> deviceEncryptionToken = $this -> updateEncryptionToken( $this -> deviceSecret, $this -> sessiontoken);
        return true;
    }

    // Disconnect from api.jdownloader.org
    // if success - cleanup sessiontoken, regaintoken, serverEncryptionToken, deviceEncryptionToken
    // return: true or false
    public function disconnect() {
        $query = "/my/disconnect?sessiontoken=".urlencode( $this -> sessiontoken);
        $res = $this -> callServer( $query, $this -> serverEncryptionToken);
        if( $res === false) {
            return false;
        }
        $content_json = json_decode( $res, true);
        $this -> sessiontoken = "";
        $this -> regaintoken = "";
        $this -> serverEncryptionToken = "";
        $this -> deviceEncryptionToken = "";
        return true;
    }

    // Enumerate Devices connected to my.jdownloader.org
    // if success - setup devices
    // call getDirectConnectionInfos to setup devices
    // return: true or false
    public function enumerateDevices() {
        $query = "/my/listdevices?sessiontoken=".urlencode( $this -> sessiontoken);
        $res = $this -> callServer( $query, $this -> serverEncryptionToken);
        if( $res === false) {
            return false;
        }
        $content_array = json_decode( $res, true);
        $this -> devices = $content_array["list"];
        $res = $this -> getDirectConnectionInfos();
        if( $res === false) {
            return false;
        }
        return true;
    }

    // Call action "/device/getDirectConnectionInfos" for each devices
    // if success - setup devices with infos
    // return: true or false
    public function getDirectConnectionInfos() {
        foreach( $this -> devices as $i => &$ivalue) {
            $res = $this -> callAction( "/device/getDirectConnectionInfos");
            if( $res === false) {
                return false;
            }
            $content_array = json_decode( $res, true);
            $this -> devices[$i]["infos"] = $content_array["data"]["infos"];
        }
        return true;
    }

    // Send links to device using action /linkgrabberv2/addLinks
    // input: device - name of device, links - array or string of links, package_name - custom package name
    // {"url":"/linkgrabberv2/addLinks",
    //  "params":["{\"priority\":\"DEFAULT\",\"links\":\"YOURLINK\",\"autostart\":true, \"packageName\": \"YOURPKGNAME\"}"],
    //  "rid":YOURREQUESTID,"apiVer":1}
    public function addLinks( $links, $package_name = null) {
        if( !is_array( $this -> devices)) {
            $this -> enumerateDevices();
        }
        if( is_array( $links)) {
            $links = implode( ",", $links);
        }
        $params = '\"priority\":\"DEFAULT\",\"links\":\"'.$links.'\",\"autostart\":true, \"packageName\": \"'.$package_name.'\"';
        $res = $this -> callAction( "/linkgrabberv2/addLinks", $params);
        if( $res === false) {
            return false;
        }
        return true;
    }

    // Retrive links
    public function queryLinks( $params = []) {
        //taken from: https://docs.google.com/document/d/1IGeAwg8bQyaCTeTl_WyjLyBPh4NBOayO0_MAmvP5Mu4/edit# (LinkQueryStorable)
        $params_default = [
            "bytesTotal" => true,
            "comment" => true,
            "status" => true,
            "enabled" => true,
            "maxResults" => -1,
            "startAt" => 0,
            "packageUUIDs" => null,
            "host" => true,
            "url" => true,
            "bytesLoaded" => true,
            "speed" => true,
            "eta" => true,
            "finished" => true,
            "priority" => true,
            "running" => true,
            "skipped" => true,
            "extractionStatus" => true
        ];

        $params = array_merge( $params_default, $params);

        $res = $this -> callAction( "/downloadsV2/queryLinks", $params);
        return $res;
    }
    // Make a call to my.jdownloader.org
    // input: query - path+params, key - key for encryption, params - additional params
    // return: result from server or false
    private function callServer( $query, $key, $params = false) {
        if( $params != "") {
            if( $key != "") {
                $params = $this -> encrypt( $params, $key);
            }
            $rid = $this -> rid_counter;
        } else {
            $rid = $this -> getUniqueRid();
        }
        if( strpos( $query, "?") !== false) { $query = $query."&"; } else { $query = $query."?"; }
        $query = $query."rid=".$rid;
        $signature = $this -> sign( $key, $query);
        $query = $query."&signature=".$signature;
        $url = $this -> api_url.$query;
        if( $params != "") {
            $res = $this -> postQuery( $url, $params, $key);
        } else {
            $res = $this -> postQuery( $url, "", $key);
        }
        if( $res === false) {
            return false;
        }
        $content_json = json_decode( $res, true);
        if( $content_json["rid"] != $this -> rid_counter) {
            return false;
        }
        return $res;
    }

    // Make a call to API function on my.jdownloader.org
    // input: device_name - name of device to send action, action - action pathname, params - additional params
    // return: result from server or false
    public function callAction( $action, $params = false) {
        if( !is_array( $this -> devices)) {
            $this -> enumerateDevices();
        }

        if( !is_array( $this -> devices) || ( count( $this -> devices) == 0)) {
            return false;
        }

        foreach( $this -> devices as $i => &$ivalue) {
            if(strtolower($this -> devices[$i]["name"]) == strtolower($this->getDeviceName())) {
                $device_id = $this -> devices[$i]["id"];
            }
        }
        if( !isset( $device_id)) {
            return false;
        }
        $query = "/t_".urlencode( $this -> sessiontoken)."_".urlencode( $device_id).$action;
        if( $params != "") {
            if(is_array($params)) {
                $params = str_replace('"', '\"', substr(json_encode($params),1,-1));
            }
            $json_data = '{"url":"'.$action.'","params":["{'.$params.'}"],"rid":'.$this -> getUniqueRid().',"apiVer":'.$this -> apiVer.'}';
        } else {
            $json_data = '{"url":"'.$action.'","rid":'.$this -> getUniqueRid().',"apiVer":'.$this -> apiVer.'}';
        }
        $json_data = $this -> encrypt( $json_data, $this -> deviceEncryptionToken);
        $url = $this -> api_url.$query;
        $res = $this -> postQuery( $url, $json_data, $this -> deviceEncryptionToken);
        if( $res === false) {
            return false;
        }
        $content_json = json_decode( $res, true);
        if( $content_json["rid"] != $this -> rid_counter) {
            return false;
        }
        return $res;
    }

    // Genarate new unique rid
    // return new rid_counter
    public function getUniqueRid() {
        $this -> rid_counter++;
        return $this -> rid_counter;
    }

    // Return current rid_counter
    public function getRid() {
        return $this -> rid_counter;
    }

    private function createSecret( $username, $password, $domain) {
        return hash( "sha256", strtolower( $username) . $password . strtolower( $domain), true);
    }

    private function sign( $key, $data) {
        return hash_hmac( "sha256", $data, $key);
    }

    private function decrypt( $data, $iv_key) {
        $iv = substr( $iv_key, 0, strlen( $iv_key)/2);
        $key = substr( $iv_key, strlen( $iv_key)/2);
        return openssl_decrypt( base64_decode( $data), "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv);
    }

    private function encrypt( $data, $iv_key) {
        $iv = substr( $iv_key, 0, strlen( $iv_key)/2);
        $key = substr( $iv_key, strlen( $iv_key)/2);
        return base64_encode( openssl_encrypt( $data, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv));
    }

    private function updateEncryptionToken( $oldToken, $updateToken) {
        return hash( "sha256", $oldToken.pack( "H*", $updateToken), true);
    }

    // postQuery( $url, $postfields, $iv_key)
    // Make Get or Post Request to $url ( $postfields)
    // Send Payload data if $postfields not null
    // return plain response or decrypted response if $iv_key not null
    private function postQuery( $url, $postfields = false, $iv_key = false) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
        if( $postfields) {
            $headers[] = "Content-Type: application/aesjson-jd; charset=utf-8";
            curl_setopt( $ch, CURLOPT_POST, true);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt( $ch, CURLOPT_HEADER, true);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = array();
        $response["text"] = curl_exec( $ch);
        $response["info"] = curl_getinfo( $ch);
        $response["code"] = $response["info"]["http_code"];
        if( $response["code"] != 200) {
            return false;
        }
        if( $postfields) {
            $response["body"] = substr( $response["text"], $response["info"]["header_size"]);
        } else {
            $response["body"] = $response["text"];
        }
        if( $iv_key) {
            $response["body"] = $this -> decrypt( $response["body"], $iv_key);
        }
        curl_close( $ch);
        return $response["body"];
    }
}

class JDownloader extends \App\SupportedApps implements \App\EnhancedApps {

    public $config;

    public $jdapi;

    function __construct() {
        $this->jdapi = new MYJDAPI();
    }

    private function getHumanSpeed($bps)
    {
        $byteUnits = ["B/s", "KB/s", "MB/s", "GB/s"];
        $i = 0;
        while($bps > 1024)
        {
            $bps = $bps / 1024;
            $i++;
        }

        return max($bps, 0.0) . $byteUnits[$i];
    }

    public function login()
    {
        if (!isset($this->config->username) || empty($this->config->username) || !isset($this->config->password) || empty($this->config->password) ||
            !isset($this->config->devicename) || empty($this->config->devicename))
            return false;
        
        if(!$this->jdapi->connect($this->config->username, $this->config->password))
        {
            return false;
        }

        if(!$this->jdapi->setDeviceName($this->config->devicename))
        {
            return false;
        }
        
        return true;
    }

    public function test()
    {
        if (!isset($this->config->username) || empty($this->config->username) || !isset($this->config->password) || empty($this->config->password) ||
            !isset($this->config->devicename) || empty($this->config->devicename))
        {
            echo "E-Mail, Password and DeviceName are required!";
            return false;
        }

        if(!$this->login())
        {
            echo "Invalid login credentials or device name";
            return false;
        }

        $links = $this->jdapi->queryLinks();
        if(!isset($links))
        {
            echo "Cannot query links, device name invalid?";
            return false;
        }

        echo "Connection to my.jdownloader.org successful";
        return true;
    }

    public function livestats()
    {
        $status = 'inactive';
        // Bps
        $speed = 0;
        if($this->login())
        {
            $speedJson = $this->jdapi->callAction("/downloadcontroller/getSpeedInBps");
            if($speedJson != null)
            {
                $speedObj = json_decode($speedJson);
                $speed = $speedObj->data;
            }

            $stateJson = $this->jdapi->callAction("/downloads/getJDState");
            if($stateJson != null)
            {
                $stateObj = json_decode($stateJson);
                $state = $stateObj->data;
            }
        }

        $data = [
            "current_speed" => isset($speed) && isset($state) ? $this->getHumanSpeed($speed)." ({$state})" : "Check configuration!",
        ];
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }
}
