<pre>
Subj: call function return value
Example: func($some)($args)
<hr />
Expected output:
passed
passed
passed
passed
<hr />
Output:
<?php
	require_once './testUtils.php';
	
	function checkFunc($func) {
		if (!is_callable($func)) { 
		  throw new Exception("Function is not callable!");
		}
		if (!is_array($func)) {
			return $func;
		}
		
		return function() use ($func) {
			return call_user_func_array($func, func_get_args());
		};
	}
	$checkFunc = 'checkFunc';
	
	class Test
	{
		static function checkFunc($func) {
			return checkFunc($func);
		}
		
		static function strlen($str) {
			return strlen($str);
		}
	}
	
	testStrict(checkFunc('strlen')('four'), 4);
	testStrict($checkFunc('strlen')('four'), 4);
	testStrict(Test::checkFunc('strlen')('four'), 4);
	testStrict(Test::checkFunc(array('Test', 'strlen'))('four'), 4);
?>
</pre>