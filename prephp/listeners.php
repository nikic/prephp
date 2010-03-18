<?php
	// $core is Prephp_Core
	
	include_once "listeners/coreListeners.php";
	$core->registerTokenCompileListener(T_LINE, 'prephp_LINE');
	
	$core->registerTokenCompileListener(T_FILE, 'prephp_FILE');
	$core->registerTokenCompileListener(T_STRING, 'prephp_real_FILE');
	
	if (version_compare(PHP_VERSION, '5.3', '<')) {
		$core->registerTokenListener(T_STRING, 'prephp_DIR_simulator');
	}
	$core->registerTokenListener(T_DIR, 'prephp_DIR');
	
	$core->registerTokenListener(array(T_REQUIRE, T_INCLUDE, T_REQUIRE_ONCE, T_INCLUDE_ONCE), 'prephp_include');
	
	if (version_compare(PHP_VERSION, '5.3', '<')) {
		include_once "listeners/lambda.php";
		$core->registerTokenListener(T_FUNCTION, 'prephp_lambda');
	}
	
	include_once "listeners/arrayAccess.php";
	$core->registerTokenListener(array(T_STRING, T_VARIABLE), 'prephp_arrayAccess');
?>