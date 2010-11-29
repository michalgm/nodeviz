<?php

#The Graph class should be a superclass that defines the abstract graph data structure and functions relating to elements of the graph data.

class Graph {

	var $data;
	var $name;

	function __construct() {
		//create empty graph structure;
		$data = array(
			'nodetypes'=> array(),
			'edgetypes'=> array(),
			'properties'=> array(),
			'nodes'=> array(),
			'edges'=> array(),
			'subgraphs' => array(),
			'queries'=> array()
		);

		$this->data = $data;

		return $this;
	}


	//Defines the graph's name if it isn't already set, then returns it
	function graphname() {
		if (! $this->name) { 
			//set the name
		}
		return $this->name;
	}

	function setupGraph($request_parameters=array(), $blank=0) {
		global $datapath;
		global $cache;
		
		//Override defaults with input values if they exist
		foreach( array_keys($this->data['properties']) as $key) {
			if(isset($request_parameters[$key])) {
				$this->data['properties'][$key]  = dbEscape($request_parameters[$key]);
			}
		}	
		if (method_exists($this, 'preProcessGraph')) {
			$this->preProcessGraph();
		}	

		//Set the name
		$graphname = $this->graphname();

		if ($blank == 0 ) {
			//Either load graph data from cache, or use loadGraphData to load it
			if ($cache != 1) { 
				$this->loadGraphData();
			} else { 
				if (is_readable("$datapath/$graphname.graph")) {
					$data = unserialize(file_get_contents("$datapath/$graphname.graph")); 
					$this->data = $data;
				} else {
					$this->data = "";
				}
			}
		}
		
		
		return $this;	
	}

	//Fill in nodes and edges
	function loadGraphData() {
		global $debug;
		if ($debug) { $start = microtime(1); }
		foreach ($this->data['nodetypes'] as $nodetype) {
			$function =  "$nodetype"."_fetchNodes";
			if(method_exists($this, $function)) {
				$this->$function();
			}
		}
		foreach (array_keys($this->data['edgetypes']) as $edgetype) {
			$function =  "$edgetype"."_fetchEdges";
			if(method_exists($this, $function)) {
				$this->$function();
			}
		}
		
		if (! isset($this->data['properties']['retainIsolates'])) {
			foreach ($this->data['nodetypes'] as $nodetype) {
				foreach (array_keys($this->data['nodes'][$nodetype]) as $id) {
					$test = 0;
					foreach (array_keys($this->data['edgetypes']) as $edgetype) {
						if (is_array($this->data['edges'][$edgetype])){
							foreach (array_keys($this->data['edges'][$edgetype]) as $edgeid) {
								if ($this->data['edges'][$edgetype][$edgeid]['toId'] == $id || $this->data['edges'][$edgetype][$edgeid]['fromId'] == $id) { $test = 1; }
							}
						}
					}
					if (! $test) { unset($this->data['nodes'][$nodetype][$id]); }
				}
			}
		}

		foreach ($this->data['nodetypes'] as $nodetype) {
			$function =  "$nodetype"."_nodeProperties";
			if(method_exists($this, $function)) {
				$this->$function();
			}
		}

		foreach (array_keys($this->data['edgetypes']) as $edgetype) {
			$function =  "$edgetype"."_edgeProperties";
			if(method_exists($this, $function)) {
				$this->$function();
			}
		}
		//load subgraphs if subgraph method is defined
		if (method_exists($this, 'getSubgraphs')) {
			$this->getSubgraphs();
		}	
		if ($debug) { $this->data['properties']['time'] = microtime(1) - $start; }	
		if (method_exists($this, "postProcessGraph")) { 
			$this->postProcessGraph();
		}
	}

	
	#-------------------------------------------------

	//Sets 'size' property to scaled value: takes graph object, entity type, and key of entity to use for scaled values
	function scaleSizes($type, $key){
		$graph = &$this->data;
		$maxSize = $graph['properties']['maxSize'][$type];
		$minSize = $graph['properties']['minSize'][$type];
		$scale = pow($maxSize,2) - pow($minSize,2);  //the range we actually want
		$vals = array();
		
		if (isset($graph['nodes'][$type])) {	
			reset($graph['nodes'][$type]);
			if (key($graph['nodes'][$type])) { 
				$shape = $graph['nodes'][$type][key($graph['nodes'][$type])]['shape'];
				//load all the cash into an array
				foreach($graph['nodes'][$type] as $node) { $vals[] =  $node[$key]; }
				$diff = max($vals) - min($vals);  //figure out the data range
				$min = min($vals);
				foreach(array_keys($graph['nodes'][$type]) as $id) {
					if ($diff == 0) {  // if all nodes are the same size, use max
						$graph['nodes'][$type][$id]['size']	= $maxSize;
					} else {
						 $normed = ($graph['nodes'][$type][$id][$key] - $min) / $diff; //normalize it to the range 0-1
						 $area = ($normed * $scale) + pow($minSize,2);  //adjust to value we want
						 //now calculate appropriate with from area depending on shape
						if ($shape == 'circle') { 
							$size = sqrt(abs($area)/pi())*2;  //get radius and multiple by 2 for diameter
						} else {
							$size = sqrt(abs($area));
						}
						//$factor = $amount/$diff;
						$graph['nodes'][$type][$id]['size']	= $size ;
					}
				}
			}
		} else if ($graph['edges'][$type]) {
			foreach($graph['edges'][$type] as $node) { $vals[] =  $node[$key]; }
			$diff = max($vals) - min($vals);
			foreach(array_keys($graph['edges'][$type]) as $id) {
				if ($diff == 0) {
					$graph['edges'][$type][$id]['size']	= $maxSize;
				} else {
					$amount = $graph['edges'][$type][$id][$key] - min($vals);
					$factor = $amount/$diff;
					$graph['edges'][$type][$id]['size'] = ($factor * ($maxSize - $minSize)) + $minSize;	
				}
			}
		}
		return $graph;
	}

	function iloadGraphData($graph=NULL) {
		if (!$graph) {
			$graph = createGraph(); 
		}

		foreach ($graph['nodetypes'] as $nodetype) {
			$function =  "$nodetype"."_fetchNodes";
			$graph = $function($graph);
		}

		foreach (array_keys($graph['edgetypes']) as $edgetype) {
			$function =  "$edgetype"."_fetchEdges";
			$graph = $function($graph);
		}
		
		if (! $graph['properties']['retainIsolates']) {
			foreach ($graph['nodetypes'] as $nodetype) {
				foreach (array_keys($graph['nodes'][$nodetype]) as $id) {
					$test = 0;
					foreach (array_keys($graph['edgetypes']) as $edgetype) {
					   if (is_array($graph['edges'][$edgetype])){
						foreach (array_keys($graph['edges'][$edgetype]) as $edgeid) {
							if ($graph['edges'][$edgetype][$edgeid]['toId'] == $id || $graph['edges'][$edgetype][$edgeid]['fromId'] == $id) { $test = 1; }
						}
						}
					}
					if (! $test) { unset($graph['nodes'][$nodetype][$id]); }
				}
			}
		}

		foreach ($graph['nodetypes'] as $nodetype) {
			$function =  "$nodetype"."_nodeProperties";
			$graph = $function($graph);
		}

		foreach (array_keys($graph['edgetypes']) as $edgetype) {
			$function =  "$edgetype"."_edgeProperties";
			$graph = $function($graph);
		}
		
		return $graph;
	}

	//returns the node array corresponding to an id
	function lookupNodeID($id) {
		$graph = $this->data;
		foreach($graph['nodetypes'] as $type) {
			if (isset($graph['nodes'][$type][$id])) { 
				return $graph['nodes'][$type][$id];
			}
		}
		foreach(array_keys($graph['edgetypes']) as $type) {
			if (isset($graph['edges'][$type][$id])) { 
				return $graph['edges'][$type][$id];
			}
		}
	}

	function checkGraph() {
		global $debug;
		$graph = $this->data;
		if ($debug)  { return; }
		if ($graph == "") {
			echo "statusCode = 2; statusString = 'We\'re sorry. The files needed to display these options are missing. Please contact the site administrator.';";
			exit;
		}
		if ($graph == 'empty graph') {
			echo "statusCode = 3; statusString = 'These options return no relationship.';";
			exit;
		}
		foreach ($graph['nodes'] as $nodetype) {
			if (sizeof($nodetype) ==0 ) { 
				echo "statusCode = 3; statusString = 'These options return no relationship.';";
				exit;
			}
		}
	}

	function addquery($name, $query) {
		global $debug;
		if ($debug) { $this->data['queries'][$name] = $query; }	
	}



}
