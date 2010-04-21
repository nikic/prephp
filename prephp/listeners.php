<?php
	$p = $this->preprocessor;
	
	// PHP 5.3 simulators
	if (version_compare(PHP_VERSION, '5.3', '<')) {
		$p->registerStreamManipulator(T_STRING, 'prephp_DIR_simulator');
		
		include_once "listeners/lambda.php";
		$p->registerStreamManipulator(T_FUNCTION, 'prephp_lambda');
		
		include_once "listeners/const.php";
		$p->registerStreamManipulator(T_CONST, 'prephp_const');
	}
	
	// PHP Extenders
	include_once "listeners/arrayAccess.php";
	$p->registerStreamManipulator(array(T_STRING, T_VARIABLE), 'prephp_arrayAccess');
	
	include_once "listeners/funcRetCall.php";
	$p->registerStreamManipulator(array(T_STRING, T_VARIABLE), 'prephp_funcRetCall');
	
	// Core Listeners
	include_once "listeners/coreListeners.php";
	$p->registerTokenCompiler(T_LINE, 'prephp_LINE');
	
	$p->registerStreamManipulator(array(T_REQUIRE, T_INCLUDE, T_REQUIRE_ONCE, T_INCLUDE_ONCE), 'prephp_include');
	$p->registerStreamManipulator(T_DIR, 'prephp_DIR');
	
	$p->registerTokenCompiler(T_FILE, 'prephp_FILE');
	$p->registerTokenCompiler(T_STRING, 'prephp_real_FILE');
?>