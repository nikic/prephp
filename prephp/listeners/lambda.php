<?php
	function prephp_lambda($tokenStream, $i) {
		$functionToken = $i;
		
		$i = $tokenStream->skipWhiteSpace($i);
		if(!$tokenStream[$i]->is(T_OPEN_ROUND))
			return; // normal function

		
		// now we do some manipulations
		
		// first get the function source code (+ parameters)
		$i = $tokenStream->findNextToken($i, T_OPEN_CURLY);
		
		if ($i === false) {
			throw new Exception("Lambda: Lamda Function definition could not be recognized!");
		}
		
		$functionEnd = $tokenStream->findComplementaryBracket($i);
		
		// now we have the whole function between $functionStart and $functionEnd
		$functionStream = $tokenStream->extractStream($functionToken, $functionEnd);
		
		$functionName = 'prephp_lambda_'.md5(mt_rand(0, mt_getrandmax())); // TODO: Kinda Prephp_Core::uniqueIdentifier()
		
		// insert callback as string
		$tokenStream->insertToken($functionToken,
			new Prephp_Token(
				T_CONSTANT_ENCAPSED_STRING,
				"'" . $functionName . "'",
				$functionStream[0]->getLine()
			)
		);
		
		// insert function code
		$eosToken = $tokenStream->findPreviousEOS($functionToken);
		$tokenStream->insertToken(++$eosToken,
			new Prephp_Token(
				Prephp_Token::T_WHITESPACE,
				"\n",
				-1
			)
		);
		$tokenStream->insertStream(++$eosToken,
			$functionStream
		);
		$tokenStream->insertStream(++$eosToken,
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