<?php
	error_reporting(E_ALL | E_STRICT);
	
	echo __DIR__ . "\n";
	
	define('__DIR__', dirname(__FILE__));
	
	echo __DIR__;
	
	// Was sagt uns das? Wir können magische Konstanten neu definieren!
?>