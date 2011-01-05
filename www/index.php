<?php
	require_once('framework/FrameworkUtils.php');
	$svgdoctype = setupHeaders();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php echo $svgdoctype; ?> xml:lang="en" lang="en">
<head>
	<title>!Page Title!</title>
	<link rel="stylesheet" type="text/css" href="framework/style.css"/>
	<!--[if IE]><link rel="stylesheet" href="style/ie.css" type="text/css" /><![endif]-->

	<script type="text/javascript" src="framework/js/prototype.js" ></script>
	<script type="text/javascript" src="framework/js/prototypeex.js" ></script>
	<script type="text/javascript" src="framework/js/effects.js" ></script>
	<script type="text/javascript" src="framework/js/slider.js" ></script>
	<script type="text/javascript" src="framework/js/controls.js" ></script>
	<script type="text/javascript" src="framework/js/tablekit/tablekit.js"></script>

	<script type="text/javascript" src="framework/GraphFramework.js" ></script>
	<script type="text/javascript" src="framework/GraphImage.js" ></script>
	<script type="text/javascript" src="framework/GraphList.js" ></script>
	<script type="text/javascript" src="framework/GraphRaster.js" ></script>
	<script type="text/javascript" src="framework/GraphSVG.js" ></script>
	<script type="text/javascript" src="framework/GraphSVGZoom.js" ></script>
	<script type="text/javascript">
	//<![CDATA[
		function initGraph() {
			gf = new GraphFramework({graphdiv: 'graphs', listdiv:'lists'});
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
