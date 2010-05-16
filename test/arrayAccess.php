<pre>
Subj: Array access after function call
Example: func()[$key]
<hr />
Output:
<?php
	require_once './testUtils.php';
	
	$array = array('hi', 7);
	
	function get() {
		global $array;
		return $array;
	}
	
	$get = 'get';
	
	class Test
	{
		public function get() {
			global $array;
			return $array;
		}
		
		public static function get_static() {
			global $array;
			return $array;
		}
	}
	
	// T1: Test T_STRING call
	testStrict(get()[0], 'hi', 'func()[]');
	
	// T2: Test T_VARIABLE call
	testStrict($get()[1], 7, '$func()[]');
	
	// T3: Test class non-static call
	$test = new Test;
	$testName = 'test';
	testStrict($test->get()[1], 7, '$obj->method()[]'); 
	
	// T4: Test class static call
	testStrict(Test::get_static()[0], 'hi', 'Class::method()[]');
	
	// dollar tests
	testStrict($$get()[1], 7, '$$func()[]');
	testStrict($$testName->$$get()[1], 7, '$$obj->$$method()[]');
	
	$testClass = 'Test';
	$method = 'get_static';
	testStrict($testClass::$method()[1], 7, '$class::$method()[] (depends on varClassStatic)');
?>
</pre>