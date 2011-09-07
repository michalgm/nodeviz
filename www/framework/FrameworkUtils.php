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

	'setupfiles' => array(),
);

if (php_sapi_name() != 'cli') {
	ini_set('zlib.output_compression',1);
}

//config.php will allow you to override any set globals 
if(file_exists('config.php')) { 
	include_once("config.php"); 
} elseif(file_exists($framework_config['application_path'].'/config.php')) { 
	include_once($framework_config['application_path']."/config.php"); 
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
		if (! $logfile) {
			trigger_error("Unable to write log to log directory '$logdir'", E_USER_ERROR);
		}
	}
	fwrite($logfile, time()." - $string\n");
}

//tries to return integer zoom values scaled appropriately for zoom levels
function scaleValueToZoom($valueMax,$valueMin,$value){
	$range = log($valueMax) - log($valueMin);
	$relative = (log($value)-log($valueMin))/$range;
	return round(9*$relative);
}

//interpolate colors between values for scaling
//TODO: Alpha?
function scaleValueToColor($valueMax,$valueMin,$value,$colorStartHex,$colorEndHex){
	$valRange = $valueMax -$valueMin;
	$valFraction = ($value-$valueMin)/$valRange;
	$startRGB = html2rgb($colorStartHex);
	$endRGB = html2rgb($colorEndHex);
	$red = $startRGB[0]+(($endRGB[0]-$startRGB[0]) * $valFraction);
	$green = $startRGB[1]+(($endRGB[1]-$startRGB[1]) * $valFraction);
	$blue = $startRGB[2]+(($endRGB[2]-$startRGB[2]) * $valFraction);
	$newCol = rgb2html($red,$green,$blue);
	return $newCol;

}

//from http://www.anyexample.com/programming/php/php_convert_rgb_from_to_html_hex_color.xml
function html2rgb($color)
{
    if ($color[0] == '#')
        $color = substr($color, 1);

    if (strlen($color) == 6)
        list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
    elseif (strlen($color) == 3)
        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
    else
        return false;

    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

    return array($r, $g, $b);
}

//From http://www.anyexample.com/programming/php/php_convert_rgb_from_to_html_hex_color.xml
function rgb2html($r, $g=-1, $b=-1)
{
    if (is_array($r) && sizeof($r) == 3)
        list($r, $g, $b) = $r;

    $r = intval($r); $g = intval($g);
    $b = intval($b);

    $r = dechex($r<0?0:($r>255?255:$r));
    $g = dechex($g<0?0:($g>255?255:$g));
    $b = dechex($b<0?0:($b>255?255:$b));

    $color = (strlen($r) < 2?'0':'').$r;
    $color .= (strlen($g) < 2?'0':'').$g;
    $color .= (strlen($b) < 2?'0':'').$b;
    return '#'.$color;
}

//generate an array of hex colors to be mapped to values
//based on discussion here: http://www.krazydad.com/makecolors.php
function makeColorMap($values){
	$numCols = count($values);
	$center = 128;
	$width = 127;
	$frequency = 1;
	$phaseR = 0;
	$phaseG = 2;
	$phaseB = 4;
	$c=0;
	$colArray = array();
	while ($c < $numCols){
		$red = sin($frequency*$c+$phaseR) * $width + $center;
		$green = sin($frequency*$c+$phaseG) * $width + $center;
		$blue = sin($frequency*$c+$phaseB) * $width + $center;
		$colArray[$values[$c]] = rgb2html($red,$green,$blue);
		$c = $c+1;
	}
	return $colArray;
}

//Make a number of common abbreviations for this dataset to try to shorten labels
	function shortenLabel($origLabel){
		$origLabel = str_replace("Foundation","Fdn.",$origLabel);
		$origLabel = str_replace("Center","Ctr.",$origLabel);
		$origLabel = str_replace("Institute","Inst.",$origLabel);
		$origLabel = str_replace("University","U.",$origLabel);
		$origLabel = str_replace("Society","Soc.",$origLabel);
		$origLabel = str_replace("Association","Assn.",$origLabel);
		$origLabel = str_replace("National","Nat.",$origLabel);
		$origLabel = str_replace("International","Intl.",$origLabel);
		$origLabel = str_replace("Corporation","Corp.",$origLabel);
		$origLabel = str_replace("Incorporated","Inc.",$origLabel);
		$origLabel = str_replace("Department","Dept.",$origLabel);
		$origLabel = str_replace("District","Dist.",$origLabel);
		$origLabel = str_replace("Museum","Mus.",$origLabel);
		$origLabel = str_replace("Government","Govt.",$origLabel);
		return $origLabel;
	}

//makes numbers shorter by only keeping significant digits
function formatHumanSuffix($number,$fullLabel){
	$codes = array('1'=>"",'1000'=>'K','1000000'=>'M','1000000000'=>'B','1000000000000'=>'T');
	if ($fullLabel){
		$codes = array('1'=>"",'1000'=>' Thousand','1000000'=>' Million','1000000000'=>'  Billion','1000000000000'=>' Trillion');
	}
	$divisor = 1;
	$suffix = "";
	foreach(array_keys($codes) as $div){
		if ($number > $div){
			$divisor = $div;
			$suffix = $codes[$div];
		} else {break;}
	}
	if ($suffix != ""){
		$number=$number/$divisor;
		$number=number_format($number,1).$suffix;
	}
	return $number;
}

