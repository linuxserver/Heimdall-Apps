<?php

namespace App\SupportedApps\PodFetch;

class PodFetch extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->url("/api/v1/info"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "inactive";

        $res1 = parent::execute($this->url("/api/v1/podcasts"));
        $podcasts = json_decode($res1->getBody());

        $res2 = parent::execute($this->url("/api/v1/sys/info"));
        $sysinfo = json_decode($res2->getBody());

        $data = [];
        if ($podcasts && is_array($podcasts)) {
            $data["podcast_count"] = count($podcasts);
            $status = "active";
        }
        if ($sysinfo && isset($sysinfo->podcast_directory)) {
            $data["disk_used"] = self::fmt($sysinfo->podcast_directory);
            $free = array_sum(array_column($sysinfo->disks ?? [], 'available_space'));
            $data["disk_free"] = self::fmt($free);
            $status = "active";
        }

        return parent::getLiveStats($status, $data);
    }

    private static function fmt($bytes)
    {
        if ($bytes >= 1099511627776) {
            return round($bytes / 1099511627776, 1) . 'T';
        }
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1) . 'G';
        }
        return round($bytes / 1048576, 1) . 'M';
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url);
        $username = $this->config->username;
        $password = $this->config->password;
        $rebuild_url = str_replace(
            "http://",
            "http://" . $username . ":" . $password . "@",
            $api_url
        );
        $rebuild_url = str_replace(
            "https://",
            "https://" . $username . ":" . $password . "@",
            $rebuild_url
        );
        $rebuild_url = rtrim($rebuild_url, "/");
        return $rebuild_url . $endpoint;
    }
}
