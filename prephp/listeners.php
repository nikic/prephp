<?php
	// $core is Prephp_Core
	
	require_once "listeners/coreListeners.php";
	$core->registerTokenCompileListener(Prephp_Token::T_LINE, 'prephp_lineHardcoder');
?>