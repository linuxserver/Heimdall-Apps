<?php

namespace App\SupportedApps\AVMFritzbox;

class AVMFritzbox extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;
    protected $method = 'POST';

    function __construct()
    {
    }

    public function getAttrs($calltype)
    {
        switch ($calltype) {
            case "statusInfo":
                $verb = "GetStatusInfo";
                $ns = "WANIPConnection";
                break;
            case "linkProperties":
                $verb = "GetCommonLinkProperties";
                $ns = "WANCommonInterfaceConfig";
                break;
            default:
                $verb = "GetAddonInfos";
                $ns = "WANCommonInterfaceConfig";
        }

        $attrs = [
            "headers" => [
                "Content-Type" => "text/xml; charset='utf-8'",
                "SoapAction" => "urn:schemas-upnp-org:service:{$ns}:1#{$verb}",
            ],
            "body" => "<?xml version='1.0' encoding='utf-8'?>
                       <s:Envelope s:encodingStyle='http://schemas.xmlsoap.org/soap/encoding/' xmlns:s='http://schemas.xmlsoap.org/soap/envelope/'>
                            <s:Body>
                                <u:{$verb} xmlns:u='urn:schemas-upnp-org:service:{$ns}:1' />
                            </s:Body>
                       </s:Envelope>",
        ];

        return $attrs;
    }

    public static function getAvailableStats()
    {
        return [
            "NewConnectionStatus" => "Status",
            "NewUptime" => "Up Time",
            "NewLayer1DownstreamMaxBitRate" => "Max Down",
            "NewLayer1UpstreamMaxBitRate" => "Max Up",
            "NewByteReceiveRate" => "Down",
            "NewByteSendRate" => "Up",
            "NewX_AVM_DE_TotalBytesReceived64" => "Received",
            "NewX_AVM_DE_TotalBytesSent64" => "Send",
        ];
    }

    private static function formatValueUsingStat($stat, $value)
    {
        if (!isset($value)) {
            return "N/A";
        }

        switch ($stat) {
            case "NewConnectionStatus":
                return "{$value}";
            case "NewUptime":
                return self::toTime((int)$value);
            case "NewLayer1DownstreamMaxBitRate":
            case "NewLayer1UpstreamMaxBitRate":
                return format_bytes(((int)$value) / 8, false, "<span>", "/s</span>");
            case "NewByteReceiveRate":
            case "NewByteSendRate":
                return format_bytes((int)$value, false, "<span>", "/s</span>");
            case "NewX_AVM_DE_TotalBytesReceived64":
            case "NewX_AVM_DE_TotalBytesSent64":
                return format_bytes((int)$value, false, "<span>", "</span>");
            default:
                return "{$value}";
        }
    }

    private static function toTime($timestamp)
    {
        $hours = floor($timestamp / 3600);
        $minutes = floor($timestamp % 3600 / 60);
        $seconds = $timestamp % 60;

        $hourDuration = sprintf('%02dh', $hours);
        $minDuration =  sprintf('%02dm', $minutes);
        $secDuration =  sprintf('%02ds', $seconds);
        $HourMinSec = $hourDuration . $minDuration . $secDuration;

        if ($hourDuration > 0) {
            $hourDuration = $hourDuration;
        } else {
            $hourDuration = '00h';
        }

        if ($minDuration > 0) {
            $minDuration = $minDuration;
        } else {
            $minDuration = '00m';
        }

        if ($secDuration > 0) {
            $secDuration = $secDuration;
        } else {
            $secDuration = '00s';
        }

        $HourMinSec = $hourDuration . $minDuration . $secDuration;

        return $HourMinSec;
    }

    public function test()
    {
        $test = parent::appTest(
            $this->url("statusInfo"),
            $this->getAttrs("statusInfo")
        );
        echo $test->status;
    }

    public function livestats()
    {
        $status = "active";

        $statusInfo = parent::execute(
            $this->url("statusInfo"),
            $this->getAttrs("statusInfo")
        );
        $statusInfoDetails = simplexml_load_string($statusInfo->getBody())->children('s', true)->children('u', true)->children();

        $linkProperties = parent::execute(
            $this->url("linkProperties"),
            $this->getAttrs("linkProperties")
        );
        $linkPropertiesDetails = simplexml_load_string($linkProperties->getBody())->children('s', true)->children('u', true)->children();

        $addonInfo = parent::execute(
            $this->url("addonInfo"),
            $this->getAttrs("addonInfo")
        );
        $addonInfoDetails = simplexml_load_string($addonInfo->getBody())->children('s', true)->children('u', true)->children();

        $data = ["visiblestats" => []];

        if ($statusInfoDetails && $linkPropertiesDetails && $addonInfoDetails) {
            foreach ($this->config->availablestats as $stat) {
                if (!isset(self::getAvailableStats()[$stat])) {
                    continue;
                }

                $newstat = new \stdClass();
                $newstat->title = self::getAvailableStats()[$stat];

                switch ($stat) {
                    case "NewConnectionStatus":
                    case "NewUptime":
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $statusInfoDetails->$stat
                        );
                        break;
                    case "NewLayer1DownstreamMaxBitRate":
                    case "NewLayer1UpstreamMaxBitRate":
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $linkPropertiesDetails->$stat
                        );
                        break;
                    case "NewByteReceiveRate":
                    case "NewByteSendRate":
                    case "NewX_AVM_DE_TotalBytesReceived64":
                    case "NewX_AVM_DE_TotalBytesSent64":
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $addonInfoDetails->$stat
                        );
                        break;
                    default:
                        $newstat->value = self::formatValueUsingStat(
                            $stat,
                            $addonInfoDetails->$stat
                        );
                }

                $data["visiblestats"][] = $newstat;
            }
        }

        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        if ($endpoint == "statusInfo") {
            $endpoint = "WANIPConn1";
        } else {
            $endpoint = "WANCommonIFC1";
        }

        $fritzURL = $this->config->url;

        $api_url = "$fritzURL:49000/igdupnp/control/{$endpoint}";

        return $api_url;
    }
}
