<?php
require '../util.php';
$config = (require '../../config.php');
$info = fetch_rpc($config['api'], 'get_generated_coins', '');
$supplyRaw = $info['result']['coins'];
$supply = number_format($supplyRaw / $config['coinUnits'], $config['coinDecimals'], ".", "");
print_r($supply);
