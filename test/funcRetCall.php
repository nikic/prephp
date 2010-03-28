<pre>
Subj: const outside classes
Example: const foo = 'bar'
<hr />
Expected output:
strlen: 3
substr: o
array(2) {
  [0]=>
  array(2) {
    ["name"]=>
    string(33) "I am surely not a function, am I?"
    ["args"]=>
    array(0) {
    }
  }
  [1]=>
  array(2) {
    ["name"]=>
    string(11) "Me neither!"
    ["args"]=>
    array(2) {
      [0]=>
      string(4) "some"
      [1]=>
      string(4) "args"
    }
  }
}
<hr />
Output:
<?php
	class Func_Log
	{
		private static $errs;
		
		public static function logName($name) {
			self::$errs[] = array('name' => $name);
		}
		
		public static function logArgs() {
			self::$errs[count(self::$errs)-1]['args'] = func_get_args();
		}
		
		public static function dump() {
			var_dump(self::$errs);
		}
	}
	
	function checkFunc($func) {
		if (is_callable($func)) {
			return $func;
		}
		
		Func_Log::logName($func);
		return array('Func_Log', 'logArgs');
	}
	
	echo 'strlen: ' . checkFunc('strlen')('foo') . "\n";
	echo 'substr: ' . checkFunc('substr')('foo', 1, 1) . "\n";
	
	checkFunc('I am surely not a function, am I?')();
	checkFunc('Me neither!')('some', 'args');
	
	Func_Log::dump();

?>
</pre>