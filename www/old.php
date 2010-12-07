<?php

	require_once('../config.php');
	require_once('lib/graphtypes.php');

	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; } else { $type = 'congress'; }
	$graphinfo = $graphtypes[$type];
	if(!$graphinfo) { echo "Invalid type  $type"; exit;}
	$setupfile = $graphinfo['setupfile'];

	$known = array('msie', 'firefox', 'safari', 'webkit', 'opera', 'netscape', 'konqueror', 'gecko');
	preg_match_all( '#(?<browser>' . join('|', $known) .  ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#', strtolower( $_SERVER[ 'HTTP_USER_AGENT' ]), $browser );
	$svgdoctype = "";
	if (isset($browser['browser']) && isset($browser['browser'][0]) && $browser['browser'][0] == 'msie') { 
		header("content-type:text/html");
	} else {
		header("content-type:application/xhtml+xml");
		$svgdoctype = 'xmlns:svg="http://www.w3.org/2000/svg"';
	}
	$baseurl = "http://".preg_replace("/\/[^\/]*$/", "/", $_SERVER['HTTP_HOST']. $_SERVER['PHP_SELF']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php echo $svgdoctype; ?> xml:lang="en" lang="en">
<head>
	<title>!Page Title!</title>
	<link rel="stylesheet" href="style.css" type="text/css" />
	<!--[if IE]><link rel="stylesheet" href="style/ie.css" type="text/css" /><![endif]-->
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;" />

	<script type="text/javascript" src="js/prototype.js" ></script>
	<script type="text/javascript" src="js/prototypeex.js" ></script>
	<script type="text/javascript" src="js/effects.js" ></script>
	<script type="text/javascript" src="js/slider.js" ></script>
	<script type="text/javascript" src="js/controls.js" ></script>
	<script type="text/javascript" src="svgpan.js" ></script>
	<script type="text/javascript" src="svg.js" ></script>

	<script type="text/javascript" src="js/tablekit/tablekit.js"></script>
	<script type="text/javascript" src="tables.js"></script>
	<!--<script type="text/javascript" src="js/rsh.js"></script>-->
	<script type="text/javascript" src="optionDefaults.js"></script>
	<script type="text/javascript" src="framework.js" ></script>
	<script type="text/javascript">
	//<![CDATA[
	<?php echo "var setupfile='$setupfile';"; ?>
	//]]>	
	</script>

	
</head>

<body onload="<?php echo $graphinfo['onload']; ?>">
	<div id="error"></div>
	<div id="nosvgbrowser">
		The web browser you are using does not support some of the technologies used by this site, and therefore some interactivity has been disabled.<br/> For the best experience, please consider using a modern, standards-compliant browser, such as <a href='http://www.mozilla.com/firefox/'>Firefox</a>, <a href='http://www.google.com/chrome'>Chrome</a>, or <a href='http://www.apple.com/safari/'>Safari</a>.
	</div>

	<h1 id="graph-title"><?php echo $graphinfo['title']; ?></h1>
	<h2 id="graph-subtitle" class="note"></h2>
	
	<div id="optionbar" style="display:none;">
		<?php echo $graphinfo['options']; ?>
	</div>

	<div id='lightbox' class="modal" style='display:none;'>
		<img src='images/close.png' style='position: absolute; top: 10px; right: 10px; cursor: pointer;' alt='close' onclick="hideLightbox(); return false;" />
		<div id='lightboxcontents'></div>
	</div>

<div id='tooltip'></div>

<div id='screen' onclick='hideAllModals();' style='display:none;'></div>
<div id="graphcontent">

	<div id="graphs">
		<div id="zoomcontrols">
			<span class="zoomin" onclick="zoom('in');" alt='Zoom In' title='Zoom In'>[+]</span>
			<div id="zoomSlider" class="slider"><div id="zoomHandle" class="handle"></div></div>
			<span class="zoomout" onclick="zoom('out');" alt='Zoom Out' title='Zoom Out'>[-]</span>
			<span class="zoomreset" onclick="zoom('reset');" alt='Reset Zoom' title='Reset Zoom'>[0]</span>
		</div>
		<div id='images'></div>
		<div id='imagescreen' style='display:none;'></div>
		<div id='info' style='position: absolute; top: 400px; left: 10px; width: 350px; height: 145px; display:none; z-index: 100;'>
			<div id='infocontent' style='background: white; position: absolute; top: 0px; left: 0px; width: 348px; height: 145px; border: 1px solid #666666;'>
				<img src='images/close.png' alt='Close Window' id='close' style='position: absolute; top: 2px; right: 2px; z-index: 100; width: 10px; height: 10px; border: none;' onmouseover="$('close').style.cursor='pointer';" onmouseout="$('close').style.pointer='default';" onclick="hideInfo();" />
				<div id='infocontenttext' style='width: 338px; padding: 0px 5px; height: 140px; background: white;position: absolute; top: 5px; left: 0px;  overflow: auto;' ></div>
			</div>
		</div>
		<div id='highlight' style='position: absolute; z-index: 5; visibility: hidden; filter:alpha(opacity=50); opacity: .50; cursor: pointer; top: 0px; left: 0px;'><img id='highlightimg' alt='' style='visibility: hidden; width: 100%; height: 100%;' src='images/highlight.gif' /></div>
	</div><!-- #graphs -->

	<div id="lists">

		<div id='node_search_container'>
			<label for="node_search">Search</label>
			<input id="node_search" autocomplete="off" size="20" type="text" value="" />

			<div class="autocomplete" id="node_list" style="display:none"></div>
		</div>

		<div id="tables" style='display: none;'>
			<div id="candidateList"></div>
			<div id="candidateInfo"></div>
		</div>

		<div id="cotables">
			<div id="companySort">
			Company &amp; Contribution Details:
			</div>
			<div id="companyList"></div>
			<div id="companyInfo"></div>
		</div>
		
	</div><!-- #lists -->
	
	</div><!-- #content -->
	
</body>
</html>
