<?php
/************************************************************
Name:	request.php
Version:
Date:
Called by html frontend via HTTP RPC. Returns HTML and javascript strings to be interpreted by frontend javascript
************************************************************/
require_once("../config.php");
global $debug;

#sleep(3);

if (isset($_REQUEST['csv']) && $debug == 0) {
	header('Content-type: text/csv');
} else {
	header('Content-type: text/javascript');
}

$output = "statusCode = 7; statusString = 'No data was returned'"; //This is a placeholder error

if (isset($_REQUEST['search'])) {
	include_once('search.php');
	$output = initSearch($_REQUEST);
} else {
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

	if (isset($_REQUEST['csv'])) {
		include_once('lib/csv.php');
		global $cache;
		$graph->setupGraph($_REQUEST, 1);
		if ($debug == 0) { 
			$shortname = basename($graph->graphname()).'.csv';
			header("Content-disposition: attachment; filename=$shortname");
		}
		$output = createCSV($graph);
	} else {
		//either build or load the cached graph
		$graph->setupGraph($_REQUEST);

		//Make sure we actually have data in the graph object
		//if bad, checkGraph() will print an error state and exit.
		$graph->checkGraph();

		if(isset($_REQUEST['action'])) {
			$ajaxfunc = 'ajax_'.$_REQUEST['action'];
			$output = $graph->$ajaxfunc($graph);
		} else if (isset($_REQUEST['table'])) {
			include_once('lib/graphtable.php');
			$output = createTable($graph);
		} else {
			$returnSVG = isset($_REQUEST['useSVG']) ? 1 : 0;
			include_once('../framework/GraphVizExporter.php');
			$output = GraphVizExporter::generateGraphvizOutput($graph, $datapath, 'jpg', $returnSVG);
		}
	}
}
print $output;

?>
