<?php
	require_once('../config.php');
	$svgdoctype = setupHeaders();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php echo $svgdoctype; ?> xml:lang="en" lang="en">
<head>
	<title>!Page Title!</title>
	<link rel="stylesheet" href="style.css" type="text/css" />
	<!--[if IE]><link rel="stylesheet" href="style/ie.css" type="text/css" /><![endif]-->

	<script type="text/javascript" src="js/prototype.js" ></script>
	<script type="text/javascript" src="js/prototypeex.js" ></script>
	<script type="text/javascript" src="js/effects.js" ></script>
	<script type="text/javascript" src="js/slider.js" ></script>
	<script type="text/javascript" src="js/controls.js" ></script>
	<script type="text/javascript" src="svgpan.js" ></script>

	<script type="text/javascript" src="js/tablekit/tablekit.js"></script>
	<script type="text/javascript" src="tables.js"></script>
	<!--<script type="text/javascript" src="js/rsh.js"></script>-->
	<script type="text/javascript" src="optionDefaults.js"></script>
	<script type="text/javascript" src="GraphFramework.js" ></script>
	<script type="text/javascript" src="GraphImage.js" ></script>
	<script type="text/javascript" src="GraphList.js" ></script>
	<script type="text/javascript" src="GraphRaster.js" ></script>
	<script type="text/javascript" src="GraphSVG.js" ></script>
	<script type="text/javascript">
	//<![CDATA[
		function initGraph() {
			graphframework = new GraphFramework('graphs', 'lists');
		}	
	//]]>	
	</script>

	
</head>
<body onload='initGraph();'>
	<form id='graphoptions'>
		<input name='setupfile' type='radio' value='FECCanComGraph' checked='1' /> FECCanComGraph
		<input name='setupfile' type='radio' value='ContributionGraph' /> ContributionGraph
	</form>	
		
	<div id='graphs'>
	</div>
	<div id='lists'>
	</div>
</body>
</html>
