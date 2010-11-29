<?php 
require('../config.php');

$session = "2009";
$data_download_dir = "../data/";


//download and import campaign contribution data from the California secretary of state site

//get the list of CA ids we will import
$ca_ids = dbLookupArray("select ca_committee_id,entityid,orig_name from entities where ca_committee_id is not null group by ca_committee_id");
//TODO make this recursive?


//print_r($ca_ids);

//delete the old records from the contribs table
print("Deleted all contributions from ca_contribs table.");
dbWrite("delete from ca_contribs;"); 

print("Fetching CA contribution records..\n");
//for each id
foreach(array_keys($ca_ids) as $ca_id ){
	print ("Starting ".$ca_ids[$ca_id]['orig_name']."...");
	//download tsv files into data dir
	//download regular contribution file
	$regular = $data_download_dir.$ca_id.".csv";
	$web = "http://cal-access.ss.ca.gov/Campaign/Committees/DetailContributionsReceivedExcel.aspx?id=".$ca_id."&session=".$session;
	copy($web,$regular);
	print ("\tDownloaded regular file into $data_download_dir \n");
	
	//download late and greater than 5k file
	$late5k = $data_download_dir.$ca_id."_late5k.csv";
	$web = "http://cal-access.ss.ca.gov/Campaign/Committees/DetailLateExcel.aspx?id=".$ca_id."&session=".$session."&view=LATE1";
	copy($web,$late5k);
	print ("\tDownloaded late and greater than 5k file into $data_download_dir \n");
	
	//IF DOING EXPESNES ADD HERE
	
	//read in each line of regular contribution file
	/*
	    [0] => NAME OF CONTRIBUTOR
    [1] => PAYMENT TYPE
    [2] => CITY
    [3] => STATE
    [4] => ZIP
    [5] => ID NUMBER
    [6] => EMPLOYER
    [7] => OCCUPATION
    [8] => AMOUNT
    [9] => TRANSACTION DATE
    [10] => FILED DATE
    [11] => TRANSACTION NUMBER
  */

		if (($f = fopen($regular,"r")) !== FALSE){
			
			while (($row = fgetcsv($f,0,"\t",'"')) !== FALSE){
				//skip blank rows and header
				if ((count($row)==12) & ($row[0] != "NAME OF CONTRIBUTOR")){
					// strip dollar formating from amounts
					$row[8] = str_replace("$","",$row[8]);
					$row[8] = str_replace(",","",$row[8]);
					//round contribution amount
					$row[8] = round($row[8]);
					//get rid of spaces in transaction number
					$row[11] = str_replace(" ","",$row[11]);
					
					$insertquery = "insert into ca_contribs(name_of_contributor,payment_type,city,state,zip,id_number,employer,occupation,amount,transaction_date,filed_date,transaction_number,filer_id,filer_entityid) VALUES ('".dbEscape($row[0])."','$row[1]','$row[2]','$row[3]','$row[4]','$row[5]','".dbEscape($row[6])."','".dbEscape($row[7])."','$row[8]',str_to_date('$row[9]', '%m/%d/%Y'),str_to_date('$row[10]', '%m/%d/%Y'), '$row[11]',$ca_id,".$ca_ids[$ca_id]['entityid'].")";
					dbwrite($insertquery);
				}
			}
		} else {
			print("\tunable to open file $regular \n");
			exit;
		}
		
		
	//load in each line of 5k file
	/*
	[0] => NAME OF CONTRIBUTOR
    [1] => CITY
    [2] => STATE/ZIP
    [3] => ID NUMBER
    [4] => EMPLOYER
    [5] => OCCUPATION
    [6] => AMOUNT
    [7] => TRANSACTION TYPE
    [8] => TYPE
    [9] => TRANS. DATE
    [10] => FILED DATE
    [11] => TRANS #
    */
		if (($f = fopen($late5k,"r")) !== FALSE){
			
			while (($row = fgetcsv($f,0,"\t",'"')) !== FALSE){
				//skip blank rows and header
				if ((count($row)==12) & ($row[0] != "NAME OF CONTRIBUTOR")){
					//split state/zip
					$statezip = explode("/",$row[2]);
					$row[2] = $statezip[0];
					$zip = $statezip[1];
					// strip dollar formating from amounts
					$row[6] = str_replace("$","",$row[6]);
					$row[6] = str_replace(",","",$row[6]);
					//round contribution amount
					$row[8] = round($row[8]);
					//get rid of spaces in transaction number
					$row[11] = str_replace(" ","",$row[11]);
					
					$insertquery = "insert into ca_contribs(name_of_contributor,city,state,zip,id_number,employer,occupation,amount,transaction_type,type,transaction_date,filed_date,transaction_number,filer_id,filer_entityid) VALUES ('".dbEscape($row[0])."','$row[1]','$row[2]',$zip,'$row[3]','".dbEscape($row[4])."','".dbEscape($row[5])."','$row[6]','$row[7]','$row[8]',str_to_date('$row[9]', '%m/%d/%Y'),str_to_date('$row[10]', '%m/%d/%Y'),'$row[11]',$ca_id,".$ca_ids[$ca_id]['entityid'].")";
					dbwrite($insertquery);
				}
			}
		} else {
			print("\tunable to open file $late5k \n");
			exit;
		}
	print ("\tInserted records into database \n\n");
} //end id loop	


//flag duplicate records using the the last part (after the "-") of the transaction record for match
$dupequery="update ca_contribs a join (select name_of_contributor,transaction_date,trim(substring(transaction_number,locate('-',transaction_number)+1)) short_id from ca_contribs group by trim(substring(transaction_number,locate('-',transaction_number)+1)), name_of_contributor having count(*) > 1) dupes on trim(substring(a.transaction_number,locate('-',a.transaction_number)+1)) = short_id and a.name_of_contributor = dupes.name_of_contributor set duplicate = 1 where a.transaction_type is not null";
dbWrite($dupequery);
print("Flagged duplicate contribution records where late and regular filings overlapp\n");


//list new names that need to be added to entities into entities table
$newentquery = "select row_id,name_of_contributor,concat('http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=',filer_id) source,state,zip from ca_contribs where row_id in (select row_id from ca_contribs left join entities on name_of_contributor = orig_name where orig_name is null and duplicate = 0 group by name_of_contributor);";

$newents = dbLookupArray($newentquery);

print("Adding new entities for new names that have showed up in contributions. These need to their names checked manualy, industry filled in, and possibly name matching:\n");

//loop over each one, print out and insert in db
foreach (array_keys($newents) as $rowid){

	//TODO: propose possible matches
	//make label sentance case version of name
	$label = ucwords(strtolower($newents[$rowid]['name_of_contributor']));
 	$insertquery = "insert into entities set orig_name = '".dbEscape($newents[$rowid]['name_of_contributor'])."',label = '".dbEscape($label)."',source='".$newents[$rowid]['source']."',state='".$newents[$rowid]['state']."',zip='".$newents[$rowid]['zip']."'";
 	dbWrite($insertquery);
 	print("\t$label \n");
}

//copy over ids to match ids
print("\nCopying new ids to matchids. If matchids are changed, rerun to merge records.\n");
dbWrite("update entities set match_id = entityid where match_id is null;");

//match entities back onto contributions
dbWrite("update ca_contribs b join entities a on orig_name = name_of_contributor set b.entityid = a.match_id;");
print("Matched entities back onto contributions and filled in entity id\n");
print("done.\n\n");
?>
