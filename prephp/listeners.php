<?php
	// $core is Prephp_Core
	
	include_once "listeners/coreListeners.php";
	$core->registerTokenCompileListener(Prephp_Token::T_LINE, 'prephp_lineHardcoder');
	$core->registerTokenListener(Prephp_Token::T_REQUIRE, 'prephp_require_listener');
	
	include_once "listeners/lambda.php";
	$core->registerTokenListener(Prephp_Token::T_FUNCTION, 'prephp_lambda');
?>