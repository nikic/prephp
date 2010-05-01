<pre>
Subj: Array access after function call
Example: func()[$key]
<hr />
Expected output:
passed
passed
passed
passed
<hr />
Output:
<?php
	require './testUtils.php';
	
	$passed = array('hi', 7);
	
	function get() {
		global $passed;
		return $passed;
	}
	
	$get = 'get';
	
	class Test
	{
		public function get() {
			global $passed;
			return $passed;
		}
		
		public static function get_static() {
			global $passed;
			return $passed;
		}
	}
	
	// T1: Test T_STRING call
	testStrict(get()[0], 'hi');
	
	// T2: Test T_VARIABLE call
	testStrict($get()[1], 7);
	
	// T3: Test class non-static call
	$test = new Test;
	testStrict($test->get()[(((1)))], 7); 
	
	// T4: Test class static call
	testStrict(Test::get_static()[0], 'hi');
?>
</pre>