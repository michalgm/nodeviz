<?php

//api key for Oil Change on the sunlightapi
$apikey = "3d31e77632dee1932263856761f7494b";

// get_fecs_for_zip("00601");  //PR 00601 //DC 20017

//debug
//echo(get_pic_url_for_fec('S4IL00180'));
//print_r(get_fecs_for_zip('94606'));
//echo(convert_fec_sunlight('S4IL00180'));


//use sunlight apis to convert from fec id to their api id for use inother functions
function convert_fec_sunlight($fecId){
	//echo('converting'.$fecId."\n");
	$query = 'http://api.sunlightlabs.com/people.convertId.php?id='.$fecId.'&fromcode=FEC_ID&tocode=entity_id';
	$recode = file_get_contents($query);
	$converted = json_decode($recode,true);
	return($converted['entity_id']);
}

function convert_sunlight_fec($entity_id){
	//echo('converting'.$fecId."\n");
	$query = 'http://api.sunlightlabs.com/people.convertId.php?id='.$entity_id.'&fromcode=entity_id&tocode=FEC_ID';
	$recode = file_get_contents($query);
	$converted = json_decode($recode,true);
	return($converted['FEC_ID']);
}

function fetch_sunlight_by_govtrack($govtrack_id) {
	global $apikey;
	$query = "http://services.sunlightlabs.com/api/legislators.get?apikey=$apikey&govtrack_id=".$govtrack_id;
	$result = file_get_contents($query);
	if ($result) { 
		$converted = json_decode($result,true);
		return($converted['response']['legislator']['fec_id']);
	}
}

/* returns an array with info about the candidate
    [entity_id] => fakeopenID445
    [CRPcandID] => N00006692
    [URL] => http://boxer.senate.gov
    [lastname] => Boxer
    [firstname] => Barbara
    [state_full_name] => CALIFORNIA
    [state_abbreviation] => CA
    [party] => D
    [title] => Senator
    [member110congress] => yes
    [senator_class] => III
    [webform] => http://boxer.senate.gov/contact
    [phone] => (202) 224-3553
    [FEC_ID] => S2CA00286
    [WashPost_ID] => b000711
    [VoteSmart_ID] => S0105103
    [GovTrack_ID] => 300011
    [district] => S
    [gender] => F
    [congress_office] => 112 HART SENATE OFFICE BUILDING WASHINGTON DC 20510
    [congresspedia] => http://www.sourcewatch.org/index.php?title=Barbara_Boxer
    [photo] => Barbaraboxer.jpg
    [BioGuide_ID] => B000711
    [Eventful_ID] => P0-001-000016035-6
*/
function get_all_data($entity_id){
	#$converted = dbLookupArray("select 0, title, firstname, lastname, party, state_abbreviation, district, congress_office, phone, webform, c.chamber, max(congress_num) as congress_num, voting_score, contrib_total, pac_total, c.fec_id as id, 'Can' as type from congressmembers c left join vote_scores on (fec_id = candidateid) where fec_id = '$entity_id' group by fec_id");
	$converted = dbLookupArray("select 0, title, firstname, lastname, party, state_abbreviation, district, congress_office, phone, webform, c.chamber, max(congress_num) as congress_num, contrib_total, pac_total, c.fec_id as id, 'Can' as type from congressmembers c where fec_id = '$entity_id' group by fec_id");
	if(isset($converted[0])) { 
		$converted = $converted[0];
	}
  	//$query = 'http://api.sunlightlabs.com/people.getPersonInfo.php?id='.$entity_id;
	//$recode = file_get_contents($query);
	//$converted = json_decode($recode,true);
	return($converted);
}

//return fec ids for senetors and reps for a given zip code
//can be more than one district per zip!
function get_fecs_for_zip($zip, $plus4=null){
	global $apikey;
	global $current_congress;
    //this new zip method won't do zip plus 4, and returns 0 for at large districts!
	$disctquery = 'http://services.sunlightlabs.com/api/districts.getDistrictsFromZip.json?apikey='.$apikey.'&zip='.$zip;

	if (isset($plus4)) { $disctquery.="&plus4=$plus4"; }
	$districtinfo = json_decode(file_get_contents($disctquery),true);
	if (! is_array($districtinfo) || ! isset($districtinfo['response']['districts']['0'])) {
		return;
	}

	$cong = fetchCol("select FEC_ID from congressmembers where state_abbreviation = '".$districtinfo['response']['districts']['0']['district']['state']."' and chamber = 'S' and congress_num=$current_congress");
	 if (!is_array($cong)){ $cong = array();} //for example DC has no senators
	//reps are harder, may be multiple districts...
	foreach($districtinfo['response']['districts'] as $district){
		$distresult =fetchCol("select FEC_ID from congressmembers where state_abbreviation = '".$district['district']['state']."' and chamber = 'H' and district='".$district['district']['number']."' and congress_num=$current_congress");
		if (!is_array($distresult)){$distresult = array();}  //incase it don't match in db
		$cong = array_merge($cong, $distresult);
	  //$rep = json_decode(file_get_contents('http://api.sunlightlabs.com/people.reps.getRepFromDistrict.php?state='.$district['state'].'&district='.$district['district']),true);
	  //$cong[] = convert_sunlight_fec($rep['entity_id']);
	}
	
	return $cong;
}

function get_pic_url_for_fec($fecId){
  $alldata = get_all_data(convert_fec_sunlight($fecId));
  //if it exists, return the url
  if (count($alldata) > 1){
 	 $picurl = 'http://sunlightlabs.com/widgets/popuppoliticians/resources/images/'.$alldata['photo'];
 	 return($picurl);
  } else {
     return;
  }
}

function format_html($fecId){
  //$alldata = get_all_data(convert_fec_sunlight($fecId));
	$string= "";
  $alldata = get_all_data($fecId);
	if ($fecId[0] == 'S') { $alldata[district] = ""; }
  $picurl = "data/logos/s$fecId.png";
  $string .= '<img src="'.$picurl.'" style="float: left; margin: 0px 5px 5px 0px;">';
  $string .= $alldata['title']." ".$alldata['firstname']." ".$alldata['lastname'].' ('.$alldata['party'].'-'.$alldata['state_abbreviation'].$alldata[district].')';
  return $string;
}
?>
