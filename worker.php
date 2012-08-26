<?php
	
	require "predis/autoload.php";
	Predis\Autoloader::register();
	
	//create new workers and assign functions
	$getResponse_worker = new GearmanWorker();
	$getResponse_worker->addServer('127.0.0.1', 4730);
	$getResponse_worker->addFunction("getResponse","get_response_fn");

	$convert_worker = new GearmanWorker();
	$convert_worker->addServer('127.0.0.1', 4730);
	$convert_worker->addFunction("convertHTMLToJSON","convert_html_to_json_fn");


	$background_worker = new GearmanWorker();
	$background_worker->addServer('127.0.0.1', 4730);
	$background_worker->addFunction("storeRedis","store_in_redis_fn");
	
	while (1) {
		print "Waiting for a job \n";

		$ret_get = $getResponse_worker->work();
		$ret_convert = $convert_worker->work();
		$ret_background = $background_worker->work();
		if (($getResponse_worker->returnCode() != GEARMAN_SUCCESS) || ($background_worker->returnCode() != GEARMAN_SUCCESS) || ($convert_worker->returnCode() != GEARMAN_SUCCESS))
			break;
	}


	function get_response_fn($job){
		echo "Generating response\n";
		$workload = $job->workload();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $workload);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result; 
	}


	function convert_html_to_json_fn($job){
		echo "Received job: " . $job->handle() . "\n";
		$workload = $job->workload();
		//TODO code to convert html to json
		$arguments = unserialize($workload);
		echo "Wassup\n";


		$redis_store_client = new Gearmanclient();
		$redis_store_client->addServer('127.0.0.1',4730);
		echo "Sending job to store in redis\n";
		$redis_store_client->doBackground('storeRedis',$workload);
	}


	function store_in_redis_fn($job){
		echo "Received Redis job: " . $job->handle() . "\n";
		$workload = $job->workload();
		$arguments = unserialize($workload);
		$response = $arguments[0];
		$url = $arguments[1];
		
		//was recieving an error for port 6379; change the port number as to your liking
		$redis = new Predis\Client('tcp://127.0.0.1:4567');
		$domain = get_domain_name($url);
		$date = date("d/m/y");
		echo "Storing in redis\n";
		$redis->sadd("$domain:$url:$date",$response);
		$redis->sadd("$domain:$url:donedates",$date);
		$redis->sadd("$domain",$url);
		echo "Store complete\n";
	}


	function get_domain_name($url){
		$nowww = ereg_replace('www\.','',$url);
		$domain = parse_url($nowww);
		if(!empty($domain["host"])){
    	    return $domain["host"];
     	} 
     	else {
     	    return $domain["path"];
     	}
    }



?>