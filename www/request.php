<?php
/************************************************************
Name:	request.php
Version:
Date:
Called by html frontend via HTTP RPC. Returns HTML and javascript strings to be interpreted by frontend javascript
************************************************************/
set_error_handler('handleError');
require_once("../config.php");
global $debug;
#sleep(3);

header('Content-type: application/json');
$response = array('statusCode'=>7, 'statusString'=>'No data was returned');

if (isset($_REQUEST['setupfile'])) { 
	//if ($setupfiles[$_REQUEST['setupfile']]) {
		$setupfile = $_REQUEST['setupfile'];
		#include_once('../framework/'.$_REQUEST['setupfile']); 
	//} else { echo "'Invalid setup file.'; statusCode='8';"; exit; }
} else { 
	$setupfile = 'ContributionGraph';
}
include_once("../framework/$setupfile.php");

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
	include_once('../framework/GraphVizExporter.php');
	$data = GraphVizExporter::generateGraphvizOutput($graph, $datapath, 'jpg', $returnSVG);
}

setResponse(1, 'Success', $data);

function setResponse($statusCode, $statusString, $data="") {
	$response = array('statusCode'=>$statusCode, 'statusString'=>$statusString, 'data'=>$data);
	print json_encode($response, JSON_FORCE_OBJECT);
	exit;
}

function handleError($errno, $errstr, $errfile, $errline) {
	global $debug;
	$details = $debug ? " ($errfile - line $errline)" : "";
	setResponse($errno, "$errstr$details");
}

?>
