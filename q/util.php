<?php
function build_post_context($postdata) {
    return stream_context_create(array(
        'http' =>
            array(
    			'method' => 'POST',
				'header' => 'content-type: application/json',
				'content' => $postdata
			)
		)
	);
};

function build_rpc_body($method, $params) {
    if ($params == ''){
        return '{"jsonrpc":"2.0","id":"blockexplorer","method":"'.$method.'"}';
    } else {
        return '{"jsonrpc":"2.0","id":"blockexplorer","method":"'.$method.'","params":'.$params.'}';
    }
};

function fetch_rpc($api, $method, $params) {
	$url = $api . '/json_rpc';
	$rendered_rpc = build_rpc_body($method, $params);
	$context = build_post_context($rendered_rpc);
	$response = file_get_contents($url, false, $context);
	return json_decode($response, true);
};

function fetch_get_info($api) {
	$_url = $api . '/get_info';
	$response = file_get_contents($_url);
	return json_decode($response, true);
};
