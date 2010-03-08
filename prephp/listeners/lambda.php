<?php
	function prephp_lambda($tokenStream, $i) {
		$functionToken = $i;
		
		$i = $tokenStream->skipWhiteSpace($i);
		if(!$tokenStream[$i]->is(Prephp_Token::T_OPEN_ROUND))
			return; // normal function

		
		// now we do some manipulations
		
		// first get the function source code (+ parameters)
		$depth = 0;
		do { // TODO: include findCorrespondingToken in Token_Stream
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
		$functionStream = $tokenStream->extractStream($functionToken, $functionEnd);
		
		$functionName = 'prephp_lambda_'.md5(mt_rand(0, mt_getrandmax())); // TODO: Kinda Prephp_Core::uniqueIdentifier()
		// insert callback as string
		$tokenStream->insertToken($functionToken,
			new Prephp_Token(
				Prephp_Token::T_CONSTANT_ENCAPSED_STRING,
				"'" . $functionName . "'",
				$functionStream[0]->getLine()
			)
		);
		
		// insert function code
		$semicolonToken = $tokenStream->findPreviousToken($functionToken, Prephp_Token::T_SEMICOLON);
		$tokenStream->insertToken(++$semicolonToken,
			new Prephp_Token(
				Prephp_Token::T_WHITESPACE,
				"\n",
				-1
			)
		);
		$tokenStream->insertStream(++$semicolonToken,
			$functionStream
		);
		$tokenStream->insertStream(++$semicolonToken,
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
?>