<?php	
	function prephp_LINE($token) {
		return (string)$token->getLine();
	}
	
	function prephp_FILE($token) {
		return '\'' . Prephp_Core::getInstance()->getCurrent() . '\'';
	}
	
	function prephp_real_FILE($token) {
		if ($token->getContent() == 'PREPHP__FILE__') {
			echo 'compiling real';
			return '__FILE__';
		}
		return false;
	}
	
	function prephp_DIR_simulator($tokenStream, $i) {
		if ($tokenStream[$i]->getContent() == '__DIR__') {
			$tokenStream[$i] = new Prephp_Token(
				T_DIR,
				'__DIR__',
				$tokenStream[$i]->getLine()
			);
		}
	}
	
	function prephp_DIR($tokenStream, $i) {		
		$line = $tokenStream[$i]->getLine();
		
		$tokenStream->extractStream($i, $i); // remove __DIR__
		
		$tokenStream->insertStream($i,
			array(
				new Prephp_Token(
					T_STRING,
					'dirname',
					$line
				),
				new Prephp_Token(
					T_OPEN_ROUND,
					'(',
					$line
				),
				new Prephp_Token(
					T_FILE,
					'__FILE__',
					$line
				),
				new Prephp_Token(
					T_CLOSE_ROUND,
					')',
					$line
				),
			)
		);
	}
	
	function prephp_include($tokenStream, $i) {
		$i = $tokenStream->skipWhitespace($i);
		if ($tokenStream[$i]->is(T_OPEN_ROUND))
			++$i;
		
		$tokenStream->insertStream($i,
			array(
				new Prephp_Token(
					T_STRING,
					'prephp_rt_prepareInclude',
					$tokenStream[$i]->getLine()
				),
				new Prephp_Token(
					T_OPEN_ROUND,
					'(',
					$tokenStream[$i]->getLine()
				),
				new Prephp_Token(
					T_FILE,
					'__FILE__',
					$tokenStream[$i]->getLine()
				),
				new Prephp_Token(
					T_COMMA,
					',',
					$tokenStream[$i]->getLine()
				),
			)
		);
		
		$i = $tokenStream->findNextToken($i, T_SEMICOLON);
		$tokenStream->insertToken($i,
			new Prephp_Token(
				T_CLOSE_ROUND,
				')',
				$tokenStream[$i]->getLine()
			)
		);
	}
?>