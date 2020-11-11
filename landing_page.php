<?php

header("Refresh: 120; url={$_SERVER['PHP_SELF']}");
include 'node_explorer.php';
$table_html = @get_nodes_table("http://sohamb03.ml:1234/nodes");
echo $table_html;

?>