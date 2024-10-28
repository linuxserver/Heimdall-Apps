<?php

namespace App\SupportedApps\Diaspora;

class Diaspora extends \App\SupportedApps implements \App\EnhancedApps
{

    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    private function getBaseDomain($url) {
        $regex = '/^(https?:)/i';
        $baseurl = preg_replace($regex, '', $url);
        $baseurl = str_replace('/', '', $baseurl);
        return $baseurl;
    }
    private function fetchApi() {
        $url = "https://api.fediverse.observer/";
        $podurl = parent::normaliseurl($this->config->url);
        $podurl = $this->getBaseDomain($podurl);
        $query = 'query{
                    node(domain: "'.$podurl.'"){
                      id
                      name
                      metatitle
                      metadescription
                      detectedlanguage
                      metaimage
                      owner
                      onion
                      i2p
                      ip
                      ipv6
                      greenhost
                      host
                      dnssec
                      sslexpire
                      servertype
                      camo
                      terms
                      pp
                      support
                      softwarename
                      shortversion
                      fullversion
                      masterversion
                      daysmonitored
                      monthsmonitored
                      date_updated
                      date_laststats
                      date_created
                      metalocation
                      country
                      city
                      state
                      zipcode
                      countryname
                      lat
                      long
                      uptime_alltime
                      latency
                      sslexpire
                      total_users
                      active_users_monthly
                      active_users_halfyear
                      local_posts
                      comment_counts
                      score
                      status
                      signup
                      podmin_statement
                      services
                      protocols
                    }
                  }';
        $data = array('query' => $query);
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    "Content-Type: application/json"
                ],
                'content' => json_encode($data),
            ],
        ];
        $context = stream_context_create($options);
        $res = file_get_contents($url, false, $context);
        $rdata = json_decode($res, true);
        return $rdata;
    }

    public function test()
    {
        try {
            $this->fetchApi("/");
            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo $err->getMessage();
        }
    }

    public function livestats()
    {
        $status = "inactive";

        $RawDetails = $this->fetchApi();
        $nodeCount = count($RawDetails['data']['node']);
        if ($nodeCount > 0) {
        $Details = $RawDetails['data']['node'][0];
        $data = [
            "COMMENT_COUNTS" => $Details["comment_counts"],
            "LOCAL_POSTS" => $Details["local_posts"],
            "TOTAL_USERS" => $Details["total_users"],
            "ACTIVE_USERS_MONTHLY" => $Details["active_users_monthly"],
            "ACTIVE_USERS_HALFYEAR" => $Details["active_users_halfyear"],
            "SIGNUP" => $Details["signup"],
        ];

        foreach ($this->config->availablestats as $stat) {
            $newstat = new \stdClass();
            $newstat->title = self::getAvailableStats()[$stat];
            $newstat->value = number_format($data[strtoupper($stat)]);
            $data["visiblestats"][] = $newstat;
        }
        $status = "active";
        return parent::getLiveStats($status, $data);
        } else {
            return NULL;
        }
    }


    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url).$endpoint;
        return $api_url;
    }

    public static function getAvailableStats()
    {
        return [
            //"id" => "ID",
            //"name" => "NAME",
            //"metatitle" => "METATITLE",
            //"metadescription" => "METADESCRIPTION",
            //"detectedlanguage" => "DETECTEDLANGUAGE",
            //"metaimage" => "METAIMAGE",
            //"owner" => "OWNER",
            //"onion" => "ONION",
            //"i2p" => "I2P",
            //"ip" => "IP",
            //"ipv6" => "IPV6",
            //"greenhost" => "GREENHOST",
            //"host" => "HOST",
            //"dnssec" => "DNSSEC",
            //"sslexpire" => "SSLEXPIRE",
            //"servertype" => "SERVERTYPE",
            //"camo" => "CAMO",
            //"terms" => "TERMS",
            //"pp" => "PP",
            //"support" => "SUPPORT",
            //"softwarename" => "SOFTWARENAME",
            //"shortversion" => "SHORTVERSION",
            //"fullversion" => "FULLVERSION",
            //"masterversion" => "MASTERVERSION",
            //"daysmonitored" => "DAYSMONITORED",
            //"monthsmonitored" => "MONTHSMONITORED",
            //"date_updated" => "Date Updated",
            //"date_laststats" => "Date Laststats",
            //"date_created" => "Date Created",
            //"metalocation" => "METALOCATION",
            //"country" => "COUNTRY",
            //"city" => "CITY",
            //"state" => "STATE",
            //"zipcode" => "ZIP",
            //"countryname" => "Country Name",
            //"lat" => "LAT",
            //"long" => "LONG",
            //"uptime_alltime" => "Uptime Alltime",
            //"latency" => "Latency",
            //"sslexpire" => "SSLExpire",
            "total_users" => "Total Users",
            "active_users_monthly" => "Active Users Monthly",
            "active_users_halfyear" => "Active Users HalfYear",
            "local_posts" => "Local Posts",
            "comment_counts" => "Comment Counts",
            //"score" => "Score",
            //"status" => "Status",
            "signup" => "Signup",
            //"podmin_statement" => "Podmin Statement",
            //"services" => "Services",
            //"protocols" => "Protocols",
        ];
    }

}
