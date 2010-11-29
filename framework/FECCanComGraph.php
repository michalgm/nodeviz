<?php
include_once('../config.php');
include_once('Graph.php');
/*
creates the graph data structure that will be used to pass data among components.  Structure must not change
*/
class FECCanComGraph extends Graph { 

	function __construct() {
		parent::__construct();
		
		$this->racecode_filter_values = array(
			'P'=>" racecode='P' ",
			'S'=>" racecode='S' ",
			'H'=>" racecode='H' ",
			'C'=>" (racecode='H' or racecode='S') ",
		);

		// gives the classes of nodes
		$this->data['nodetypes'] = array('candidates', 'companies'); //FIXME - add properties for types
		
	  	// gives the classes of edges, and which nodes types they will connect
		$this->data['edgetypes'] = array( 'com2can' => array('companies', 'candidates'));
		
		// graph level properties
		$this->data['properties'] = array(
			'electionyear' => '00',
			'congress_num' => '110',
			'racecode' => 'P',
			'minCandidateAmount' =>'-99999999',
			'minCompanyAmount' => '-99999999',
			'minContribAmount' => '-99999999',
			'candidateFilterIndex' => -1,
			'companyFilterIndex' => -1,
			'contribFilterIndex' => -1,
			'candidateLimit' =>'',
			'companyLimit' =>'',
			'candidateids' => '',
			'companyids' => '',
			'allcandidates' => 0,
			//sets the scaling of the elements in the gui
			'minSize' => array('candidates' => '.25', 'companies' => '.25', 'com2can' =>'1'),
			'maxSize' => array('candidates' => '3', 'companies' => '3', 'com2can' =>'80'),
			'sitecode' => ''
		);
		// special properties for controling layout software
		//BROKEN, USE GV PARAMS
		$this->data['graphvizProperties'] = array(
			'graph'=> array(
				'bgcolor'=>'#FFFFFF',
				'size' => '6.52,6.52!'
			),
			'node'=> array('label'=> ' ', 'imagescale'=>'true','fixedsize'=>1, 'style'=> 'setlinewidth(7), filled', 'regular'=>'true'),
			'edge'=>array('len'=>8, 'arrowhead'=>'none', 'color'=>'#66666666')
		);
	}

	/**
	There must be a  <nodetype_fetchNodes() function for each defined node type.  
	It returns the ids of the nodes for the type. 
	**/

	function candidates_fetchNodes() {
		$graph = &$this->data;
		$precheck = "";
		if ($graph['properties']['candidateids']) {
			foreach (explode(',', $graph['properties']['candidateids']) as $id) { 
				$graph['nodes']['candidates'][$id] = Array();
				$graph['nodes']['candidates'][$id]['id'] = $id;
			}
			return $graph;
		}
		$congress_num = $graph['properties']['congress_num'];
		$racecode = $graph['properties']['racecode'];
		$racecode_filter = "";
		if (isset($this->racecode_filter_values[$racecode])) { 
			$racecode_filter = 'and '.$this->racecode_filter_values[$racecode];
		}
		$minCandidateAmount = $graph['properties']['minCandidateAmount'];
		$allcandidates = " join congressmembers d on a.candidateid = d.fec_id and a.congress_num = d.congress_num ";
		if (is_numeric($congress_num)) { 
			$congress = "a.congress_num = '$congress_num' and c.congress_num = '$congress_num' and "; 
		} else {
			if ($congress_num == 'pre' && $graph['properties']['companyids'] ) {
				$precheck = " left join congressmembers b on c.candidateid = b.fec_id and c.congress_num = b.congress_num ";
				$congress = " c.companyid = '".$graph['properties']['companyids']."' and b.fec_id is null and ";
			} else { 
				$congress = "a.congress_num = c.congress_num and";
			}
		}
		if ($graph['properties']['allcandidates'] == 1 || $racecode == 'P') { $allcandidates = ""; }
		if ($graph['properties']['sitecode'] != 'carbon') { $sitecode = " and sitecode = '".$graph['properties']['sitecode']."' "; } else { $sitecode = ""; }

		$query ="select a.CandidateID as id from candidates a join contributions c on a.CandidateID = c.CandidateID $precheck $allcandidates where $congress c.congress_num is not null $racecode_filter $sitecode group by a.CandidateID having sum(amount) >= $minCandidateAmount order by sum(amount) desc";
		$this->addquery('candidates', $query, $graph);

		$result = dbLookupArray($query);
		$graph['nodes']['candidates'] = $result;
		return $graph;
	}

	function companies_fetchNodes() {
		$graph = &$this->data;
		if ($graph['properties']['companyids']) {
			foreach (explode(',', $graph['properties']['companyids']) as $id) { 
				$graph['nodes']['companies'][$id] = Array();
				$graph['nodes']['companies'][$id]['id'] = $id;
			}
			return $graph;
		}
		$congress_num = $graph['properties']['congress_num'];
		$racecode = $graph['properties']['racecode'];
		$racecode_filter = $this->racecode_filter_values[$racecode];
		$minCompanyAmount = $graph['properties']['minCompanyAmount'];
		$allcandidates = " join congressmembers d on a.candidateid = d.fec_id and a.congress_num = d.congress_num ";
		$limit = "";
		$sitecode = "";
		if ($graph['properties']['sitecode'] != 'carbon') { $sitecode = " and sitecode = '".$graph['properties']['sitecode']."' "; }
		$congress = "";
		if (is_numeric($congress_num)) { 
			$congress = "a.congress_num = '$congress_num' and c.congress_num = '$congress_num' and "; 
		} else if ($congress_num == 'pre') {
			if ($graph['properties']['candidateids']) { 
				$cnumquery = "select a.congress_num from contributions a left join congressmembers b on candidateid = b.fec_id and a.congress_num = b.congress_num where b.congress_num is null and a.congress_num is not null and a.CandidateID = '".$graph['properties']['candidateids']."' $sitecode group by a.congress_num";
				$congress_nums = arrayValuesToInString(fetchCol($cnumquery));
				$congress = " a.congress_num in($congress_nums) and";
			}
		}
		if ($graph['properties']['allcandidates'] || $racecode == 'P' || isset($graph['properties']['candidateids'][0])) { $allcandidates = ""; }
		$query ="select CompanyID as id from contributions a join candidates c use index(CandidateID) on a.CandidateID = c.CandidateID $allcandidates where $congress $racecode_filter and a.congress_num is not null $sitecode group by a.COmpanyID having sum(amount) >= $minCompanyAmount order by sum(amount) desc";
		$this->addquery('companies', $query, $graph);
		$result = dbLookupArray($query);
		$graph['nodes']['companies'] = $result;
		return $graph;
	}


	/**
	There must be a  <nodetype>_nodeProperties() function for each node class.
	It sets the properties of the nodes of that type. 
	**/
	function candidates_nodeProperties() {
		$graph = &$this->data;
		global $candidate_images;
		global $current_congress;

		$congress_num = $graph['properties']['congress_num'];
		$racecode = $graph['properties']['racecode'];
		$racecode_filter = "";
		if (isset($this->racecode_filter_values[$racecode])) { 
			$racecode_filter = ' and '.$this->racecode_filter_values[$racecode];
		}
		$limit = "";
		$sitecode = "";
		$breakdown = "";
		if ($graph['properties']['sitecode'] != 'carbon') { 
			$sitecode = " and sitecode = '".$graph['properties']['sitecode']."' "; 
		} else { 
			$breakdown = ", format(sum(if(sitecode='coal', amount, 0)), 0) as coalcash, format(sum(if(sitecode='oil', amount, 0)),0) as oilcash ";
		}

		$congress = "";
		if (is_numeric($congress_num)) { 
			$congress = "a.congress_num = '$congress_num' and c.congress_num = '$congress_num' and "; 
		} else if ($congress_num == 'pre') {
			if ($graph['properties']['candidateids']) { 
				$cnumquery = "select a.congress_num from contributions a left join congressmembers b on candidateid = b.fec_id and a.congress_num = b.congress_num where b.congress_num is null and a.congress_num is not null and a.CandidateID = '".$graph['properties']['candidateids']."' $sitecode group by a.congress_num";
				$congress_nums = arrayValuesToInString(fetchCol($cnumquery));
				$congress = " c.congress_num in($congress_nums) and";
			}
		} 
		if ($graph['properties']['companyids']) {
			$company_ids = " and c.Companyid in (".arrayToInString($graph['nodes']['companies']).") ";
		} else {
			$company_ids = "";
		}
		if ($graph['properties']['candidateLimit']) { $limit = " limit ".$graph['properties']['candidateLimit']; }
		$idlist = arrayToInString($graph['nodes']['candidates']);
		if ($graph['properties']['allcandidates'] || $racecode == 'P') { 
			$cantable = "select CandidateID, CandidateName, substr(PartyDesignation1,1,1) as PartyDesignation1, currentdistrict, campaignstate, congress_num from candidates a where a.CandidateID in ($idlist) ";
		} else { 
			$cantable = "select fec_id as candidateid, concat(lastname, ', ', firstname) as CandidateName, party as PartyDesignation1, district as currentdistrict, state_abbreviation as campaignstate, congress_num from congressmembers a where fec_id in ($idlist)";
		}
		$query ="select a.CandidateID as id,  CandidateName as Name, PartyDesignation1, sum(Amount) as cash, format(sum(Amount),0) as nicecash ,max(Amount+0) as max, min(Amount+0) as min, currentdistrict, campaignstate, if(d.congress_num = $current_congress, 1, 0) as current_member $breakdown from ($cantable) a join contributions c on a.CandidateID = c.CandidateID left join (select fec_id, max(congress_num) as congress_num from congressmembers group by fec_id) d on d.fec_id = a.CandidateID where $congress c.congress_num is not null $racecode_filter $company_ids $sitecode group by a.CandidateID order by cash desc $limit";
		$this->addquery('candidates_props', $query, $graph);
		$nodes = dbLookupArray($query);
		$graph['nodes']['candidates'] = $nodes;
		foreach($graph['nodes']['candidates'] as &$node) {
			$node['shape'] = 'box';
			$node['onClick'] = "selectNode('".$node['id']."');";
			if ($node['campaignstate'] != '00' && $node['campaignstate'] != '') {
				$state = "-$node[campaignstate]";
			} else { $state = ""; }
			$node['onMouseover'] = "highlightNode('".$node['id']."', '".safeLabel(niceName($node['Name'])." (".$node['PartyDesignation1'][0]."$state)").'<br/>$'.$node['nicecash']."');";
			$node['FName'] = htmlspecialchars(niceName($node['Name']), ENT_QUOTES);
			$node['Name'] = htmlspecialchars(niceName($node['Name'], 1), ENT_QUOTES);
			$node['color'] = lookupPartyColor($node['PartyDesignation1']);
			$node['fillcolor'] = 'white';
			$image = "$candidate_images".$node['id'].".jpg";
			if (! file_exists($image)) { $image = "$candidate_images"."unknownCandidate.jpg"; }
			$node['image'] = $image;
			$node['type'] = 'Can';
		}
		$this->scaleSizes('candidates', 'cash');
		return $graph;
	}

	function companies_nodeProperties() {
		$graph = &$this->data;
		global $company_images;
		global $current_congress;
		$congress_num = $graph['properties']['congress_num'];
		$racecode = $graph['properties']['racecode'];
		$racecode_filter = "";
		if (isset($this->racecode_filter_values[$racecode])) { 
			$racecode_filter = ' and '.$this->racecode_filter_values[$racecode];
		}
		$company_ids = arrayToInString($graph['nodes']['companies']);
		$candidate_ids = "";
		$sitecode = "";
		$limit = "";
		$breakdown = "";
		if ($graph['properties']['sitecode'] != 'carbon') { 
			$sitecode = " and sitecode = '".$graph['properties']['sitecode']."' "; 
		} else { 
			$breakdown = ", format(sum(if(sitecode='coal', amount, 0)), 0) as coalcash, format(sum(if(sitecode='oil', amount, 0)),0) as oilcash ";
		}

		if (is_numeric($congress_num)) { 
			$congress = "a.congress_num = '$congress_num' and c.congress_num = '$congress_num' and "; 
		} else if ($congress_num == 'total') {
			$congress = "a.congress_num = c.congress_num and";
		} else if ($congress_num == 'pre') {
			if ($graph['properties']['candidateids']) { 
				$cnumquery = "select a.congress_num from contributions a left join congressmembers b on candidateid = b.fec_id and a.congress_num = b.congress_num where b.congress_num is null and a.congress_num is not null and a.CandidateID = '".$graph['properties']['candidateids']."' $sitecode group by a.congress_num";
				$congress_nums = arrayValuesToInString(fetchCol($cnumquery));
				$congress = " a.congress_num = c.congress_num and a.congress_num in($congress_nums) and";
			} else { $congress = "a.congress_num = c.congress_num and"; }
		}
		if ($graph['properties']['candidateids']) {
			$candidate_ids = " and a.CandidateID in (".arrayToInString($graph['nodes']['candidates']).") ";
		} else { 
			$candidate_ids = "";
		}
		if ($graph['properties']['companyLimit']) { $limit = " limit ".$graph['properties']['companyLimit']; }
		
		$query ="select CompanyID as id,b.Name , sum(Amount) as cash, format(sum(Amount),0) as nicecash, max(0 + Amount) as max, min(0 + Amount) as min, image_name as image, if(oil_related < coal_related, 'coal', 'oil') as sitecode $breakdown from contributions a join companies b on a.CompanyID = b.id join candidates c on a.CandidateID = c.CandidateID where $congress a.congress_num is not null and a.CompanyID in ($company_ids) $candidate_ids $racecode_filter $sitecode group by a.COmpanyID order by cash desc $limit";
		$this->addquery('companies_props', $query,$graph);
		$nodes = dbLookupArray($query);
		$graph['nodes']['companies'] = $nodes;
		foreach($graph['nodes']['companies'] as &$node) {
			$node['shape'] = 'circle';
			$node['onClick'] = "selectNode('".$node['id']."');";
			$node['onMouseover'] = "highlightNode('".$node['id']."', '".safeLabel($node['Name']).'<br/>$'.$node['nicecash']."');";
			$node['color'] = lookupPartyColor($node['sitecode']);
			//$node['fillcolor'] = 'white';
			$image = "$company_images"."c".$node['image'].".png";
			if (! file_exists($image)) { 
				if ($graph['properties']['sitecode'] == 'carbon') { 
					$image = "$company_images"."cunknown_".$node['sitecode']."_co.png"; 
				} else {
					$image = "$company_images"."cunknown_".$graph['properties']['sitecode']."_co.png"; 
				}
			}
			$node['image'] = $image;
			$node['type'] = 'Com';
			$node['Name'] = htmlspecialchars($node['Name'], ENT_QUOTES);
		}
		$this->scaleSizes('companies', 'cash');
	}

	//NEED TO HAVE COMMENTS GIVING THE NAMES OF THE PROPERTIES ADDED
	//Edge Ids need to be unique so P0001_2_1,P0001_2_2 
	//Need to add a 'fromID' and 'toID' property to each edge
	function com2can_fetchEdges() {
		dbwrite("SET group_concat_max_len := @@max_allowed_packet");
		$graph = &$this->data;
		$congress_num = $graph['properties']['congress_num'];
		$candidateIds = arrayToInString($graph['nodes']['candidates']);
		$companyIds = arrayToInString($graph['nodes']['companies']);
		$minContribAmount = $graph['properties']['minContribAmount'];
		$limit = "";
		$sitecode = "";
		$congress = "";
		$precheck = "";
		if ($graph['properties']['sitecode'] != 'carbon') { $sitecode = " and sitecode = '".$graph['properties']['sitecode']."' "; }
		if ($congress_num == 'pre') {
			if ($graph['properties']['candidateids']) { 
				$cnumquery = "select a.congress_num from contributions a left join congressmembers b on candidateid = b.fec_id and a.congress_num = b.congress_num where b.congress_num is null and a.congress_num is not null and a.CandidateID = '".$graph['properties']['candidateids']."' $sitecode group by a.congress_num";
				$congress_nums = arrayValuesToInString(fetchCol($cnumquery));
				$congress = " a.congress_num in($congress_nums) and";
			} else { 
				$precheck = " left join congressmembers b on candidateid = b.fec_id and a.congress_num = b.congress_num ";
				$congress = " b.fec_id is null and "; 
			}
		} else if (is_numeric($congress_num)) { $congress = "  a.congress_num  = '$congress_num' and"; }
		$query ="select concat(a.CompanyID, '_', a.CandidateId) as id, a.CandidateID as toId, a.CompanyID as fromId, group_concat(concat(\"'\", a.crp_key, \"'\")) as ContribIDs from contributions a  $precheck where a.CandidateID in ($candidateIds) and a.CompanyID in ($companyIds) and a.congress_num is not null and $congress amount >= $minContribAmount $sitecode group by concat(a.CandidateID, a.CompanyID) order by a.CandidateId, CompanyID desc";

		// Darrell:  recommend put the abs() around amount in the query above so that returned contributions also show up on the website.
		//	$query ="select concat(a.CompanyID, '_', a.CandidateId) as id, a.CandidateID as toId, a.CompanyID as fromId, group_concat(concat(\"'\", a.crp_key, \"'\")) as ContribIDs from contributions a  where a.CandidateID in ($candidateIds) and a.CompanyID in ($companyIds) and a.congress_num is not null and $congress abs(amount) >= $minContribAmount group by concat(a.CandidateID, a.CompanyID) order by a.CandidateId, CompanyID desc";
		$this->addquery('com2can', $query, $graph);
		$result = dbLookupArray($query);
		$graph['edges']['com2can'] = $result;	
		return $graph;
	}

	function com2can_edgeProperties() {
		$graph = &$this->data;
		$congress_num = $graph['properties']['congress_num'];
		$candidateIds = arrayToInString($graph['nodes']['candidates']);
		$companyIds = arrayToInString($graph['nodes']['companies']);
		$sitecode = "";
		$congress = "";
		$precheck = "";
		$breakdown = "";

		if ($graph['properties']['sitecode'] != 'carbon') { 
			$sitecode = " and sitecode = '".$graph['properties']['sitecode']."' "; 
		} else { 
			$breakdown = ", sum(if(a.sitecode='coal', amount, 0)) as coalcash, sum(if(a.sitecode='oil', amount, 0)) as oilcash ";
		}

		if ($congress_num == 'pre') {
			if ($graph['properties']['candidateids']) { 
				$cnumquery = "select a.congress_num from contributions a left join congressmembers b on candidateid = b.fec_id and a.congress_num = b.congress_num where b.congress_num is null and a.congress_num is not null and a.CandidateID = '".$graph['properties']['candidateids']."' $sitecode group by a.congress_num";
				$congress_nums = arrayValuesToInString(fetchCol($cnumquery));
				$congress = " a.congress_num in($congress_nums) and";
			} else { 
				$precheck = " left join congressmembers b on candidateid = b.fec_id and a.congress_num = b.congress_num ";
				$congress = " b.fec_id is null and "; 
			}
		} else if (is_numeric($congress_num)) { $congress = "  a.congress_num  = '$congress_num' and"; }

		$query ="select concat(a.CompanyID, '_', a.CandidateId) as id, sum(Amount) as cash, format(sum(Amount), 0) as nicecash $breakdown from contributions a $precheck where $congress a.congress_num is not null and a.CandidateID in ($candidateIds) and a.CompanyID in ($companyIds) $sitecode group by concat(a.CandidateID, a.CompanyID)  order by cash desc, a.CandidateId, CompanyID desc";
		$this->addquery('com2can_props', $query, $graph);
		$edgeprops = dbLookupArray($query);
		//$graph['edges']['com2can'] = $nodes;  //don't use this, would replace the edges
		
		foreach(array_keys($graph['edges']['com2can']) as $key) {
			$edge = $graph['edges']['com2can'][$key];
			if(! array_key_exists($edge['id'], $edgeprops)) { 
				unset($graph['edges']['com2can'][$key]); 
				continue;
			}
			$edge['onClick'] = "selectEdge('".$edge['id']."');";
			$edge['onClick'] = "selectEdge(eventObject)";
			$edge['cash'] = $edgeprops[$edge['id']]['cash'];   //get the appropriate ammount properties
			$edge['nicecash'] = $edgeprops[$edge['id']]['nicecash']; 
			$edge['Name'] = htmlspecialchars($graph['nodes']['companies'][$edge['fromId']]['Name'], ENT_QUOTES);
			$edge['CandidateName'] = $graph['nodes']['candidates'][$edge['toId']]['Name'];
			$edge['weight'] = $edge['cash'];
			$edge['onMouseover'] = "this.style.cursor = 'pointer'; showTooltip('$".$edge['nicecash']."');";
			$edge['type'] = 'com2can';
			if (isset($edgeprops[$edge['id']]['coalcash'])) {
				$edge['coalcash'] = $edgeprops[$edge['id']]['coalcash'];
				$edge['oilcash'] = $edgeprops[$edge['id']]['oilcash'];
			}
			$graph['edges']['com2can'][$key] = $edge;
		}
		$this->scaleSizes('com2can', 'cash');
	}

	function graphname() {
		if (! $this->name) { 
			$graph = &$this->data;
			global $datapath;
			$props = $graph['properties'];
			$localdatapath = "$datapath$props[sitecode]";
			if (! is_dir("$localdatapath")) {
				mkdir("$localdatapath") || print "unable to create dir $localdatapath"; 
				chmod("$localdatapath", 0777);
			}	
			if ($props['candidateids'] != '') { 
				if( ! is_dir("$localdatapath/individuals")) { 
					mkdir("$localdatapath/individuals") || print "unable to create dir $localdatapath/individuals"; 
					chmod("$localdatapath/individuals", 0777);
					clearstatcache();
				}
				$graphname = "individuals/$props[candidateids]_$props[congress_num]";
			} elseif ($props['companyids'] != '') { 
				if( ! is_dir("$localdatapath/companies")) { 
					mkdir("$localdatapath/companies") || print "unable to create dir $localdatapath/companies"; 
					chmod("$localdatapath/companies", 0777);
					clearstatcache();
				}
				$graphname = "companies/$props[companyids]_$props[congress_num]";
			} else {
				$dir = "$props[racecode]$props[congress_num]";
				if( ! is_dir("$localdatapath/$dir")) { 
					mkdir("$localdatapath/$dir") || print "unable to create dir $localdatapath/$dir"; 
					chmod("$localdatapath/$dir", 0777);
					clearstatcache();
				}
				if (isset($props['candidateFilterIndex']) && $props['candidateFilterIndex'] != '-1') {
					$graphname = "$dir/$props[candidateFilterIndex]_$props[companyFilterIndex]_$props[contribFilterIndex]";
				} else { 
					$graphname = "$dir/$props[racecode]$props[congress_num]";
				}
			}
			$this->name = $props['sitecode'].'/'.$graphname;
		}
		return $this->name;
	}


	/**
	UI Functions for displaying info on graph ui.   fixme: need standard naming  convention.  ajax_<nodetype>_showInfo()  ?
	**/

	

	function ajax_showCanInfo() {
		$types = array('P'=>'President', 'H'=>'House', 'S'=>'Senate');
		$id = dbEscape($_GET['id']);
		$output = "";
		$output .= canInfoHeader($id)."<a href='#' onclick=\"toggleDisplay('tables'); return false;\">Show contributions</a> ";
		//use a differnt url if it is a prez candidate
		if (substr($id,0,1) != 'P'){
			$output .= " <a class='email_button' href='http://priceofoil.org/action/'>Send Email</a>"; 
			//echo " | <a href ='voteTables.php?chamber=".substr($id,0,1) ."#$id'>Voting profile</a></div> ";
		} else {
			 //lookup the appropriate url
			 //DISABLED 'CAUSE PREZ CAMPAIGN INACTIVE
			 /*
			$url = fetchRow("select DIA_form_url from presidential_candidates where candidateid ='$id';");
			if ($url[0]){
				$output .= " | <a href='".htmlspecialchars($url[0])."'>Send Email </a>";
				//$output .= "</div> ";
			}
			*/
		}
		return $output;
	}

	function ajax_showComInfo() {
		$types = array('P'=>'President', 'H'=>'House', 'S'=>'Senate');
		$id = dbEscape($_GET['id']);
		$output = comInfoHeader($id);
		$output.= "<a href='#' onclick=\"toggleDisplay('cotables'); return false;\">Show contributions</a>";
		return $output;
	}

	function ajax_showcom2canInfo($graph) {
		require_once('../www/lib/graphtable.php');
		$types = array('P'=>'President', 'H'=>'House', 'S'=>'Senate');
		$id = dbEscape($_GET['id']);
		$ids = $graph->data['edges']['com2can'][$id]['ContribIDs'];
		$nodes = explode('_', $id);
		$output = "<h2>Contributions from ".$graph->data['nodes']['companies'][$nodes[0]]['Name'];
		$output.= " to ".$graph->data['nodes']['candidates'][$nodes[1]]['Name']."</h2>";
		$output .= createContributionTable($graph, $nodes[0], $nodes[1]);
		return $output;
	}

	function preProcessGraph() {
		#Set True Filter Values
		$props = &$this->data['properties'];
		if ($props['candidateFilterIndex'] != -1) { 
			$options_js = file_get_contents('../www/optionDefaults.js');
			$options_js = str_replace('advanced_opts = ', '', $options_js);
			$options_array = json_decode($options_js, true);
			$opts = $options_array[$props['sitecode']][$props['congress_num']][$props['racecode']];
			$props['minContribAmount'] = $opts['minContribAmount'][$props['contribFilterIndex']];
			$props['minCompanyAmount'] = $opts['minCompanyAmount'][$props['companyFilterIndex']];
			$props['minCandidateAmount'] = $opts['minCandidateAmount'][$props['candidateFilterIndex']];
		}
	}
}

