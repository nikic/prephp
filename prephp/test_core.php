<?php
	error_reporting(E_ALL | E_STRICT);
	
	require_once 'Core.php';
	
	function lineHardcoder($token) {
		return (string)$token->getLine();
	}
	
	function lambda($tokenStream, $i) {
		$functionToken = $i;
		
		$i = $tokenStream->skipWhiteSpace($i);
		if(!$tokenStream[$i]->is(Prephp_Token::T_OPEN_ROUND))
			return; // normal function

		
		// now we do some manipulations
		
		// first get the function source code (+ parameters)
		$depth = 0;
		do {
			$i_open = $tokenStream->findNextToken($i, Prephp_Token::T_OPEN_CURLY);
			$i_close = $tokenStream->findNextToken($i, Prephp_Token::T_CLOSE_CURLY);
			
			if($i_open == false && $i_close == false) {
				throw new Exception('Open and Close Curly not matching'); // Prephp_exception pls
			}
			
			if($i_open > $i_close) {
				++$depth;
				$i = $i_open;
			}
			else {
				--$depth;
				$i = $i_close;
			}
		}
		while($depth > 0);
		
		$functionEnd = $i;
		// now we have the whole function between $functionStart and $functionEnd
		$functionStream = $tokenStream->splice($functionToken, $functionEnd);
		
		$functionName = 'prephp_lambda_'.md5(mt_rand(0, mt_getrandmax()));
		// insert callback as string
		$tokenStream->insertStreamAt($functionToken,
			array(
				new Prephp_Token(
					Prephp_Token::T_CONSTANT_ENCAPSED_STRING,
					"'" . $functionName . "'",
					$functionStream[0]->getLine()
				),
			)
		);
		
		// insert function code
		$semicolonToken = $tokenStream->findPreviousToken($functionToken, Prephp_Token::T_SEMICOLON);
		$tokenStream->insertStreamAt($semicolonToken + 1,
			$functionStream
		);
		$tokenStream->insertStreamAt($semicolonToken + 2,
			array(
				new Prephp_Token(
					Prephp_Token::T_WHITESPACE,
					" ",
					-1
				),
				new Prephp_Token(
					Prephp_Token::T_STRING,
					$functionName ,
					-1
				),
			)
		);
		
	}
	
	$source = <<<'FOO'
html
<?php
	function lambda($callback) {
		return call_user_func($callback);
	}
	
	echo __LINE__;
	echo lambda(
		function() {
			return 1;
		}
	);
?>
html
FOO;
	
	$core = new Prephp_Core(token_get_all($source));
	
	$core->addTokenCompileListener(Prephp_Token::T_LINE, 'lineHardcoder');
	$core->addTokenListener(Prephp_Token::T_FUNCTION, 'lambda');
	
	echo "<pre>";
	$out = $core->compile();
	echo $out;
	file_put_contents('test_core_cache.php', $out);
?>