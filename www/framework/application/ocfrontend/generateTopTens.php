<?php 
require_once("../config.php");

//writeSummaries();

function cache_summaries(){
	$topTenTargetDir = "../www/cache/topTens/";
	echo "generating html include files for top ten summaires\n";
	//create the directory if it doesn't exist
	if(!is_dir($topTenTargetDir)){
  		mkdir($topTenTargetDir,0777);
  		chmod($topTenTargetDir,0777);
 	 }

	//TODO need some query robustness here. 
	$results = array();
	//range of cycle years we are dealing wth
	$results['cycleRange'] = fetchRow('select min(cycle), max(cycle) from contributions;');
	//total contribution cash
	$results['total'] = fetchRow('select concat("$",round(sum(amount)/1000000)," Million") from contributions;');
	//year and race breakdown
	$results['byYear'] = dbLookupArray('select cycle Cycle, 
concat("$",format(sum(if(racecode="H",amount,0))/1000000,0),"M") House,
concat("$",format(sum(if(racecode="S",amount,0))/1000000,0),"M") Senate,
concat("$",format(sum(if(racecode="P",amount,0))/1000000,1),"M") Presidential,
concat("$",format(sum(amount)/1000000,0),"M") Total 
from contributions group by cycle;');
	$results['topComp'] = dbLookupArray('select id,name,concat("$",format(sum(amount),0)) cash,image_name, if(oil_related>=coal_related,"oil","coal") type from companies a join contributions b on a.id = b.companyid where a.match_id=a.id and b.racecode != "P" group by id order by sum(amount) desc limit 6;');
	#$results['topCans'] = dbLookupArray('select a.FEC_ID can_id,concat(firstname," ", lastname) name, concat(a.party,"&ndash;",state_abbreviation," ",district) dist,if(a.party="R","REP",if(a.party="D","DEM","OTHER")) partycode,concat("$",format(sum(amount),0)) cash, concat("(",min(year),"-", max(year),")") inoffice from congressmembers a join contributions b on a.fec_id = b.candidateid and a.congress_num=111 group by a.FEC_ID order by sum(amount) desc limit 6;');
	$results['topCans'] = dbLookupArray('select a.FEC_ID can_id,concat(firstname," ", lastname) name, concat(a.party,"&ndash;",state_abbreviation) dist,if(a.party="R","REP",if(a.party="D","DEM","OTHER")) partycode,concat("$",format(sum(amount),0)) cash, concat("",min(b.congress_num),"th&ndash;", max(b.congress_num),"th Congress") inoffice from congressmembers a join contributions b on a.fec_id = b.candidateid and a.congress_num=111 group by a.FEC_ID order by sum(amount) desc limit 6;');
	$results['topRel']=dbLookupArray('select count(*) numcontribs,  concat("$",format(sum(amount),0)) cash, candidateid,image_name,companyName,concat(title," ", firstname," ", lastname," (",c.party,"-",state_abbreviation," ",district,")") name from contributions  join companies on companyid = id join congressmembers c on candidateid = c.FEC_ID group by candidateid,company order by sum(amount) desc limit 5 ');
	$results['topCong']=dbLookupArray('select a.congress_num, concat("$",format(sum(amount),0)) cash from contributions a join congressmembers b on a.candidateid = b.fec_id and a.congress_num = b.congress_num where racecode != "P" group by a.congress_num order by sum(amount) desc');

$topComp ="";
$topCans ="";
$topRel = "";
$topCong="";
//create html of images and totals for oil comapny list
foreach(array_keys($results['topComp']) as $id){
	$topComp=$topComp." <li onclick=\"document.location.href='view.php?type=search&com=".$results['topComp'][$id]['id']."';\"><p class='name'>".$results['topComp'][$id]['name']."<span class='details'>".$results['topComp'][$id]['type']."</span></p>
	<p class='contributions'>".$results['topComp'][$id]['cash']."</p><a href='view.php?type=search&com=".$results['topComp'][$id]['id']."' class='go icon'>Go to this profile</a></li>";
}


//create html of images and totals for candidates
foreach(array_keys($results['topCans']) as $id){
	$topCans=$topCans." <li onclick=\"document.location.href='view.php?type=search&can=".$results['topCans'][$id]['can_id']."';\"><p class='name'> ".$results['topCans'][$id]['name']." <span class='party-state ".$results['topCans'][$id]['partycode']."'>(".$results['topCans'][$id]['dist'].")</span>
	<span class='details'>".$results['topCans'][$id]['inoffice']."</span></p>
	<p class='contributions'>".$results['topCans'][$id]['cash']."</p><a href='view.php?type=search&can=".$results['topCans'][$id]['can_id']."&congress_num=total' class='go icon'>Go to this profile</a></li>"
;
}

global $congresses;
//create html of top congresses
foreach(array_keys($results['topCong']) as $id){
	$topCong=$topCong." <li onclick=\"document.location.href='view.php?type=congress&congress_num=".$results['topCong'][$id]['congress_num']."';\"><p class='name'>".$results['topCong'][$id]['congress_num']."th Congress <span class='details'>".$congresses[$results['topCong'][$id]['congress_num']]."</span></p>
				<p class='contributions'>".$results['topCong'][$id]['cash']."</p>
				<a href='view.php?type=congress&congress_num=".$results['topCong'][$id]['congress_num']."' class='go icon'>Go to this profile</a></li>"
;
}


saveFile($topTenTargetDir.'topCong.inc','<div class="congresses">
		<h3 onclick="document.location.href=\'view.php?type=congress\';">Dirtiest Congresses</h3>
		<ul>
			'.$topCong.' 
		</ul>
	</div>'   );
	
saveFile($topTenTargetDir.'topCans.inc','<div class="politicians">
		<h3 onclick="document.location.href=\'overview.php?type=politician\';">Dirtiest Politicians</h3>
		<ul>
			'.$topCans.'
		</ul>
	</div>'  );
	
saveFile($topTenTargetDir.'topComp.inc','<div class="companies">
		<h3 onclick="document.location.href=\'overview.php?type=company\';">Big-Spending Companies</h3>
		<ul>
			'.$topComp.'
		</ul>
	</div>'  );
	
}

function saveFile($filename,$content){
		if (!$handle = fopen($filename,"w")) {
			echo "Cannot open file ($filename) to write topTen cache\n"; 
		} else {

			// Write $somecontent to our opened file.
			if (fwrite($handle, $content) === FALSE) {
				echo "Cannot write to file ($filename)";
			} else {
				echo "wrote topTen cache to file ($filename)\n";
				fclose($handle);
			}
		}
}

?>

	
