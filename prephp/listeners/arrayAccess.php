<?php
	function prephp_arrayAccess($tokenStream, $iCallStart) {
		$i = $tokenStream->skipWhitespace($iCallStart);
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
		
		$iCallEnd = $tokenStream->findComplementaryBracket($i);
		
		$iArrayAccess = $tokenStream->skipWhitespace($iCallEnd);

		// not an array access ("[")
		if (!$tokenStream[$iArrayAccess]->is(T_OPEN_SQUARE)) {
			return;
		}
		
		$sArrayAccess = $tokenStream->extractStream($iArrayAccess, $tokenStream->findComplementaryBracket($iArrayAccess));
		$sArrayAccess->extractStream(0, 0); // remove "["
		$sArrayAccess->extractStream(count($sArrayAccess)-1, count($sArrayAccess)-1); // remove "]"
		
		$sCall = $tokenStream->extractStream($iCallStart, $iCallEnd);
		
		// now insert prephp_rt_arrayAccess()
		$tokenStream->insertStream($iCallStart,
			array(
				new Prephp_Token(
					T_STRING,
					'prephp_rt_arrayAccess'
				),
				'(',
					$sCall,
					',',
					$sArrayAccess,
				')',
			)
		);
	}
?>