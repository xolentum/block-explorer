<head>
<script src="https://kit.fontawesome.com/72d92586f6.js" crossorigin="anonymous"></script>
</head>

<?php
header("Refresh: 120; url={$_SERVER['PHP_SELF']}");

function build_table($array){
	$html = '<table>';
	$html .= '<tr>';
	foreach($array[0] as $key=>$value){
			$html .= '<th>' . $key . '</th>';
		}
	$html .= '</tr>';

	foreach( $array as $key=>$value){
		$html .= '<tr>';
		foreach($value as $key2=>$value2){
			$html .= '<td>' . $value2 . '</td>';
		}
		$html .= '</tr>';
	}

	$html .= '</table>';
	return $html;
}

function get_history_badge($badge_list){
	$final_badge_list = array();
	$up_badge = "<i style=\"color:green\" class=\"fas fa-circle\"></i>";
	$down_badge = "<i style=\"color:red\" class=\"fas fa-circle\"></i>";
	foreach ($badge_list as $badge_value){
		if($badge_value){
			array_push($final_badge_list,$up_badge);
		}
		else{
			array_push($final_badge_list,$down_badge);
		}
	}
	return join("",$final_badge_list);
}

function get_version($original_version){
	$regex_str = explode("-",$original_version)[0];
	return $regex_str ? $regex_str : false;
}

function do_table($url,$name,$req_params){
	$ctx = stream_context_create(array('http'=>
		array(
			'timeout' => 3,
		)
	));
	$check_url = @file_get_contents($url."/get_info",true,$ctx);
	if($check_url === false){
		return null;
	}
	$arr = json_decode($check_url,true);
	$version = get_version($req_params["version"]) or $version = "1.0.0";

	$member = array(
		"NODE NAME"=>$name,
		"HOSTNAME:PORT"=>$req_params["host"].":".$req_params["port"],
		"VERSION"=>$version,
		"HEIGHT"=>$req_params["height"],
		"IN/OUT"=>$arr["incoming_connections_count"]."/".$arr["outgoing_connections_count"],
		"HISTORY"=>get_history_badge($req_params["history"])
	);
	return $member;
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
echo @get_nodes_table("https://xol-api.sohamb03.me/nodes");

?>