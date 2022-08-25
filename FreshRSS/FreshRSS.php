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
			if($this->freshrssApiData("api/fever.php?api")->getStatusCode() != 200) throw new Exception();
			$data_error = null;
			$data_category = null;
			$data = json_decode($this->freshrssApiData("api/fever.php?api&groups")->getBody());
			if($data != null && $data->auth === 0) $data_error = "Error: Invalid Username and/or API key, Make sure your using the 'API password' and not the 'Authentication token'";
			elseif($this->config->category !=null && $this->config->category !=""){
				foreach($data->groups as $dataInner){
					if(strtolower($dataInner->title) == strtolower($this->config->category)) $data_category = true;
				}
				
				if(!$data_category) $data_error = "Error: Could not find catagory '" . $this->config->category . "'. Please make sure catagory is spelled correctly";
			}
			if($data != null && $data->auth === 1 && $data_error == null) $data_error = "Welcome " . $this->config->username . ", you are connected to API";
			echo $data_error;		
		}
		catch(\Throwable $e){echo "Error: Please check URL";}
	}

	public function freshrssApiData($dataUrl)
	{
		$attrs = [
			"body" => "api_key=" . $this->getApiKey(),
			"headers" => [
				"Content-Type" => "application/x-www-form-urlencoded",
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

	public function livestats()
	{
		$status = "inactive";
		$data = [];
		$data["unread"] = null;
		$data["embed"] = null;
		$data["category"] = 'null';
		
		try{
			if($this->freshrssApiData("api/fever.php?api")->getStatusCode() != 200) throw new Exception();	
			if($this->config->embedded =='' || $this->config->embedded == null) throw new Exception();
			if(filter_var($this->config->embedded, FILTER_VALIDATE_BOOLEAN)){
			$data["embed"]=true;
				
				if ($this->config->category =='' || $this->config->category == null) {
					
				$body = json_decode($this->freshrssApiData("api/fever.php?api&unread_item_ids")->getBody());
				
					if ($body->auth === 1) {
						
						if ($body->unread_item_ids != ""){
							if(count(explode(",", $body->unread_item_ids)) <= 10) $data["feedIds"] = $body->unread_item_ids; else{
								$data["feedIdArray"]=explode(",", $body->unread_item_ids,11);
								$trash=array_pop($data["feedIdArray"]);
								$data["feedIds"]=implode(",",$data["feedIdArray"]);
							}
						$body = json_decode($this->freshrssApiData("api/fever.php?api&items&with_ids=" . $data["feedIds"])->getBody()); 
						$data["feed"]=array();
						$data["feedTotal"]=$body->total_items;
						$i=0;
							foreach($body->items as $itemInner){
									
							$data["feed"][$i]=[];
							$data["feed"][$i]['title']=$itemInner->title;
							$data["feed"][$i]['body']=$itemInner->html;
							$data["feed"][$i]['link']=$itemInner->url;
							$i++;
							}
						}
					}
				}
				else{
					$data["category"] = $this->config->category;
					$body = json_decode($this->freshrssApiData("api/fever.php?api&groups")->getBody());
					
					foreach($body->groups as $dataInner){
						if(strtolower($dataInner->title) == strtolower($this->config->category)) $data["categoryid"] = $dataInner->id;
					}
					
					$data["feed"]=array();
					
					if($data["categoryid"] !=null){
						
						if ($body->auth === 1){
							foreach($body->groups as $dataInner){
								if(strtolower($dataInner->title) == strtolower($this->config->category)) $data["categoryid"] = $dataInner->id;
							}	
							
						$body = json_decode($this->freshrssApiData("api/fever.php?fever.php?api&items&group_id=" . $data['categoryid'])->getBody());
							
							if ($body->auth === 1) {
								
							$i=0;
								foreach(array_slice($body->items, 0, 10 ) as $itemInner){
								$data["feed"][$i]=[];
								$data["feed"][$i]['title']=$itemInner->title;
								$data["feed"][$i]['body']=$itemInner->html;
								$data["feed"][$i]['link']=$itemInner->url;
								$i++;
								}								
							}
							else throw new Exception();
						}
						else throw new Exception();
				
					}
					else throw new Exception();					
				}
			}
			else{
				if ($this->config->category =='' || $this->config->category == null) {
					
				$body = json_decode($this->freshrssApiData("api/fever.php?api&unread_item_ids")->getBody());
				
					if ($body->auth === 1) {
						if ($body->unread_item_ids != "") $data["unread"] = count(explode(",", $body->unread_item_ids)) . " Unread"; else $data["unread"] = 0 . " Unread";
					}
					else throw new Exception();
				}
				else{
					$data["category"] = $this->config->category;
					
					if($this->freshrssApiData("api/fever.php?api&groups")->getStatusCode() != 200) throw new Exception();
					
					$body = json_decode($this->freshrssApiData("api/fever.php?api&groups")->getBody());
					
					foreach($body->groups as $dataInner){
						if(strtolower($dataInner->title) == strtolower($this->config->category)) $data["categoryid"] = $dataInner->id;
					}

					if($data["categoryid"] !=null){
						
					$body = json_decode($this->freshrssApiData("api/fever.php?api&mark=item&as=unread&id=" . $data['categoryid'])->getBody());
					
						if ($body->auth === 1) {
							if ($body->unread_item_ids != "") $data["unread"] = count(explode(",", $body->unread_item_ids)) . " Unread"; else $data["unread"] = 0 . " Unread";
						}
						else throw new Exception();				
					}
					else throw new Exception();
				}
			}
		}
		catch(\Throwable $e){$data["unread"] = "Error";}

		return parent::getLiveStats($status, $data);
	}

	public function url($endpoint)
	{
		$api_url = parent::normaliseurl($this->config->url) . $endpoint;
		return $api_url;
	}

	public function getApiKey()
	{
		return md5($this->config->username . ":" . $this->config->apikey);
	}
}
