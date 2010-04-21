<?php
	error_reporting(E_ALL |E_STRICT);
	
	if(!isset($_GET['prephp_path']))
		die();

	require_once 'classes/Core.php';
	
	class Prephp_Core extends Prephp_Core_Abstract
	{
		
	}
	
	$core = Prephp_Core::getInstance();
	
	require_once 'functions.php';
	
	if ($filename = $core->process($_GET['prephp_path'])) {
		require $filename;
	}
	else {
		$core->http_404();
	}
?>