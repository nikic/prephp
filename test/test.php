html
<?php
	function lambda($callback) {
		return call_user_func($callback);
	}
	
	echo "<pre>";
	
	echo lambda(
		function() {
			return "This is a lambda function";
		}
	);
	echo "\n" . 'You are currently looking at line (should be 14): ' . __LINE__;
	
	echo "\n" . 'This is FILE: ' . __FILE__;
	
	echo "\n" . 'This is the real file location: ' . PREPHP__FILE__;
	
	echo "\n" . 'This is DIR: ' . __DIR__;
	
	echo "\n" . 'This is an included (require) file:' . "\n";
	
	require 'test_included.php';
	
?>
html