<pre>
Subj:
Example:
<hr />
Output:
<?php
	require_once './testUtils.php';
	
	class Foo
	{
		const constant = 'hi';
		
		static $property = 'hi';
		
		static function method($nix = '') {
			return 'hi';
		}
	}
	
	$class = 'Foo';
	$method = 'method';
	$property = 'property';
	
	testStrict($class::constant, 'hi', '$class::constant');
	
	testStrict($class::method(), 'hi', '$class::method()');
	testStrict($class::method(2), 'hi', '$class::method(args)');
	testStrict($class::$method(), 'hi', '$class::$method()');
	
	testStrict($class::$property, 'hi', '$class::$property');
	testStrict($class::$inexistantProperty, null, '$class::$inexistantProperty');
	// ToDo: Throw error:
	// Fatal error:  Access to undeclared static property: Foo::$inexistantProperty in D:\xampp\htdocs\prephp\cache\test\varClassStatic.php on line 31
	
	testStrict($class::$$$$method(), 'hi', '$class::$$$$method()');
	testStrict($class::$$property, 'hi', '$class::$$property');
?>
</pre>