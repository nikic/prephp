<?php
	function prephp_lineHardcoder($token) {
		return (string)$token->getLine();
	}
	
	function prephp_require_listener($tokenStream, $i) {
		$tokenStream[$i] = new Prephp_Token(
			Prephp_Token::T_STRING,
			'prephp_require',
			$tokenStream[$i]->getLine()
		);
		
		if($tokenStream[$i+1]->is(Prephp_Token::T_OPEN_ROUND)) // require(), so its like a function
			return;
			
		// otherwise insert ( and )
		$i = $tokenStream->skipWhiteSpace($i);
		$tokenStream->insertStreamAt($i,
			array(
				new Prephp_Token(
					Prephp_Token::T_OPEN_ROUND,
					'(',
					$tokenStream[$i]->getLine()
				)
			)
		);
		
		$i = $tokenStream->findNextToken($i, Prephp_Token::T_SEMICOLON);
		$tokenStream->insertStreamAt($i,
			array(
				new Prephp_Token(
					Prephp_Token::T_CLOSE_ROUND,
					')',
					$tokenStream[$i]->getLine()
				)
			)
		);
	}
?>