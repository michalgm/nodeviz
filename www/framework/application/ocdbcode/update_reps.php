<?php 
require('../config.php');
require('../www/lib/sunlightFunctions.php');

print "Loading xml file...\n";
#copy('http://www.govtrack.us/data/us/110/repstats/people.xml', './people.xml');
#copy('http://www.govtrack.us/data/us/110/people.xml', './people.xml');
if (! isset($argv[1])) { 
	copy('http://www.govtrack.us/data/us/people.xml', './people.xml');
}
$xml = simplexml_load_file('./people.xml');
//	print_r($xml); exit;

print "Processing xml file...\n";
foreach ($xml as $person) {
	foreach ($person->role as $role) {
		$noncycle_start = 0;
		$noncycle_end = 0;
		$senate_filler = 0;
		$class = '';
		$year = substr($role['startdate'], 0, 4);
		$endyear = substr($role['enddate'], 0, 4);
		if ($year >= 1995) { 
			if((!$year % 2) || $role['startdate'] != "$year-01-01") { $noncycle_start = 1; }
			if($endyear % 2 || $role['enddate'] != "$endyear-12-31") { $noncycle_end = 1; }
			$congs = Array();
			$cong = ceil($year/2)-894;
			$party = strtoupper(substr($role['party'], 0, 1));
			$title = 'Senator';
			$chamber = 'S';
			$congs[] = $cong;
			$firstname = $person['nickname'] ? $person['nickname'] : $person['firstname'];
			$firstname = dbEscape($firstname);
			$lastname = dbEscape($person['lastname']);
			if ($role['type'] == 'rep') { 
				$title = 'Representative'; 
				$chamber = 'H';
			} else if ($role['type'] == 'sen') {
				$class = '-';
				if ($endyear != $year + 5 && ! $noncycle_start && ! $noncycle_end) {
					$noncycle_start = 1;
					$noncycle_end = 1;
					print "A wierdo! $person[firstname] $person[lastname] - $endyear, $startyear $role[startdate], $role[enddate]\n";
				}
				if ($noncycle_start == 0 && $noncycle_end == 0) { $class = get_senate_class($year); }
				if ($endyear >= $year +2) {
					$congs[] = $cong+1;
					if ($endyear >= $year +4) {
						$congs[] = $cong+2;
					}
				}	
			} else { continue; }
			foreach ($congs as $cong) {
				$cycle = ($cong+894)*2;
				$query = "insert into congressmembers values(null, null, '$person[osid]', null, '$person[pvsid]', $person[id], '$role[district]', '$lastname', '$firstname', '$title', '$person[gender]',  null, '$role[state]', '$party', '$class', null, null, '$role[url]', '$person[id]"."_$cong', '$role[startdate]', '$role[enddate]', $noncycle_end, $cong, '$chamber', $noncycle_start, $senate_filler, 0,0, $cycle) on duplicate key update CRPcandID = '$person[osid]', GovTrack_ID =  $person[id], district = '$role[district]', lastname = '$lastname', firstname = '$firstname', title = '$title', gender = '$person[gender]',state_abbreviation =  '$role[state]', party = '$party', senator_class='$class', url = '$role[url]', candidate_key = '$person[id]"."_$cong', startdate = '$role[startdate]', enddate = '$role[enddate]', noncycle_end = $noncycle_end, congress_num = $cong, chamber = '$chamber', noncycle_start = $noncycle_start, senate_filler = $senate_filler, contrib_total=0, pac_total=0, year = $cycle, VoteSmart_ID = '$person[pvsid]'";
				dbwrite($query);
				//$class = '';
				$senate_filler = 1;
			}
		}
	}
}


print "deleting oldies\n";
$min_cong = min(array_keys($congresses));
$max_cong = max(array_keys($congresses));
dbwrite("delete from congressmembers where congress_num<$min_cong");
dbwrite("delete from congressmembers where congress_num>$max_cong");

print "deleting fakes\n";
dbwrite('delete from candidates where fake=1');

print "updating new reps\n";
dbwrite("update congressmembers a join candidates b on a.state_abbreviation = b.campaignstate and a.congress_num = b.congress_num and a.chamber = b.chamber and a.district = b.currentdistrict set fec_id = candidateid where a.FEC_ID is null and PartyDesignation1 like concat(party, '%') and candidatename = concat(lastname, ', ', firstname)");

print "updating fakes\n";
dbwrite("insert into candidates ( candidate_key, CandidateId, YearOfElection, fake, congress_num, winner, campaignid, chamber, campaignstate, partydesignation1 ) select concat(fec_id,substring(((a.congress_num+894)*2), 3, 2)) as ckey, fec_id, substring(((a.congress_num+894)*2), 3, 2) as year, 1, a.congress_num, 1, concat(substring(fec_id, 1, 1),substring(((a.congress_num+894)*2), 3, 2)), substring(fec_id, 1, 1), substring(fec_id, 3,2), substring(party, 1, 1) from congressmembers a left join candidates b on b.candidateid = a.fec_id and a.congress_num = b.congress_num where b.candidateid is null  and a.congress_num >=$min_cong and fec_id is not null");

dbwrite("insert into candidates ( candidate_key, CandidateId, YearOfElection, fake, congress_num, winner, campaignid, chamber, campaignstate, partydesignation1 ) select concat(a.candidateid,substring(((a.congress_num+894)*2), 3, 2)) as ckey, a.candidateid, substring(((a.congress_num+894)*2), 3, 2) as year, 1, a.congress_num, 0, concat(substring(a.candidateid, 1, 1),substring(((a.congress_num+894)*2), 3, 2)), substring(a.candidateid, 1, 1), substring(a.candidateid, 3,2), substring(party, 1, 1) from contributions a left join candidates b on b.candidateid = a.candidateid and a.congress_num = b.congress_num where b.candidateid is null  and a.congress_num >=$min_cong and a.candidateid is not null group by a.candidateid, a.congress_num");

dbwrite("replace into candidates select a.candidate_key, a.CandidateID, b.CandidateName, b.PartyDesignation1, b.Filler, b.PartyDesignation3, null, b.Filler2, null, b.StreetOne, b.StreetTwo, b.City, b.State, b.Zipcode, b.PrincipalCampaignCommId, a.YearofElection, b.CurrentDistrict, NULL, nULL, a.campaignid, a.winner, a.CampaignState, a.congress_num, a.chamber, 1 from candidates a join candidates b on a.candidateid = b.candidateid and a.congress_num = (b.congress_num +1)  where a.candidateName is null and a.fake = 1");


dbwrite("replace into candidates select a.candidate_key, a.CandidateID, b.CandidateName, b.PartyDesignation1, b.Filler, b.PartyDesignation3, null, b.Filler2, null, b.StreetOne, b.StreetTwo, b.City, b.State, b.Zipcode, b.PrincipalCampaignCommId, a.YearofElection, b.CurrentDistrict, NULL, nULL, a.campaignid, a.winner, a.CampaignState, a.congress_num, a.chamber, 1 from candidates a join candidates b on a.candidateid = b.candidateid  where a.candidateName is null and a.fake = 1 and b.candidatename is not null order by congress_num desc");

print "updating winners\n";
dbwrite("update candidates set winner = 0;");
#dbwrite("update candidates a join congressmembers b on fec_id = candidateid and a.congress_num = b.congress_num and senate_filler != 1 set winner = 1");
dbwrite("update candidates a join congressmembers b on fec_id = candidateid and a.congress_num = b.congress_num set winner = 1");

dbwrite("update congressmembers set district = '98' where state_abbreviation = 'DC' and district = '00'");

dbwrite("update congressmembers a join candidates b on fec_id = candidateid set party = substring(PartyDesignation1, 1, 1) where a.party = '' and yearofelection < 04");

#Attempt to update missing fec_ids
$people = dbLookupArray("select govtrack_id, candidate_key from congressmembers where fec_id is null");
foreach ($people as $person) {
	$fec_id = fetch_sunlight_by_govtrack($person['govtrack_id']);
	if ($fec_id) {
		dbwrite("update congressmembers set fec_id='$fec_id' where candidate_key = '".$person['candidate_key']."'");
	}
}
dbwrite("update congressmembers a join (select a.candidate_key, b.candidateid from congressmembers a join candidates b on a.state_abbreviation = b.campaignstate and a.congress_num = b.congress_num and a.chamber = b.chamber and a.district = b.currentdistrict and lastname = substring_index(candidatename, ',', 1) where a.FEC_ID is null and PartyDesignation1 like concat(party, '%') and b.winner=1 group by a.candidate_key having count(*) = 1) b using (candidate_key) set a.fec_id = b.candidateid"); 
#dbwrite("update congressmembers join (select * from (select candidateid, recipient_id from congressmembers a join contributions on a.fec_id = candidateid where CRPcandID = '' group by candidateid, recipient_id) a group by CandidateId having count(*) = 1) a on fec_id = candidateid set CRPcandID = recipient_id");
dbwrite("update congressmembers a join congressmembers b using(GovTrack_ID, title, state_abbreviation, district)  set a.fec_id = b.fec_id where a.fec_id is null and b.fec_id is not null and b.congress_num = a.congress_num-1");
$reps = dbLookupArray("select candidateid, concat(lastname, ', ', firstname) as name, candidatename from congressmembers a join candidates b on a.state_abbreviation = b.campaignstate and a.congress_num = b.congress_num and a.chamber = b.chamber and a.district = b.currentdistrict where a.FEC_ID is null and PartyDesignation1 like concat(party, '%')");
if ($reps) { print "Found candidates with missing ids. These should be manually updated in congressmembers, and then this script should be re-run:\n"; }
foreach ($reps as $rep) { 
	print "\tmissing candidate id for ".$rep['name'].". Maybe ".$rep['candidateid']." (".$rep['candidatename'].")\n";
}
dbwrite("update congressmembers a set fec_id = govtrack_id where fec_id is null");

function get_senate_class($year) { 
	$classes = array(5=>'II', 3=>'I', 1=>'III');
	return $classes[$year%6];
}

?>

