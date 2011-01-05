<?php

require("../config.php");


$cans = dbLookupArray('select fec_id, govtrack_id, entity_id from congressmembers group by fec_id');

$origdir = '../data/can_originals/';
$outdir = $candidate_images;

print sizeof($cans). " candidates found\n";

foreach ($cans as $can) {
	if ((!file_exists("$origdir/$can[fec_id]-100px.jpg") || !file_exists("$origdir/$can[fec_id]-50px.jpg") || !file_exists("$origdir/$can[fec_id]-200px.jpg")) && ! file_exists("$origdir/$can[fec_id]-sl.jpg")) {
		print "looking on govtrack - ";
		foreach (array('50', '100', '200') as $size) { 
			if (! file_exists("$origdir/$can[fec_id]-$size"."px.jpg")) {
				print "fetching $size for $can[govtrack_id]\n";
				copy("http://www.govtrack.us/data/photos/$can[govtrack_id]-$size"."px.jpeg", "$origdir/$can[fec_id]-$size"."px.jpg");
			}
		}
		print ".";
	}
	if (! file_exists("$outdir/$can[fec_id].jpg")) {
		print "create web image - ";
		if (file_exists("$origdir/$can[fec_id]-200px.jpg")) { 
			exec("convert $origdir/$can[fec_id]-200px.jpg -quality 92 -resize 64x -crop 64x64+0+10 $outdir/$can[fec_id].jpg");
		} else if (file_exists("$origdir/$can[fec_id]-sl.jpg")) {
			exec("convert $origdir/$can[fec_id]-sl.jpg -quality 92 -resize 64x -crop 64x64+0+0! $outdir/$can[fec_id].jpg");
		}
	}
	if (file_exists("$outdir/$can[fec_id].jpg") && ! file_exists("$outdir/s$can[fec_id].jpg")) {
		print "create web thumbnail - ";
		exec("convert $outdir/$can[fec_id].jpg -resize 16x $outdir/s$can[fec_id].jpg");
	}
}



