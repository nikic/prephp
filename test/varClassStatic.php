<pre>
Subj: use a class variable for scope resolution operator
Example: $class::const, $class::$property, $class::method()
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
	$stringWithClass = 'class';
	$method = 'method';
	$stringWithProperty = 'property';
	
	testStrict($class::constant, 'hi', '$class::constant');
	
	testStrict($class::method(), 'hi', '$class::method()');
	testStrict($class::method(2), 'hi', '$class::method(arg)');
	testStrict($class::$method(), 'hi', '$class::$method()');
    testStrict($class::$$$$method(), 'hi', '$class::$$$$method()');
	
	testStrict($class::$property, 'hi', '$class::$property');
	testStrict($class::$inexistantProperty, null, '$class::$inexistantProperty');
    testStrict($class::$$stringWithProperty, 'hi', '$class::$$property');
	// Todo: trigger_error:
	// Access to undeclared static property: Foo::$inexistantProperty in ... on line ...
	
	testStrict($$stringWithClass::$property, 'hi', '$$class::$property');
?>
</pre>