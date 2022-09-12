<?php namespace App\SupportedApps\FreshRSS;

class FreshRSS extends \App\SupportedApps implements \App\EnhancedApps
{
	public $config;

	function __construct()
	{
	}

	private $clientVars = [
		"http_errors" => false,
		"timeout" => 15,
		"connect_timeout" => 15,
		"verify" => false,
	];

	public function test()
	{
		try{
			$body=$this->freshrssApiData("login","api/greader.php/accounts/ClientLogin");
			if($body==null) throw new \Exception("Error: Please check URL");
			if($body->getStatusCode() == 401) throw new \Exception("Error: Invalid Username and/or API key, Make sure your using the 'API password' and not the 'Authentication token'");
			if($body->getStatusCode() == 200){
				if($this->config->category !=null && $this->config->category !=""){
					$data = $body->getBody();
					$data_auth=substr($data,(strpos($data,"Auth=")+5),strlen($data));
					$data = json_decode($this->freshrssApiData("api","api/greader.php/reader/api/0/subscription/list?output=json",$data_auth)->getBody());
					$data_category=false;
					
					foreach($data->subscriptions as $dataInner){
						if(strtolower($dataInner->title) == strtolower($this->config->category)) $data_category = true;
					}
					
					if(!$data_category) throw new \Exception("Error: Could not find catagory '" . $this->config->category . "'. Please make sure catagory is spelled correctly");
				}
				
				echo "Welcome " . $this->config->username . ", you are connected to API";
			}
			else echo "Error something went wrong";
		}
		catch(\Throwable $e){echo $e->getMessage();}
	}

	public function livestats()
	{
		$status = "inactive";
		$data = [];
		$data["unread"] = null;
		$data["embed"] = false;
		$data["category"] = null;
		$data["error"] = false;
		$data["feed"] = null;
		$data["id"] = null;

		try{
			$body=$this->freshrssApiData("login","api/greader.php/accounts/ClientLogin");
			if($body==null) throw new \Exception();
			if($body->getStatusCode() == 401) throw new \Exception();
			if($body->getStatusCode() == 200){
				$response = $body->getBody();
					if($response==null) throw new \Exception();
				$data["auth"]=substr($response,(strpos($response,"Auth=")+5),strlen($response));

				if(filter_var($this->config->embedded, FILTER_VALIDATE_BOOLEAN)){
					$data["embed"]=true;
					
					if($this->config->category !=null && $this->config->category !=""){
						
						$data["category"]=$this->config->category;
						
						$response = json_decode($this->freshrssApiData("api","api/greader.php/reader/api/0/subscription/list?output=json",$data["auth"])->getBody());
							if($response==null) throw new \Exception();
							
							foreach($response->subscriptions as $responseInner){
								if(strtolower($responseInner->title) == strtolower($data["category"])) $data["id"] = $responseInner->id;
							}
							
						if($data["id"]==null) throw new \Exception();
							
						$response = json_decode($this->freshrssApiData("api","api/greader.php/reader/api/0/stream/contents/" . $responseInner->id . "?output=json&n=10",$data["auth"])->getBody());
							if($response==null) throw new \Exception();
						$i=0;
						$data["feed"]=array();
							foreach($response->items as $responseInner){
								$data["feed"][$i]=[];
								$data["feed"][$i]["title"]=$responseInner -> title;
								$data["feed"][$i]["url"]=$responseInner -> canonical[0] -> href;
							$i++;
							}

						
					}
					else{

						$response = json_decode($this->freshrssApiData("api","api/greader.php/reader/api/0/stream/contents/user/-/state/com.google/reading-list?output=json&n=10",$data["auth"])->getBody());
							if($response==null) throw new \Exception();
						$i=0;
						$data["feed"]=array();
							foreach($response->items as $responseInner){
								$data["feed"][$i]=[];
								$data["feed"][$i]["title"]=$responseInner -> title;
								$data["feed"][$i]["url"]=$responseInner -> canonical[0] -> href;
							$i++;
							}				
					}
				}
				else{
					$response = json_decode($this->freshrssApiData("api","api/greader.php/reader/api/0/unread-count?output=json",$data["auth"])->getBody());
					
					if($this->config->category !=null && $this->config->category !=""){
						
						$data["category"]=$this->config->category;
						
							foreach($response->unreadcounts as $responseInner){
								$data["id"]=substr(strtolower($responseInner->id),13,strlen($responseInner->id));
								if($data["id"] == strtolower($data["category"])) $data["unread"] = $responseInner->count;
							}						
							
							if($data["unread"]==null) throw new \Exception();
					}
					else $data["unread"] = $response->max;
				}
			}			
		}
		catch(\Throwable $e){$data["embed"]=true;}
		
		return parent::getLiveStats($status, $data);
	}

	public function freshrssApiData($dataMode,$dataUrl,$dataAuth=null)
	{
		if($dataMode=="login"){
			
			$attrs = [
				"headers" => [
					"Content-Type" => "application/x-www-form-urlencoded",
				],			
				"form_params" =>[
					"Email" => $this->config->username,
					"Passwd" => $this->config->apikey
				],
			];
			
			$res = parent::execute(
				$this->url($dataUrl),
				$attrs,
				$this->clientVars,
				"POST"
			);

			return $res;
		}
		elseif($dataMode=="api"){
			$attrs = [
				"headers" => ["Authorization" => "GoogleLogin auth=" . $dataAuth,],
			];
			
			$res = parent::execute(
				$this->url($dataUrl),
				$attrs,
				$this->clientVars,
				"POST"
			);

			return $res;			
		}
	}

	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}

}
