<?php

namespace App\SupportedApps\OpenClaw;

class OpenClaw extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    // "test"/"test" is the one client id+mode pair in OpenClaw's closed enum that
    // carries no browser-origin, CLI-locality, or control-ui special casing, so a
    // headless poller like this one gets plain operator-role connect handling.
    private const CLIENT_ID = 'test';
    private const CLIENT_MODE = 'test';
    private const ROLE = 'operator';
    private const SCOPES = ['operator.read'];
    private const PROTOCOL = 4;

    public function test()
    {
        $result = $this->fetchStats();
        if ($result['status'] === 'pairing_required') {
            echo 'Device pairing requested - approve it once on the OpenClaw host with '
                . '`openclaw devices approve --latest`, then test again';

            return;
        }
        if ($result['status'] !== 'ok') {
            echo $result['message'];

            return;
        }
        echo 'Successfully communicated with the API';
    }

    public function livestats()
    {
        $result = $this->fetchStats();
        if ($result['status'] !== 'ok') {
            return parent::getLiveStats('inactive', ['visiblestats' => []]);
        }

        $data = ['visiblestats' => [
            (object) ['title' => 'Active Agents', 'value' => number_format($result['agents'])],
            (object) ['title' => 'Active Sessions', 'value' => number_format($result['sessions'])],
        ]];

        return parent::getLiveStats('active', $data);
    }

    public function url($endpoint)
    {
        return $this->normaliseurl($this->config->url ?? '') . ltrim($endpoint, '/');
    }

    /**
     * Called from config.blade.php at render time: Heimdall app configs are a
     * plain form round-trip with no separate "generate and persist" step, so a
     * device identity that doesn't exist yet is minted on the spot and saved
     * via the hidden field the next time the form is submitted.
     */
    public static function deviceKeypairFor(?string $existingPem): string
    {
        if (! empty($existingPem)) {
            return $existingPem;
        }
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_ED25519]);
        openssl_pkey_export($key, $pem);

        return $pem;
    }

    public static function deviceIdFor(?string $pem): ?string
    {
        if (empty($pem)) {
            return null;
        }
        $private = openssl_pkey_get_private($pem);
        if ($private === false) {
            return null;
        }
        $details = openssl_pkey_get_details($private);
        $raw = self::rawEd25519PublicKey($details['key'] ?? '');

        return $raw === null ? null : hash('sha256', $raw);
    }

    /**
     * @return array{status: string, message?: string, agents?: int, sessions?: int}
     */
    private function fetchStats(): array
    {
        if (empty($this->config->url)) {
            return ['status' => 'error', 'message' => 'No URL has been specified'];
        }
        if (empty($this->config->device_private_key)) {
            return ['status' => 'error', 'message' => 'Missing device identity - save the item once to generate it'];
        }

        $connection = $this->connectAndAuthenticate();
        if ($connection['status'] !== 'ok') {
            return $connection;
        }

        $socket = $connection['socket'];

        try {
            $agents = $this->call($socket, 'agents.list', (object) []);
            $sessions = $this->call($socket, 'sessions.list', (object) []);
        } finally {
            $this->closeSocket($socket);
        }

        if (! $agents['ok'] || ! $sessions['ok']) {
            $message = $agents['error']['message'] ?? ($sessions['error']['message'] ?? 'Request failed');

            return ['status' => 'error', 'message' => $message];
        }

        $activeSessions = 0;
        foreach ($sessions['payload']['sessions'] ?? [] as $session) {
            if (! empty($session['hasActiveRun'])) {
                $activeSessions++;
            }
        }

        return [
            'status' => 'ok',
            'agents' => count($agents['payload']['agents'] ?? []),
            'sessions' => $activeSessions,
        ];
    }

    /**
     * @return array{status: string, message?: string, socket?: resource}
     */
    private function connectAndAuthenticate(): array
    {
        $url = parse_url(rtrim($this->config->url, '/'));
        $host = $url['host'] ?? null;
        if (! $host) {
            return ['status' => 'error', 'message' => 'Invalid URL'];
        }
        $scheme = $url['scheme'] ?? 'http';
        $port = $url['port'] ?? ($scheme === 'https' ? 443 : 80);
        $path = $url['path'] ?? '';
        $path = $path === '' ? '/' : $path;

        $context = stream_context_create();
        if ($scheme === 'https' && \App\Setting::fetch('skip_tls_verification')) {
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
        }

        $transport = $scheme === 'https' ? 'ssl' : 'tcp';
        $socket = @stream_socket_client(
            "$transport://$host:$port",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (! $socket) {
            return ['status' => 'error', 'message' => "Connection refused - $errstr"];
        }
        stream_set_timeout($socket, 10);

        $key = base64_encode(random_bytes(16));
        fwrite($socket, "GET $path HTTP/1.1\r\n"
            . "Host: $host:$port\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: $key\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "\r\n");

        $statusLine = fgets($socket, 1024);
        if ($statusLine === false || strpos($statusLine, '101') === false) {
            fclose($socket);

            return ['status' => 'error', 'message' => 'WebSocket upgrade failed'];
        }
        while (($line = fgets($socket, 4096)) !== false) {
            if (trim($line) === '') {
                break;
            }
        }

        $challenge = $this->readJsonFrame($socket);
        $nonce = $challenge['payload']['nonce'] ?? null;
        if (! is_string($nonce) || $nonce === '') {
            fclose($socket);

            return ['status' => 'error', 'message' => 'Did not receive gateway challenge'];
        }

        $device = $this->buildDeviceAuth($this->config->device_private_key, $nonce, $this->config->token ?? null);
        if ($device === null) {
            fclose($socket);

            return ['status' => 'error', 'message' => 'Invalid stored device identity'];
        }

        $connectParams = [
            'minProtocol' => self::PROTOCOL,
            'maxProtocol' => self::PROTOCOL,
            'client' => [
                'id' => self::CLIENT_ID,
                'mode' => self::CLIENT_MODE,
                'version' => '1.0.0',
                'platform' => 'heimdall',
            ],
            'role' => self::ROLE,
            'scopes' => self::SCOPES,
            'device' => $device,
            'auth' => (object) array_filter([
                'token' => $this->config->token ?? null,
                'password' => $this->config->password ?? null,
            ]),
        ];

        $response = $this->call($socket, 'connect', $connectParams);
        if (! $response['ok']) {
            fclose($socket);
            if (($response['error']['code'] ?? null) === 'NOT_PAIRED') {
                return [
                    'status' => 'pairing_required',
                    'message' => $response['error']['message'] ?? 'Device pairing required',
                ];
            }

            return ['status' => 'error', 'message' => $response['error']['message'] ?? 'Connect failed'];
        }

        return ['status' => 'ok', 'socket' => $socket];
    }

    /**
     * Sends a req frame and waits for the matching res frame, transparently
     * answering pings and skipping unrelated event frames along the way.
     *
     * @return array{ok: bool, payload: mixed, error: ?array}
     */
    private function call($socket, string $method, $params): array
    {
        $id = bin2hex(random_bytes(8));
        $frame = json_encode(['type' => 'req', 'id' => $id, 'method' => $method, 'params' => $params]);
        $this->writeFrame($socket, $frame);

        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            $message = $this->readJsonFrame($socket);
            if ($message === null) {
                break;
            }
            if (($message['type'] ?? null) === 'res' && ($message['id'] ?? null) === $id) {
                return [
                    'ok' => (bool) ($message['ok'] ?? false),
                    'payload' => $message['payload'] ?? null,
                    'error' => $message['error'] ?? null,
                ];
            }
        }

        return ['ok' => false, 'payload' => null, 'error' => ['message' => 'Timed out waiting for gateway response']];
    }

    private function buildDeviceAuth(string $pem, string $nonce, ?string $token): ?array
    {
        $private = openssl_pkey_get_private($pem);
        if ($private === false) {
            return null;
        }
        $details = openssl_pkey_get_details($private);
        $rawPublic = self::rawEd25519PublicKey($details['key'] ?? '');
        if ($rawPublic === null) {
            return null;
        }

        $deviceId = hash('sha256', $rawPublic);
        $signedAt = (int) round(microtime(true) * 1000);
        // Matches OpenClaw's buildDeviceAuthPayload (v2 wire format): pipe-joined
        // fields, verified byte-for-byte by the gateway against its own copy - the
        // gateway reconstructs the "token" field from connectParams.auth.token, so
        // it must be signed exactly as sent, not left blank.
        $payload = implode('|', [
            'v2',
            $deviceId,
            self::CLIENT_ID,
            self::CLIENT_MODE,
            self::ROLE,
            implode(',', self::SCOPES),
            (string) $signedAt,
            $token ?? '',
            $nonce,
        ]);
        if (! openssl_sign($payload, $signature, $private, 0)) {
            return null;
        }

        return [
            'id' => $deviceId,
            'publicKey' => self::base64UrlEncode($rawPublic),
            'signature' => self::base64UrlEncode($signature),
            'signedAt' => $signedAt,
            'nonce' => $nonce,
        ];
    }

    private static function rawEd25519PublicKey(string $publicPem): ?string
    {
        if (strpos($publicPem, 'BEGIN') === false) {
            return null;
        }
        $lines = array_filter(explode("\n", trim($publicPem)), fn ($line) => strpos($line, '-----') === false);
        $der = base64_decode(implode('', $lines), true);
        // Ed25519 SPKI DER is a fixed 12-byte algorithm-identifier prefix followed
        // by the 32 raw public key bytes - strip the prefix once length checks out.
        if ($der === false || strlen($der) !== 44) {
            return null;
        }

        return substr($der, 12);
    }

    private static function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    private function readJsonFrame($socket): ?array
    {
        $payload = $this->readMessage($socket);
        if ($payload === null) {
            return null;
        }
        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function readMessage($socket): ?string
    {
        $buffer = '';
        while (true) {
            $frame = $this->readFrame($socket);
            if ($frame === null) {
                return null;
            }
            if ($frame['opcode'] === 0x9) {
                $this->writeFrame($socket, $frame['payload'], 0xA);

                continue;
            }
            if ($frame['opcode'] === 0xA) {
                continue;
            }
            if ($frame['opcode'] === 0x8) {
                return null;
            }
            $buffer .= $frame['payload'];
            if ($frame['fin']) {
                return $buffer;
            }
        }
    }

    /**
     * @return array{fin: bool, opcode: int, payload: string}|null
     */
    private function readFrame($socket): ?array
    {
        $header = $this->readExact($socket, 2);
        if ($header === null) {
            return null;
        }
        $b1 = ord($header[0]);
        $b2 = ord($header[1]);
        $fin = ($b1 & 0x80) !== 0;
        $opcode = $b1 & 0x0F;
        $masked = ($b2 & 0x80) !== 0;
        $len = $b2 & 0x7F;

        if ($len === 126) {
            $ext = $this->readExact($socket, 2);
            if ($ext === null) {
                return null;
            }
            $len = unpack('n', $ext)[1];
        } elseif ($len === 127) {
            $ext = $this->readExact($socket, 8);
            if ($ext === null) {
                return null;
            }
            $len = unpack('J', $ext)[1];
        }

        $maskKey = '';
        if ($masked) {
            $maskKey = $this->readExact($socket, 4);
            if ($maskKey === null) {
                return null;
            }
        }

        $payload = $len > 0 ? $this->readExact($socket, $len) : '';
        if ($payload === null) {
            return null;
        }
        if ($masked) {
            for ($i = 0, $n = strlen($payload); $i < $n; $i++) {
                $payload[$i] = chr(ord($payload[$i]) ^ ord($maskKey[$i % 4]));
            }
        }

        return ['fin' => $fin, 'opcode' => $opcode, 'payload' => $payload];
    }

    private function readExact($socket, int $length): ?string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($socket);
                if (! empty($meta['timed_out']) || feof($socket)) {
                    return null;
                }

                continue;
            }
            $data .= $chunk;
        }

        return $data;
    }

    private function writeFrame($socket, string $payload, int $opcode = 0x1): void
    {
        $length = strlen($payload);
        $maskKey = random_bytes(4);
        $masked = '';
        for ($i = 0; $i < $length; $i++) {
            $masked .= chr(ord($payload[$i]) ^ ord($maskKey[$i % 4]));
        }

        $frame = chr(0x80 | $opcode);
        if ($length <= 125) {
            $frame .= chr(0x80 | $length);
        } elseif ($length <= 65535) {
            $frame .= chr(0x80 | 126) . pack('n', $length);
        } else {
            $frame .= chr(0x80 | 127) . pack('J', $length);
        }
        $frame .= $maskKey . $masked;
        fwrite($socket, $frame);
    }

    private function closeSocket($socket): void
    {
        if (is_resource($socket)) {
            $this->writeFrame($socket, '', 0x8);
            fclose($socket);
        }
    }
}
