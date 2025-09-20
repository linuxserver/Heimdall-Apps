<?php

namespace App\SupportedApps\Tachidesk;

class Tachidesk extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct() {
    }

    public function test()
    {
        $test = parent::appTest($this->url('api/v1/settings/about'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';

        $category  =  $this->getCategory();

        $res = parent::execute($this->url('api/v1/category/' . $category));
        $details = json_decode($res->getBody());

        if ($details) {
            $status = 'active';
            $data = [
                "unread_chapters_count" => $this->getChapterCount($details),
                "active_series_count" => count($details),
            ];
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    private function getChapterCount($categoryDetails)
    {
        $chapterCount = 0;
        foreach ($categoryDetails as $series) {
            $chapterCount += $series->unreadCount ?? 0;
        }
        return $chapterCount;
    }

    private function getCategory()
    {
        if (isset($this->config->category)) {
            $res = parent::execute($this->url('api/v1/category'));
            $resData = json_decode($res->getBody());
            $collectedResponse = collect($resData);

            $filteredResponse = $collectedResponse->whereIn('name', [$this->config->category]);
            if ($filteredResponse->count() > 0) {
                return $filteredResponse->first()->id;
            }
        }

        return '0';
    }
}
