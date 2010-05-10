<?php	
	function prephp_LINE($token) {
		return (string)$token->getLine();
	}
	
	function prephp_FILE($token) {
		return '\'' . Prephp_Core::getInstance()->getCurrent() . '\'';
	}
	
	function prephp_real_FILE($token) {
		if ($token->getContent() == 'PREPHP__FILE__') {
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
		$tokenStream->extractStream($i, $i); // remove __DIR__
		
		$tokenStream->insertStream($i,
			array(
				new Prephp_Token(
					T_STRING,
					'dirname'
				),
				'(',
					new Prephp_Token(
						T_FILE,
						'__FILE__'
					),
				')',
			)
		);
	}
	
	function prephp_include($tokenStream, $i) {
		$i = $tokenStream->skipWhitespace($i);
		if ($tokenStream[$i]->is(T_OPEN_ROUND)) {
			$i = $tokenStream[$i]->skipWhitespace($i);
		}
		
		$tokenStream->insertStream($i,
			array(
				new Prephp_Token(
					T_STRING,
					'prephp_rt_prepareInclude'
				),
				'(',
					new Prephp_Token(
						T_FILE,
						'__FILE__',
						$tokenStream[$i]->getLine()
					),
					',',
			)
		);
		
		$tokenStream->insertToken($tokenStream->findEOS($i),
				')'
		);
	}
?>