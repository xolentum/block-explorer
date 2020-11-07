<?php

<head>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

$base_arr = json_decode(file_get_contents("https://raw.githubusercontent.com/xolentum/public-nodes-json/main/xolentum-public-nodes.json",true),true)["nodes"];

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


function do_table($url,$name){
	$arr = json_decode(file_get_contents($url,true),true);
}

foreach($base_arr as $req_params){
	$badge = $req_params["ssl"] ? "<i class=\"fa fa-shield\" aria-hidden=\"true\"></i>" : "";
	$NODE_NAME = $badge.$req_params["name"];
	$ssl = $req_params["ssl"]? "s" : "";
	$REQ_URL = "http".$ssl."://".$req_params["url"].":".$req_params["port"];
	echo do_table($REQ_URL,$NODE_NAME);
}

?>