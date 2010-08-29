<?php
	function prephp_funcRetCall($tokenStream, $iFirstFuncStart) {
		$i = $iFirstFuncStart;
        
		do {
            if ($tokenStream[$i]->is(T_DOLLAR)) {
                $i = $tokenStream->skip($i, T_DOLLAR);
                
                // invalid syntax
                if (!$tokenStream[$i]->is(T_VARIABLE)) {
                    return;
                }
            }
            
            $i = $tokenStream->skipWhitespace($i);
            
            if ($tokenStream[$i]->is(T_OPEN_SQUARE)) {
                $i = $tokenStream->complementaryBracket($i);
                $i = $tokenStream->skipWhitespace($i);
            }
            elseif ($tokenStream[$i]->is(T_OPEN_ROUND)) {
                $i = $tokenStream->complementaryBracket($i);
                $i = $tokenStream->skipWhitespace($i);
            }
        } while ($tokenStream[$i]->is(T_PAAMAYIM_NEKUDOTAYIM, T_OBJECT_OPERATOR) && $i = $tokenStream->skipWhitespace($i));
        
        $iFirstFuncEnd = $tokenStream->skipWhitespace($i, true);
        
        // not a function call
        if (!$tokenStream[$iFirstFuncEnd]->is(T_CLOSE_ROUND)) {
            return;
        }
		
        // not a function return value call
		if (!$tokenStream[$i]->is(T_OPEN_ROUND)) {
			return;
		}
		
		$sSecondFunc = $tokenStream->extract($i, $tokenStream->complementaryBracket($i));
		$sFirstFunc  = $tokenStream->extract($iFirstFuncStart, $iFirstFuncEnd);
		
		$sSecondFunc->extract(0); // remove (
		$sSecondFunc->extract(count($sSecondFunc)-1); // remove )
		
		// now, insert call_user_func
		$tokenStream->insert($iFirstFuncStart,
			array(
				new Prephp_Token(
					T_STRING,
					'call_user_func'
				),
				'(',
					$sFirstFunc,
					count($sSecondFunc)!=0 ? ',' : null,
					$sSecondFunc,
				')',
			)
		);
        
        return true;
	}
?>