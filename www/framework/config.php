<?php
#config.php
# Use this file to specify file paths, set framework options, and define available graph setup files. 
# You can also put any functions you'd like to have available in your application code in here.
#
# Note: all paths should be defined relative to the location of the framework_path (which should be the directory containing this file, and, more importantly, request.php
#

$framework_config = array(
	'framework_path' => getcwd(),
	'web_path' => '../', #The *local filesystem* path where your index page is (and all web paths will be relative to)
	'application_path' => './application/', #The location of your application code (and your graph setupfiles)
	'library_path' => './library/', #The location of the Graph Framework library code
	'log_path' => "../log/", # Where should we write logs (needs to be writable by your webserver)
	'cache_path' => "../cache/", # Where should we store graph cache files (needs to be writable by your webserver)
	'cache' => 0, # Should we use stored cache files, or regenerate graphs from scratch every time
	'debug' => 1, # Should we include extra debugging information during graph generation
	'old_graphviz' => 0, #Set this to 1 if graphviz version < 2.24

	#setupfiles needs to be an associative array of the graph setup files your application will use. 
	'setupfiles' => array('crpgraphSetup.php'=>1, 'voteGraphSetup.php'=>1,'committeeGraphSetup.php'=>1, 'FECCanComGraph.php'=>1, 'FoundationGraph.php'=>1, 'ContributionGraph.php'=>1),
)

?>
