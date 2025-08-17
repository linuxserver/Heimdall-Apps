<?php

namespace App\SupportedApps\WhatsupDocker;

class WhatsupDocker extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \GuzzleHttp\Cookie\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        // If auth credentials are provided, test the authenticated endpoint
        if ($this->hasAuthCredentials()) {
            // Test containers endpoint which requires authentication
            $test = parent::appTest($this->url('api/containers'), $this->getAttrs());
            echo $test->status;
        } else {
            // No auth configured, test the public app endpoint
            $test = parent::appTest($this->url('api/app'), $this->getAttrs());
            echo $test->status;
        }
    }

    public function livestats()
    {
        $status = 'inactive';

        try {
            // Get watched containers from WUD API
            $res = parent::execute($this->url('api/containers'), $this->getAttrs());
            $containers = json_decode($res->getBody(), true);

            if ($containers && is_array($containers)) {
                $status = 'active';

                // Calculate container statistics
                $totalContainers = count($containers);
                $updatableContainers = 0;
                $watchedContainers = 0;

                foreach ($containers as $container) {
                    // Count updatable containers (based on API doc structure)
                    if (isset($container['updateAvailable']) && $container['updateAvailable'] === true) {
                        $updatableContainers++;
                    }

                    // All containers returned by /api/containers are watched containers
                    $watchedContainers++;
                }

                $details = ['visiblestats' => []];

                // Show configured stats, or all if none selected
                $availableStats = isset($this->config->availablestats) && !empty($this->config->availablestats)
                    ? $this->config->availablestats
                    : array_keys(self::getAvailableStats());

                foreach ($availableStats as $stat) {
                    $newstat = new \stdClass();
                    $newstat->title = self::getAvailableStats()[$stat];

                    switch ($stat) {
                        case 'TotalContainers':
                            $newstat->value = $totalContainers;
                            break;
                        case 'UpdatableContainers':
                            $newstat->value = $updatableContainers;
                            break;
                        case 'WatchedContainers':
                            $newstat->value = $watchedContainers;
                            break;
                        default:
                            $newstat->value = 0;
                    }

                    $details['visiblestats'][] = $newstat;
                }

                return parent::getLiveStats($status, $details);
            }
        } catch (Exception $e) {
            // If auth is configured but containers API fails, don't fallback
            if ($this->hasAuthCredentials()) {
                $status = 'inactive';
            } else {
                // No auth configured, try basic status check with app endpoint
                try {
                    $res = parent::execute($this->url('api/app'), $this->getAttrs());
                    if ($res->getStatusCode() === 200) {
                        $status = 'active';
                    }
                } catch (Exception $e2) {
                    $status = 'inactive';
                }
            }
        }

        return parent::getLiveStats($status, []);
    }

    private function hasAuthCredentials()
    {
        return !empty($this->config->username) && !empty($this->config->password);
    }

    private function getAttrs()
    {
        $attrs = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ];

        // Add basic authentication if both username and password are configured
        if ($this->hasAuthCredentials()) {
            $attrs['auth'] = [$this->config->username, $this->config->password];
        }

        return $attrs;
    }

    public function url($endpoint)
    {
        $base_url = parent::normaliseurl($this->config->url);
        // Ensure trailing slash for WUD API
        if (!str_ends_with($base_url, '/')) {
            $base_url .= '/';
        }
        return $base_url . $endpoint;
    }

    public static function getAvailableStats()
    {
        return [
            'TotalContainers' => 'Total',
            'UpdatableContainers' => 'Updatable',
            'WatchedContainers' => 'Watched',
        ];
    }
}
