<pre>
Subj: Core Components.
Example: __FILE__, __DIR__, PREPHP__FILE__, __LINE__, include
<hr />
Expected output (maybe with different paths for source and cache):
You are currently looking at line: 16
This is FILE: D:\xampp\htdocs\prephp\test\coreComponents.php
This is the real file location: D:\xampp\htdocs\prephp\cache\test\coreComponents.php
This is DIR: D:\xampp\htdocs\prephp\test
This is an included (require_once) file:

INCLUDED: D:\xampp\htdocs\prephp\test\coreComponents_include.php
<hr />
Output:
<?php
    echo "\n" . 'You are currently looking at line: ' . __LINE__;
    
    echo "\n" . 'This is FILE: ' . __FILE__;
    
    echo "\n" . 'This is the real file location: ' . PREPHP__FILE__;
    
    echo "\n" . 'This is DIR: ' . __DIR__;
    
    echo "\n" . 'This is an included (require_once) file:' . "\n";
    
    require_once 'coreComponents_include.php';
?>