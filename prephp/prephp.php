<?php
	error_reporting(E_ALL |E_STRICT);
	
	// prephp.php was called directly without a parameter: DIE!
	if(!isset($_GET['prephp_path']))
		die();
	
	require_once 'config.php';
	
	// first prepare some vars
	define('prephp_base_dir', realpath($prephp_config['htaccess_location']) . DIRECTORY_SEPARATOR);
	define('prephp_source_dir', realpath(prephp_base_dir . $prephp_config['source_location']) . DIRECTORY_SEPARATOR);
	define('prephp_cache_dir', realpath(prephp_base_dir . $prephp_config['cache_location']) . DIRECTORY_SEPARATOR);
	
	if (prephp_base_dir === false) {
		die('PrePHP: Could not realpath()-resolve htaccess_location.');
	}
	if (prephp_source_dir === false) {
		die('PrePHP: Could not realpath()-resolve source_location.
		Please check whether the source directory exists.
		Your specified source dir: "'.$prephp_config['source_location'].'"');
	}
	if (prephp_source_dir === false) {
		die('PrePHP: Could not realpath()-resolve cache_location.
		Please check whether the cache directory exists.
		Your specified cache dir: "'.$prephp_config['cache_location'].'"');
	}
	
	$prephp_path = $_GET['prephp_path']; unset($_GET['prephp_path']);
	
	// exclude files
	foreach ($prephp_config['exclude'] as $prephp_exclude) {
		if (strpos($prephp_path, $prephp_exclude) === 0) { // exclude this file
			if (file_exists(prephp_source_dir.$prephp_path)) {
				require prephp_source_dir.$prephp_path;
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
		prephp_source_dir,
		prephp_cache_dir
	);
	
	// Now the listeners should be registered
	require_once 'listeners.php';
	
	$filename = $core->buildFile($prephp_path);
	
	if ($filename === false) {
		header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
		echo '404';
		die();
	}
	else {
		require_once 'functions.php'; // this file defines some functions used run-time
		require $filename;
	}
?>