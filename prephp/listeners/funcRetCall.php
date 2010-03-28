<?php
	function prephp_funcRetCall($tokenStream, $i) {
		$firstFuncStart = $i;
		
		$i = $tokenStream->skipWhitespace($i);
		
		if (!$tokenStream[$i]->is(T_OPEN_ROUND)) {
			return; //not a function call
		}
		
		$i = $tokenStream->findComplementaryBracket($i);
		
		if ($i === false) {
			throw new Prephp_Exception("funcRetCall: Could not find expected ')'");
		}
		
		$firstFuncEnd = $i;
		
		$i = $tokenStream->skipWhitespace($i);
		
		if (!$tokenStream[$i]->is(T_OPEN_ROUND)) {
			return; // not a function return value call
		}
		
		$secondFuncStart = $i;
		
		$i = $tokenStream->findComplementaryBracket($i);
		
		if ($i === false) {
			throw new Prephp_Exception("funcRetCall: Could not find expected ')'");
		}
		
		$secondFuncEnd = $i;
		
		$secondFunc = $tokenStream->extractStream($secondFuncStart, $secondFuncEnd);
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