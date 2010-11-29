<?php 
require('../config.php');

$url = $argv[1];
$interest = $argv[2];
if ($interest != 1) { $interest = 0; }
#$url = "http://www.govtrack.us/data/us/110/rolls/h2007-1040.xml";
$file = file_get_contents($url);
$xml = new SimpleXMLElement($file);
$cong =  $xml['session'];
$type = $xml['where'];
$date = $xml['datetime'];
$result = $xml->result;
$vote = $xml['roll'];
$billid = substr($xml[where], 0, 1).$xml[year]."-".$vote;
$bill = $xml->bill[type].$xml->bill[number];
$question = $xml->question;
//bill_id, date, question, result, ytotal,ntotal,bill, congress_num, chamber, nvtotal, ptotal, vote, public_interest, bill_category, description, oc_title, passed
$query = "replace into bills values('$billid', '$date', '$question', '$result', $xml[aye], $xml[nay], '$bill', $cong, '$type', $xml[nv], $xml[present],'$vote', $interest, null, null, null, null)";
$res = dbWrite($query);
if (! $res ) {
	print "Unable to insert bill: $query\n";
	exit;
}
print "$billid ($bill): ";
//print $query;
//print_r( $xml);

//Votes table:  vote_id,congress_num, bill_number, GovTrack_ID, vote, branch, state, district, bill_id, candidateid. 
foreach ($xml->voter as $voter) {
	//get the candidate id for the GovTrackID
	$fecID = fetchCol("select FEC_ID from congressmembers where GovTrack_ID = '$voter[id]' group by FEC_ID;");
	$district = 0;
	if ($type == 'house') { $district = $voter[district]; }
	$q = "replace into votes values('$billid"."_$voter[id]', $cong, '$bill', $voter[id], '$voter[vote]', '$type', '$voter[state]', $district, '$billid','$fecID[0]' )";
	dbWrite($q);
	print "$voter[vote]";
}
exit;
