<?php

namespace App\SupportedApps\GrampsWeb;

use Illuminate\Support\Facades\Cache;

class GrampsWeb extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // Gramps Web access tokens are valid for 15 minutes (not configurable), so
    // reuse a token for a little less than that instead of logging in on every
    // tile refresh. The token endpoint is also rate limited to 1 request/second.
    private const TOKEN_TTL = 840;

    // Stats shown when the item has live stats enabled but none were selected.
    private const DEFAULT_STATS = ['people', 'families'];

    public function __construct()
    {
    }

    public function test()
    {
        try {
            $this->fetchMetadata(true);
            echo 'Successfully communicated with the API';
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function livestats()
    {
        $status = 'inactive';
        $data = ['visiblestats' => []];

        try {
            $counts = $this->fetchMetadata()->object_counts ?? null;
        } catch (\Exception $e) {
            $counts = null;
        }

        if ($counts !== null) {
            $available = self::getAvailableStats();
            $selected = $this->config->availablestats ?? null;
            if (empty($selected)) {
                $selected = self::DEFAULT_STATS;
            }

            foreach ($selected as $stat) {
                if (!isset($available[$stat])) {
                    continue;
                }
                $newstat = new \stdClass();
                $newstat->title = $available[$stat];
                $newstat->value = number_format((int) ($counts->$stat ?? 0));
                $data['visiblestats'][] = $newstat;
            }
        }

        // Counts change rarely, so keep the slower "inactive" refresh interval.
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        return parent::normaliseurl($this->config->url) . $endpoint;
    }

    public static function getAvailableStats()
    {
        return [
            'people' => 'People',
            'families' => 'Families',
            'events' => 'Events',
            'places' => 'Places',
            'sources' => 'Sources',
            'citations' => 'Citations',
            'repositories' => 'Repositories',
            'media' => 'Media',
            'notes' => 'Notes',
            'tags' => 'Tags',
        ];
    }

    /**
     * GET api/metadata/, which holds the object counts of the tree.
     * Retries once with a new token when the cached one was rejected
     * (401 = expired, 422 = invalid, e.g. after the server's secret changed).
     */
    private function fetchMetadata($freshToken = false)
    {
        $res = $this->getMetadata($this->getToken($freshToken));
        if (in_array($res->getStatusCode(), [401, 422]) && !$freshToken) {
            $res = $this->getMetadata($this->getToken(true));
        }

        switch ($res->getStatusCode()) {
            case 200:
                $data = json_decode($res->getBody());
                if (!is_object($data) || !isset($data->object_counts)) {
                    throw new \Exception('Failed: unexpected response from api/metadata/');
                }
                return $data;
            case 401:
            case 403:
            case 422:
                throw new \Exception('Failed: this user has no access to the tree');
            default:
                throw new \Exception('Failed: api/metadata/ returned HTTP ' . $res->getStatusCode());
        }
    }

    private function getMetadata($token)
    {
        $res = parent::execute($this->url('api/metadata/'), [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        if ($res === null) {
            throw new \Exception('Failed: could not connect to Gramps Web');
        }
        return $res;
    }

    private function getToken($fresh = false)
    {
        $key = 'grampsweb.token.' . sha1(($this->config->url ?? '') . '|' . ($this->config->username ?? ''));
        if (!$fresh) {
            $token = Cache::get($key);
            if (!empty($token)) {
                return $token;
            }
        }

        if (empty($this->config->username) || empty($this->config->password)) {
            throw new \Exception('Failed: username and password are required');
        }

        $res = parent::execute($this->url('api/token/'), [
            'json' => [
                'username' => $this->config->username,
                'password' => $this->config->password,
            ],
            'headers' => ['Accept' => 'application/json'],
        ], null, 'POST');

        if ($res === null) {
            throw new \Exception('Failed: could not connect to Gramps Web');
        }

        switch ($res->getStatusCode()) {
            case 200:
                break;
            case 401:
            case 403:
                Cache::forget($key);
                throw new \Exception('Failed: invalid credentials');
            case 404:
            case 405:
                throw new \Exception('Failed: please make sure the URL points to Gramps Web');
            case 429:
                throw new \Exception('Failed: too many login attempts, try again in a moment');
            default:
                throw new \Exception('Failed: api/token/ returned HTTP ' . $res->getStatusCode());
        }

        $token = json_decode($res->getBody())->access_token ?? null;
        if (empty($token)) {
            throw new \Exception('Failed: no access token in the response');
        }

        Cache::put($key, $token, self::TOKEN_TTL);
        return $token;
    }
}
