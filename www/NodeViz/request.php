<?php
/** Script to handle AJAX request and manage graph generation and response.
Called by html frontend via HTTP RPC. Returns HTML and javascript strings to be interpreted by frontend javascript
*/

header('Content-type: application/json');
set_error_handler('handleError');
require_once("NodeVizUtils.php");
set_include_path(get_include_path().PATH_SEPARATOR.$nodeViz_config['library_path'].PATH_SEPARATOR.$nodeViz_config['application_path']);
#chdir($nodeViz_config['web_path']);
#reinterpret_paths();

$nodeViz_config['debug'] = 1;
#sleep(3);

$response = array('statusCode'=>7, 'statusString'=>'No data was returned');

if (isset($_REQUEST['setupfile'])) { 
	$setupfile = $_REQUEST['setupfile'];
} elseif (isset($nodeViz_config['default_setupfile'])) { 
	$setupfile = $nodeViz_config['default_setupfile'];
} else { trigger_error("No Setupfile defined.", E_USER_ERROR); }

if (isset($nodeViz_config['setupfiles']["$setupfile.php"])) {
	if(file_exists($nodeViz_config['application_path']."$setupfile.php")) { ;
		include_once($nodeViz_config['application_path']."$setupfile.php");
	} else { trigger_error("Setup file '$setupfile' does not exist", E_USER_ERROR); }
} else { trigger_error("Invalid setup file: $setupfile", E_USER_ERROR); }

$datapath = $nodeViz_config['nodeViz_path'].'/'.$nodeViz_config['cache_path'];

$graph = new $setupfile();

//either build or load the cached graph
$graph->setupGraph($_REQUEST);
//Make sure we actually have data in the graph object
//if bad, checkGraph() will print an error state and exit.
$graph->checkGraph();

if(isset($_REQUEST['action'])) {
	$ajaxfunc = 'ajax_'.$_REQUEST['action'];
	if (method_exists($graph, $ajaxfunc)) { 
		$data = $graph->$ajaxfunc($graph);
	} else {
		trigger_error('"'.$_REQUEST['action'].'" is an invalid method.', E_USER_ERROR);
	}
} else {
	$returnSVG = isset($_REQUEST['useSVG']) ? 1 : 0;
	include_once('GraphVizExporter.php');
	$data = GraphVizExporter::generateGraphvizOutput($graph, $datapath, 'jpg', $returnSVG);
}

setResponse(1, 'Success', $data);

function setResponse($statusCode, $statusString, $data="") {
	$response = array('statusCode'=>$statusCode, 'statusString'=>$statusString, 'data'=>$data);
	//if php version < 5.3.0 we need to emulate the object string
	if (PHP_MAJOR_VERSION <= 5 & PHP_MINOR_VERSION < 3){
		print __json_encode($response);
	} else {
		print json_encode($response, JSON_FORCE_OBJECT);
	}
			
	if ($statusCode == 256) { 
		exit;
	}
}

function handleError($errno, $errstr, $errfile, $errline) {
	global $nodeViz_config;
	$details = $nodeViz_config['debug'] ? " ($errfile - line $errline)" : "";
	setResponse($errno, "$errstr$details");
	return true;
}

/**
Returns JSON encoded object representation of its argument. 
This is a hack to do JSON encoding when running on PHP < 5.3 which does have the FORCE_OBJECTS flag http://www.php.net/manual/en/function.json-encode.php#100835
**/
function __json_encode( $data ) {           
    if( is_array($data) || is_object($data) ) {
        $islist = is_array($data) && ( empty($data) || array_keys($data) === range(0,count($data)-1) );
       
        if( $islist ) {
           // $json = '[' . implode(',', array_map('__json_encode', $data) ) . ']';
            $items = Array();
            $index = 0;
            foreach ($data as $value) {
            		$items[] = '"'.$index.'":'.__json_encode($value);
            		$index ++;
            }
            $json = '{' . implode(',', $items) . '}';
        } else {
            $items = Array();
            foreach( $data as $key => $value ) {
                $items[] = __json_encode("$key") . ':' . __json_encode($value);
            }
            $json = '{' . implode(',', $items) . '}';
        }
    } elseif( is_string($data) ) {
        # Escape non-printable or Non-ASCII characters.
        # I also put the \\ character first, as suggested in comments on the 'addclashes' page.
        $string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';
        $json    = '';
        $len    = strlen($string);
        # Convert UTF-8 to Hexadecimal Codepoints.
        for( $i = 0; $i < $len; $i++ ) {
           
            $char = $string[$i];
            $c1 = ord($char);
           
            # Single byte;
            if( $c1 <128 ) {
                $json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
                continue;
            }
           
            # Double byte
            $c2 = ord($string[++$i]);
            if ( ($c1 & 32) === 0 ) {
                $json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
                continue;
            }
           
            # Triple
            $c3 = ord($string[++$i]);
            if( ($c1 & 16) === 0 ) {
                $json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128));
                continue;
            }
               
            # Quadruple
            $c4 = ord($string[++$i]);
            if( ($c1 & 8 ) === 0 ) {
                $u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1;
           
                $w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3);
                $w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128);
                $json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
            }
        }
    } else {
        # int, floats, bools, null
        $json = strtolower(var_export( $data, true ));
    }
    return $json;
} 

?>
