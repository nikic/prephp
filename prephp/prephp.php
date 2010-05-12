<?php
	error_reporting(E_ALL | E_STRICT);
	
	if (!isset($_GET['prephp_path']))
		die();

	require_once './classes/Core.php';
	
	class Prephp_Core extends Prephp_Core_Abstract
	{
		
	}
	
	require_once './functions.php';
	
	require Prephp_Core::getInstance()->execute($_GET['prephp_path']);
?>