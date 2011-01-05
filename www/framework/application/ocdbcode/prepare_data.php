<?php

require_once("../config.php");

/*unify relationship data into relationships table */

query("delete from relationships");

//make sure any merges on entities table get back matched onto appropriate tables?

query("update memberships join entities on member_name = orig_name set member_entityid = match_id;");

//load in CA contributions
query("insert into relationships select null, a.entityid, filer_entityid, name_of_contributor, b.label, amount, transaction_number,transaction_date, 'cal', concat('http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=', filer_id, '&view=general'), row_id,null, if(occupation != '', concat(occupation, '/', employer), '') from ca_contribs a join entities b on filer_entityid = b.entityid where duplicate !=1");

//load in memberships
query("insert into relationships SELECT null, member_entityid, organization_entityid, b.label, c.label, null, concat('member_',rowid) transaction_id,null, 'member', m.source, rowid,null, relation FROM memberships m join entities b on b.entityid = member_entityid and member_entityid is not null join entities c on c.entityid =organization_entityid and organization_entityid is not null");

//load in fec relations
query("insert into relationships SELECT null, d.entityid, r.entityid, d.label, r.label, amount, crp_key,date, concat('fed_',f.type), '', crp_key,null, occupation FROM fec_contribs f  join entities r on recipient_id = r.fec_committee_id join entities d on company = d.label or company = d.orig_name where company != ''");

//flag relations to indicate which view they should be displayed in 
query("update relationships set view = null");
//query("update relationships set view = 'prop_23' where to_id = 257 ");
//query("update relationships set view = 'prop_25/26' where to_id in ('376','377')");
$prop23s = fetchCol("select relationship_id from relationships where to_id = 257");
foreach ($prop23s as $relationship_id){
	recursiveFlag($relationship_id,"prop_23");
}

$prop26s = fetchCol("select relationship_id from relationships where to_id in (376,377)");
foreach ($prop26s as $relationship_id){
	recursiveFlag($relationship_id,"prop_25_26");
}



//search back up a tree to find things that contrib to it
function recursiveFlag($relationship_id,$flag){
	//get the flag of the passed entityid
	$myflag = fetchValue("select view from relationships where relationship_id = $relationship_id");
	//print("\tmyflag:'$myflag' ".is_null($myflag)."\n");
	if($myflag == $flag){
		return;  //we've been here before, don't want to get stuck in a loop
	} else {
		if (is_null($myflag)){
			//set it to the flag we are setting
			query("update relationships set view = '$flag' where relationship_id = $relationship_id");
		} else {
			print("\tboth\n");	
			//set it to both flag //DANGER could loop on the both condition
			query("update relationships set view = 'both' where relationship_id = $relationship_id");
		}
		//get the children edges and run on them
		$more_edges = fetchCol("select relationship_id from relationships where to_id = (select from_id from relationships where relationship_id= $relationship_id)");
		foreach ($more_edges as $edge){
			recursiveFlag($edge,$flag);
		}
	}
	
}
query("update entities set cash = 0, prop26_cash = 0, prop23_cash = 0");


//total contributions
query("update entities join (select from_id, sum(amount) amount, sum(if(view='prop_23' or view='both', amount, 0)) as prop23cash, sum(if(view='prop_25_26' or view='both', amount, 0)) as prop26cash from relationships where amount is not null group by from_id) edges on from_id = entityid set cash = amount, prop23_cash = prop23cash, prop26_cash = prop26cash");

//total contribs to prop23
query("update entities set prop23_cash = (select sum(amount) from relationships where to_id = 257) where entityid = 257;");

//total less than 1k contribs to prop23
query("update entities set prop23_cash = (select sum(amount) from relationships where to_id = 257 and amount < 1000) where entityid = 322;");

//total contributions to yes on 26
query("update entities set prop26_cash = (select sum(amount) from relationships where to_id = 377) where entityid = 377");

//total contributions to no on 25, yes on 26
query("update entities set prop26_cash = (select sum(amount) from relationships where to_id = 376) where entityid = 376");

//write a file with a cash containing the percentage values and stuff

$DEMPercent23 = fetchValue("select round(sum(if(entities.type = 'oil' or entities.type = 'coal' ,amount,0))/sum(amount)*100,0) percent from relationships join entities on from_id = entityid where view = 'prop_23'");
$nonCAPercent23 = fetchValue("select round(sum(if(!isnull(entities.state) and entities.state != 'CA',amount,0))/ sum(amount)*100) from relationships join entities on from_id = entityid where view = 'prop_23'");


$contents = '<?php 
$statsCache=array("DEM_percent_23"=>"'.$DEMPercent23.'", 
"nonCAPercent_23"=>"'.$nonCAPercent23.'");?>';
$fp = fopen('../www/stats_cache.php', 'w');
fwrite($fp, $contents);
fclose($fp);


?>
