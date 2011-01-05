<?php
//function to compute the quartile values for the appropriate race to be used as defaults for ui
//congresnum and racecode
//return 4 values for companies, contribs and, candidates
//DON'T FORGET TO USE DIFFERNT CONGRESSNUM CRITERIA FOR PREZ RACE
include_once('../config.php');
//print_r(getDefaults($argv[1],$argv[2]));

//make a big array to hold all the values and params
$sitecodes = array('carbon','oil','coal');
$races = array('H','S','P');
$congri = array_keys($congresses);
$allvals = array();

foreach($sitecodes as $sitecode){
	foreach($races as $race){
		foreach($congri as $cong){
			if ($race == 'P' and ($cong % 2) != 0) { continue; }
			$allvals[$sitecode][$cong][$race] = getDefaults($sitecode, $cong,$race);	
		}
	}
}
print("advanced_opts = ".json_encode($allvals));


function getDefaults($sitecode, $congNum, $raceCode){
	$sitefilter = "";
	if ($sitecode != 'carbon') { 
		$sitefilter = " and sitecode = '$sitecode' ";
	}
//get ids of candiates with appropriate race code and congress
 $canIdQuery ="select candidateid from contributions where racecode='".$raceCode."' 
 and congress_num = '".$congNum."' $sitefilter group by candidateid;";
 
 //print("\ncanquery:\n\t".$canIdQuery);
 
 //get ids of of companies at the other end of edges for 
 //given candidates
// $compIdQuery ="select CompanyID as id from contributions a join companies b on a.CompanyID = b.id join candidates c on a.CandidateID = c.CandidateID where c.CandidateID in (".$canIdQuery.");";
 
 //run the query
 $canIds = arrayToInString(dbLookupArray($canIdQuery));
 //$compIds = arrayToInString(dbLookupArray(compIdQuery));
 
 //print("\ncan ids\n\t".$canIds);
 
 $contribAmountQuery =" amount from contributions a where racecode='".$raceCode."' and congress_num = '".$congNum."' and amount > 0  $sitefilter group by amount order by amount";

//print("\ncomp amount query:\n\t".$contribAmountQuery);

$canAmountQuery = " amount from (select sum(amount) as amount from contributions a where racecode='".$raceCode."' and congress_num = '".$congNum."' and amount >= 0 $sitefilter group by a.CandidateId) cand group by amount order by amount";

//print("\ncan amount query:\n\t".$canAmountQuery);

$compAmountQuery = " amount from (select sum(amount) as amount from contributions a where racecode='".$raceCode."' and congress_num = '".$congNum."' and amount >= 0 $sitefilter group by a.CompanyId) comps group by amount order by amount ";

//print("\ncomp amount query:\n\t".$compAmountQuery);

$results = array();
//compute quartiles for edge weights
$results['minContribAmount'] = quartiles($contribAmountQuery);
//compute quartiles for companies
$results['minCandidateAmount'] = quartiles($canAmountQuery);
//compute quartiels for candidates
$results['minCompanyAmount'] = quartiles($compAmountQuery);

 return($results);
}

function quartiles($amountQuery){
  dbWrite("set @row = 0;"); //set row counter var
  $query = "select amount from (select @row :=@row+1 as row, amount from (select ".$amountQuery.") a ) counts where row in( ceil(@row*3/4) ,ceil(@row*2/4) ,ceil(@row*1/4)) order by amount desc";
  $results = dbLookupArray($query);
  $values = array();
  foreach (array_keys($results) as $key) {
	  $values[] = "$key";
	}
  return($values);
}


?>

