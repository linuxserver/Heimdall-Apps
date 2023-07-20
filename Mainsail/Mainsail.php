<?php namespace App\SupportedApps\Mainsail;

use Carbon\Carbon;

class Mainsail extends \App\SupportedApps implements \App\EnhancedApps
{
        public $config;

        //protected $login_first = true; // Uncomment if api requests need to be authed first
        //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

        function __construct()
        {
        }

        public function test()
        {
                $test = parent::appTest($this->url("/server/info"));
                echo $test->status;
        }

        public function livestats()
        {
                $status = "standby";
                $res = parent::execute($this->url('/printer/objects/query?display_status&toolhead&print_stats'));
                if (!$res) {
                        return parent::getLiveStats($status, ["error" => "Connection"]);
                }
                $details = json_decode($res->getBody());
                $data = [];
                
                $status = $details->result->status->print_stats->state;
                
                if ($status == 'printing'){

                    $data["completed_pct"] = round($details->result->status->display_status->progress) . '%';
    
                    $total_seconds = $details->result->status->toolhead->estimated_print_time;
                    $completed_seconds = $details->result->status->print_stats->print_duration;
                    // $total_seconds = 10000;
                    //$completed_seconds = 1000;
                    if ($completed_seconds === null) {
                            $data["estimated"] = "N/A";
                    } elseif ($completed_seconds > $total_seconds) {
                            $data["estimated"] = "Soon!";
                    } elseif ($completed_seconds > 0) {
                            $remaining_seconds = $total_seconds - $completed_seconds;
                            $data["estimated"] = Carbon::now()
                                    ->addSeconds($remaining_seconds)
                                    ->diffForHumans();
                    } else {
                            $data["estimated"] = "N/A";
                    }
 
                }
                else {
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
