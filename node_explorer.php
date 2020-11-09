<head>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<?php

function build_table($array){
	$html = '<table>';
	$html .= '<tr>';
	foreach($array[0] as $key=>$value){
			$html .= '<th>' . htmlspecialchars($key) . '</th>';
		}
	$html .= '</tr>';

	foreach( $array as $key=>$value){
		$html .= '<tr>';
		foreach($value as $key2=>$value2){
			$html .= '<td>' . htmlspecialchars($value2) . '</td>';
		}
		$html .= '</tr>';
	}

	$html .= '</table>';
	return $html;
}

function do_table($url,$name,$req_params){
	$ctx = stream_context_create(array('http'=>
		array(
			'timeout' => 2,
		)
	));
	$check_url = @file_get_contents($url."/get_info",true,$ctx);
	if($check_url === false){
		return null;
	}
	$arr = json_decode($check_url,true);
	$member = array(
		"NODE_NAME"=>$name,
		"HOSTNAME:PORT"=>$req_params["host"].":".$req_params["port"],
		"VERSION"=>"1.0.0",
		"HEIGHT"=>$arr["height"],
		"IN/OUT (TX)"=>$arr["incoming_connections_count"]."/".$arr["outgoing_connections_count"]." ({$arr["tx_pool_size"]})",
		"UPTIME"=>get_time_diff($arr["start_time"])
	);
	return $member;
}

function get_time_diff($original){
	date_default_timezone_set("UTC");
	$page = $_SERVER['PHP_SELF'];
	$sec = "120";
	header("Refresh: $sec; url=$page");
	$now = new DateTime();
	$timestamp=$original;
	$conv = new DateTime(date("Y-m-d H:i:s", $timestamp));
	$interval = date_diff($now,$conv);
	return $interval->format('%aD %hH %iM %sS');
}

function get_nodes_table($url){
	$base_arr = json_decode(file_get_contents($url,true),true);
	$final_arr = array();
	foreach($base_arr as $req_params){
		$badge = $req_params["ssl"] ? "<i class=\"fa fa-shield\" aria-hidden=\"true\"></i>" : "";
		$NODE_NAME = $badge.$req_params["name"];
		$ssl = $req_params["ssl"]? "s" : "";
		$REQ_URL = "http".$ssl."://".$req_params["host"].":".$req_params["port"];
		$pushup = do_table($REQ_URL,$NODE_NAME,$req_params);
		if($pushup!==null){
			array_push($final_arr,$pushup);
		}
	}
	$html = build_table($final_arr);
	return $html;
}

// unhash line if you want to print table
// echo get_nodes_table("http://sohamb03.ml:1234/nodes");

?>