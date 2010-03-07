<?php
	// $core is Prephp_Core
	
	include_once "listeners/coreListeners.php";
	$core->registerTokenCompileListener(Prephp_Token::T_LINE, 'prephp_lineHardcoder');
	
	include_once "listeners/lambda.php";
	$core->registerTokenListener(Prephp_Token::T_FUNCTION, 'prephp_lambda');
?>