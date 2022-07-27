<?php namespace App\SupportedApps\RabbitMQ;

class RabbitMQ extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	public function test()
	{
		$test = parent::appTest(
			$this->url("api/overview"),
			$this->apiRequestAttributes()
		);
		echo $test->status;
	}

	public function livestats()
	{
		$status = "inactive";
		$res = parent::execute(
			$this->url("api/overview"),
			$this->apiRequestAttributes()
		);
		$details = json_decode($res->getBody());

		$data = [];

		if ($details) {
			$data["in"] = number_format(
				$details->message_stats->publish_details->rate +
					$details->message_stats->redeliver_details->rate,
				1
			);
			$data["total"] = number_format($details->queue_totals->messages);
			$data["out"] = number_format(
				$details->message_stats->deliver_details->rate +
					$details->message_stats->ack_details->rate,
				1
			);
		}

		return parent::getLiveStats($status, $data);
	}

	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}

	private function apiRequestAttributes(): array
	{
		return [
			"headers" => [
				"Authorization" =>
					"Basic " .
					base64_encode(
						$this->config->username . ":" . $this->config->password
					),
			],
		];
	}
}
