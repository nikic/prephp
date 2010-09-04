<?php
    // Autoloader test
    
    spl_autoload_register(function ($class) {
        echo $class;
    });
        
    spl_autoload_call('Some__N__Strange__N__Class__N__Name');
?>

<?php
    // Resolution tests
    
    namespace A;
    use B\D, C\E as F;
    
    __halt_compiler();
    
    // function calls

    foo();      // first tries to call "foo" defined in namespace "A"
                // then calls global function "foo"

    \foo();     // calls function "foo" defined in global scope

    my\foo();   // calls function "foo" defined in namespace "A\my"

    F();        // first tries to call "F" defined in namespace "A"
                // then calls global function "F"

    // class references

    new B();    // creates object of class "B" defined in namespace "A"
                // if not found, it tries to autoload class "A\B"

    new D();    // using import rules, creates object of class "D" defined in namespace "B"
                // if not found, it tries to autoload class "B\D"

    new F();    // using import rules, creates object of class "E" defined in namespace "C"
                // if not found, it tries to autoload class "C\E"

    new \B();   // creates object of class "B" defined in global scope
                // if not found, it tries to autoload class "B"

    new \D();   // creates object of class "D" defined in global scope
                // if not found, it tries to autoload class "D"

    new \F();   // creates object of class "F" defined in global scope
                // if not found, it tries to autoload class "F"

    // static methods/namespace functions from another namespace

    B\foo();    // calls function "foo" from namespace "A\B"

    B::foo();   // calls method "foo" of class "B" defined in namespace "A"
                // if class "A\B" not found, it tries to autoload class "A\B"

    D::foo();   // using import rules, calls method "foo" of class "D" defined in namespace "B"
                // if class "B\D" not found, it tries to autoload class "B\D"

    \B\foo();   // calls function "foo" from namespace "B"

    \B::foo();  // calls method "foo" of class "B" from global scope
                // if class "B" not found, it tries to autoload class "B"

    // static methods/namespace functions of current namespace

    A\B::foo();   // calls method "foo" of class "B" from namespace "A\A"
                  // if class "A\A\B" not found, it tries to autoload class "A\A\B"

    \A\B::foo();  // calls method "foo" of class "B" from namespace "A\B"
                  // if class "A\B" not found, it tries to autoload class "A\B"
    
    namespace\foo(); // calls function "foo" from namespace "A"
?>