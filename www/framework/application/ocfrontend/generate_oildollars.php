<?php
require_once('../config.php');
$canpath = "../data/can_originals/";  //where to find candidate images
$total_font = "FreeMonospacedB";   //font name to use for money
$name_font = "URWChanceryMediumI";   //font name for candidate name
$target_dir = "../www/dollars/";   //where the images should end up
$racecodes = array("S","H");
global $current_congress;
$includeprez = 0;
//query for congress 

//get the db connection
print("linking to db\n");

$db = dbconnect();
//for each racecode category
//get the list of candidate ids to run
//now includes pres
print("getting ids\n");
$can_query = "select FEC_ID from congressmembers where congress_num = $current_congress union select cong_candidateid from presidential_candidates where cong_candidateid is not null union select candidateid from presidential_candidates";
$candidates = fetchCol($can_query);
$scratch_dir = "../data/oildollars/";
if (! file_exists($scratch_dir)) {
	mkdir($scratch_dir);
}
//for each candidate
foreach ($candidates as $can_id){
	//all this bidness to make sure we do it the same way as in the graph 
	$props_query = "select a.CandidateID, CandidateName, sum(Amount) as cash from candidates a join contributions c on a.CandidateID = c.CandidateID where a.CandidateID = '$can_id' and a.congress_num = c.congress_num and c.congress_num is not null group by a.CandidateID;"; 
    //some candidates have no contribs, so do name seperately
    $name_query = "select CandidateId,CandidateName from candidates where candidateId = '$can_id'";
//prez query: I think this is really stupid and a bad idea, but it is what they want
	$prez_total = fetchRow("select format(sum(amount),0),sum(amount), p.candidateid as prezid from presidential_candidates p join contributions c on p.candidateid = c.candidateid where cong_candidateid = '$can_id' and congress_num = 110 group by null");

    $prez_total[0] = $prez_total[0] ? $prez_total[0] : 0;
	//get the properties for the candidate
	$can_props = dbLookupArray($props_query);
	$name_result = dbLookupArray($name_query);
	$can_total = $can_props[$can_id]['cash'];
	if($includeprez & $prez_total[0] > 0){ 
		$can_total = $can_total + $prez_total[1];
	}
	$can_name = niceName($name_result[$can_id]['CandidateName']);
 	print($can_name);

	//check if we have a source image
	if(file_exists($canpath.$can_id.'-200px.jpg'))
	{
		//scale the candidate image to the right size
		$run = "convert ".$canpath.$can_id."-200px.jpg -colorspace gray  -resize 382x472  $scratch_dir/".$can_id.".jpg ";  //-sketch 0x10+45
		print (exec($run));
	//try a lower resolution version
	} elseif(file_exists($canpath.$can_id.'-100px.jpg'))
	{
		//scale the candidate image to the right size
		$run = "convert ".$canpath.$can_id."-100px.jpg -colorspace gray  -resize 382x472  $scratch_dir/".$can_id.".jpg ";  //-sketch 0x10+45
		print (exec($run));
	//try a lower resolution version
	} elseif(file_exists($canpath.$can_id.'-50px.jpg'))
	{
		//scale the candidate image to the right size
		$run = "convert ".$canpath.$can_id."-50px.jpg -colorspace gray  -resize 382x472  $scratch_dir/".$can_id.".jpg ";  //-sketch 0x10+45
		print (exec($run));	
		
	} elseif(file_exists($canpath.$can_id.'-sl.jpg'))
	{
		//scale the candidate image to the right size
		$run = "convert ".$canpath.$can_id."-sl.jpg -colorspace gray  -resize 382x472  $scratch_dir/".$can_id.".jpg ";  //-sketch 0x10+45
		print (exec($run));	
		
	} elseif(file_exists($canpath.$can_id.'.jpg'))
	{
		//scale the candidate image to the right size
		$run = "convert ".$canpath.$can_id.".jpg -colorspace gray  -resize 382x472  $scratch_dir/".$can_id.".jpg ";  //-sketch 0x10+45
		print (exec($run));		
		
	} else { //no image file
	    print("missing candidate image file ".$canpath.$can_id.".jpg\n");
	    //make a no image availible image
		$run = "convert -background white -size 210x227 -fill darkgray -pointsize 8 -gravity center label:'No photo\n available' $scratch_dir/".$can_id.".jpg ";  //-sketch 0x10+45
		print (exec($run));
	}
	//composite the white dollar sized background with the candidate image
	$run = "composite -gravity center -geometry +0+50  $scratch_dir/".$can_id.".jpg ../data/oildollarbg.gif  $scratch_dir/".$can_id."_dollar.jpg";
	//print $run."\n";
	print (exec($run));
	$run = "composite ../data/DirtyEnergyMoney_front.png $scratch_dir/".$can_id."_dollar.jpg $scratch_dir/".$can_id."_dollar.jpg";
	//print $run."\n";
	print (exec($run));
	//write the dollar ammount on left side
	$run = "convert $scratch_dir/".$can_id."_dollar.jpg -font ".$total_font." -pointsize 55 -fill green -draw \"text 300,540 '\\$".number_format($can_total)."'\" $scratch_dir/".$can_id."_dollar.jpg";
	print (exec($run));
	//and again on the right side
	$run = "convert $scratch_dir/".$can_id."_dollar.jpg -font ".$total_font." -pointsize 55 -fill green -draw \"text 1230,305 '\\$".number_format($can_total)."'\" $scratch_dir/".$can_id."_dollar.jpg";
	print (exec($run));
	//if it includes presidental $s, say so
	if ($includeprez & $prez_total[0]){
	 echo "  include prez $";
		$run = "convert $scratch_dir/".$can_id."_dollar.jpg -font ".$total_font." -pointsize 25 -fill gray -draw \"text 50,764 'total includes \\$".$prez_total[0]." from 2008 presidential campaign.'\" $scratch_dir/".$can_id."_dollar.jpg";
		print (exec($run));
	} 
	
	//make 400px image with candidate name centered
	$run = "convert -background white -size 420x75 -font ".$name_font."  -gravity center label:'".$can_name."' $scratch_dir/nameImage.jpg";
	print (exec($run));
	
	//add on the candidate name image on lower left and right
	$run = "composite -geometry +225+560 $scratch_dir/nameImage.jpg $scratch_dir/".$can_id."_dollar.jpg  $scratch_dir/".$can_id."_dollar.jpg";
	print (exec($run));
	$run = "composite -geometry +1190+560 $scratch_dir/nameImage.jpg $scratch_dir/".$can_id."_dollar.jpg -density 200x200 $scratch_dir/".$can_id."_dollar.jpg";
	print (exec($run));

   
	 //make pdf   and also add in the back of the dollar
	$run = "convert $scratch_dir/".$can_id."_dollar.jpg  ../data/DirtyEnergyMoney_back.png -append -density 200x200 $scratch_dir/".$can_id."_dollar.pdf";
	print (exec($run));
	//make scaled down version
   	$run = "convert -resize 600x300 $scratch_dir/".$can_id."_dollar.jpg  $scratch_dir/".$can_id."_dollar.jpg";
	print (exec($run));
	
	//optmize pdf filesize
	$run = "gs -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dPDFSETTINGS=/printer -sOutputFile=/tmp/pdfshrink.pdf $scratch_dir/$can_id"."_dollar.pdf && mv /tmp/pdfshrink.pdf $scratch_dir/$can_id"."_dollar.pdf";
	print (exec($run));
	
	//move finished image to the target directory
	$run = "mv $scratch_dir/".$can_id."_dollar.pdf ".$target_dir;
	print (exec($run));
	$run = "mv $scratch_dir/".$can_id."_dollar.jpg ".$target_dir;
	print (exec($run));
	//clean iup
	$run = "rm $scratch_dir/".$can_id.".jpg"; 
	print("\nfinished $can_id \n");
}

//copy over those congress folks that also have prez races to show their prez dollars. 

print("\ndone.\n");
?>
