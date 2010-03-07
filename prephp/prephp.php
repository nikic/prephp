<?php
	error_reporting(E_ALL |E_STRICT);
	
	// prephp.php was called directly without a parameter: DIE!
	if(!isset($_GET['prephp_path']))
		die();
	
	require_once 'config.php';
	
	$prephp_path = $_GET['prephp_path']; unset($_GET['prephp_path']);
	
	// exclude files
	foreach ($prephp_config['exclude'] as $prephp_exclude) {
		if (strpos($prephp_path, $prephp_exclude) === 0) { // exclude this file
			if (file_exists($prephp_config['htaccess_location'].$prephp_path)) {
				require $prephp_config['htaccess_location'].$prephp_path;
			}
			else {
				header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
				echo '404';
			}
			
			die();
		}
	}
	
	require_once 'classes/Core.php';
	
	$core = Prephp_Core::get();
	$core->createCache(
		$prephp_config['htaccess_location'].$prephp_config['source_location'],
		$prephp_config['htaccess_location'].$prephp_config['cache_location']
	);
	
	// Now the Listeners should be registered
	require_once 'listeners.php';
	
	$filename = $core->buildFile($prephp_path);
	
	if($filename === false) {
		header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
		echo '404';
		die();
	}
	else {
		require $filename;
	}	
?>