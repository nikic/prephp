<?php
	error_reporting(E_ALL |E_STRICT);
	
	if(!isset($_GET['prephp_path']))
		die();
	
	require_once 'config.php';
	
	$prephp_path = $_GET['prephp_path']; unset($_GET['prephp_path']);
	
	foreach ($prephp_config['exclude'] as $prephp_exclude) {
		if (strpos($prephp_path, $prephp_exclude) === 0) { // exclude this file
			if (file_exists($prephp_config['htaccess_location'].$prephp_path)) {
				require $prephp_config['htaccess_location'].$prephp_path;
			}
			else {
				header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
				echo '404';
			}
			
			die(); // No further execution of this script
		}
	}
	
	require_once 'Core.php';
	
	$core = Prephp_Core::get();
	$core->createCache(
		$prephp_config['htaccess_location'].$prephp_config['source_location'],
		$prephp_config['htaccess_location'].$prephp_config['cache_location']
	);
	
	$filename = $core->buildFile($prephp_path);
	
	if($filename === false) {
		header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
		echo '404';
		die();
	}
	else {
		require $filename;
	}
	
	
	/*{// GATHER different infos
	
	// set $prephp_ variables, unset GETs. (So the scripts don't get prephp stuff)
	$prephp_path = $_GET['prephp_path']; unset($_GET['prephp_path']);
	$prephp_request_uri = $_SERVER['REQUEST_URI'];
	
	// construct server part (http://example.org:110/)
	$prephp_server = 'http';
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
		$prephp_server .= 's';
	}
	$prephp_server .= "://" . $_SERVER['SERVER_NAME'];
	if ($_SERVER['SERVER_PORT'] != '80') {
		$prephp_server .= ':'.$_SERVER['SERVER_PORT'];
	}
	
	$prephp_url = $prephp_server.$prephp_request_uri;
	
	}// GATHER END
	
	foreach ($prephp_config['exclude'] as $prephp_exclude) {
		if (strpos($prephp_path, $prephp_exclude) === 0) { // exclude this file
			if (file_exists($prephp_config['htaccess_location'].$prephp_path)) {
				require $prephp_config['htaccess_location'].$prephp_path;
			}
			else {
				header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
				echo '404';
			}
			
			die(); // No further execution of this script
		}
	}
	
	$prephp_file = $prephp_config['htaccess_location'].$prephp_config['file_location'].$prephp_path;
	
	if(!file_exists($prephp_file)) {
		header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
		echo '404';
		die();
	}
	
	// Now everything's okay*/
	
?>