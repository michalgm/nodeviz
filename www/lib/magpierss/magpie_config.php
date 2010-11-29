<?php 
	$magpieCacheDir = './cache/magpie/';
	//make sure the cache dir exists
	if(!is_dir($magpieCacheDir)){
  		mkdir($magpieCacheDir,0777);
  		chmod($magpieCacheDir,0777);
 	 }
	define('MAGPIE_INPUT_ENCODING', 'UTF-8');
	define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');
	define('MAGPIE_CACHE_DIR', $magpieCacheDir);
	require_once('rss_fetch.inc');
?>