<?php
	// $core is Prephp_Core
	
	include_once "listeners/coreListeners.php";
	$core->registerTokenCompileListener(Prephp_Token::T_LINE, 'prephp_LINE');
	
	$core->registerTokenCompileListener(Prephp_Token::T_FILE, 'prephp_FILE');
	$core->registerTokenCompileListener(Prephp_Token::T_STRING, 'prephp_real_FILE');
	
	$core->registerTokenListener(Prephp_Token::T_STRING, 'prephp_DIR_simulator');
	$core->registerTokenListener(Prephp_Token::T_DIR, 'prephp_DIR');
	
	$core->registerTokenListener(Prephp_Token::T_REQUIRE, 'prephp_include');
	
	
	include_once "listeners/lambda.php";
	$core->registerTokenListener(Prephp_Token::T_FUNCTION, 'prephp_lambda');
?>