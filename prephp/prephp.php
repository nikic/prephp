<?php
	error_reporting(E_ALL |E_STRICT);
	
	require_once 'config.php';
	
	// set $prephp_ variables, unset GETs. (So the scripts don't get prephp stuff)
	$prephp_path = $_GET['prephp_path']; unset($_GET['prephp_path']);
	$prephp_request_uri = $_GET['prephp_request_uri']; unset($_GET['prephp_request_uri']);
	$prephp_http_host = $_GET['prephp_http_host']; unset($_GET['prephp_http_host']);
	$prephp_request_filename = $_GET['prephp_request_filename']; unset($_GET['prephp_request_filename']);
	
	// first of all: exclude excluded files
	foreach ($prephp_config['exclude'] as $prephp_exclude) {
		if (strpos($prephp_path, $prephp_exclude) === 0) { // exclude this file
			if	(file_exists($prephp_config['htaccess_location'].$prephp_path)) {
				require $prephp_config['htaccess_location'].$prephp_path;
			}
			else {
				echo '404'; // Throw 404 error
			}
			
			die(); // No further execution of this script
		}
	}
	
	$prephp_file = $prephp_config['htaccess_location'].$prephp_config['file_location'].$prephp_path;
	
	if(!file_exists($prephp_file)) {
		echo '404'; // TODO: Header, How can we find Apaches error pages?
		
		die();
	}
	
	// Now everything's okay
	
	echo "<pre>";
	
?>