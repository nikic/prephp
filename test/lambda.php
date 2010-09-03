<pre>
Subj: Lambda functions and Closures
Example: function() {}, function() use() {}
<hr />
Output:
<?php
    require_once './testUtils.php';
    
    $lambdas = array();
    $lambdas[0] = function() {
        return 'nothing';
    };
    $lambdas[1] = function($arg) {
        return $arg;
    };
    
    $prefix = 'foo';
    $lambdas[2] = function($arg) use($prefix) {
        return $prefix . '_' . $arg;
    };

    $unusedActually = 0;
    $lambdas[3] = function($arg) use(&$prefix, $unusedActually) {
        $prefix = $arg;
    };
    
    testStrict($lambdas[0](), 'nothing', 'without args');
    testStrict($lambdas[1]('foo'), 'foo', 'with arg');
    testStrict($lambdas[2]('foo'), 'foo_foo', 'with use()');
    $lambdas[3]('bar');
    testStrict($prefix, 'bar', 'with use(&)');
?>
</pre>