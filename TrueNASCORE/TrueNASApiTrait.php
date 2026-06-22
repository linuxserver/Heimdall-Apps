<?php

namespace App\SupportedApps\TrueNASCORE;

use App\Helpers\TrueNASWebSocketClient;
use Illuminate\Support\Facades\Log;

/**
 * Shared trait for TrueNAS API communication.
 *
 * Provides WebSocket (JSON-RPC 2.0) and REST API support with automatic fallback.
 * Required for TrueNAS 25.04+ as the REST API is deprecated in 26.04.
 */
trait TrueNASApiTrait
{
    private ?TrueNASWebSocketClient $wsClient = null;
    private ?string $lastApiMode = null;

    /**
     * Get the configured API mode.
     *
     * @return string 'auto', 'websocket', or 'rest'
     */
    public function getApiMode(): string
    {
        return $this->getConfigValue('api_mode', 'auto');
    }

    /**
     * Check if WebSocket library is available.
     *
     * @return bool
     */
    public function isWebSocketAvailable(): bool
    {
        return class_exists('WebSocket\Client');
    }

    /**
     * Make an API call using the configured transport.
     *
     * @param string $method The method name (e.g., 'system.info' or 'alert.list')
     * @param array $params Optional parameters
     * @return mixed The result from the API
     * @throws \Exception If the call fails
     */
    public function apiCall(string $method, array $params = [])
    {
        $mode = $this->getApiMode();

        if ($mode === 'websocket') {
            return $this->callWebSocket($method, $params);
        }

        if ($mode === 'rest') {
            return $this->callRest($method, $params);
        }

        // Auto mode: try WebSocket first, fall back to REST
        if ($this->isWebSocketAvailable()) {
            try {
                return $this->callWebSocket($method, $params);
            } catch (\Exception $e) {
                Log::debug('WebSocket failed, falling back to REST: ' . $e->getMessage());
            }
        }

        return $this->callRest($method, $params);
    }

    /**
     * Make a WebSocket JSON-RPC 2.0 call.
     *
     * @param string $method The JSON-RPC method name
     * @param array $params Optional parameters
     * @return mixed The result
     * @throws \Exception If the call fails
     */
    private function callWebSocket(string $method, array $params = [])
    {
        $this->ensureWebSocketConnected();
        $this->lastApiMode = 'websocket';
        return $this->wsClient->call($method, $params);
    }

    /**
     * Make a REST API call.
     *
     * @param string $method The method name (will be converted to REST endpoint)
     * @param array $params Optional parameters (sent as JSON body for POST)
     * @return mixed The result
     * @throws \Exception If the call fails
     */
    private function callRest(string $method, array $params = [])
    {
        $this->lastApiMode = 'rest';

        // Map JSON-RPC methods to REST endpoints
        $endpointMap = [
            'core.ping' => 'core/ping',
            'system.info' => 'system/info',
            'alert.list' => 'alert/list',
        ];

        $endpoint = $endpointMap[$method] ?? str_replace('.', '/', $method);
        $url = $this->url($endpoint);
        $attrs = $this->attrs();

        if (!empty($params)) {
            $attrs['json'] = $params;
        }

        $res = parent::execute($url, $attrs);

        if ($res === null) {
            throw new \Exception('REST API call failed');
        }

        $body = $res->getBody()->getContents();

        // Handle simple string responses (like "pong")
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Return raw response for non-JSON (e.g., "pong" string)
            return trim($body, '"');
        }

        return $decoded;
    }

    /**
     * Ensure WebSocket client is connected.
     *
     * @throws \Exception If connection fails
     */
    private function ensureWebSocketConnected(): void
    {
        if ($this->wsClient !== null && $this->wsClient->isConnected()) {
            return;
        }

        if (!$this->isWebSocketAvailable()) {
            throw new \Exception('WebSocket library not available');
        }

        $baseUrl = parent::normaliseurl($this->config->url, false);
        $apiKey = $this->config->apikey;
        $ignoreTls = $this->getConfigValue('ignore_tls', false);

        $this->wsClient = new TrueNASWebSocketClient($baseUrl, $apiKey, $ignoreTls);
        $this->wsClient->connect();
    }

    /**
     * Close WebSocket connection if open.
     */
    public function disconnectWebSocket(): void
    {
        if ($this->wsClient !== null) {
            $this->wsClient->disconnect();
            $this->wsClient = null;
        }
    }

    /**
     * Test API connection.
     *
     * @return object Test result with code, status, and response
     */
    public function testApi(): object
    {
        if (empty($this->config->url)) {
            return (object) [
                'code' => 404,
                'status' => 'No URL has been specified',
                'response' => 'No URL has been specified',
            ];
        }

        $mode = $this->getApiMode();

        // Try WebSocket if enabled
        if ($mode !== 'rest' && $this->isWebSocketAvailable()) {
            try {
                $this->ensureWebSocketConnected();
                $result = $this->wsClient->ping();

                if ($result) {
                    return (object) [
                        'code' => 200,
                        'status' => 'Successfully communicated with the API (WebSocket)',
                        'response' => 'pong',
                    ];
                }
            } catch (\Exception $e) {
                if ($mode === 'websocket') {
                    return (object) [
                        'code' => null,
                        'status' => 'WebSocket connection failed: ' . $e->getMessage(),
                        'response' => 'Connection failed',
                    ];
                }
                Log::debug('WebSocket test failed, trying REST: ' . $e->getMessage());
            } finally {
                $this->disconnectWebSocket();
            }
        }

        // Try REST if WebSocket failed or not available
        if ($mode !== 'websocket') {
            $test = parent::appTest($this->url('core/ping'), $this->attrs());
            if ($test->code === 200) {
                if ($test->response != '"pong"') {
                    $test->status = 'Failed: ' . $test->response;
                } else {
                    $test->status = 'Successfully communicated with the API (REST)';
                }
            }
            return $test;
        }

        return (object) [
            'code' => null,
            'status' => 'WebSocket not available and REST mode disabled',
            'response' => 'Connection failed',
        ];
    }

    /**
     * Get the API mode that was used for the last call.
     *
     * @return string|null 'websocket' or 'rest', or null if no call made
     */
    public function getLastApiMode(): ?string
    {
        return $this->lastApiMode;
    }
}
