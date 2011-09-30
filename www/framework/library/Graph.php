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

		$this->width = null;
		$this->height = null;

		$this->data = $data;

		return $this;
	}


	//Defines the graph's name if it isn't already set, then returns it
	function graphname() {
		if (! $this->name) { 
			$this->name = crc32(serialize($this->data['properties']));
			//set the name
		}
		return $this->name;
	}

	function setupGraph($request_parameters=array(), $blank=0) {
		global $datapath;
		global $framework_config;
		$cache = $framework_config['cache'];
		
		$this->input_parameters = $request_parameters;
		//Override defaults with input values if they exist
		foreach( array_keys($this->data['properties']) as $key) {
			if(isset($request_parameters[$key])) {
				//We can't call dbEscape - ain't part of framework - so be careful!
				//$this->data['properties'][$key]  = dbEscape($request_parameters[$key]);
				$this->data['properties'][$key]  = $request_parameters[$key];
			}
		}	
		if (isset($request_parameters['graphWidth'])) {
			$this->width = $request_parameters['graphWidth'];
		}
		if (isset($request_parameters['graphHeight'])) {
			$this->height = $request_parameters['graphHeight'];
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
					if(isset($this->data['nodes'][$node['id']])) { trigger_error('Node id '.$node['id']." already exists", E_USER_ERROR); }
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
					if(! isset($edge['toId'])) { trigger_error("toId is not set for edge ".$edge['id'], E_USER_ERROR); }
					if(! isset($edge['fromId'])) { trigger_error("fromId is not set for edge ".$edge['id'], E_USER_ERROR); }
					if(isset($this->data['edges'][$edge['id']])) { trigger_error('Node id '.$edge['id']." already exists", E_USER_ERROR); }
					$this->data['edges'][$edge['id']] = $edge;
					$this->data['edges'][$edge['id']]['type'] = $edgetype;
				}
			}
		}

		$this->checkIsolates();

		foreach ($this->data['nodetypes'] as $nodetype) {
			$function =  "$nodetype"."_nodeProperties";
			if(method_exists($this, $function)) {
				$existing_nodes = $this->getNodesByType($nodetype);
				$nodes = $this->$function($existing_nodes);
				foreach (array_keys($existing_nodes) as $nodeid) { unset($this->data['nodes'][$nodeid]); }
				foreach ($nodes as $node) {
					$this->data['nodes'][$node['id']] = $node;
					$this->data['nodes'][$node['id']]['type'] = $nodetype;
					$this->data['nodes'][$node['id']]['relatedNodes'] = array();
				}
				$this->data['nodetypesindex'][$nodetype] = array_keys($nodes);
			}
		}

		$this->checkIsolates();

		foreach (array_keys($this->data['edgetypes']) as $edgetype) {
			$function =  "$edgetype"."_edgeProperties";
			if(method_exists($this, $function)) {
				$existing_edges = $this->getEdgesByType($edgetype);
				$edges = $this->$function($existing_edges);
				foreach (array_keys($existing_edges) as $edgeid) { unset($this->data['edges'][$edgeid]); }
				foreach ($edges as $edge) {
					$this->data['edges'][$edge['id']] = $edge;
					$this->data['edges'][$edge['id']]['type'] = $edgetype;
				}
				//$this->data['edgetypesindex'][$edgetype] = array_keys($edges);
			}
		}
		//should we check for edges linking to non-existant nodes?

		$this->checkIsolates();
	
		//Populate the related nodes fields for each node by stepping through the edges
		foreach ($this->data['edges'] as $edge) {
			if (! $edge['toId']) { print 'none!'; print_r($edge); } 
			if ($this->data['nodes'][$edge['toId']]) {
				$this->data['nodes'][$edge['toId']]['relatedNodes'][$edge['fromId']][] = $edge['id'];
			}
			if ($this->data['nodes'][$edge['fromId']]) {
				$this->data['nodes'][$edge['fromId']]['relatedNodes'][$edge['toId']][] = $edge['id'];
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
		$graph = $this->data;
		$maxSize = $graph['properties']['maxSize'][$type];
		$minSize = $graph['properties']['minSize'][$type];
		if (isset($graph['properties']['log_scaling'])) {
			$log = $graph['properties']['log_scaling'];
		} else {
			$log = 0;
		}

		$vals = array();
		
		//if (isset($graph['nodetypesindex'][$type])) {	
		reset($array);
		if (key($array)) { 
			if(isset($graph['nodetypesindex'][$type])) {
				$shape = $array[key($array)]['shape'];
			} else {
				$shape = 'edge';
			}
			if ($shape == 'edge') {
				$scale = $maxSize - $minSize;  //the range we actually want
			} else if ($shape == 'circle') { 
				//we need to comvert sizes from diameter to radius
				$maxSize = $maxSize/2;
				$minSize = $minSize/2;
				$scale = pow($maxSize,2)*pi() - pow($minSize,2)*pi();  //the range we actually want
			} else {
				$scale = pow($maxSize,2) - pow($minSize,2);  //the range we actually want
			}
			//load all the cash into an array
			foreach($array as $node) { $vals[] =  $node[$key]; }
			//This code was for reseting values < 0 to zero
			/*foreach($array as $node) { 
				if ($node[$key] > 0) { 
					$vals[] =  $node[$key];
				} else { $vals[] = 0; }
		   	}
			*/
			$min = min($vals);
			$max = max($vals);
			$adj_min = $min + abs($min)+1;
			$adj_max = $max + abs($min)+1;
			if ($log) {
				$diff = log($adj_max) - log($adj_min);  //figure out the data range
			} else {
				$diff = $max - $min;  //figure out the data range
			}
			foreach(array_keys($array) as $id) {
				if ($diff == 0) {  // if all nodes are the same size, use max
					$array[$id]['size']	= $maxSize;
				} else {
					$value = $array[$id][$key];
					//This code was for reseting values < 0 to zero
					//if ($value <= $min) { $normed = 0; } 
					if ($log) { 
						$normed = (log($value+abs($min)+1) - log($adj_min)) / $diff; //normalize it to the range 0-1
					} else {
						$normed = ($value - $min) / $diff; //normalize it to the range 0-1
					}
					 //now calculate appropriate with from area depending on shape
					if ($shape == 'edge') { 
						$size = ($normed * $scale) + $minSize;  //adjust to value we want
					} else if ($shape == 'circle') { 
						$area = ($normed * $scale) + pow($minSize,2)*pi();  //adjust to value we want
						$size = sqrt(abs($area)/pi())*2;  //get radius and multiple by 2 for diameter
					} else {
						$area = ($normed * $scale) + pow($minSize,2);  //adjust to value we want
						$size = sqrt(abs($area));
					}
					$array[$id]['size']	= $size ;
				}
			}
		}

		//reorder the values for debugging
		$array = $this->subval_sort($array, $key);
		return $array;
	}

	function subval_sort($a,$subkey) {
		foreach($a as $k=>$v) {
			$b[$k] = strtolower($v[$subkey]);
		}
		asort($b);
		foreach($b as $key=>$val) {
			$c[$key] = $a[$key];
		}
		return $c;
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
		if ($graph == "") {
			 trigger_error("We're sorry. The files needed to display these options are missing. Please contact the site administrator.", E_USER_ERROR);
		}
		if ($graph['nodes'] == "" || sizeof($graph['nodes']) == 0 || gettype(current($graph['nodes'])) != 'array') {
			 trigger_error('These options return no relationship.', E_USER_ERROR);
		}
	}

	function addquery($name, $query) {
		global $debug;
		if ($debug) { $this->data['queries'][$name] = $query; }	
	}

	function checkIsolates() {
		//Get rid of any isolated nodes if retainIsolates is set to 0
		if (isset($this->data['properties']['removeIsolates'])) {
			foreach (array_keys($this->data['nodes']) as $id) {
				$has_edges = 0;
				foreach (array_keys($this->data['edges']) as $edgeid) {
					//if (! isset($this->data['edges'][$edgeid]['toId'])) { print_r($this->data['edges'][$edgeid]); print $edgeid;}
					if ($this->data['edges'][$edgeid]['toId'] == $id || $this->data['edges'][$edgeid]['fromId'] == $id) { 
						$has_edges = 1; 
						continue;
					}
				}
				if (! $has_edges) { 
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
	}

	function getNodesByType($type) {
		$nodes = array();
		foreach($this->data['nodetypesindex'][$type] as $id) {
			$nodes[$id] = $this->data['nodes'][$id];
		}
		return $nodes;	
	}

	function getEdgesByType($type) {
		$edges = array();
		foreach($this->data['edgetypesindex'][$type] as $id) {
			$edges[$id] = $this->data['edges'][$id];
		}
		return $edges;	
	}

}
