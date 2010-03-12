<?php
	function prephp_lambda($tokenStream, $i) {
		/*
		Basic idea of our implementation of lamda functions and closures:
		
		First of all, we avoided to use create_function. Even though create_function
		actually is maybe the most exact implementation of lambda functions and closures
		in php it is said to have quite some memory leaks and other problems. Therefore
		we decided to define the functions as real php functions and replace the function
		call with the name of the function. This name is something like
		prephp_lambda_%{md5(mt_rand())}. In PHP a function can be called by calling a variable
		with the function name. Exactly this behaviour is used. The virtual functions created
		this way are inserted at the beginning of the file, the lambda function was defined in.
		
		To handle closured we use another trick:
		All use()-variables are declared in
		$GLOBALS[%{functionName}][%{var}]
		So, if i want to use($a), it is declared like this:
		$GLOBALS['prephp_lamda_SOMELONGSTRING']['a'] = $a;
		Then, at the beginning of the lambda functions code block the variables are assigned back:
		$a = $GLOBALS['prephp_lamda_SOMELONGSTRING']['a'];
		*/
		
		$funcTok = $i; // function token position
		
		$i = $tokenStream->skipWhiteSpace($i);
		if (!$tokenStream[$i]->is(T_OPEN_ROUND)) {
			return; // normal function
		}
		
		// start of the function definition (including '{')
		$funcDefStart = $tokenStream->findNextToken($i, T_OPEN_CURLY);
		if ($funcDefStart === false) {
			throw new Prephp_Exception("Lambda-Listener: No function definition (couldn't find '{')");
		}
		
		// end of the function definition
		$funcDefEnd = $tokenStream->findComplementaryBracket($funcDefStart);
		
		// function definition stream
		$funcDef = $tokenStream->extractStream($funcDefStart, $funcDefEnd);
		// function arguments and use() definition
		$funcTop = $tokenStream->extractStream($funcTok, $funcDefStart - 1);
		
		$funcName = 'prephp_lambda_'.md5(mt_rand(0, mt_getrandmax()));
		
		// insert lambda function name as string as replacement for the function
		$tokenStream->insertToken($funcTok,
			new Prephp_Token(
				T_CONSTANT_ENCAPSED_STRING,
				"'" . $funcName . "'",
				$funcDef[0]->getLine()
			)
		);
		
		// now we exctract all the variables to use and the function arguments
		// $use will contain an array of this form:
		// array(
		//   array(
		//     0 => 'varname',
		//     1 => false // is ref?
		//   )
		// )
		$use = array();

		// to extract the function args, later
		$funcArgsStart = $funcTop->findNextToken(0, T_OPEN_ROUND);
		$funcArgsEnd = $funcTop->findComplementaryBracket($funcArgsStart);
		
		$i = $funcTop->findNextToken($funcArgsEnd, array(T_STRING, T_USE));
		$numof = count($funcTop);
		// check if there is a use()
		if ($i !== false || $funcTop[$i]->getContent() == 'use') {
			$isRef = false;
			do {
				if ($funcTop[$i]->is(T_AMP)) {
					$isRef = true;
				}
				elseif ($funcTop[$i]->is(T_VARIABLE)) {
					if (!$isRef) {
						$use[] =
						array(
							substr($funcTop->getContent(), 1), // remove $
							false // no ref
						);
					}
					else {
						$use[] =
						array(
							substr($funcTop[$i]->getContent(), 1), // remove $
							true // ref
						);
					}
					
					$isRef = false;
				}
				
				++$i;
			}
			while ($i < $numof && !$funcTop[$i]->is(T_CLOSE_ROUND));
		}
		
		// the function argument stream (including brackets)
		$funcArgs = $funcTop->extractStream($funcArgsStart, $funcArgsEnd);
		
		if (!empty($use)) {
			// now we register the used vars as $GLOBALs
			
			$registerGlobals = array(
				new Prephp_Token(
					T_WHITESPACE,
					"\n",
					-1
				),
				new Prephp_Token(
					T_VARIABLE,
					'$GLOBALS',
					-1
				),
				new Prephp_Token(
					T_OPEN_SQUARE,
					'[',
					-1
				),
				new Prephp_Token(
					T_CONSTANT_ENCAPSED_STRING,
					"'" . $funcName . "'",
					-1
				),
				new Prephp_Token(
					T_CLOSE_SQUARE,
					']',
					-1
				),
				new Prephp_Token(
					T_EQUAL,
					'=',
					-1
				),
				new Prephp_Token(
					T_ARRAY,
					'array',
					-1
				),
				new Prephp_Token(
					T_OPEN_ROUND,
					'(',
					-1
				),
			);
			
			foreach ($use as $u) {
				$registerGlobals[] = new Prephp_Token(
					T_CONSTANT_ENCAPSED_STRING,
					"'" . $u[0] . "'",
					-1
				);
				$registerGlobals[] = new Prephp_Token(
					T_DOUBLE_ARROW,
					'=>',
					-1
				);
				
				// is is ref
				if ($u[1]) {
					$registerGlobals[] = new Prephp_Token(
						T_AMP,
						'&',
						-1
					);
				}
				$registerGlobals[] = new Prephp_Token(
					T_VARIABLE,
					'$' . $u[0],
					-1
				);
				$registerGlobals[] = new Prephp_Token(
					T_COMMA,
					',',
					-1
				);
			}
			
			$registerGlobals[] = new Prephp_Token(
				T_CLOSE_ROUND,
				')',
				-1
			);
			$registerGlobals[] = new Prephp_Token(
				T_SEMICOLON,
				';',
				-1
			);
			
			// insert after last statement before lambda
			$i = $tokenStream->findPreviousEOS($funcTok);
			$tokenStream->insertStream(++$i,
				$registerGlobals
			);
		}
		
		// now insert lambda function code after first T_OPEN_TAG
		$i = $tokenStream->findNextToken(0, T_OPEN_TAG);
		$tokenStream->insertStream(++$i,
			array(
				new Prephp_Token(
					T_WHITESPACE,
					"\n",
					-1
				),
				new Prephp_Token(
					T_FUNCTION,
					'function',
					-1
				),
				new Prephp_Token(
					T_WHITESPACE,
					' ',
					-1
				),
				new Prephp_Token(
					T_STRING,
					$funcName,
					-1
				),
			)
		);
		$tokenStream->insertStream($i+=4,
			$funcArgs
		);
		
		// insert function code (with a newline after it)
		$funcDef->appendStream(array(
			new Prephp_Token(
				T_WHITESPACE,
				"\n",
				-1
			)
		));
		//var_dump($funcDef);
		$tokenStream->insertStream($i+=count($funcArgs),
			$funcDef
		);
		//var_dump($tokenStream);
		
		if (!empty($use)) {
			// redeclare use()-vars
			$redeclareVars = array(
				new Prephp_Token(
					T_WHITESPACE,
					"\n",
					-1
				),
			);
			
			foreach ($use as $u) {
				$redeclareVars = array_merge($redeclareVars, array(
					new Prephp_Token(
						T_VARIABLE,
						'$' . $u[0],
						-1
					),
					new Prephp_Token(
						T_EQUAL,
						'=',
						-1
					),
				));
				
				// is ref?
				if ($u[1]) {
					$redeclareVars[] = new Prephp_Token(
						T_AMP,
						'&',
						-1
					);
				}
				
				$redeclareVars = array_merge($redeclareVars, array(
					new Prephp_Token(
						T_VARIABLE,
						'$GLOBALS',
						-1
					),
					new Prephp_Token(
						T_OPEN_SQUARE,
						'[',
						-1
					),
					new Prephp_Token(
						T_CONSTANT_ENCAPSED_STRING,
						"'" . $funcName . "'",
						-1
					),
					new Prephp_Token(
						T_CLOSE_SQUARE,
						']',
						-1
					),
					new Prephp_Token(
						T_OPEN_SQUARE,
						'[',
						-1
					),
					new Prephp_Token(
						T_CONSTANT_ENCAPSED_STRING,
						"'" . $u[0] . "'",
						-1
					),
					new Prephp_Token(
						T_CLOSE_SQUARE,
						']',
						-1
					),
					new Prephp_Token(
						T_SEMICOLON,
						';',
						-1
					),
					new Prephp_Token(
						T_WHITESPACE,
						"\n",
						-1
					),
				));
			}
			
			++$i; // after the first '{'
			$tokenStream->insertStream($i, $redeclareVars);			
		}
		
		
		/* This is the old code (lambda only):
		
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
		);*/
	}
?>