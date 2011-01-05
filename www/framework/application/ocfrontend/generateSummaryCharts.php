<?php
// Standard inclusions     
include("lib/pChart/pData.class");  
include("lib/pChart/pChart.class");  
require_once("../config.php");

   $canImageTargetDir = "../www/cache/summarychart/candidates/";
   $compImageTargetDir ="../www/cache/summarychart/companies/";
   $congImageTargetDir = "../www/cache/summarychart/congresses/";

///usr/share/fonts/truetype/msttcorefonts/Arial.ttf
  //database
function setupChartDirs() { 
 	global $compImageTargetDir;
	global $canImageTargetDir;
	global $congImageTargetDir;	
  //set up directory structure for cache
  $dirs = array("../www/cache/summarychart/",
   $compImageTargetDir,
   $canImageTargetDir,
   $congImageTargetDir,
   $canImageTargetDir."timeline/",
   $canImageTargetDir."industries/",
   $canImageTargetDir."pacs/",
   $canImageTargetDir."categories/",
   $compImageTargetDir."timeline/",
   $compImageTargetDir."party/",
   $congImageTargetDir."industries/",
   $congImageTargetDir."party/");
   
   foreach($dirs as $dir){
  		if(!is_dir($dir)){
			#print "$dir\n"; ob_flush();
  			mkdir($dir,0777);
			clearstatcache();
 		 }
 	}
}

   if(isset($argv[1])) { 
	   //congPlots($argv[1]);
	}
  
  
   //THIS CODE IS ALL FOR TESTING
  /*
  
  //open db connection
  $db = dbConnect();
  
  $canids = array('H6OH23033','H4TX06117','H4PA13124','H8CA09060','S0CA00199','S2TX00106','S4OK00083','S6UT00063');
  $comids = array('4081','1799','1737','671','5582','460','1666');
  $congs = array('106','107','108','109','110','111');
  
  
  foreach ($canids as $canid){
  	canPlots($canid);
  	htmlTestCan($canid);
  }
  
  foreach($comids as $comid){
  	comPlots($comid);
  	htmlTestCom($comid);
  }
  
  foreach($congs as $cong){
  	congPlots($cong);
  	htmlTestCong($cong);
  }
  */
  
  
  function canPlots($canid){
	  setupChartDirs();
  	chamberComparePlot($canid);
  	categoryComparePlot($canid);
  	industryComparePlots($canid);
  	pacComparePlots($canid);
  }
  
  function comPlots($comid){
	  setupChartDirs();
  	comPartyPlots($comid);
  	comTimelinePlots($comid);
  }
  
  function congPlots($congnum){
	  setupChartDirs();
  	congIndustryPlot($congnum);
  	congPartyPlots($congnum);
  }
  
  function congPartyPlots($congnum){
  	global $congresses;
  	  global $congImageTargetDir;
	  $name = $congresses[$congnum];
  	$amounts = fetchRow("select dsum,cash-(rsum+dsum) other, rsum from (select sum(if(party='D',amount,0)) dsum, sum(if(party='R',amount,0)) rsum, sum(amount) cash from contributions where racecode != 'P' and congress_num= $congnum)  c");
  	$cats = array('Democrat','Other','Republican');
  	//drawing constants
	  $plotWidth = 255;
	  $plotHeight = 40;
	  $lMargin=5;
	  $tMargin=38;
	  // Dataset definition   
	  $DataSet = new pData;
	  $DataSet->AddPoint($amounts,"amounts"); 
	  $DataSet->AddPoint($cats,"categories");  
	  
	  // Initialise the graph  
	  $Test = new pChart(310,90);
	  
	  //color pallette definition
	  $Test->setColorPalette(0,35, 62, 103); //Dem  rgb(35, 62, 103)
	  $Test->setColorPalette(1,150,150,150); //other 
	  $Test->setColorPalette(2,162, 42, 40); //rgb(162, 42, 40)
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);     
	  $Test->setGraphArea($lMargin,$tMargin,$plotWidth,$plotHeight);          
	  $Test = barPlot($Test,$amounts,$cats,$plotWidth-40,$plotHeight,$lMargin+40,$tMargin);
	  if ($amounts[0] > 0){
	  	$Test->drawFromPNG("../www/images/donkey.png",0,$tMargin,100);
	  }
	  if ($amounts[2] > 0){
	 	 $Test->drawFromPNG("../www/images/elephant.png",$plotWidth+10,$tMargin,100); 
	  }
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);
	  //$Test->drawTitle($lMargin+42,88,"Dirty Energy contributions to ".$congnum."th congress by party:",0,0,0);
	  
	  $Test->Render($congImageTargetDir."party/".$congnum.".png");
  }

  function congIndustryPlot($congnum){
  
  	  //run the queries to load data from db
  	  global $congresses;
  	  global $congImageTargetDir;
	  $name = $congresses[$congnum];
  	  $amounts = fetchCol("select sum(amount) cash,sitecode from contributions where congress_num = $congnum and racecode != 'P' group by sitecode");
	  $cats = fetchCol("select sitecode from contributions group by sitecode");

		//drawing constants
	 $plotWidth = 300;
	 $plotHeight = 40;
	 $lMargin=5;
	 $tMargin=38;
	 // Dataset definition   
	  $DataSet = new pData;
	  $DataSet->AddPoint($amounts,"Industry contributions"); 
	  $DataSet->AddPoint($cats,"Industry categories");  
	  
	  // Initialise the graph  
	  $Test = new pChart(310,90);
	  
	  //color pallette definition
	  //$Test->setColorPalette(0,150,150,150); //other
	  $Test->setColorPalette(0,189,181,139); //coal colors
	  $Test->setColorPalette(1,149,183,197); //oil colors
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);     
	  $Test->setGraphArea($lMargin,$tMargin,$plotWidth,$plotHeight);          
	  $Test = barPlot($Test,$amounts,$cats,$plotWidth,$plotHeight,$lMargin,$tMargin);
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8); 
	  //$Test->drawTitle($lMargin+2,88,"Dirty energy contributions to the ".$congnum."th congress by industry:",0,0,0);
	  $Test->Render($congImageTargetDir."industries/".$congnum.".png");
  }

  
  function comTimelinePlots($comid){
  	global $compImageTargetDir;
  	$name = fetchCol("select name from companies where id=$comid");
  	$name=$name[0];
  	$amounts = fetchCol("select sum(ifnull(amount,0)) cash,b.cycle from contributions a right join (select distinct cycle from contributions) b on a.cycle = b.cycle and companyid = $comid group by cycle");
  	$cycle= fetchCol("select b.cycle,sum(ifnull(amount,0)) cash from contributions a right join (select distinct cycle from contributions) b on a.cycle = b.cycle and companyid = $comid group by cycle");
  	 // Dataset definition   
	  $DataSet = new pData;
	  $DataSet->AddPoint($amounts,"$name");  
	  $DataSet->AddPoint($cycle,"Cycle");
	  $DataSet->AddSerie("$name"); 
	  $DataSet->SetAbsciseLabelSerie("Cycle");

	  // Initialise the graph  
	  $Test = new pChart(310,90);
		//color pallette definition
	  $Test->setColorPalette(0,102,153,51);
	  
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);     
	  $Test->setGraphArea(60,20,300,70);          
	  $Test->drawGraphArea(250,250,250,FALSE);  
	  $Test->setCurrency("$");
	  $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,0,TRUE);      
	  //$Test->drawFilledLineGraph($DataSet->GetData(),$DataSet->GetDataDescription(),10,TRUE);
	  //$Test->drawPlotGraph($DataSet->GetData(), $DataSet->GetDataDescription(),3,2,255,255,255);
	  $Test->drawBarGraph($DataSet->GetData(), $DataSet->GetDataDescription(),TRUE);
	  //$Test->drawTitle(0,10,"Contributions from $name by congress term:",0,0,0); 
	  $Test->Render($compImageTargetDir."timeline/".$comid.".png");
  }
  
  function comPartyPlots($comid){
  	global $compImageTargetDir;
  	$name = fetchCol("select name from companies where id=$comid");
  	$name=$name[0];
  	$amounts = fetchRow("select dsum,cash-(rsum+dsum) other, rsum from (select sum(if(party='D',amount,0)) dsum, sum(if(party='R',amount,0)) rsum, sum(amount) cash from contributions where companyid = $comid ) c");
  	$cats = array('Democrat','Other','Republican');
  	//drawing constants
	  $plotWidth = 255;
	  $plotHeight = 40;
	  $lMargin=5;
	  $tMargin=38;
	  // Dataset definition   
	  $DataSet = new pData;
	  $DataSet->AddPoint($amounts,"amounts"); 
	  $DataSet->AddPoint($cats,"categories");  
	  
	  // Initialise the graph  
	  $Test = new pChart(310,90);
	  
	  //color pallette definition
	  $Test->setColorPalette(0,35, 62, 103); //Dem  rgb(35, 62, 103)
	  $Test->setColorPalette(1,150,150,150); //other 
	  $Test->setColorPalette(2,162, 42, 40); //rgb(162, 42, 40)
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);     
	  $Test->setGraphArea($lMargin,$tMargin,$plotWidth,$plotHeight);          
	  $Test = barPlot($Test,$amounts,$cats,$plotWidth-40,$plotHeight,$lMargin+40,$tMargin);
	  if ($amounts[0] > 0){
	  	$Test->drawFromPNG("../www/images/donkey.png",0,$tMargin,100);
	  }
	  if ($amounts[2] > 0){
	 	 $Test->drawFromPNG("../www/images/elephant.png",$plotWidth+10,$tMargin,100); 
	  }
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);
	  //$Test->drawTitle($lMargin+42,88,"$name's contributions by party:",0,0,0);
	  
	  $Test->Render($compImageTargetDir."party/".$comid.".png");
  }
  
  
  function pacComparePlots($canid){
     global $canImageTargetDir;
	//run the queries to load data from db
	  $name = fetchCol("select lastname from congressmembers where FEC_ID = '$canid' order by congress_num desc limit 1");
	  $name = $name[0];
  	  $amounts = fetchRow("select sum(ifnull(contrib_total-pac_total,0)) nonpac, sum(ifnull(pac_total,0)) pac from congressmembers where FEC_ID ='$canid'");

	  $cats = array("dirty energy PACs","employees");
	  //drawing constants
	  $plotWidth = 300;
	  $plotHeight = 40;
	  $lMargin=5;
	  $tMargin=38;
	  // Dataset definition   
	  $DataSet = new pData;
	  $DataSet->AddPoint($amounts,"amounts"); 
	  $DataSet->AddPoint($cats,"categories");  
	  
	  // Initialise the graph  
	  $Test = new pChart(310,90);
	  
	  //color pallette definition
	  $Test->setColorPalette(0,243,123,125); //pac
	  $Test->setColorPalette(1,247,160,139); //employee
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);     
	  $Test->setGraphArea($lMargin,$tMargin,$plotWidth,$plotHeight);          
	  $Test = barPlot($Test,$amounts,$cats,$plotWidth,$plotHeight,$lMargin,$tMargin);
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);
	  //$Test->drawTitle($lMargin+2,88,"$name's dirty energy money by contributor type:",0,0,0);
	  
	  $Test->Render($canImageTargetDir."pacs/".$canid.".png");

  }
  
  function industryComparePlots($canid){
  	  global $canImageTargetDir;
        //run the queries to load data from db
	  $name = fetchCol("select lastname from congressmembers where FEC_ID = '$canid' order by congress_num desc limit 1");
	  $name = $name[0];
  	  $amounts = fetchCol("select sum(ifnull(amount,0)) cash,codes.sitecode from (select distinct sitecode from contributions) codes left join contributions c on c.sitecode = codes.sitecode and candidateid = '$canid' group by sitecode");
	  $cats = fetchCol("select codes.sitecode,sum(ifnull(amount,0)) cash from (select distinct sitecode from contributions) codes left join contributions c on c.sitecode = codes.sitecode and candidateid = '$canid' group by sitecode");

		//drawing constants
	 $plotWidth = 300;
	 $plotHeight = 40;
	 $lMargin=5;
	 $tMargin=38;
	 // Dataset definition   
	  $DataSet = new pData;
	  $DataSet->AddPoint($amounts,"Industry contributions"); 
	  $DataSet->AddPoint($cats,"Industry categories");  
	  
	  // Initialise the graph  
	  $Test = new pChart(310,90);
	  
	  //color pallette definition
	  //$Test->setColorPalette(0,150,150,150); //other
	  $Test->setColorPalette(0,189,181,139); //coal colors
	  $Test->setColorPalette(1,149,183,197); //oil colors
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);     
	  $Test->setGraphArea($lMargin,$tMargin,$plotWidth,$plotHeight);          
	  $Test = barPlot($Test,$amounts,$cats,$plotWidth,$plotHeight,$lMargin,$tMargin);
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8); 
	  //$Test->drawTitle($lMargin+2,88,"$name's dirty energy money by  industry:",0,0,0);
	  $Test->Render($canImageTargetDir."industries/".$canid.".png");
  }
  
  
  function categoryComparePlot($canid){
	  global $canImageTargetDir;
	  //run the queries to load data from db
	  $name = fetchCol("select lastname from congressmembers where FEC_ID = '$canid' order by congress_num desc limit 1");
	  $name = $name[0];
	  $amounts = fetchCol("select sum(amount) cash,catshort from contributions where candidateid ='$canid' group by catshort order by cash desc");
	  $cats = fetchCol("select substr(catshort,1,31), sum(amount) cash from contributions where candidateid ='$canid' group by catshort order by cash desc");

	//drawing constants
	 $plotWidth = 250;
	 $plotHeight = 20;
	 $lMargin=5;
	 $tMargin=58;
	 
	  // Dataset definition   
	  $DataSet = new pData;
	  $DataSet->AddPoint($amounts,"Industry contributions"); 
	  $DataSet->AddPoint($cats,"Industry categories");  
 
	  // Initialise the graph  
	  $Test = new pChart(310,90);
	  
		//color pallette definition
	  $Test->setColorPalette(0,102,153,51); //money colors
	  $Test->setColorPalette(1,102,153,51);
	  $Test->setColorPalette(2,102,153,51);
	  $Test->setColorPalette(3,102,153,51);
	  $Test->setColorPalette(4,102,153,51);
	  $Test->setColorPalette(5,102,153,51);
	  $Test->setColorPalette(6,102,153,51);
	  $Test->setColorPalette(7,102,153,51);
	  $Test->setColorPalette(8,102,153,51);
	  $Test->setColorPalette(9,102,153,51);
	  $Test->setColorPalette(10,102,153,51);
	  $Test->setColorPalette(11,102,153,51);
	  $Test->setColorPalette(12,102,153,51);
	  $Test->setColorPalette(13,102,153,51);
	  $Test->setColorPalette(14,102,153,51);
	  $Test->setColorPalette(15,102,153,51);
	  $Test->setColorPalette(16,102,153,51);
	  $Test->setColorPalette(17,102,153,51);
	  $Test->setColorPalette(18,102,153,51);
	  $Test->setColorPalette(19,102,153,51);
	  $Test->setColorPalette(20,102,153,51);
	  $Test->setColorPalette(21,102,153,51);
	  $Test->setColorPalette(22,102,153,51);
	  $Test->setColorPalette(23,102,153,51);
	  $Test->setColorPalette(24,102,153,51);
	  $Test->setColorPalette(25,102,153,51);
	  
	  
	  
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);     
	  $Test->setGraphArea($lMargin,$tMargin,$plotWidth,$plotHeight);          
	  $Test = barPlot($Test,$amounts,$cats,$plotWidth,$plotHeight,$lMargin,$tMargin);
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8); 
	 // $Test->drawTitle($lMargin+2,88,"$name's dirty energy money by  category:",0,0,0);
	  $Test->Render($canImageTargetDir."categories/".$canid.".png");
}

 function barPlot($Test,$amounts,$cats,$plotWidth,$plotHeight,$lMargin,$tMargin){
 	//convert amounts to percentages and percentages to widths
	  $total = 0;
	  $percentages;
	  $widths = array();
	  foreach($amounts as $amount){
	  	if ($amount > 0){
	  		$total += $amount;
	  	}
	  }
	  if ($total > 0){
		  foreach($amounts as $amount){
			$widths[] = round(($amount / $total) * $plotWidth);
			$percentages[] = round(($amount / $total)*100);
		  }
	  }
	  
	   
	   //draw rects for each industry
	   $x = $lMargin;
	   $tempIndex =0;
	   
	   
	   foreach($widths as $width){
	   	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8); 
	      $Test->drawFilledRectangle($x,$tMargin,$x+$width,$plotHeight+$tMargin,$Test->Palette[$tempIndex]['R'],$Test->Palette[$tempIndex]['G'],$Test->Palette[$tempIndex]['B'],FALSE,100);

$Test->drawRectangle($x,$tMargin,($x+$width),$plotHeight+$tMargin,255,255,255,TRUE,0);

		//only draw label if there is enough room
		if ($width > 20){
			$Test->drawTextBox( round($x+($width/3.0)), $tMargin-15,$x+round($width/2.0)+100,$tMargin,$cats[$tempIndex],20,100,100,100,ALIGN_LEFT,FALSE,30,255,255,0);
		}
		
		//only draw percent if there is enough room
		//only draw label if there is enough room
		if ($width > 20){
			$Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial_Black.ttf",7);  
			$Test->drawTextBox( $x+2, $tMargin,$x+$width,$tMargin+$plotHeight,"$percentages[$tempIndex]%",0,250,250,250,ALIGN_CENTER,FALSE,255,255,255,0);
		}

		$x += $width;
		$tempIndex ++;
	   }
	   
	   return($Test);
 }
  
  
  
  
  function chamberComparePlot($canid){
	  global $canImageTargetDir;
	  //run the queries to load data from db
	  $name = fetchCol("select lastname from congressmembers where FEC_ID = '$canid' limit 1");
	  $name = $name[0];
	  $chamber = fetchCol("select chamber from congressmembers where FEC_ID = '$canid' order by congress_num desc limit 1");
	  $chamber = $chamber[0];
	  $Hamounts_cycle = fetchCol("select avg(cash) cash from (select candidateid,sum(amount) cash,cycle from contributions where racecode = '".$chamber."' group by candidateid,cycle) t group by cycle");	
      $cycle_cycle = fetchCol("select distinct year from congressmembers");
	  $amounts_cycle = fetchCol("select ifnull(contrib_total,0) cash,c.year from congressmembers a right join (select distinct year from congressmembers) c on c.year = a.year and FEC_ID ='$canid' ");
	  $avgTitle = "House avg.";
	  if ($chamber == "S"){
	  	$avgTitle = "Senate avg.";
	  }
	  
	  // Dataset definition   
	  $DataSet = new pData;
	  $DataSet->AddPoint($Hamounts_cycle,"$avgTitle"); 
	  $DataSet->AddPoint($amounts_cycle,"$name");  
	  $DataSet->AddPoint($cycle_cycle,"Cycle");
	  $DataSet->AddSerie("$avgTitle"); 
	  $DataSet->AddSerie("$name"); 
	  $DataSet->SetAbsciseLabelSerie("Cycle");

	  // Initialise the graph  
	  $Test = new pChart(310,90);
		//color pallette definition
	  $Test->setColorPalette(0,100,100,100);
	  $Test->setColorPalette(1,102,153,51);
	  
	  $Test->setFontProperties("/usr/share/fonts/truetype/msttcorefonts/Arial.ttf",8);     
	  $Test->setGraphArea(60,20,200,70);          
	  $Test->drawGraphArea(250,250,250,FALSE);  
	  $Test->setCurrency("$");
	  $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,FALSE);      
	  $Test->drawFilledLineGraph($DataSet->GetData(),$DataSet->GetDataDescription(),10,TRUE);
	  $Test->drawPlotGraph($DataSet->GetData(), $DataSet->GetDataDescription(),3,2,255,255,255);
	  $Test->drawLegend(203,25,$DataSet->GetDataDescription(),255,255,255);
	  //$Test->drawTitle(0,10,"Dirty energy accepted by $name per congress term:",0,0,0); 
	  $Test->Render($canImageTargetDir."timeline/".$canid.".png");
}

function htmlTestCan($canid){

 global $canImageTargetDir;
	print("<h1>$canid</h1>");
	print("<img style='margin:25px;' src='".$canImageTargetDir."timeline/".$canid.".png'>");
	print("<img style='margin:25px;' src='".$canImageTargetDir."industries/".$canid.".png'>");
	print("<img style='margin:25px;' src='".$canImageTargetDir."pacs/".$canid.".png'>");
	print("<img style='margin:25px;' src='".$canImageTargetDir."categories/".$canid.".png'><br><br>");
}

function htmlTestCom($comid){
	global $compImageTargetDir;
	print("<h1>$comid</h1>");
	print("<img style='margin:25px;' src='".$compImageTargetDir."timeline/".$comid.".png'>");
	print("<img style='margin:25px;' src='".$compImageTargetDir."party/".$comid.".png'><br><br>");
}

function htmlTestCong($cong){
	global $congImageTargetDir;
	print("<h1>$cong</h1>");
	print("<img style='margin:25px;' src='".$congImageTargetDir."industries/".$cong.".png'>");
	print("<img style='margin:25px;' src='".$congImageTargetDir."party/".$cong.".png'><br><br>");
}

?>
