<?php
	function prephp_funcRetCall($tokenStream, $i) {
		$firstFuncStart = $i;
		
		$i = $tokenStream->skipWhitespace($i);
		if ($tokenStream[$i]->is(array(T_PAAMAYIM_NEKUDOTAYIM, T_OBJECT_OPERATOR))) {
			$i = $tokenStream->skipWhitespace($i);
			
			// the following cannot occur if syntax's correct, actually
			if (!$tokenStream[$i]->is(array(T_STRING, T_VARIABLE))) {
				return;
			}
			
			$i = $tokenStream->skipWhitespace($i);
		}

		// not a function call
		if (!$tokenStream[$i]->is(T_OPEN_ROUND)) {
			return;
		}
		
		$firstFuncEnd = $i = $tokenStream->findComplementaryBracket($i);
		
		$i = $tokenStream->skipWhitespace($i);
		
		if (!$tokenStream[$i]->is(T_OPEN_ROUND)) {
			return; // not a function return value call
		}
		
		$secondFunc = $tokenStream->extractStream($i, $tokenStream->findComplementaryBracket($i));
		$firstFunc = $tokenStream->extractStream($firstFuncStart, $firstFuncEnd);
		
		// insert now
		
		$i = $firstFuncStart;
		
		$tokenStream->insertStream($i,
			array(
				new Prephp_Token(
					T_STRING,
					'call_user_func',
					-1
				),
				new Prephp_Token(
					T_OPEN_ROUND,
					'(',
					-1
				),
			)
		);
		
		$tokenStream->insertStream($i+=2,
			$firstFunc
		);
		
		$i += count($firstFunc);
		
		// remove ( and )
		$secondFunc->extractStream(0, 0); // remove (
		$secondFunc->extractStream(count($secondFunc)-1, count($secondFunc)-1); // remove )
		
		if (count($secondFunc) != 0) {
			$tokenStream->insertToken($i,
				new Prephp_Token(
					'T_COMMA',
					',',
					-1
				)
			);
			
			$tokenStream->insertStream(++$i,
				$secondFunc
			);
		}
		
		$tokenStream->insertToken($i+=count($secondFunc),
			new Prephp_Token(
				'T_CLOSE_ROUND',
				')',
				-1
			)
		);
	}
?>