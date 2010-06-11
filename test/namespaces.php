<?php
	namespace {                   // {
		class HalloWorld {}       // class HalloWorld {}
		
		namespace\strlen('');     // strlen('');
	}                             // }
	
	namespace Foo\Bar\Higher {    // {
		class HalloWorld {}       // class Foo__N__Bar__N__Higher__N__HalloWorld {}
	}                             // }
	
	namespace Foo\Bar {           // {
		use \HalloWorld;          // 
		
		class HalloWorld {}       // class Foo__N__Bar__N__HalloWorld {}
		
		new namespace\HalloWorld; // new Foo__N__Bar__N__HalloWorld;
		new HalloWorld;           // new HalloWorld;
		new Higher\HalloWorld;    // new Foo__N__Bar__N__Higher__N__HalloWorld;
		
		strlen('');               // call_user_func(prephp_rt_checkFunction('Foo__N__Bar__N__strlen','strlen'),'');
	}                             // }
?>