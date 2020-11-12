<head>
<script src="https://kit.fontawesome.com/72d92586f6.js" crossorigin="anonymous"></script>
</head>

<?php

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

function get_formatted_date($unix){
	return gmdate("D, j M Y H:i:s \G\M\T", $unix);
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

function get_readable_hashrate_string($hashrate){
	$i = 0;
	$byteUnits = [' H', ' kH', ' MH', ' GH', ' TH', ' PH', ' EH', ' ZH', ' YH' ];
	while ($hashrate > 1000){
		$hashrate /= 1000;
		$i++;
	}
	return number_format((float)$hashrate,2,'.','').$byteUnits[$i];
}

function do_table($req_params){
	$member = array(
		"POOL NAME"=>$req_params["name"],
		"HEIGHT"=>$req_params["height"],
		"HASH RATE"=>get_readable_hashrate_string($req_params["hashrate"]),
		"MINERS"=>$req_params["miners"],
		"POOL_FEE"=>$req_params["fee"],
		"MIN_PAYOUT"=>$req_params["min_payout"],
		"LAST BLOCK FOUND"=>get_formatted_date($req_params["last_block_timestamp"]),
		"HISTORY"=>get_history_badge($req_params["history"])
	);
	return $member;
}

function get_pools_table($url){
	$base_arr = json_decode(file_get_contents($url,true),true);
	$final_arr = array();
	foreach($base_arr as $req_params){
		$pushup = do_table($req_params);
		if($pushup!==null){
			array_push($final_arr,$pushup);
		}
	}
	$html = build_table($final_arr);
	return $html;
}

echo get_pools_table("https://xol-api.sohamb03.me/pools");

?>