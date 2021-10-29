var api = 'https://xes.xolentum.org/node_web';
var apiList = [
        "http://node.xolentum.org:13580",
        "http://xolentum.xyz:13580"
    ];

var nodesAPI = 'https://xes.xolentum.org/nodes';
var poolsAPI = 'https://xes.xolentum.org/pools';
var blocksAPI = 'https://xes.xolentum.org/blocks';

var blockTargetInterval = 60;
var coinUnits = 1000000000000;
var symbol = 'XOL';
var refreshDelay = 30000;
var blocksPerPage = 20;
var whiteTheme = "css/light.css";
var nightTheme = "css/dark.css";
var addressPattern = new RegExp("^(X|i|s)[a-zA-Z0-9]+");
