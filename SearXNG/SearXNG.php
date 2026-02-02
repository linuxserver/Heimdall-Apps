<?php

namespace App\SupportedApps\SearXNG;

class SearXNG extends \App\SupportedApps implements \App\SearchInterface
{
    public $type = "external";

    public function getResults($query, $provider)
    {
        $url = rtrim($provider->url, "/");
        $q = urlencode($query);
        return redirect($url . "/search?q=" . $q);
    }
}
