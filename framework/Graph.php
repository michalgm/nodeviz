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
			'queries'=> array(),
			'nodetypesindex'=> array(),
			'edgetypesindex'=> array(),
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
				$nodes = $this->$function();
				$this->data['nodetypesindex'][$nodetype] = array_keys($nodes);
				foreach ($nodes as $node) { 
					$this->data['nodes'][$node['id']] = $node;
					$this->data['nodes'][$node['id']]['type'] = $nodetype;
				}
			}
		}

		foreach (array_keys($this->data['edgetypes']) as $edgetype) {
			$function =  "$edgetype"."_fetchEdges";
			if(method_exists($this, $function)) {
				$edges = $this->$function();
				$this->data['edgetypesindex'][$edgetype] = array_keys($edges);
				foreach ($edges as $edge) { 
					$this->data['edges'][$edge['id']] = $edge;
					$this->data['edges'][$edge['id']]['type'] = $edgetype;
				}
			}
		}

		if (! isset($this->data['properties']['retainIsolates'])) {
			foreach (array_keys($this->data['nodes']) as $id) {
				$test = 0;
				foreach (array_keys($this->data['edges']) as $edgeid) {
					if (! isset($this->data['edges'][$edgeid]['toId'])) { print_r($this->data['edges'][$edgeid]); print $edgeid;}
					if ($this->data['edges'][$edgeid]['toId'] == $id || $this->data['edges'][$edgeid]['fromId'] == $id) { $test = 1; }
				}
				if (! $test) { 
					//This node is not associated with any edges, so we remove it from the nodes and nodetypesindex arrays
					$nodetype = $this->data['nodes'][$id]['type'];
					unset($this->data['nodes'][$id]); 
					$index = array_search($id, $this->data['nodetypesindex'][$nodetype]);
					if ($index) {
						unset($this->data['nodetypesindex'][$nodetype][$index]); 
					}
				}
			}
		}

		foreach ($this->data['nodetypes'] as $nodetype) {
			$function =  "$nodetype"."_nodeProperties";
			if(method_exists($this, $function)) {
				$nodes = $this->$function();
				foreach ($nodes as $node) {
					unset($this->data['nodes'][$node['id']]); //need to unset to inherit new order
					$this->data['nodes'][$node['id']] = $node;
				}
			}
		}

		foreach (array_keys($this->data['edgetypes']) as $edgetype) {
			$function =  "$edgetype"."_edgeProperties";
			if(method_exists($this, $function)) {
				$edges = $this->$function();
				foreach ($edges as $edge) {
					unset($this->data['edges'][$edge['id']]); //need to unset to inherit new order
					$this->data['edges'][$edge['id']] = $edge;
				}
			}
		}

		//Populate the related nodes fields for each node by stepping through the edges
		foreach ($this->data['edges'] as $edge) {
			if ($this->data['nodes'][$edge['toId']]) {
				$this->data['nodes'][$edge['toId']]['relatedNodes'][$edge['fromId']] = 'from';
			}
			if ($this->data['nodes'][$edge['fromId']]) {
				$this->data['nodes'][$edge['fromId']]['relatedNodes'][$edge['toId']] = 'to';
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
	function scaleSizes($array, $type, $key){
		$graph = &$this->data;
		$maxSize = $graph['properties']['maxSize'][$type];
		$minSize = $graph['properties']['minSize'][$type];
		$scale = pow($maxSize,2) - pow($minSize,2);  //the range we actually want
		$vals = array();
		
		if (isset($graph['nodetypesindex'][$type])) {	
			reset($array);
			if (key($array)) { 
				$shape = $array[key($array)]['shape'];
				//load all the cash into an array
				foreach($array as $node) { $vals[] =  $node[$key]; }
				$diff = max($vals) - min($vals);  //figure out the data range
				$min = min($vals);
				foreach(array_keys($array) as $id) {
					if ($diff == 0) {  // if all nodes are the same size, use max
						$array[$id]['size']	= $maxSize;
					} else {
						 $normed = ($array[$id][$key] - $min) / $diff; //normalize it to the range 0-1
						 $area = ($normed * $scale) + pow($minSize,2);  //adjust to value we want
						 //now calculate appropriate with from area depending on shape
						if ($shape == 'circle') { 
							$size = sqrt(abs($area)/pi())*2;  //get radius and multiple by 2 for diameter
						} else {
							$size = sqrt(abs($area));
						}
						//$factor = $amount/$diff;
						$array[$id]['size']	= $size ;
					}
				}
			}
		} else if ($graph['edgetypesindex'][$type]) {
			foreach($array as $node) { $vals[] =  $node[$key]; }
			$diff = max($vals) - min($vals);
			foreach(array_keys($array) as $id) {
				if ($diff == 0) {
					$array[$id]['size']	= $maxSize;
				} else {
					$amount = $array[$id][$key] - min($vals);
					$factor = $amount/$diff;
					$array[$id]['size'] = ($factor * ($maxSize - $minSize)) + $minSize;	
				}
			}
		}
		return $array;
	}

	//returns the node array corresponding to an id
	function lookupNodeID($id) {
		$graph = $this->data;
		if (isset($graph['nodes'][$id])) { 
			return $graph['nodes'][$id];
		}
		if (isset($graph['edges'][$id])) { 
			return $graph['edges'][$id];
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
