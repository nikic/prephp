<?php
	function prephp_arrayAccess($tokenStream, $iCallStart) {
        $i = $iCallStart;
        
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

		$iCallEnd = $tokenStream->skipWhitespace($i, true);
        
        // not a function call
        if (!$tokenStream[$iCallEnd]->is(T_CLOSE_ROUND)) {
            return;
        }

		// not an array access ("[")
		if (!$tokenStream[$i]->is(T_OPEN_SQUARE)) {
			return;
		}
		
		$sArrayAccess = $tokenStream->extract($i, $tokenStream->complementaryBracket($i));
		$sArrayAccess->extract(0); // remove "["
		$sArrayAccess->extract(count($sArrayAccess)-1); // remove "]"
		
		$sCall = $tokenStream->extract($iCallStart, $iCallEnd);
		
		// now insert prephp_rt_arrayAccess()
		$tokenStream->insert($iCallStart,
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
        
        return true;
	}
?>