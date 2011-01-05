<?php
/************************************************************
Name:	request.php
Version:
Date:
Called by html frontend via HTTP RPC. Returns HTML and javascript strings to be interpreted by frontend javascript
************************************************************/
header('Content-type: application/json');
set_error_handler('handleError');
require_once("FrameworkUtils.php");
set_include_path($framework_config['framework_path'].PATH_SEPARATOR.$framework_config['application_path']);
chdir($framework_config['web_path']);
reinterpret_paths();

$framework_config['debug'] = 1;
#sleep(3);

$response = array('statusCode'=>7, 'statusString'=>'No data was returned');

if (isset($_REQUEST['setupfile'])) { 
	$setupfile = $_REQUEST['setupfile'];
} elseif (isset($framework_config['default_setupfile'])) { 
	$setupfile = $framework_config['default_setupfile'];
} else { trigger_error("No Setupfile defined.", E_USER_ERROR); }

if (isset($framework_config['setupfiles']["$setupfile.php"])) {
	if(file_exists($framework_config['application_path']."$setupfile.php")) { ;
		include_once($framework_config['application_path']."$setupfile.php");
	} else { trigger_error("Setup file '$setupfile' does not exist", E_USER_ERROR); }
	#include_once('../framework/'.$_REQUEST['setupfile']); 
} else { trigger_error("Invalid setup file: $setupfile", E_USER_ERROR); }

$webdatapath = "";

$graph = new $setupfile();

//either build or load the cached graph
$graph->setupGraph($_REQUEST);
//Make sure we actually have data in the graph object
//if bad, checkGraph() will print an error state and exit.
$graph->checkGraph();

if(isset($_REQUEST['action'])) {
	$ajaxfunc = 'ajax_'.$_REQUEST['action'];
	if (method_exists($graph, $ajaxfunc)) { 
		$data = $graph->$ajaxfunc($graph);
	} else {
		trigger_error('"'.$_REQUEST['action'].'" is an invalid method.', E_USER_ERROR);
	}
} else {
	$returnSVG = isset($_REQUEST['useSVG']) ? 1 : 0;
	include_once('GraphVizExporter.php');
	$data = GraphVizExporter::generateGraphvizOutput($graph, $datapath, 'jpg', $returnSVG);
}

setResponse(1, 'Success', $data);

function setResponse($statusCode, $statusString, $data="") {
	$response = array('statusCode'=>$statusCode, 'statusString'=>$statusString, 'data'=>$data);
	print json_encode($response, JSON_FORCE_OBJECT);
		exit;
	if ($statusCode == 256) { 
	}
}

function handleError($errno, $errstr, $errfile, $errline) {
	global $framework_config;
	$details = $framework_config['debug'] ? " ($errfile - line $errline)" : "";
	setResponse($errno, "$errstr$details");
}

?>
