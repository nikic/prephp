<?php
	// $core is Prephp_Core
	
	// PHP 5.3 simulators
	if (version_compare(PHP_VERSION, '5.3', '<')) {
		$core->registerTokenListener(T_STRING, 'prephp_DIR_simulator');
		
		include_once "listeners/lambda.php";
		$core->registerTokenListener(T_FUNCTION, 'prephp_lambda');
		
		include_once "listeners/const.php";
		$core->registerTokenListener(T_CONST, 'prephp_const');
	}
	
	// PHP Extenders
	include_once "listeners/arrayAccess.php";
	$core->registerTokenListener(array(T_STRING, T_VARIABLE), 'prephp_arrayAccess');
	
	include_once "listeners/funcRetCall.php";
	$core->registerTokenListener(array(T_STRING, T_VARIABLE), 'prephp_funcRetCall');
	
	// Core Listeners
	include_once "listeners/coreListeners.php";
	$core->registerTokenCompileListener(T_LINE, 'prephp_LINE');
	
	$core->registerTokenListener(array(T_REQUIRE, T_INCLUDE, T_REQUIRE_ONCE, T_INCLUDE_ONCE), 'prephp_include');
	$core->registerTokenCompileListener(T_FILE, 'prephp_FILE');
	$core->registerTokenCompileListener(T_STRING, 'prephp_real_FILE');
	$core->registerTokenListener(T_DIR, 'prephp_DIR');
?>