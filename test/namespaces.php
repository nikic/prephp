<?php
    namespace {
        class HalloWorld {}    // class HalloWorld {}
        
        namespace\strlen('');  // strlen('');
        
        spl_autoload_register(function ($class) {
            echo $class;
        });
        
        spl_autoload_call('Some__N__Strange__N__Class__N__Name');
    }
    
    namespace Foo\Bar\Higher {
        class Hallo {}         // class Foo__N__Bar__N__Higher__N__Hallo {}
    }
    
    namespace Foo\Bar {
        use \HalloWorld;       // 
        
        class Hallo {}         // class Foo__N__Bar__N__Hallo {}
        
        new namespace\Hallo;   // new Foo__N__Bar__N__Hallo;
        new HalloWorld;        // new HalloWorld;
        new Higher\Hallo;      // new Foo__N__Bar__N__Higher__N__Hallo;
        
        strlen('');            // call_user_func(prephp_rt_checkFunction('Foo__N__Bar__N__strlen','strlen'),'');
    }
?>