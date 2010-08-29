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
    
    $obj = new Test;
	$objName = 'obj';
    
    $get = 'get';
    $get_static = 'get_static';
    $Test = 'Test';
	
	testStrict(get()[0],                'hi', 'func()[]');
    testStrict(Test::get_static()[0],   'hi', 'Class::method()[]');
    testStrict($Test::$get_static()[0], 'hi', '$class::$method()[] (depends on varClassStatic)');
    
	testStrict($get()[1],               7,    '$func()[]');
	testStrict($$get()[1],              7,    '$$func()[]');
    
    testStrict($obj->get()[1],          7,    '$obj->method()[]'); 
	testStrict($$objName->$$get()[1],   7,    '$$obj->$$method()[]');
    
    class Very {
        public static function long() {
            return new self;
        }
        
        public function call() {
            global $array;
            return $array;
        }
    }
    testStrict(Very::long()->call()[0], 'hi', 'complex resolution');
?>
</pre>