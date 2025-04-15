<?php

namespace App\SupportedApps\Ghostfolio;

use Exception;

class Ghostfolio extends \App\SupportedApps implements \App\EnhancedApps
{
    public $config;

    public function __construct()
    {
    }

    public function test()
    {
        try {
            $this->auth();
            $perf = $this->getPortfolioPerformance();

            echo "Successfully communicated with the API";
        } catch (Exception $err) {
            echo "Error connecting to Ghostfolio: " . $err->getMessage();
        }
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }

    public function auth(): void
    {
        $body = json_encode(["accessToken" => $this->config->password]);

        $vars = [
            "http_errors" => false,
            "timeout" => 5,
            "body" => $body,
            "headers" => [
                "Content-Type" => "application/json",
            ],
        ];

        $result = parent::execute(
            $this->url("api/v1/auth/anonymous"),
            [],
            $vars,
            "POST"
        );

        if ($result === null) {
            throw new Exception("Could not connect to Ghostfolio");
        }

        $responseBody = $result->getBody()->getContents();
        $response = json_decode($responseBody, true);
        if (null === $response || $result->getStatusCode() !== 201 || !isset($response['authToken'])) {
            throw new Exception("Error logging in");
        }

        $this->config->jwt = $response['authToken'];
    }


    public function livestats()
    {
        $this->auth();
        $status = 'inactive';

        $data = $this->getPortfolioPerformance();
        foreach ($this->config->availablestats as $num => $key) {
            $stat = new \stdClass();
            $stat->title = self::getAvailableStats()[$key];
            $value = $data[$key] ?? null;
            $stat->value = is_numeric($value)
                ? rtrim(
                    rtrim(number_format($value, 1, decimal_separator: '.', thousands_separator: ''), characters: '0'),
                    characters: '.'
                )
                : substr(string: $value, offset: 0, length: 1);
            $details["visiblestats"][] = $stat;
        }
        return parent::getLiveStats($status, $details);
    }



    private function getPortfolioPerformance(): mixed
    {
        $attrs = [
            "headers" => [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $this->config->jwt,
            ],
            "query" => [
                "range" => $this->config->selected_range,
            ],
        ];
        $result = parent::execute(
            $this->url("api/v2/portfolio/performance"),
            $attrs,
        );

        if (null === $result || $result->getStatusCode() !== 200) {
            throw new Exception("Error retrieving performance");
        }
        $data = json_decode($result->getBody()->getContents(), true);
        return $data['performance'];
    }

    public static function getAvailableStats()
    {
        return [
            "netPerformancePercentageWithCurrencyEffect" => "Net Perf (%)",
            "netPerformanceWithCurrencyEffect" => "Net Perf",
            "totalInvestment" => "Invested",
            "currentValueInBaseCurrency" => "Value",
            "currentNetWorth" => "Net Worth",
        ];
    }
}
