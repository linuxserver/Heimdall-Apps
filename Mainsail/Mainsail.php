<?php

namespace App\SupportedApps\Mainsail;

use Carbon\Carbon;

class Mainsail extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        //https://moonraker.readthedocs.io/en/latest/web_api/#query-server-info
        $test = parent::appTest($this->url("/server/info"));
        echo $test->status;
    }

    public function livestats()
    {
        $status = "standby";
        //https://moonraker.readthedocs.io/en/latest/web_api/#query-printer-object-status
        $res = parent::execute($this->url('/printer/objects/query?display_status&print_stats&virtual_sdcard'));
        if (!$res) {
            return parent::getLiveStats($status, ["error" => "Connection"]);
        }
        $details = json_decode($res->getBody());
        $printer = $details->result->status;
        $data = [];

        $status = $printer->print_stats->state ?? "standby";

        if ($status == 'printing') {
            // Moonraker reports progress as a fraction between 0.0 and 1.0.
            // display_status is set by M73, virtual_sdcard is the file position fallback.
            $progress = $printer->display_status->progress ?? 0;
            if (!$progress) {
                $progress = $printer->virtual_sdcard->progress ?? 0;
            }
            $data["completed_pct"] = round($progress * 100) . '%';

            // Time actually spent printing, excluding pauses
            $elapsed = $printer->print_stats->print_duration ?? 0;
            $remaining = null;

            // Prefer the slicer estimate from the file metadata
            //https://moonraker.readthedocs.io/en/latest/web_api/#get-gcode-metadata
            $filename = $printer->print_stats->filename ?? '';
            if ($filename !== '') {
                $meta = parent::execute(
                    $this->url('/server/files/metadata?filename=' . rawurlencode($filename))
                );
                if ($meta) {
                    $estimated = json_decode($meta->getBody())->result->estimated_time ?? null;
                    if ($estimated) {
                        $remaining = $estimated - $elapsed;
                    }
                }
            }

            // Otherwise extrapolate from progress and elapsed time
            if ($remaining === null && $progress > 0 && $elapsed > 0) {
                $remaining = ($elapsed / $progress) - $elapsed;
            }

            if ($remaining === null) {
                $data["estimated"] = "N/A";
            } elseif ($remaining <= 0) {
                $data["estimated"] = "Soon!";
            } else {
                $data["estimated"] = Carbon::now()
                    ->addSeconds((int) round($remaining))
                    ->diffForHumans();
            }
        } else {
            $data["state"] = ucwords($status);
        }
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url, false) . ':' . $this->config->moonraker_port . $endpoint;
        return $api_url;
    }
}
