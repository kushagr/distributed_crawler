<?php
	
	$client = new GearmanClient();

	$client->addServer('127.0.0.1', 4730);

	echo "Sending Job\n";

	$uri = "http://www.reddit.com/";

	#if (http_response($uri,'200'))
	$response = $client->do("getResponse",$uri);	
	
	if ($response) {
		echo "Response recieved\n";
		$json_content = $client->doBackground("convertHTMLToJSON",$response);
		}
?>
