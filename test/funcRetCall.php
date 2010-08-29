<pre>
Subj: call function return value
Example: func($some)($args)
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
			$args = func_get_args();
			return call_user_func_array($func, $args);
		};
	}
	$checkFunc = 'checkFunc';
	
	class Test
	{
		public static function checkFunc($func) {
			return checkFunc($func);
		}
		
		public static function strlen($str) {
			return strlen($str);
		}
        
        public static function instance() {
            return new self;
        }
        
        public function strlenFunction() {
            return 'strlen';
        }
	}
    
    function func() {
        return array('func');
    }
    
    function test() {
        return 'test';
    }
	
	testStrict(checkFunc('strlen')('four'), 4, 'func()()');
	testStrict($checkFunc('strlen')('four'), 4, '$func()()');
	testStrict(Test::checkFunc('strlen')('four'), 4, 'Class::method()()');
	testStrict(Test::checkFunc(array('Test', 'strlen'))('four'), 4, 'Class::method(array(...))()');
	
	testStrict($$checkFunc('strlen')('four'), 4, '$$func()()');
	testStrict(Test::$$checkFunc('strlen')('four'), 4, 'Class::$$method()()');
    
    testStrict(Test::instance()->strlenFunction()('four'), 4, 'complex resolution');
    
    testStrict(func()[0]()[0](), array('func'), 'func()[0]()[0]() (depends on arrayAccess)');
    testStrict(test()()(), 'test', 'func()()()');
?>
</pre>