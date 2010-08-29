<?php
	define('C_EXP_NAME', 1);
	define('C_EXP_EQU', 2);
	define('C_EXP_VAL', 3);
	define('C_EXP_COMMA', 4);
	
	function prephp_const($tokenStream, $iConstKeyword) {
		// check that we're not inside a class
		// find previous { on same level
		$iLastOpen = $iConstKeyword;
		while ($iLastOpen = $tokenStream->find($iLastOpen, T_OPEN_CURLY, true)) {
			if ($tokenStream->complementaryBracket($iLastOpen) < $iConstKeyword) {
				continue;
			}

			// no class before { => not in class
			if (false === $iLastClass = $tokenStream->find($iLastOpen, T_CLASS, true)) {
				break;
			}
			
			// there is another { between class and our { => not in class
			if ($tokenStream->find($iLastClass, T_OPEN_CURLY) != $iLastOpen) {
				break;
			}
			
			return; // => in class
		}

		$constants = array();
		
		$iEOS = $tokenStream->findEOS($iConstKeyword);
		
		$name = '';
		$expecting = C_EXP_NAME;
		$i = $iConstKeyword;
		while (++$i < $iEOS) {
			if ($tokenStream[$i]->is(T_WHITESPACE)) {
				continue;
			}
			
			if ($expecting === C_EXP_NAME && $tokenStream[$i]->is(T_STRING)) {
                $name = $tokenStream[$i]->content;
                $expecting = C_EXP_EQU;
			}
			elseif ($expecting === C_EXP_EQU && $tokenStream[$i]->is(T_EQUAL)) {
                $expecting = C_EXP_VAL;
			}
			elseif ($expecting === C_EXP_COMMA && $tokenStream[$i]->is(T_COMMA)) {
				$expecting = C_EXP_NAME;
			}
			elseif ($expecting === C_EXP_VAL && $tokenStream[$i]->is(T_LNUMBER, T_DNUMBER, T_CONSTANT_ENCAPSED_STRING, T_STRING)) {
                $constants[$name] = $tokenStream[$i];
                $expecting = C_EXP_COMMA;
			}
			else {
				throw new Prephp_Exception('Const: Found unexpected ' . $tokenStream[$i]->name);
			}
		}
		
		$tokenStream->extract($iConstKeyword, $iEOS);
		
		$aConstants = array();
		
		foreach ($constants as $name => $tValue) {
			array_push($aConstants,
				new Prephp_Token(
					T_STRING,
					'define'
				),
				'(',
					new Prephp_Token(
						T_CONSTANT_ENCAPSED_STRING,
						'\''.$name.'\''
					),
					',',
					$tValue,
				')',
				';',
                new Prephp_Token(T_WHITESPACE, "\n")
			);
		}
		
		$tokenStream->insert($iConstKeyword, $aConstants);
	}
?>