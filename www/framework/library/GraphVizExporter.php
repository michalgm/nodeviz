<?php

//Interperter file to take graph data structure and convert into a dot file

//global vars that allow tweaking settings of sections in dot file and setting how
//neato will run when it reads the file.  See http://www.graphviz.org/doc/info/attrs.html
//for list of params and dfns. 
class GraphVizExporter {

 	static $GV_PARAMS = array(
		'graph' => array(
			'outputorder' => 'edgesfirst',
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
			if(! isset($node['size'])) { print_r($node); }
			//format the the string.  
			$dot .= "\"".$node['id'].'" ['; //id of node
			//write out properties
			$dot .= 'width="'.$node['size'].'" ';
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
			'href="a" '.
			'weight="'.GraphVizExporter::getWeightGV($edge['weight']).'" ';
			if($edge['size']) {
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

	public static function generateGraphFiles($graph, $datapath, $format) {
		global $framework_config;

		$graphname = $graph->graphname();
		$dotString = GraphVizExporter::createDot($graph);

		$GV_PARAMS = GraphVizExporter::$GV_PARAMS;
		if (isset($graph->data['graphvizProperties'])) {
			$GV_PARAMS = array_merge_recursive_unique($GV_PARAMS, $graph->data['graphvizProperties']);
		}

		$layoutEngine = $GV_PARAMS['graph']['layoutEngine'];
		$layoutEngine = (isset($layoutEngine) && $layoutEngine != 'neato') ? "-K$layoutEngine" : "";

		$imageFile = "$datapath/$graphname.png";
		$dotFile = "$datapath/$graphname.dot";
		$svgFile = "$datapath/$graphname.svg.raw";
		if ($framework_config['debug']) { 
			$nicegraphfile = fopen("$datapath/$graphname.nicegraph", "w");
			fwrite($nicegraphfile, print_r($graph, 1));
			fclose($nicegraphfile);
			$origdot = fopen("$datapath/$graphname"."_orig.dot", "w");
			fwrite($origdot, $dotString);
			fclose($origdot);
		}
		$descriptorspec = array(
		   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		   2 => array("file", $framework_config['log_path']."/graphviz.log", "a") // stderr is a file to write to
		);

		//use neato to generate and save image file, and generate imap file to STDOUT
		chdir($framework_config['web_path']);
		$process = proc_open("neato -vvv $layoutEngine -Tsvg -o $svgFile -Tdot -o $dotFile -Tpng -o $imageFile -Tcmapx ", $descriptorspec, $pipes);
		fwrite($pipes[0], $dotString);
		fclose($pipes[0]);
		$imap =  stream_get_contents($pipes[1]); //store imap file
		fclose($pipes[1]);
		$result = proc_close($process);
		chdir($framework_config['framework_path']);
		if($result) { 
			trigger_error("GraphViz interpreter failed - returned $result", E_USER_ERROR);
		}
		
		$imap = GraphVizExporter::processImap($imap, $datapath, $graphname);
		
		$svg = GraphVizExporter::processSVG($svgFile, $datapath, $graphname);

		$graphData = GraphVizExporter::processGraphData($graph->data, $datapath, $graphname);
		
		if ($format != 'png') { 
			#system("convert -quality 92 $datapath$graphname.png $datapath$graphname.$format");
			chdir($framework_config['web_path']);
			system("grep -v levelfour $datapath$graphname.svg | convert -quality 92 svg:- $datapath$graphname.$format");
			chdir($framework_config['framework_path']);
			unlink("$datapath$graphname.png");
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

	public static function generateGraphvizOutput($graph, $datapath, $format, $returnsvg = 0) {
		global $framework_config;
		$graphname = $graph->graphname();
		$imageFile = "$datapath$graphname.$format";
		$dotFile = "$datapath$graphname.dot";
		$svgFile = "$datapath$graphname.svg";
 
		$cache = $framework_config['cache'];
		$output = "";
		if ($cache != 1) {
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
		$path = preg_replace("|^".$framework_config['web_path']."|", "", $framework_config['cache_path']);
		$image = "$path$graphname.$format";
		$dot = "$path$graphname.dot";
		return array('image'=>$image, 'graph'=>$graph, 'overlay'=>$overlay, 'dot'=>$dot);
	}

	public static function processImap($imap, $datapath, $graphname) {
		$imap = str_replace('<map id="G" name="G">', "", $imap);
		$imap = str_replace("</map>", "", $imap);
		$imap = preg_replace("/ (target|title|href|alt)=\"[^\"]*\"/", "", $imap);
		$imap = "<map id='G' name='G'>".join("\n", array_reverse(explode("\n", $imap)))."</map>";
		$imapfile = fopen ("$datapath/$graphname.imap", 'w');
		fwrite($imapfile, $imap);
		fclose($imapfile);
		return $imap;
	}

	public static function processSVG($svgFile, $datapath, $graphname) {
		global $old_graphviz;
		global $framework_config;
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
			$svg = str_replace("font-size=\"10.00\"", "font-size=\"30.00\"", $svg);
			$svg = preg_replace("/<!-- ([^ ]+) -->\n/", "", $svg);
			$svg = preg_replace("/^.*fill=\"(black|white).*\n/m", "", $svg);
			$svg = str_replace("G</title>\n<polygon fill=\"#ffffff", "G</title>\n<polygon id='svgscreen' style=\"opacity:0;\" fill=\"#ffffff", $svg);
			#$svg = preg_replace("/<polygon id='svgscreen'[^>]*>/", "", $svg);
			$svg = preg_replace("/id=\"graph1/", "id=\"graph0", $svg);
			//$svg = preg_replace("/<g id=\"graph0/", "<script xlink:href=\"svgpan.js\"/> <g id=\"graph0", $svg);
			$svg = preg_replace("/viewBox=\"[^\"]*\"/", "", $svg);
			$svg = preg_replace("/<ellipse fill=\"#.*/", "", $svg);
			//rescale the svg
		#	preg_match("/transform=\"scale\(([\.\d]+)/", $svg, $matches);
		#	$newscale = substr($matches[1]/(96/72), 0, 8);
		#	$svg = preg_replace("/transform=\"scale\([^\)]+\)/", "transform=\"scale($newscale $newscale)", $svg);
		}
		$svg = preg_replace("/^.*?<svg/s", "<svg", $svg);
		$svg = str_replace("&#45;&gt;", "_", $svg);
		$svg = str_replace("pt\"", "px\"", $svg);
		$svg = preg_replace("/<title>.*/m", "", $svg);
		$svg = preg_replace("/^<\/?a.*\n/m", "", $svg);
		#$svg = preg_replace("/^<text.*\n/m", "", $svg);
		$svg = preg_replace("/^<text/m", "<text class='levelfour'", $svg);
		$svg = preg_replace("/levelfour' text-anchor=\"middle\"([^>]+ fill)/", "' text-anchor='end'$1", $svg);
		$svg = preg_replace("/\.\.\/www\//", "", $svg);

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
		if (! $framework_config['debug']) { unlink($svgFile); }
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
