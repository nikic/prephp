<?php
    function testStrict($check, $compare = true, $title = 'unnamed test') {
        if ($check === $compare) {
            echo 'passed ', $title, "\n";
            return;
        }
        
        echo 'failed (expected "', $compare, '", got "', $check , '") ', $title, "\n";
    }
?>