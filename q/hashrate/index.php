<?php
require '../util.php';
$config = (require '../../config.php');
$info = fetch_get_info($config['api']);
$difficulty = $info['difficulty'];
$hashrate = round($difficulty / $config['blockTargetInterval']);
print_r($hashrate);