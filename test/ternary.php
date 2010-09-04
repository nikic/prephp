<pre>
Subj: Ternary Operator without middle part
Example: expr1 ?: expr2;
<hr />
Output:
<?php
    require_once './testUtils.php';
    
    function incr() {
        static $a = 0;
        return ++$a;
    }
    
    testStrict(7 ?: 8, 7, 'expr ?: expr');
    testStrict(incr() ?: 7, 1, 'func() ?: expr');
?>
</pre>