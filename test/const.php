<pre>
Subj: const outside classes
Example: const foo = 'bar'
<hr />
Expected output:
Output:
one: 1
two: 2
three: three
four: four
five: 5
six: six
seven: 7
<hr />
Output:
<?php
	const one = 1;
	const two = 2.0;
	const three = 'three';
	const four = "four";
	
	const	five	= 5,
			six		= 'six',
			seven	= true;
	
	echo 'one: '.one . "\n";
	echo 'two: '.two . "\n";
	echo 'three: '.three . "\n";
	echo 'four: '.four . "\n";
	echo 'five: '.five . "\n";
	echo 'six: '.six . "\n";
	echo 'seven: '.(6+seven) . "\n";
?>
</pre>