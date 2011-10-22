<?php

//require('libgv-php5/gv.php');

/**
Interpreter file to take a Graph data structure and convert into a ".dot" formatted file than can be passed to GraphViz.  Manages launching and running GraphVis program to do the graph layout. Also includes functions to clean up SVG and imagemap files.
*/
class GraphVizExporter {
	
	/**Global vars that allow tweaking settings of sections in dot file and setting how
neato will run when it reads the file.  See http://www.graphviz.org/doc/info/attrs.html
for list of params and dfns. Used as default values but can be overridden in Graph setup files. 
*/
 	static $GV_PARAMS = array(
		'graph' => array(
			'outputorder' => 'edgesfirst',  /*! draw edges before drawing nodes !*/
			'truecolor' => 'true',
			//'maxiter' => '10000', //turning this off speeds things up, but does it mean that some might not converge?
			'size' => '9,9!',
			'dpi' => '96',
			//'sep=' => '0.2',
			'bgcolor' => 'transparent',
			'splines' => '1',
			'epsilon'=>'0.0',
			'layoutEngine'=>'neato',
			#'ratio'=>'fill'
		),
		'node' => array(
			'style' => 'setlinewidth(16), filled',
			'fontsize' => '10',
			'fixedsize' => 'true', 
			'label'=>' ',
			'imagescale'=>'true'
		),
		'edge' => array(
			'len' => '8',
			'style'=>'setlinewidth(2)',
			'labelfloat' => 'true'
		)
	);


	/**  Loops through the passed graph structure and writes it to a text string in the .dot file format. All graph data will be written, even if it is not a valid GraphViz paramters.  When some important GraphViz params are not included, it uses the default values in $GV_PARAMS.
		@param $graph  the graph to be converted
		@returns $dot a string in .dot format ready to be written out
	
	*/
	static function createDot($graph){ //add more arguments to control dot file creation
		$GV_PARAMS = GraphVizExporter::$GV_PARAMS;

		//Set the size if it was passed in
		if (isset($graph->width)) { 
			$size = ($graph->width/96).','.($graph->height/96)."!";
			$graph->data['graphvizProperties']['graph']['size'] = $size;
		}

		//Merge any properties set in graphSetup into GV_PARAMS
		if (isset($graph->data['graphvizProperties'])) {
			$GV_PARAMS = array_merge_recursive_unique($GV_PARAMS, $graph->data['graphvizProperties']);
		} 

		$dot = "digraph G {\ngraph ["; 
		//get the graph-level params from the params object
		foreach (array_keys($GV_PARAMS['graph']) as $key) {
			if ($GV_PARAMS['graph'][$key] != null){
				$dot .= $key.'="'.$GV_PARAMS['graph'][$key].'" ';
			}
		}
		$dot .="];\n";

		

		//default formatting for nodes
		$dot .= "node [";
		//get the node-level params from the params object
		foreach (array_keys($GV_PARAMS['node']) as $key) {
			if ($GV_PARAMS['node'][$key] != null){
				$dot .= $key.'="'.$GV_PARAMS['node'][$key].'" ';
			}
		}
		$dot .= "];\n";

		//for each node
		foreach ($graph->data['nodes'] as $node){
			//We should probably log a warning if it has no size
			//if(! isset($node['size'])) { print_r($node); }
			//format the the string.  
			$dot .= "\"".$node['id'].'" ['; //id of node
			//write out properties
			if (isset($node['size'])) { 
				$dot .= 'width="'.$node['size'].'" ';
			}
			$dot .= 'href="a" ';
			if (! isset($node['target'])) { $node['target'] = $node['id']; }
			if (isset($node['label'])) { $dot .= 'label="'.$node['label'].'" '; }
			//Write out all other node properties (color, shape, onClick)
			foreach(array_keys($node) as $key) { 
				if (! in_array($key, array('size', 'label'))) { //skip keys that we have to convert
					$dot .= "$key=\"".$node[$key].'" ';
				}
			}	
			$dot .= "];\n"; 
		//FIXME MOVE LOGO CODE TO GRAPHBUILDER

		}

		//default properties for edges
		$dot .= "edge [";
		//get the edge-level params from the params object
		foreach (array_keys($GV_PARAMS['edge']) as $key) {
			if ($GV_PARAMS['edge'][$key] != null){
				$dot .= $key.'="'.$GV_PARAMS['edge'][$key].'" ';
			}
		}
		$dot .="];\n";

		//for each edge
		foreach($graph->data['edges'] as $edge ){
			//format the string
			$dot .= '"'.$edge['fromId'].'" -> "'.$edge['toId']."\" [".
			'href="a" ';
			if (isset($edge['weight'])) {
				$dot .= 'weight="'.GraphVizExporter::getWeightGV($edge['weight']).'" ';
			}
			if(isset($edge['size'])) {
				$dot .= 'style="setlinewidth('.$edge['size'].')" ';
				if (isset($edge['arrowhead']) && $edge['arrowhead'] != 'none' && $GV_PARAMS['edge']['arrowhead'] != 'none') { 
					$dot .= 'arrowsize="'. ($edge['size']*5).'" ';
				}
			}
			if (! isset($edge['target'])) { $edge['target'] = $edge['id']; }
			//Write out all other node properties (color, shape, onClick)
			foreach(array_keys($edge) as $key) { 
				if (! in_array($key, array('href', 'weight', 'size'))) { //skip keys that we have to convert
					$dot .= "$key=\"".$edge[$key].'" ';
				}
			}
			$dot .= "];\n";
	
			//also add properties
		}
		
		//hack test subgraph function
		foreach (array_keys($graph->data['subgraphs']) as $sg_name){
			$dot .= "subgraph $sg_name {\n";
			$subgraph = $graph->data['subgraphs'][$sg_name];
			//add any properties
			if (isset($subgraph['properties'])){
				foreach (array_keys($subgraph['properties']) as $prop){
					$dot .= $prop."=".$subgraph['properties'][$prop].";\n";
				}
			}
			if (isset($subgraph['nodes'])){
				foreach ($subgraph['nodes'] as $node){
					$dot .= "$node;\n";
				}
			}
			$dot .= "}\n";
		}
		
		//terminate dot file
		$dot .= "}\n";

		//remove all newlines from dot, so GV doesn't choke
		$dot = str_replace("\n", " ", $dot);
		return $dot;

	}

	//if no value is set, returns 1
	//have to use funny names 'cause we can't declare as private
	static function getWeightGV($value){
	  if ($value==null){
		 return 1;
	  }
	  return $value;
	}

	/**
	Manage the export of the Graph object, piping the .dot file to GraphViz to compute layouts, and cacheing images,etc. First creates graph file using createDot().  Uses neato network layout by default, other graphviz layouts can be specified iwht the layoutEngine parameter. Uses the Graph's graphname() function for the base of the filename. Normally writes out a .dot, .svg .png and .imap versions of the network.  If debugMode is set, it will also write out a human-readable dot file with a suffix .nicegraph.  Post-processes the .imap, .svg and ? file using the fuctions processImap(), processSVG() and processGraphData().
	@param $graph the Graph object to be exported
	@param $datapath the data working directory that the files should be witten to
	@param $format  string giving suffix for other image format to save graph images as. (i.e. ".jpg")
	@returns an array with $imap and $svg elements
	*/
	public static function generateGraphFiles($graph, $datapath, $format) {
		global $nodeViz_config;

		$graphname = $graph->graphname();
		$dotString = GraphVizExporter::createDot($graph);

		$GV_PARAMS = GraphVizExporter::$GV_PARAMS;
		if (isset($graph->data['graphvizProperties'])) {
			$GV_PARAMS = array_merge_recursive_unique($GV_PARAMS, $graph->data['graphvizProperties']);
		}

		$layoutEngine = $GV_PARAMS['graph']['layoutEngine'];

		$imageFile = "$datapath/$graphname.png";
		$dotFile = "$datapath/$graphname.dot";
		$svgFile = "$datapath/$graphname.svg.raw";
		$imapFile = "$datapath/$graphname.imap";
		if ($nodeViz_config['debug']) { 
			$nicegraphfile = fopen("$datapath/$graphname.nicegraph", "w");
			fwrite($nicegraphfile, print_r($graph, 1));
			fclose($nicegraphfile);
			$origdot = fopen("$datapath/$graphname"."_orig.dot", "w");
			fwrite($origdot, $dotString);
			fclose($origdot);
		}
	
		//use gv.php to process dot string, apply layout, and generate outputs
		chdir($nodeViz_config['web_path']);
		ob_start();
		$gv = gv::readstring($dotString);
		gv::layout($gv, $layoutEngine);		
		gv::render($gv, 'svg', $svgFile);
		//FIXME - we should be able to use 'renderresult' to write to string, but it breaks - why?
		gv::render($gv, 'cmapx', $imapFile);
		if($nodeViz_config['debug']) {
			gv::render($gv, 'dot', $dotFile);
		}
		gv:rm($gv);
		if(ob_get_contents()) {
			ob_end_clean();
			trigger_error("GraphViz interpreter failed", E_USER_ERROR);
		}
		ob_end_clean();
		chdir($nodeViz_config['nodeViz_path']);
		
		$imap = GraphVizExporter::processImap($imapFile, $datapath, $graphname);
		
		$svg = GraphVizExporter::processSVG($svgFile, $datapath, $graph);

		$graphData = GraphVizExporter::processGraphData($graph->data, $datapath, $graphname);
		
		if ($format != 'png') { 
			#system("convert -quality 92 $datapath$graphname.png $datapath$graphname.$format");
			chdir($nodeViz_config['web_path']);
			system("grep -v levelfour $datapath$graphname.svg | convert -quality 92 svg:- $datapath$graphname.$format");
			chdir($nodeViz_config['nodeViz_path']);
		}
		
		#chmod all our files
		foreach (array('.svg', '.svg.raw', '.dot', '.graph', '.nicegraph', '_orig.dot', '.imap', ".$format") as $ext) {
			if (is_file("$datapath$graphname$ext")) {
				$perms = fileperms("$datapath$graphname$ext");
				if (decoct(fileperms("$datapath$graphname$ext")) != '100666') { 
					chmod("$datapath$graphname$ext", 0666) || print "can't chmod file: $datapath/$graphname$ext : ".decoct(fileperms("$datapath$graphname$ext"));
				}
			}
		}	   
		return array($imap, $svg);
	}

	/**
	Determines if the network image files need to be generated or loaded from cache, and loads information into arrays to be returned to client. Contents of image and overly change depending if the browser making the request set useSVG request parameter.
	@param $graph  the Graph object, to be returned as JSON. 
	@param $datapath string giving the path to the cache directory
	@param $format  string indicating if it will be returning svg or png image for network?
	@param $returnsvg NOT USED?  seems to read request param instead.
	@returns an array with elements for the 'image', 'graph', 'overlay', and 'dot' data.
	*/
	public static function generateGraphvizOutput($graph, $datapath, $format, $returnsvg = 0) {
		global $nodeViz_config;
		$graphname = $graph->graphname();
		$imageFile = "$datapath$graphname.$format";
		$dotFile = "$datapath$graphname.dot";
		$svgFile = "$datapath$graphname.svg";
 
		$cache = $nodeViz_config['cache'];
		
		//check if cache directory is writable
		if (! is_dir($nodeViz_config['cache_path']) || ! is_readable($nodeViz_config['cache_path'])) {
			trigger_error("Unable to read cache to cache directory '".$nodeViz_config['cache_path']."'", E_USER_ERROR);
		}
		$output = "";
		
		//either write files to the cache, or load in the cached files
		if ($cache != 1) {
			require('libgv-php5/gv.php'); //load the graphviz php bindings
			if (! is_writable($nodeViz_config['cache_path'])) {
				trigger_error("Unable to write cache to cache directory '".$nodeViz_config['cache_path']."'", E_USER_ERROR);
			}
			list($imap, $svg) = GraphVizExporter::generateGraphFiles($graph, $datapath, $format);
		} else {
			$imap = file_get_contents("$datapath/$graphname.imap");
			$svg = file_get_contents("$datapath/$graphname.svg");
		}
		if (! $cache) { $imageFile .= "?".(rand()%1000); } //append random # to image name to prevent browser caching
		if (isset($_REQUEST['useSVG']) && $_REQUEST['useSVG'] == 1) { 
			$overlay = "<div id='svg_overlay' style='display: none'>$svg</div>";
		} else {
			$overlay = $imap;
		}
		$path = preg_replace("|^".$nodeViz_config['web_path']."|", "", $nodeViz_config['cache_path']);
		$image = "$path$graphname.$format";
		$dot = "$path$graphname.dot";
		return array('image'=>$image, 'graph'=>$graph, 'overlay'=>$overlay, 'dot'=>$dot);
	}

	public static function processImap($imapFile, $datapath, $graphname) {
		$imap = file_get_contents($imapFile);
		$imap = str_replace('<map id="G" name="G">', "", $imap);
		$imap = str_replace("</map>", "", $imap);
		$imap = preg_replace("/ (target|title|href|alt)=\"[^\"]*\"/", "", $imap);
		$imap = "<map id='G' name='G'>".join("\n", array_reverse(explode("\n", $imap)))."</map>";
		$imapfile = fopen ($imapFile, 'w');
		fwrite($imapfile, $imap);
		fclose($imapfile);
		return $imap;
	}


	/**
		Modifies the SVG rendering of the network produced by GraphViz to be ready to insert into the XHTML document, tweaks some elements to work better in the NodeViz interactive display. Writes out the new SVG file and (when not in debug mode) deltes the old SVG file.  Key changes to SVG are:
			- adding a large 'screen' which will be used to hide the graph when certain elements are hilited
			- changing the id of the svg element
			- removing svg document header
			- removing title tags
			- adding a zoom_level class to text elements to work with the css zooming
			- rewriting image paths from local to web paths
		@param $svgFile string with the name of the file containing the SVG content
		@param $datapath string with the location of the cache directory where files are location
		@param $graph the Graph object corresponding to the SVG image.
		@returns $svg string with modified SVG content
	*/
	public static function processSVG($svgFile, $datapath, $graph) {
		global $old_graphviz;
		global $nodeViz_config;
		$graphname = $graph->graphname();
		#clean up the raw svg
		$svg = file_get_contents($svgFile);
		if ($old_graphviz) {
			$svg = preg_replace("/<!-- ([^ ]+) -->\n<g id=\"(node|edge)\d+\"/", "<g id=\"$1\"", $svg);
			$svg = preg_replace("/^.*fill:(black|white).*\n/m", "", $svg);
			$svg = str_replace("<polygon style=\"fill:#ffffff", "<polygon id='svgscreen' style=\"fill:#ffffff; opacity:0", $svg);
		} else {
			function shiftlabels($matches) { 
				return 'rx="'.$matches[1].'"'.$matches[2].'start'.$matches[3].(($matches[1])+$matches[4]+15).'"';
			}
			$svg = preg_replace_callback("/rx=\"([^\"]+)\"([^<]+<text text-anchor=\")[^\"]+(\" x=\")([^\"]+)\"/", "shiftlabels", $svg);
			$svg = str_replace("font-size=\"10.00\"", "font-size=\"16.00\"", $svg);
			$svg = preg_replace("/<!-- ([^ ]+) -->\n/", "", $svg);
			$svg = preg_replace("/^.*fill=\"(black|white).*\n/m", "", $svg);
			$svg = str_replace("G</title>\n<polygon fill=\"#ffffff", "G</title>\n<polygon id='svgscreen' style=\"opacity:0;\" fill=\"#ffffff", $svg);
			//$svg = str_replace("fill=\"none", "style=\"opacity:0;\" fill=\"#ffffff", $svg);
			#$svg = preg_replace("/<polygon id='svgscreen'[^>]*>/", "", $svg);
			$svg = preg_replace("/id=\"graph1/", "id=\"graph0", $svg);
			//$svg = preg_replace("/<g id=\"graph0/", "<script xlink:href=\"svgpan.js\"/> <g id=\"graph0", $svg);
			$svg = preg_replace("/viewBox=\"[^\"]*\"/", "", $svg);
			//$svg = preg_replace("/<ellipse fill=\"#.*/", "", $svg);
			//rescale the svg
		#	preg_match("/transform=\"scale\(([\.\d]+)/", $svg, $matches);
		#	$newscale = substr($matches[1]/(96/72), 0, 8);
		#	$svg = preg_replace("/transform=\"scale\([^\)]+\)/", "transform=\"scale($newscale $newscale)", $svg);
		}
		$svg = preg_replace("/^.*?<svg/s", "<svg", $svg); //Remove SVG Document header
		$svg = str_replace("&#45;&gt;", "_", $svg); //FIXME? convert HTML -> to _?
		$svg = str_replace("pt\"", "px\"", $svg); //convert points to pixels
		$svg = preg_replace("/<title>.*/m", "", $svg); //remove 'title' tags
		$svg = preg_replace("/^<\/?a.*\n/m", "", $svg); //FIXME? remove cruft after anchor tags
		#$svg = preg_replace("/^<text.*\n/m", "", $svg);
		$svg = preg_replace("/^<text/m", "<text class='zoom_7'", $svg); //FIXME set zoom class on labels
		$svg = preg_replace("/zoom_7' text-anchor=\"middle\"([^>]+ fill)/", "' text-anchor='end'$1", $svg); //FIXME change the text anchor on labels?
		$svg = preg_replace("/\.\.\/www\//", "", $svg); //FIXME change the local web path to be relative to http web path
		//$tf = preg_match("/transform=\"scale(\([\-\.\d]+)\) rotate\(0\) translate\(([\-\.\d]+) ([\-\.\d]+)\)/", $svg);

		#resize the svgscreen polygon to fill up the entire allotted graph viewable area
		#(we need to convert coords from pixel values to scaled svg)
		preg_match("/transform=\"scale\(([\d\.\-]+) ([\d\.\-]+)?\)/", $svg, $tf);
		if(!$tf[2]) { $tf[2] = $tf[1]; }
		$converted_width = $graph->width / $tf[1];
		$converted_height = $graph->height / $tf[2];
		$points = "0,0 0,-$converted_height $converted_width,-$converted_height $converted_width,0 0,0";
		$svg = preg_replace('/(<polygon id=\'svgscreen\'.*?" points=)"([^"]+)"/', "$1\"$points\"", $svg);

		#pull out all the node x values
		/*
		preg_match_all("/<ellipse[^>]+ cx=\"([^\"]+)\"/", $svg, $matches);
		$matches = $matches[1];
		sort($matches);
		$x_values = array_values(array_unique($matches));
		preg_match("/svgscreen[^>]+ points=\"[^,]+,[^,]+,([^ ]+)/", $svg, $matches);
		$y = $matches[1] *.97;
		$labels = "
			<text class='columnlabels' text-anchor='middle' x='".$x_values[1]."' y='$y' font-family='Arial, Helvetica, sans-serif' font-size='150.00' fill='#999999' >Direct Giving Only</text>
			<text class='columnlabels' text-anchor='middle' x='".$x_values[2]."' y='$y' font-family='Arial, Helvetica, sans-serif' font-size='150.00' fill='#999999' >Both Direct and Indirect</text>
		";
		$svg = preg_replace("/(svgscreen'[^>]*>)/", "$1$labels", $svg);
		*/

		#write out the new svg
		$svgout = fopen("$datapath/$graphname.svg", 'w');
		fwrite($svgout, $svg);
		fclose($svgout);
	
		#delete the raw svg
		if (! $nodeViz_config['debug']) { unlink($svgFile); }
		return $svg;
	}

	public static function processGraphData($data, $datapath, $graphname) {
		unset($data['properties']['graphvizProperties']);
		unset($data['queries']);
		foreach (array_keys($data['nodes']) as $node) {
			foreach(array('mouseout', 'size', 'max', 'min', 'color', 'fillcolor', 'weight') as $key) { 
				unset($data['nodes'][$node][$key]); 
			}
		}
		foreach (array_keys($data['edges']) as $edge) {
			foreach(array('mouseout', 'size', 'max', 'min', 'color', 'fillcolor', 'weight', 'width') as $key) { 
				unset($data['edges'][$edge][$key]); 
			}
		}
		$graphfile = fopen("$datapath/$graphname.graph", "w");
		fwrite($graphfile, serialize($data));
		fclose($graphfile);
		return $data;
	}
}
?>
