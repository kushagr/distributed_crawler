<?php

	$worker = new GearmanWorker();
	$worker->addServer('127.0.0.1', 4730);

	$worker->addFunction("getResponse","get_response_fn");
	$worker->addFunction("convertHTMLToJSON","convert_html_to_json_fn");

	while (1) {
		print "Waiting for a job \n";

		$ret = $worker->work();
		if ($worker->returnCode() != GEARMAN_SUCCESS)
			break;
	}

	function get_response_fn($job){
		$workload = $job->workload();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $workload);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result; 
	}


	function convert_html_to_json_fn($job){
		$workload = $job->workload();

		echo "Recieved $workload\n";
		$dom = new DOMDocument();
		@$dom->loadHTML($workload);
		$links = $dom->getElementsByTagName('a');
		foreach($links as $link){
			$href_attr = $link->getAttribute('href');
			echo "$href_attr\n";

		}

		




		// $json_response = json_encode($workload);
		// $json_file = fopen("json_file", 'w');
		// fwrite($json_file, $json_response);
		// echo "Done\n";
		// return $json_response;
		return $href_attr;
	}
?>