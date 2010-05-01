<?php
	function testStrict($check, $compare = true) {
		if ($check === $compare) {
			echo 'passed', "\n";
			return;
		}
		
		echo 'failed (expected "', $compare, '", got "', $check , '")', "\n";
	}
?>