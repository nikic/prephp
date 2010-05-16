<?php
	function prephp_funcRetCall($tokenStream, $iFirstFuncStart) {
		$i = $iFirstFuncStart;
		$numof = count($tokenStream);
		
		// skip dollars
		for (; $i < $numof && $tokenStream[$i]->is(T_DOLLAR); ++$i);
		
		$i = $tokenStream->skipWhitespace($i);
		if ($tokenStream[$i]->is(array(T_PAAMAYIM_NEKUDOTAYIM, T_OBJECT_OPERATOR))) {
			$i = $tokenStream->skipWhitespace($i);
			
			// skip dollars
			for (; $i < $numof && $tokenStream[$i]->is(T_DOLLAR); ++$i);
			
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
		
		$iFirstFuncEnd = $tokenStream->findComplementaryBracket($i);
		$iSecondFuncStart = $tokenStream->skipWhitespace($iFirstFuncEnd);
		
		if (!$tokenStream[$iSecondFuncStart]->is(T_OPEN_ROUND)) {
			return; // not a function return value call
		}
		
		$sSecondFunc = $tokenStream->extractStream($iSecondFuncStart, $tokenStream->findComplementaryBracket($iSecondFuncStart));
		$sFirstFunc = $tokenStream->extractStream($iFirstFuncStart, $iFirstFuncEnd);
		
		$sSecondFunc->extractStream(0, 0); // remove (
		$sSecondFunc->extractStream(count($sSecondFunc)-1, count($sSecondFunc)-1); // remove )
		
		// now, insert call_user_func
		$tokenStream->insertStream($iFirstFuncStart,
			array(
				new Prephp_Token(
					T_STRING,
					'call_user_func'
				),
				'(',
					$sFirstFunc,
					count($sSecondFunc)!=0?',':null,
					$sSecondFunc,
				')',
			)
		);
	}
?>