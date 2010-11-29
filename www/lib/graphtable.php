<?php
/************************************************************
Name:	graphtable.php
Version:
Date:
Called by html frontend via HTTP RPC. Returns HTML and javascript strings to be interpreted by frontend javascript
************************************************************/
require_once("../dbaccess.php");

$propsdef['companies'] = array(
	'node1' => 'companies',
	'node2' => 'companies',
	'primary' => 'fromId',
	'secondary' => 'toId',
	'other' => 'companies'
);
$racecode = $_REQUEST['racecode'];
$sitecode = $_REQUEST['sitecode'];


$disclaimer = "<div class='disclaimer'>The total of the contributions in this table may be less than the overall total because some contributions have been hidden by the filtering options. <a href='about.php#total'>[?]</a></div>";

function createTable($graph) {
	$data = "";
	//Determine if we are building the Candidate pane or the Company pane
	$viewtype = $_REQUEST['type'];
	if (isset($_REQUEST['id'])) {
		$id = dbEscape($_REQUEST['id']);
		if(isset($_REQUEST['id2'])) {
			//build right hand inner contributions table
			$data .= createContributionTable($graph, $_REQUEST['id2'], $id);
		} else {
			$data .= createDetailTable($graph, $viewtype, $id);
		}
	} else {
		$data = createIndexTable($graph, $viewtype);
	}	

	$output = "statusCode =1;\n";
	$output .= "data=".json_encode($data);
	#$output = $data;
	return $output;
}

function tableHeader($id, $type) {
	
	$data = comInfoHeader($id);
	
	return $data;
}

function createDetailTable($graph, $type, $id) {

	//SKYE'S DIRTY HACK
	$type = "companies";
	//build right hand table
	global $propsdef;
	global $sitecode;
	global $disclaimer;
	$props = $propsdef[$type];
	$data = "";
	$primary = $propsdef[$type]['primary'];
	$who = 'Contributors';
	$shorttype = 'companies';
	$display = 'block';
	$edgecount = 0;
	if (! isset($_REQUEST['noheader'])) { 
		$data .= tableHeader($id, $type);
		#$data .= "<a href='' onclick=\"$('$type"."detailstable').toggle(); return false;\">Show $who</a>";
		$display = 'none';
	}
	
	$data .= "<ul id='$type"."detailstable'>"; # style='display:$display;'>";

	$style = 'company';
	if ($type == 'company') { $style = 'politician'; }

	$primary = $props['primary'];
	$secondary = $props['secondary'];
	$odd = 'odd';
	if (isset($graph->data['edges'])){   //check if graph is empty
		$edgetypes = array_keys($graph->data['edges']);
		
	} else {
		$edgetypes = array();
	}
	function edge_sort($a, $b) {
		global $graph;
		return $graph->data['edges']['org2org'][$a]['cash'] < $graph->data['edges']['org2org'][$b]['cash'];
	}
	foreach ($edgetypes as $edgetype){
		$edge_ids = array_keys($graph->data['edges'][$edgetype]);
		//usort($edge_ids, 'edge_sort');
		$edgecount += count($edge_ids);
		foreach ($edge_ids as $edge_id) {
			$edge = $graph->data['edges'][$edgetype][$edge_id];
			$node = $graph->data['nodes'][$props['node2']][$edge[$props['secondary']]];
			if ($edge[$primary] == $id) {
				$links = getLinks($node);
				$extra = "<span class='industry $node[industry]'>(".ucwords($node['industry']).")</span>"; 
				$shorttype = strtolower($node['industry']);
				$csitecode = isset($node['industry']) ? $node['industry'] : "";
				$image = getImage($node['tileimage'], $node['type'], 1, $csitecode);
				$value = $edge['nicecash'];
				if (is_numeric(substr($value,0,1))){
					$value = "$".$value;
				}
				$data .= "
		<li id='c$edge[$secondary]' onclick='showEdge(\"$edge[fromId]\", \"$edge[toId]\");' class='$style sub $odd'>
		
			<img src='$image' alt='".$node['Name']."' class='portrait' />
			<p class='name'>$node[Name] $extra</p>
			<p class='contributions'>$value</p>
			
			<a class='zoom icon' href='#' title='Show Contributions' onclick=\"loadDetails('$edge[fromId]', '$edge[toId]', '$type'); return false;\">Show Contributions</a>
			$links[profile]
		</li>";
			$odd = $odd == 'odd' ? '' : 'odd';
			}
		}
	}
	//what to return if no edges found?
	if ($edgecount==0){
		$data.= "<li class='norelationships'>No contribution relationships found for these settings.</li>";
	}
	
	$data .= "</ul>";
	return $data;
}

function createIndexTable($graph, $type) {
	//build left-hand table
	global $propsdef;
	global $racecode;
	global $sitecode;
	$props = $propsdef['companies'];
/*
	$data = "<table class='sortable' id='$type"."Table' width='100%'><thead>";
	$data .= "<tr><th>".ucwords($type)." Name</th>";
	 $data .= "<th>State</th>"; }
	$data .= "<th>Industry</th>";
	}	
	$data .= "<th class='currency sortfirstdesc'>Oil Contributions</th></tr></thead><tbody>";
*/
	$style = 'politician';
	if ($type == 'company') { $style = 'company'; }

	$data = "<ul>";
	foreach ($graph->data['nodes'][$props['node1']] as $node) {
		#$data .= "<tr id='t$node[id]'> <td><img src='".preg_replace("/..\/www\/c([ao][mn])_images\/\/c?([^\/]+)\.([^\/]+)$/", "c$1_images/s$2.$3", $node[image])."' style='vertical-align: middle; width:20px; height: 20px;' /> <a href='#' onclick=\"showDetails('$node[id]', '$type'); return false;\">$node[Name]</a></td>";
		$extra = '';
		if ($node['industry'] != ""){
			$extra =  "<span class='industry $node[industry]'>".ucwords($node['industry'])." organization</span>";
		} 
		$csitecode = isset($node['industry']) ? $node['industry'] : "";
		$cstate = isset($node['state']) ? " <span class='state'>based in ".$node['state']."</span>" : "";
		$image = getImage($node['tileimage'], "companies", 0, $csitecode);
		$nicecash = $node['cash']==0 ? "(amount not disclosed)" : "$".$node['nicecash'];
	$data .= "
	<li id='t$node[id]' class='$style mini'>
		<img class='close' src='images/close.png' style='position: absolute; top: 10px; right: 10px; cursor: pointer;' alt='close' title='Hide Details'/>
		<img src='$image' alt='$node[Name]' class='portrait' />
		<p class='name'>$node[Name] <br/>$extra $cstate</p>
		<p class='contributions'>$nicecash</p>
		<a class='open' href='#' onclick=\"showDetails('$node[id]', '$type'); return false;\" title='Show Details'>Show Details</a>
	</li>";
	}
	$data .= "</ul>";
	return $data;
}

//actually create the table for the right hand inner contributions
function createContributionTable($graph, $company, $canid) {
	global $congress_num;
	global $partycolor;
	global $disclaimer;
	$query = "";
	//yikes, now we have two kinds of edges... they may not be in the list of that type
	if (isset($graph->data['edges']['org2org'][$company."_".$canid])){
		$ids = $graph->data['edges']['org2org'][$company."_".$canid]['ContribIDs'];
		
		$query = "select transaction_id,from_name as 'Donor Name', Details, f.state 'State', f.zip 'Zip',transaction_date Date, concat('$',format(amount,0)) as Contribution, r.source as URL from relationships r join entities f on from_id = f.entityid where transaction_id in ($ids)";
		
	}else if (isset($graph->data['edges']['orgOwnOrg']["m_".$company."_".$canid])){
		$ownids = $graph->data['edges']['orgOwnOrg']["m_".$company."_".$canid]['ContribIDs'];
		$query ="select transaction_id,from_name as 'Owner/Member Name', Details, f.state 'State', f.zip 'Zip',transaction_date Date, 'Unknown amount' as Contribution, r.source as URL from relationships r join entities f on from_id = f.entityid where transaction_id in ($ownids)";
	}
	
	
	$contribs = dbLookupArray($query);
	foreach($contribs as &$c) { 
	 
		if ($c['URL'] && $c['URL'] != '') { 
			$c['Contribution'] = "<span class='contributions'>".$c['Contribution']."</span><a href='".htmlspecialchars($c['URL'])."' target='_blank' title='View source of data' class='zoom icon'>+</a>";
		} else {
			$c['Contribution'] = "<span class='contributions'>".$c['Contribution']."</span><span title='Original Record unavailable' class='plus'></span>" ;
		}
		
		//$c['Occupation'] = htmlspecialchars($c['Occupation']);
		if (isset($c['Donor Name'])){
			$c['Donor Name'] = htmlspecialchars($c['Donor Name']);
			$c['Details'] = htmlspecialchars($c['Details']);
		} else {
			$c['Owner/Member Name'] = htmlspecialchars($c['Owner/Member Name']);
		}
		unset($c['URL']); 
	}
	$comimage = getImage($graph->data['nodes']['companies'][$company]['tileimage'], 'com', 0, $graph->data['nodes']['companies'][$company]['industry']);
	$canimage = getImage($graph->data['nodes']['companies'][$canid]['tileimage'], 'com', 0, $graph->data['nodes']['companies'][$canid]['industry']);
	$table = "<h2>Contribution Details</h2> <div id='contributionheader'> <img  class='portrait' style='' src='$comimage'/> <img id='rightarrow' src='images/green-arrow-right.png' /><img  class='portrait' style='' src='$canimage'/><h3>From ".$graph->data['nodes']['companies'][$company]['Name']." to ". $graph->data['nodes']['companies'][$canid]['Name']."</h3></div>";
	$table .= "<table id='contributionstable' class='sortable' border='1' ><thead>";
	$first = 0;
	foreach (array_keys($contribs) as $key) {
		$row = $contribs[$key];
		array_shift($row);
		if (! $first) {
			$table .="<tr>";
			foreach (array_keys($row) as $name) { 
				$class = "";
				if ($name == 'Contribution') { $class = "class='currency sortfirstdesc'"; }
				$table.="<th $class>$name</th>\n"; 
			}
			$first = 1;
			$table .="</tr></thead><tbody>";
		}
		//$color = "#EEEEEE";
		$table .="<tr id='$key'>";
		foreach($row as $col) { 
			$table .="<td>".$col."</td>\n";
		}
		$table .="</tr>";
	}
	$table .="</tbody></table>";
	#$table .= $disclaimer;
	return $table;
}

?>

