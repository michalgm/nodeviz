<?php
require('../config.php');
$db = dbconnect();

print("calculating totals and computing cash per term..\n");
dbWrite("replace into vote_scores select candidateid,null, sum(amount),sum(amount)/terms,null,chamber from contributions join (select fec_id,count(*) as terms,chamber from congressmembers group by fec_id) ids on candidateid = ids.fec_id where candidateid like concat(racecode, '%') and congress_num is not null group by candidateid;");


//FORMULA FOR PERCENTILE
//rank of value V = (((number of values below V) + 0.5*(number of values = V))/ number of values ) * 100

//figure out the total number of cases for house and for senate
//compute percentile scores for each
$chambers = array('S','H');
foreach( $chambers as $chamber){ 
	 print("computing cash percentials for chamber $chamber ..\n");
	$countQ = "select count(distinct candidateid) from vote_scores where chamber = '$chamber';";
	$count = fetchRow($countQ);
	$count = $count[0];
 	print("    $count elected officials for $chamber\n");
 	print("    inserting cash percentile scores\n");
    $percentileQ = "replace into vote_scores select candidateid,null,total_cash,perterm_cash, round( 100.0 * 
(( select count(*) from (select candidateid, perterm_cash from vote_scores where chamber = '$chamber') b where b.perterm_cash < a. perterm_cash ) 
)
/ $count, 1 ) percentile, chamber from 
(select candidateid, total_cash, perterm_cash,chamber from vote_scores where chamber = '$chamber') a;";
	dbWrite($percentileQ);
    
    //select GovTrack_ID, ttl as cash , round( 100.0 * ( select count(*) from (select GovTrack_ID, sum(contrib_total) ttl from congressmembers group by GovTrack_ID) b where b.ttl <= a.ttl ) / total.cnt, 0 ) percentile from (select GovTrack_ID, sum(contrib_total) ttl from congressmembers group by GovTrack_ID) a join (select count(*) cnt from (select GovTrack_ID, sum(contrib_total) ttl from congressmembers group by GovTrack_ID) c) total order by percentile desc;
print("   calculating voting scores...\n");
//now calculate the voting scores;
$idsQuery = "select fec_id from votes join congressmembers info on candidateid = fec_id  where chamber = '".$chamber."' group by fec_id"; 
$voteEventQuery = "select bill_id, concat(bill_id,' ',bill_category) as vote_name, public_interest, bill_category from bills where bill_id like '".$chamber."%';";
$voteEvents = dbLookupArray($voteEventQuery);
//now build the data table and score the votes
$results = array();
foreach ($voteEvents as $voteEvent) {
//  print ($voteEvent['bill_id'] );
  $votesQuery  = "select a.fec_id, vote from (".$idsQuery.") a
   left join votes b on a.fec_id = b.candidateid and bill_id = '"
  .$voteEvent['bill_id']."';";
  $votes = dbLookupArray($votesQuery);
  //change signs of votes to match public intesrest
  $votes = changeToInterest($votes, $voteEvent['public_interest']);
  //print_r($votes);
  $results[$voteEvent['bill_id']] = $votes;
  $headers[$voteEvent['bill_id']] = $voteEvent['vote_name'];
}
//now calculate the vote score for each person and insert it
$people = dbLookupArray($idsQuery.";");

foreach ($people as $person){
  $id = $person['fec_id'];
  $score = round(getScore($results,$id,0));
  //run query to insert score in db
  dbWrite("update vote_scores set voting_score = $score where candidateid = '$id';");
}

}
print("done with scores.\n");

//switches direction of vote to be in line with public interest and does some recoding
function changeToInterest($votes, $publicInterest){

 foreach($votes as &$vote){
   if ($publicInterest){
 	if ($vote['vote'] == '+'  ){
 	  $vote['vote'] = 0;
 	} elseif ($vote['vote'] == '-')  {
 	 $vote['vote'] = 1;
 	} elseif ($vote['vote'] == '0') {
 	  $vote['vote'] = 'NV';
 	} elseif ($vote['vote'] == 'P') {
 	  $vote['vote'] = 'NV';
 	} else {
 	  $vote['vote'] = 'NA';
 	}
 	
   } else { //votes was not in public interest so count he other way
     if ($vote['vote'] == '-'  ){
 	  $vote['vote'] = 0;
 	} elseif ($vote['vote'] == '+')  {
 	 $vote['vote'] = 1;
 	} elseif ($vote['vote'] == '0') {
 	  $vote['vote'] = 'NV';
 	} elseif ($vote['vote'] == 'P') {
 	  $vote['vote'] = 'NV';
 	} else {
 	  $vote['vote'] = 'NA';
 	}
   }
 	
 }
 return $votes;
 }

 function getScore($votingrecords, $govTrackId){
 //trina sez:
//- NV counts against a person (as if they voted for Big Oil)
//- NA does not count at all so that that vote is taken out of the grand total.
 
    $score = 0;
    $count = 0;
    foreach(array_keys($votingrecords) as $voteid){
       $vote = $votingrecords[$voteid][$govTrackId]['vote'];
      if (is_numeric($vote) ){
      	$score += $vote;
      	$count ++;
      }
    }
    if ($count == 0){ 
      $score ='NA';
    } else{
     $score = $score / $count;
     $score = $score*100 ; //make it into a percent
    }
    return $score;
 }

?>