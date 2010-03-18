<pre>
Subj: Array access after function call test.
Example: func()[$num]
<hr />
Expected output:
world
world
<hr />
Output:
<?php
	$hi = 'world';
	
	echo get_defined_vars()['hi'];
	
	echo "\n";
	
	$function = 'get_defined_vars';
	$var = 'hi';
	
	echo $function()[$var];
?>
</pre>