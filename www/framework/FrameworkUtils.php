<?php
$framework_config = array(
	'framework_path' => getcwd(),
	'web_path' => '../',
	'application_path' => './application/',
	'library_path' => './library/',
	'log_path' => "../log/",
	'cache_path' => "../cache/",
	'cache' => 0,
	'debug' => 1,
	'old_graphviz' => 0, #Set this to 1 if graphviz version < 2.24

	'setupfiles' => array('crpgraphSetup.php'=>1, 'voteGraphSetup.php'=>1,'committeeGraphSetup.php'=>1, 'FECCanComGraph.php'=>1, 'ContributionGraph.php'=>1),
);

if (php_sapi_name() != 'cli') {
	ini_set('zlib.output_compression',1);
}

//local.php will allow you to override any set globals 
if(file_exists('local.php')) { 
	include_once("local.php"); 
} elseif(file_exists($framework_config['application_path'].'/local.php')) { 
	include_once($framework_config['application_path']."/local.php"); 
}

function reinterpret_paths() {
	global $framework_config;
	foreach(array('application_path', 'log_path', 'cache_path') as $path) {
		$framework_config[$path] = preg_replace("|^$framework_config[web_path]|", '', $framework_config[$path]) ;
	}
}

function setupHeaders() {
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
	return $svgdoctype;
}

//writelog: writes string out to logfile
function writelog($string) {
	global $logdir, $logfile;
	if (!$logfile) {  //open logfile if it isn't open
			$logfilename = "$logdir/".basename($_SERVER['PHP_SELF']).".log";
			$logfile= fopen($logfilename, 'a'); 
	}
	fwrite($logfile, time()." - $string\n");
}



