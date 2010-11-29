<?php
/************************************************************
Name:	csv.php
Version:
Date:
returns 
************************************************************/

function createCSV($graph) {
	global $cache;
	global $datapath;
	global $debug;
	$filename = $datapath.$graph->graphname().'.csv';
	$output = "";
	if ($cache == 1 && file_exists($filename)) {
		$output = file_get_contents($filename);
	} else {
		$graph->loadGraphData();
		if ($debug) {
			$output = print_r($graph->data['queries']);
		}
		$output .= "\"CompanyName\",\"PoliticianName\",\"PoliticianParty\",\"PoliticianState\",\"PoliticanDistrict\",\"CoalAmount\",\"OilAmount\",\"TotalAmount\"\n";
		foreach ($graph->data['edges']['com2can'] as $edge) {
			$company = $graph->data['nodes']['companies'][$edge['fromId']];
			$candidate = $graph->data['nodes']['candidates'][$edge['toId']];
			
			$row = array();
			foreach(array($company['Name'], $candidate['Name'], $candidate['PartyDesignation1'], $candidate['campaignstate'], $candidate['currentdistrict'], $edge['coalcash'], $edge['oilcash'], $edge['cash']) as $value) { 
				if(is_numeric($value[0])) {
					$row[] =htmlspecialchars_decode($value);
				} else {
					$row[] ='"'.addslashes(htmlspecialchars_decode($value)).'"';
				}
			};
			$output .= join($row, ',')."\n";
		}
	}
	return $output;
}
