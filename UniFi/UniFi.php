<?php

namespace App\SupportedApps\UniFi;

use Illuminate\Support\Facades\Cache;

/**
 * Implementation based on
 * https://ubntwiki.com/products/software/unifi-controller/api
 */
class UniFi extends \App\SupportedApps
{
    public $config;

    protected $method = 'POST';

    // UniFi OS: UDM/UDR, Cloud Key, and the self-hosted UniFi OS Server
    private const UNIFI_OS_URLS = [
        "loginURL" => "/api/auth/login",
        "statsURL" => "/proxy/network/api/s/default/stat/health",
    ];

    // The standalone UniFi Network application, which predates UniFi OS
    private const LEGACY_URLS = [
        "loginURL" => "/api/login",
        "statsURL" => "/api/s/default/stat/health",
    ];

    public function __construct()
    {
        $this->jar = new \GuzzleHttp\Cookie\CookieJar();
    }

    /**
     * Log in, correcting the endpoint family when the configured one is wrong.
     *
     * The config toggle selects an API, not a hosting model. Self-hosting is no
     * longer the same thing as running the legacy Network application: a
     * self-hosted UniFi OS Server speaks the UniFi OS API, and a UniFi OS
     * device answers a legacy login path with a bare 401 without ever looking
     * at the credentials. Retry once against the other pair and keep whichever
     * one authenticates, so a misread toggle reports the real problem instead
     * of a misleading "Invalid credentials".
     *
     * Only a refused login retries, so a working configuration is untouched.
     * The same attributes are reused for the retry because the first request
     * never reached authentication.
     *
     * @param array $urls Endpoints to use; replaced when the retry succeeds.
     */
    protected function authenticate(&$urls)
    {
        $attributes = $this->getLoginAttributes();
        $res = parent::execute($this->url($urls['loginURL']), $attributes, null, 'POST');

        if ($res === null || !in_array($res->getStatusCode(), [401, 403, 404], true)) {
            return $res;
        }

        $alternate = $this->getAPIURLs($urls !== self::LEGACY_URLS);
        $altRes = parent::execute($this->url($alternate['loginURL']), $attributes, null, 'POST');

        // Anything other than the same flat refusal means this is the API the
        // device actually implements, including a 499 asking for a second
        // factor. Keep that answer so the caller can report the real reason.
        if ($altRes !== null && !in_array($altRes->getStatusCode(), [401, 403, 404], true)) {
            $urls = $alternate;
            return $altRes;
        }

        return $res;
    }

    public function test()
    {
        $urls = $this->getAPIURLs();

        // Perform login request; $urls is corrected if the other API answers
        try {
            $loginRes = $this->authenticate($urls);
        } catch (\InvalidArgumentException $exception) {
            echo "Failed: " . $exception->getMessage();
            return;
        }
        $self_hosted = $urls === self::LEGACY_URLS;

        if ($loginRes === null) {
            echo "Failed: Connection error";
            return;
        }

        $statusCode = $loginRes->getStatusCode();
        $body = json_decode($loginRes->getBody());

        $hasTotp = !empty($this->getConfigValue("totp_uri"));

        // Check for explicit failure codes
        // An incorrect or expired TOTP code is rejected here too, not with 499
        if ($statusCode === 401 || $statusCode === 403) {
            echo $hasTotp
                ? "Failed: Invalid credentials or TOTP code - each code works once, "
                    . "so wait for the next one before retrying"
                : "Failed: Invalid credentials";
            return;
        }

        // UniFi OS asks for a second factor it has not been given
        if ($statusCode === 499) {
            echo "Failed: Two-factor authentication required";
            return;
        }

        // Self-hosted controllers return 400 on auth failure
        if ($statusCode === 400) {
            $msg = isset($body->meta->msg) ? $body->meta->msg : "Invalid credentials";
            echo "Failed: " . $msg;
            return;
        }

        // For 200 responses, verify the login actually succeeded
        if ($statusCode === 200) {
            // Self-hosted: check meta.rc === "ok"
            if ($self_hosted) {
                if (!isset($body->meta->rc) || $body->meta->rc !== "ok") {
                    $msg = isset($body->meta->msg) ? $body->meta->msg : "Login failed";
                    echo "Failed: " . $msg;
                    return;
                }
            }

            // Additional verification: try to fetch stats to confirm session works
            $statsRes = parent::execute(
                $this->url($urls['statsURL']),
                $this->getAttributes(),
                null,
                'GET'
            );

            if ($statsRes !== null && $statsRes->getStatusCode() === 200) {
                $statsBody = json_decode($statsRes->getBody());
                // UDM returns data array, self-hosted returns meta.rc
                $hasData = isset($statsBody->data);
                $hasMetaOk = isset($statsBody->meta) && isset($statsBody->meta->rc) && $statsBody->meta->rc === "ok";
                if ($hasData || $hasMetaOk) {
                    echo "Successfully connected to UniFi";
                    return;
                }
            }

            // Stats fetch failed but login seemed ok
            echo "Login succeeded but unable to fetch stats - check user permissions";
            return;
        }

        // Unexpected status code
        echo "Failed: Unexpected response (HTTP " . $statusCode . ")";
    }

    public function livestats()
    {
        $status = "inactive";
        $urls = $this->getAPIURLs();
        $totpUri = $this->getConfigValue("totp_uri");

        // UniFi OS accepts each TOTP code once, so the dashboard refresh loop
        // would fail every login that lands in an already-spent time step.
        // Reusing the last result until the next code is due keeps one login
        // per period; without TOTP configured nothing is cached.
        $cacheKey = empty($totpUri) ? null : $this->totpCacheKey();
        if ($cacheKey !== null) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return parent::getLiveStats($status, $cached);
            }
        }

        try {
            $this->authenticate($urls);
        } catch (\InvalidArgumentException) {
            return parent::getLiveStats($status, ['error' => true]);
        }

        $res = parent::execute(
            $this->url($urls['statsURL']),
            $this->getAttributes(),
            null,
            'GET'
        );

        if ($res === null) {
            return parent::getLiveStats($status, ['error' => true]);
        }

        $details = json_decode($res->getBody());

        $data = [];

        if (isset($details->data)) {
            $data['error'] = false;
            foreach ($details->data as $key => $detail) {
                if ($detail->subsystem === 'wlan') {
                    // Handle lack of APs
                    // TODO: Update UI to adapt to lack of APs
                    $data['wlan_users'] = isset($detail->num_user) ? $detail->num_user : 0;
                    $data['wlan_ap'] = isset($detail->num_ap) ? $detail->num_ap : 0;
                    $data['wlan_dc'] = isset($detail->num_disconnected) ? $detail->num_disconnected : 0;
                    $data['num_ap'] = isset($detail->num_ap) ? $detail->num_ap : 0;
                }

                if ($detail->subsystem === 'lan') {
                    // Handle lack of Switches
                    // TODO: Update UI to adapt to lack of Switches
                    $data['lan_users'] = isset($detail->num_user) ? $detail->num_user : 0;
                    $data['num_sw'] = isset($detail->num_sw) ? $detail->num_sw : 0;
                }

                if ($detail->subsystem === 'wan') {
                    // Handle lack of GW
                    // TODO: Update UI to adapt to lack of GW
                    $data['wan_avail'] = isset($detail->uptime_stats->WAN->availability)
                                       ? number_format($detail->uptime_stats->WAN->availability, 0)
                                       : 0;
                    $data['num_gw'] = isset($detail->num_gw) ? $detail->num_gw : 0;
                }
            }
        } else {
            $data['error'] = true;
        }

        // Cache successes only, so a transient failure retries on the next
        // refresh instead of pinning an error tile for the rest of the period.
        if ($cacheKey !== null && $data['error'] === false) {
            Cache::put($cacheKey, $data, $this->secondsUntilNextTotpCode($totpUri));
        }

        return parent::getLiveStats($status, $data);
    }

    private function totpCacheKey()
    {
        return 'unifi_livestats_' . sha1(
            $this->config->url . '|' . $this->getConfigValue("username", '')
        );
    }

    private function secondsUntilNextTotpCode($uri)
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        $period = (int) ($query["period"] ?? 30);
        if ($period < 1) {
            $period = 30;
        }

        return $period - (time() % $period);
    }

    public function url($endpoint)
    {
        $url = parse_url(parent::normaliseurl($this->config->url));
        $scheme = $url["scheme"];
        $domain = $url["host"];
        $port = isset($url["port"]) ? $url["port"] : "443";

        $api_url =
            $scheme .
            "://" .
            $domain .
            ":" .
            $port .
            $endpoint;

        return $api_url;
    }

    public function getConfigValue($key, $default = null)
    {
        return isset($this->config) && isset($this->config->$key)
            ? $this->config->$key
            : $default;
    }

    public function getLoginAttributes()
    {
        $ignoreTls = $this->getConfigValue("ignore_tls", false);
        $username = $this->config->username;
        $password = $this->config->password;

        $body = [
            "username" => $username,
            "password" => $password,
        ];

        $totpUri = $this->getConfigValue("totp_uri");
        if (!empty($totpUri)) {
            $body["token"] = self::generateTotpCode($totpUri);
        }

        $attrs = [
            "body" => json_encode($body),
            "cookies" => $this->jar,
            "headers" => [
                "Content-Type" => "application/json"
            ]
        ];

        if ($ignoreTls) {
            $attrs["verify"] = false;
        }

        return $attrs;
    }

    public static function generateTotpCode($uri, $timestamp = null)
    {
        $parts = parse_url($uri);
        if (
            $parts === false ||
            strtolower($parts["scheme"] ?? "") !== "otpauth" ||
            strtolower($parts["host"] ?? "") !== "totp"
        ) {
            throw new \InvalidArgumentException("The TOTP value must be an otpauth://totp URI");
        }

        parse_str($parts["query"] ?? "", $query);
        $secret = self::base32Decode($query["secret"] ?? "");
        $algorithm = strtolower($query["algorithm"] ?? "sha1");
        $digits = (int) ($query["digits"] ?? 6);
        $period = (int) ($query["period"] ?? 30);

        if (!in_array($algorithm, ["sha1", "sha256", "sha512"], true)) {
            throw new \InvalidArgumentException("Unsupported TOTP algorithm");
        }
        if ($digits < 6 || $digits > 8 || $period < 1) {
            throw new \InvalidArgumentException("Invalid TOTP digits or period");
        }

        $counter = intdiv($timestamp ?? time(), $period);
        $counterBytes = pack("N2", ($counter >> 32) & 0xffffffff, $counter & 0xffffffff);
        $hash = hash_hmac($algorithm, $counterBytes, $secret, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
        $binary = unpack("N", substr($hash, $offset, 4))[1] & 0x7fffffff;

        return str_pad((string) ($binary % (10 ** $digits)), $digits, "0", STR_PAD_LEFT);
    }

    private static function base32Decode($secret)
    {
        $secret = strtoupper(str_replace([" ", "-", "="], "", trim($secret)));
        if ($secret === "") {
            throw new \InvalidArgumentException("The TOTP URI is missing a secret");
        }
        if (!preg_match("/^[A-Z2-7]+$/", $secret)) {
            throw new \InvalidArgumentException("Invalid base32 TOTP secret");
        }

        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $bits = "";
        foreach (str_split($secret) as $character) {
            $value = strpos($alphabet, $character);
            if ($value === false) {
                throw new \InvalidArgumentException("Invalid base32 TOTP secret");
            }
            $bits .= str_pad(decbin($value), 5, "0", STR_PAD_LEFT);
        }

        $decoded = "";
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }

        return $decoded;
    }

    public function getAttributes()
    {
        $attrs = [
            "cookies" => $this->jar,
        ];

        $ignoreTls = $this->getConfigValue("ignore_tls", false);

        if ($ignoreTls) {
            $attrs["verify"] = false;
        }

        return $attrs;
    }

    public function getAPIURLs($legacy = null)
    {
        if ($legacy === null) {
            $legacy = (bool) $this->getConfigValue("self_hosted", false);
        }

        return $legacy ? self::LEGACY_URLS : self::UNIFI_OS_URLS;
    }
}
