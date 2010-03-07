<?php
	require_once 'Core.php';
	
	function lineHardcoder(&$tokenStream, $i) {
		return $tokenStream[$i]->getLine();
	}
	
	$source = <<<'FOO'
html
<?php
	echo __LINE__;
?>
html
FOO;
	
	$core = new Prephp_Core(token_get_all($source));
	
	$core->addTokenCompileListener(Prephp_Token::T_LINE, 'lineHardcoder');
	
	echo "<pre>";
	echo $core->compile();
?>