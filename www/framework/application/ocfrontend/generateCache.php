<?php
require_once("../config.php");
include_once('../framework/GraphVizExporter.php');
include_once('../framework/ContributionGraph.php');
require_once('generateSummaryCharts.php');
require_once('generateTopTens.php');

#$sections = array('summaries', 'infographics', 'races', 'companies', 'candidates', 'dollars');
$sections = array('races');

$force = 0;
if (isset($argv[1])) { 
	$force = $argv[1];
}
$cache = 0;
$debug = 0;
$datapath = "../www/cache/";
$logdir = "../www/log/";
$maxChildren = 4;
$numChildren = 0;
$pids = array();

if (! isset($argv[2]) || $argv[2] == 'all') {
	foreach($sections as $section) { 
		$function = 'cache_'.$section;
		$function();
	}
} else {
	$x = 0;
	foreach($argv as $arg) {
		$x++;
		if ($x < 3) { continue; }
		if (!in_array($arg, $sections)) {
			print "$arg not a valid section - skipping\n";
			continue;
		} else {
			$function = 'cache_'.$arg;
			$function();
		}
	}
}


#writeSummaries();

function cache_infographics() {

	cacheCongSummaryGraphics();

	cacheComSummaryGraphics();

	cacheCanSummaryGraphics();
}

#cacheRaces();

#cacheCompanies();

#cacheCandidates();

/*
#compressImages(); 

#generateDollars();
 */
function cache_dollars() {
	global $force;
	if ($force) { system("rm -rf ../www/oildollars/*"); }
	echo "generating congressional oil dollars\n";
	include("generate_oildollars.php");
}


function cache_races() {
	echo "caching races;\n";
	global $_REQUEST;
	global $cache;
	global $congresses;
	global $force;
	global $datapath;
	if ($force) {
		system("rm -rf $datapath/coal/*; rm -rf $datapath/oil/*; rm -rf $datapath/carbon/*;");
	}
	$_REQUEST = array('candidateLimit' => 250, 'companyLimit'=>250);
	$count = (3*3*count(array_keys($congresses))*3*3*3);
	$x = 0;
	#foreach (array('carbon', 'oil', 'coal') as $_REQUEST['sitecode']) {
	foreach (array('carbon') as $_REQUEST['sitecode']) {
		#foreach (array('P', 'H', 'S') as $_REQUEST['racecode']) {
		foreach (array('S') as $_REQUEST['racecode']) {
			#foreach (array_keys($congresses) as $_REQUEST['congress_num']) {
			foreach (array('111') as $_REQUEST['congress_num']) {
				#foreach (array('0','1','2') as $_REQUEST['candidateFilterIndex']) {
				foreach (array('0', '1') as $_REQUEST['candidateFilterIndex']) {
					foreach (array('0') as $_REQUEST['companyFilterIndex']) {
						foreach (array('0') as $_REQUEST['contribFilterIndex']) {
							$x++; $percent = (($x/$count)*100); printf("\t%0.2f%% of %s\r", $percent, $count);;
							if ($_REQUEST['racecode'] == 'P' and ($_REQUEST['congress_num'] % 2) != 0) {continue; }
							fork_work('graph');
						}
					}
				}
			}
		}
	}
	print "\n";
}

function cache_candidates() {
	echo "caching candidates;\n";
	global $_REQUEST;
	global $cache;
	global $congresses;
	global $force;
	global $datapath;
	if ($force) {
		system("rm -rf $datapath/carbon/individuals/");
	}
	$_REQUEST = array('candidateLimit' => 250, 'companyLimit'=>250);
	$current_cong = max(array_keys($congresses));
	$query = "select concat(a.fec_id, b.congress_num) as id, a.fec_id, b.congress_num, b.chamber as racecode from congressmembers a join congressmembers b using (fec_id) where a.fec_id is not null and a.congress_num = $current_cong group by a.fec_id, b.congress_num 
				union select concat(fec_id, 'total') as id, fec_id, 'total', chamber from congressmembers where fec_id is not null and congress_num = $current_cong group by fec_id
				union select concat(a.candidateid, 'pre') as id, a.candidateid, 'pre', c.chamber from contributions a join congressmembers c on a.candidateid = c.fec_id left join congressmembers b on a.candidateid = b.fec_id and a.congress_num = b.congress_num where b.fec_id is null and a.racecode != 'P' and c.congress_num = $current_cong group by a.candidateid
				order by congress_num, fec_id	";
	$results = dbLookupArray($query);
	$count = count($results);
	$x = 0;
	foreach ($results as $can) {
		#if($can['fec_id'] != 'H8CA09060') { continue; }
		#$_REQUEST['racecode'] = $can['fec_id'][0];
		$_REQUEST['sitecode'] = 'carbon';
		$_REQUEST['congress_num'] = $can['congress_num'];
		$_REQUEST['candidateids'] = $can['fec_id'];
		$_REQUEST['racecode'] = $can['racecode'];
		$x++; $percent = (($x/$count)*100); printf("\t%0.2f%% of %s\r", $percent, $count);;
		fork_work('graph');
	}
	print "\n";
}

function cache_companies() {
	echo "caching companies:\n";
	global $_REQUEST;
	global $cache;
	global $force;
	global $datapath;
	if ($force) {
		system("rm -rf $datapath/carbon/companies/");
	}
	$_REQUEST = array('candidateLimit' => 250, 'companyLimit'=>250);
	$query = "select concat(a.companyid, a.congress_num) as id, a.companyid, a.congress_num from contributions a join congressmembers b on a.candidateid = b.fec_id and a.congress_num = b.congress_num where racecode != 'P' group by a.companyid, a.congress_num
				union select concat(companyid, 'total') as id, companyid, 'total' from contributions where racecode != 'P' group by companyid
				union select concat(a.companyid, 'pre') as id, a.companyid, 'pre' from contributions a left join congressmembers b on a.candidateid = b.fec_id and a.congress_num = b.congress_num where b.fec_id is null and a.racecode != 'P' group by companyid
				order by congress_num, companyid";
	$results = dbLookupArray($query);
	$count = count($results);
	$x = 0;
	foreach ($results as $can) {
		$_REQUEST['sitecode'] = 'carbon';
		$_REQUEST['racecode'] = 'C';
		$_REQUEST['congress_num'] = $can['congress_num'];
		$_REQUEST['companyids'] = $can['companyid'];
		#buildCache();
		$x++; $percent = (($x/$count)*100); printf("\t%0.2f%% of %s\r", $percent, $count);;
		fork_work('graph');
	}
	print "\n";
}

function cacheCongSummaryGraphics() {
	global $congresses;
	global $force;
	global $datapath;
	if($force) {
		system("rm -rf $datapath/summaryChart/congresses/*");
	}
	echo "caching congress summary graphics\n";
	$results = array_keys($congresses); //list of current congress numbers we have
	$count = count($results);
	$x = 0;
	foreach ($results as $cong) {
		$x++; $percent = (($x/$count)*100); printf("\t%0.2f%% of %s\r", $percent, $count);;
		fork_work('congsummary', $cong);
	}
	print "\n";
}

function cacheCanSummaryGraphics() {
	global $force;
	global $datapath;
	if($force) {
		system("rm -rf $datapath/summaryChart/candidates/*");
	}
	echo "caching candidate summary graphics\n";
	$query = "select distinct fec_id from congressmembers where fec_id != ''";
	$results = dbLookupArray($query);
	$count = count($results);
	$x = 0;
	foreach ($results as $can) {
		$x++; $percent = (($x/$count)*100); printf("\t%0.2f%% of %s\r", $percent, $count);;
		fork_work('cansummary', $can['fec_id']);
	}
	print "\n";
}

function cacheComSummaryGraphics() {
	global $force;
	global $datapath;
	if($force) {
		system("rm -rf $datapath/summaryChart/companies/*");
	}
	echo "caching company summary graphics\n";
	$query = "select distinct id from companies where id=match_id";
	$results = dbLookupArray($query);
	$count = count($results);
	$x = 0;
	foreach ($results as $com) {
		$x++; $percent = (($x/$count)*100); printf("\t%0.2f%% of %s\r", $percent, $count);;
		fork_work('comsummary', $com['id']);
	}
	print "\n";
}

function buildCSV() {
	global $_REQUEST;
	global $datapath;
	global $logdir;
	foreach(array('candidateFilterIndex', 'companyFilterIndex', 'contribFilterIndex', 'candidateLimit', 'companyLimit') as $key) {
		unset($_REQUEST[$key]);
	}
	$graph = new ContributionGraph();
	$graph->setupGraph($_REQUEST, 1);
	include_once('../www/lib/csv.php');
	$csv = createCSV($graph);
	$graphname = $datapath.$graph->graphname().'.csv';
	$file = fopen("$graphname", 'w');
	fwrite($file, $csv);		
	fclose($file);
}

function buildCache() {
	global $_REQUEST;
	global $datapath;
	global $logdir;
	$format = 'jpg';
	$graph = new ContributionGraph();
	$graph->setupGraph($_REQUEST, 1);
	$graphname =$graph->graphname();
	#print "$graphname: ";
	$imageFile = "$datapath/$graphname.$format";
	$dotFile = "$datapath/$graphname.dot";
	$filename = "$datapath/$graphname";
	$exists = "1";
	foreach (array(".$format", '.imap', '.graph') as $ext) {
		#echo "."; 
		if (! file_exists("$filename$ext")) { $exists = "0"; }
	}
	if ($exists == '0') {
		#echo "#";
		$graph->loadGraphData();
		foreach ($graph->data['nodes'] as $nodetype) {
			if (sizeof($nodetype) ==0 ) { 
				$graphfile = fopen("$filename.graph", 'w');
				fwrite($graphfile, serialize('empty graph'));
				fclose($graphfile);
				#echo "empty\n";
				return;
			}
		}
		GraphVizExporter::generateGraphvizOutput($graph, $datapath, $format);
		if (0 && $format == 'png') { 
			#exec("(optipng -i1 -q $datapath/$graphname.png $datapath/$graphname.png; chmod 666 $datapath/$graphname.png ) >> $datapath/optipng.log &");
			exec("optipng -i1 -q $datapath/$graphname.png $datapath/$graphname.png");
			exec("chmod 666 $datapath/$graphname.png");
		}
		#exec("optipng -i1 -q $datapath/$graphname.png");
		#chmod("$datapath/$graphname.png", 0666);
		#echo "done\n";
	} else { 
		#echo "skipping\n"; 
	}
	foreach ( array('.svg.raw', '.dot', '_orig.dot', '.nicegraph') as $ext) {
		if (file_exists("$datapath$graphname$ext")) {
			unlink("$datapath$graphname$ext");
		}
	}	
	if ((! isset($_REQUEST['candidateFilterIndex']) || ($_REQUEST['candidateFilterIndex'] == 0 && $_REQUEST['companyFilterIndex'] == 0 && $_REQUEST['contribFilterIndex'] == 0)) && $_REQUEST['sitecode'] == 'carbon')  {
		#buildCSV($graph);
	}	
}

function fork_work($worktype, $arg='') {
	global $pids;
	global $numChildren;
	global $maxChildren;
	global $db;
	$db = 0;
	if (! $worktype) { $worktype = 'graph'; }
	$pids[$numChildren] = pcntl_fork();
	if(!$pids[$numChildren]) {
		// do work
		if ($worktype == 'graph') { 
			buildCache();
		} else if ($worktype == 'cansummary'){
			canPlots($arg);
		} else if ($worktype == 'comsummary'){
			comPlots($arg);
		} else if ($worktype == 'congsummary'){
			congPlots($arg);
		}
		posix_kill(getmypid(), 9);
	} else {
		$numChildren++;
		if($numChildren == $maxChildren) {
			pcntl_wait($status);
			$numChildren--;
		}
	}
}

function compressImages() {
	echo "compressing large images;\n";
	global $datapath;
	global $pids;
	global $numChildren;
	global $maxChildren;
	global $db;

	$sizelimit = 12000; #size of images to compress, in bytes
	$bigfiles= array();

	print "Finding Big files....\n";
	exec("find $datapath -name \"*.png\"", $files);
	foreach ($files as $file) { 
		if (filesize($file) >= $sizelimit) { 
			$bigfiles[] = $file;
			#print "$file\n";
		}
	}

	$count = count($bigfiles);
	$x = 0;
	print "Found $count files to resize\n";

	foreach ($bigfiles as $file) { 
		$x++; $percent = (($x/$count)*100); printf("\t%0.2f%% of %s\r", $percent, $count);;
		$pids[$numChildren] = pcntl_fork();
		if(!$pids[$numChildren]) {
			// do work
			#print $file.": ".filesize($file)."\n";
			exec("optipng -i1 -q $file $file");
			exec("chmod 666 $file");
			posix_kill(getmypid(), 9);
		} else {
			$numChildren++;
			if($numChildren == $maxChildren) {
				pcntl_wait($status);
				$numChildren--;
			}
		}
	}	
	print "\n";
}	



?>

