<pre>
Subj: Lambda functions and Closured
Example: function() {}, function() use() {}
<hr />
Expected output:
10
10
10
10
10
10
10
10
10
10
This is a lambda function
<hr />
Output:
<?php
	$nums = array();
	for ($i = 0; $i<10; ++$i) {
		$nums[] = $i;
	}
	
	$add = 10;
	array_walk(
		$nums,
		function(&$val) use(&$add) {
			$val += $add;
			--$add;
		}
	);
	
	array_walk(
		$nums,
		function($val) {
			echo $val . "\n";
		}
	);
	
	function lambda($callback) {
		return call_user_func($callback);
	}
	
	echo lambda(
		function() {
			return "This is a lambda function";
		}
	);
?>
</pre>