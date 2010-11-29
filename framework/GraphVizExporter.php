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
		 			'epsilon'=>'0.0'),
		 'node' => array(
		 			'style' => 'setlinewidth(16), filled',
		 			'fontsize' => '10',
		 			'fixedsize' => 'true', 
		 			'label'=>' '),
		 'edge' => array(
		 			'len' => '8',
		 			'style'=>'setlinewidth(2)',
		 			'labelfloat' => 'true')

	);

	static function createDot($graph){ //add more arguments to control dot file creation
		$GV_PARAMS = GraphVizExporter::$GV_PARAMS;

		//include the needed sources files


		//make file function

		//make a string to contain the 

		//FIXME included unassigned properties as comments
		//write out the header for the graphviz file
		//THESE PROPS SHOULD BE

		//Merge any properties set in graphSetup into GV_PARAMS
		if (isset($graph['graphvizProperties'])) {
			$GV_PARAMS = array_merge_recursive_unique($GV_PARAMS, $graph['graphvizProperties']);
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

		//for each node type  
		foreach ($graph['nodetypes'] as $nodetype){
			//for each node
			foreach ($graph['nodes'][$nodetype] as $node){
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

		//for each edge type
		foreach(array_keys($graph['edgetypes']) as $edgetype){
			//for each edge
			foreach($graph['edges'][$edgetype] as $edge ){
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
		}
//hack test subgraph function
		foreach (array_keys($graph['subgraphs']) as $sg_name){
			$dot .= "subgraph $sg_name {\n";
			$subgraph = $graph['subgraphs'][$sg_name];
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


	//read in imap file and construct table of coords 
	//TODO: this function breaks if node labels contain line breaks
	static function parseDotCoords($imap, $graph){
		$lines = explode("\n", $imap); 
		$imapstr = "";
		$jsgraph = array();
		foreach( $lines as &$line){
			$node = array();
			$attribs = "";
			preg_match("/id=\"([^\"]+)\".+coords=\"([^\"]+)\"/", $line, $matches); //extract type, id, label, and coords
			if (! isset($matches[0])) {
				continue;
			}	
			list($string, $id, $coords) = $matches; 
						
			if (!$id) { continue; } //skip if there's no node id		
			$coords = explode(",", $coords);		
			$node = $graph->lookupNodeID($id);
			if (!isset($node['id'])){
			  writelog("unable to locate graph element with id of $id");
			  continue;
			}
			//use imap coords to construct node dimensions
			if (isset($node['shape']) && $node['shape'] == "circle") {
				$node['width'] = $coords[2]*2;
				$node['height'] = $coords[2]*2;
				$node['posx'] = $coords[0] - ($coords[2]);
				$node['posy'] = $coords[1] - ($coords[2]);
			} else {
				$node['width'] = ($coords[2] - $coords[0]);
				$node['height'] = ($coords[3] - $coords[1]);
				$node['posx'] = $coords[0];
				$node['posy'] = $coords[1];
			}
			//add node to graph array, indexed by id
	
			$jsgraph[$node['id']]  = $node;
			/*
			foreach (array_keys($node) as $key) { 
				if(strstr($key, 'on')) { 
					$attribs .= " $key=\"".$node[$key]."\" ";
				}
			}*/
			#print $attribs;
			$line = str_replace("/>", "$attribs/>", $line);
			$line = preg_replace('/ ContribIDs="[^"]+"/', '', $line);
			$line = preg_replace('/ PartyDesignation1="[^"]+"/', '', $line);
			$line = preg_replace('/  +"?/', ' ', $line);
			$imapstr .= $line."\n";
		}
		//convert array to string
		$string = 'graphviz = '.json_encode($jsgraph).';'; 
		return(array("<map id='G' name='G'>$imapstr</map>", $string));
	}

	//displayDotFile: accepts dot file as string, dropIsolates flag, processed through neato, writes out image header and image
	static function displayDotFile($dot,$dropIsolates) {
		$format = "jpg"; //output format
		header("Content-Type: image/$format");
		$descriptorspec = array(
		   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		   2 => array("file", "log/graphviz.log", "a") // stderr is a file to write to
		);
		if ($dropIsolates){
			$exportCommand = "dot -vvv -x -T$format | mogrify -format $format -resize 800x600 $format:- ";
	//pipe through mogrify to resize image //FIXME: we can't do this - it will break the imap file
		} else {
			#$exportCommand = "neato -Tpng | mogrify -format $format -resize 800x600 png:- ";
			$exportCommand = "dot -T$format  ";
		}
		$process = proc_open($exportCommand, $descriptorspec, $pipes);
		fwrite($pipes[0], $dot);
		fclose($pipes[0]);
		echo stream_get_contents($pipes[1]);
		fclose($pipes[1]);
	}

	public static function generateGraphFiles($graph, $datapath, $format) {
		global $logdir;
		global $debug;
		global $old_graphviz;

		$graphname = $graph->graphname();
		$dotString = GraphVizExporter::createDot($graph->data);
		$imageFile = "$datapath/$graphname.png";
		$dotFile = "$datapath/$graphname.dot";
		$svgFile = "$datapath/$graphname.svg.raw";
		if ($debug) { 
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
		   2 => array("file", "$logdir/graphviz.log", "a") // stderr is a file to write to
		);
		//use neato to generate and save image file, and generate imap file to STDOUT
		$process = proc_open(" dot -vvv -Tsvg -o $svgFile -Tdot -o $dotFile -Tpng -o $imageFile -Tcmapx ", $descriptorspec, $pipes);
		fwrite($pipes[0], $dotString);
		fclose($pipes[0]);
		$imap =  stream_get_contents($pipes[1]); //store imap file
		fclose($pipes[1]);

		$imap = preg_replace("/target=\"[^\"]+\"/", "", $imap)	;
		$imap = str_replace(" href=\"a\"", "", $imap)	;
		$imap = str_replace(" alt=\"\"", "", $imap)	;
		$imap = str_replace("<map id='G' name='G'>", "", $imap)	;
		$imap = str_replace("</map>", "", $imap);
		$imap = preg_replace("/title=\"[^\"]+\"/", "", $imap)	;
		$imap = "<map id='G' name='G'>".join("\n", array_reverse(explode("\n", $imap)))."</map>";
		list($imap, $jsCoords) = GraphVizExporter::parseDotCoords($imap, $graph); //read dotfile coords from imap file
		$imapfile = fopen ("$datapath/$graphname.imap", 'w');
		fwrite($imapfile, $imap);
		fclose($imapfile);

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
		#$x_values = array_keys(array_count_values($matches)); 

		#write out the new svg
		$svgout = fopen("$datapath/$graphname.svg", 'w');
		fwrite($svgout, $svg);
		fclose($svgout);
	
		#delete the raw svg
		if (! $debug) { unlink($svgFile); }

		$graphout = $graph->data;
		unset($graphout['properties']['graphvizProperties']);
		unset($graphout['queries']);
		foreach (array_keys($graphout['nodes']) as $nodetype) {
			foreach (array_keys($graphout['nodes'][$nodetype]) as $node) {
				foreach(array('mouseout', 'size', 'max', 'min', 'color', 'fillcolor', 'weight') as $key) { 
					unset($graphout['nodes'][$nodetype][$node][$key]); 
				}
			}
		}
		foreach (array_keys($graphout['edges']) as $edgetype) {
			foreach (array_keys($graphout['edges'][$edgetype]) as $edge) {
				foreach(array('mouseout', 'size', 'max', 'min', 'color', 'fillcolor', 'weight', 'width') as $key) { 
					unset($graphout['edges'][$edgetype][$edge][$key]); 
				}
			}
		}
		$graphfile = fopen("$datapath/$graphname.graph", "w");
		fwrite($graphfile, serialize($graphout));
		fclose($graphfile);

		if ($format != 'png') { 
			#system("convert -quality 92 $datapath$graphname.png $datapath$graphname.$format");
			#system("grep -v levelfour $datapath$graphname.svg | convert -quality 92 svg:- $datapath$graphname.$format");
			system("cd ../www/; grep -v levelfour $datapath$graphname.svg | convert -quality 92 svg:- $datapath$graphname.$format");
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
		return array($imap, $jsCoords, $svg);
	}

	public static function generateGraphvizOutput($graph, $datapath, $format, $returnsvg = 0) {
		$graphname = $graph->graphname();
		$imageFile = "$datapath$graphname.$format";
		$dotFile = "$datapath$graphname.dot";
		$svgFile = "$datapath$graphname.svg";
		global $cache;
		$output = "";
		if ($cache != 1) {
			list($imap, $jsCoords,$svg) = GraphVizExporter::generateGraphFiles($graph, $datapath, $format);
		} else {
			$imap = file_get_contents("$datapath/$graphname.imap");
			$svg = file_get_contents("$datapath/$graphname.svg");
			list($imap2, $jsCoords) = GraphVizExporter::parseDotCoords($imap, $graph); //read dotfile coords from imap file
			//$jsCoords = file_get_contents("$datapath/$graphname.js");
		}
		if (! $cache) { $imageFile .= "?".(rand()%1000); } //append random # to image name to prevent browser caching
		//$output .= "dotfile = '$dotFile';\n";
		$output = "\nstatusCode= 1;\n"; //tell the gui everything is ok in the world
		$output .= "img = '$imageFile';\n"; 
		$output .= $jsCoords;
		if (isset($_REQUEST['useSVG']) && $_REQUEST['useSVG'] == 1) { 
			$output .= "overlay = ".json_encode("<div id='svg' style='display: none'>$svg</div>");
		} else {
			$output .= "overlay = ".json_encode($imap);
		}
		return $output;
	}
}
?>
