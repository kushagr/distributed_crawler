<?php
	
	require "predis/autoload.php";
	Predis\Autoloader::register();

	$client = new GearmanClient();
	$client->addServer('127.0.0.1', 4730);
	$redis = new Predis\Client('tcp://127.0.0.1:4567');

	echo "Sending Job\n";

	

		
	function crawl($gearman_client, $redis_client, $url){
	
		$response = $gearman_client->do("getResponse",$url);	
		if ($response) {
			echo "Response recieved\n";
			$arguments = array(
				0 => $response,
				1 => $url,
				);
			$data = serialize($arguments);
			$gearman_client->doBackground("convertHTMLToJSON",$data);
			echo "Convert HTML request sent\n";
		}
		$dom = new DOMDocument();
		@$dom->loadHTML($response);
		echo "Dom initialised";
		$links = $dom->getElementsByTagName('a');
		foreach($links as $link){
			$href_attr = $link->getAttribute('href');
			$redis_client->sadd("urlset",$href_attr);			
		}
		$new_starting_point = $redis_client->spop("urlset");
		echo "***************$new_starting_point******************\n";
		return $new_starting_point;

	}

	
	$starting_point = "http://www.hackernews.com";

	$next_starting_point = crawl($client, $redis, $starting_point);
	echo "out of crawl\n";
	
	while (true){
		echo "#############$next_starting_point###############\n";
		$next_starting_point = crawl($client, $redis ,$next_starting_point);
	}

	

	// function fetch_links($html_response){
	// 	$dom = new DOMDocument();
	// 	@$dom->loadHTML($html_response);
	// 	$links = $dom->getElementsByTagName('a');
	// 	foreach($links as $link){
	// 		$href_attr = $link->getAttribute('href');
	// 		$redis->sadd("urlset",$href_attr);			
	// 	}
	// }	

	



?>
