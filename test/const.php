<pre>
Subj: const outside classes
Example: const foo = 'bar'
<hr />
Expected output:
one: 1
two: 2
three: three
four: four
five: 5
six: six
seven: 7
eight: 8
nine: 9
<hr />
Output:
<?php
    const one = 1;
    const two = 2.0;
    const three = 'three';
    const four = "four";
    
    const five	= 5,
          six	= 'six',
          seven	= true,
          eight = one;
    
    echo 'one: '   . one       . "\n";
    echo 'two: '   . two       . "\n";
    echo 'three: ' . three     . "\n";
    echo 'four: '  . four      . "\n";
    echo 'five: '  . five      . "\n";
    echo 'six: '   . six       . "\n";
    echo 'seven: ' . (6+seven) . "\n";
    echo 'eight: ' . (7+eight) . "\n";
    
    class Foo
    {
        const Bar = 'nix'; // shoudn't be replaced
    }
    
    class Bar
    {
    }
    
    const nine = 9;
    echo 'nine: ' . nine . "\n";
?>
</pre>