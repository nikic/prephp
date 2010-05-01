<?php
	$p = $this->preprocessor;
	
	require_once 'listeners/coreListeners.php';
	require_once 'listeners/arrayAccess.php';
	require_once 'listeners/funcRetCall.php';
	
	
	// PHP 5.3 simulators
	if (version_compare(PHP_VERSION, '5.3', '<')) {
		require_once 'listeners/lambda.php';
		require_once 'listeners/const.php';
		require_once 'listeners/nowdoc.php';
		
		$p->registerStreamManipulator(T_STRING, 'prephp_DIR_simulator');
		
		$p->registerStreamManipulator(T_FUNCTION, 'prephp_lambda');
		$p->registerStreamManipulator(T_CONST, 'prephp_const');
		
		$p->registerSourcePreparator('prephp_nowdoc');
	}
	
	// PHP Extenders
	
	$p->registerStreamManipulator(array(T_STRING, T_VARIABLE), 'prephp_arrayAccess');
	$p->registerStreamManipulator(array(T_STRING, T_VARIABLE), 'prephp_funcRetCall');
	
	// Core Listeners
	$p->registerTokenCompiler(T_LINE, 'prephp_LINE');
	
	$p->registerStreamManipulator(array(T_REQUIRE, T_INCLUDE, T_REQUIRE_ONCE, T_INCLUDE_ONCE), 'prephp_include');
	$p->registerStreamManipulator(T_DIR, 'prephp_DIR');
	
	$p->registerTokenCompiler(T_FILE, 'prephp_FILE');
	$p->registerTokenCompiler(T_STRING, 'prephp_real_FILE');
?>