<?php
	define('C_EXP_NAME', 1);
	define('C_EXP_EQU', 2);
	define('C_EXP_VAL', 3);
	define('C_EXP_COMMA', 4);
	
	function prephp_const($tokenStream, $iConstKeyword) {
		// check that we're not *directly* inside a class
		// find previous { on same level
		$iLastOpen = $iConstKeyword;
		while ($iLastOpen = $tokenStream->findToken($iLastOpen, T_OPEN_CURLY, true)) {
			if ($tokenStream->findComplementaryBracket($iLastOpen) < $iConstKeyword) {
				continue;
			}

			// no class before { => not in class
			if (false === $iLastClass = $tokenStream->findToken($iLastOpen, T_CLASS, true)) {
				break;
			}
			
			// there is another { between class and our { => not in class
			if ($tokenStream->findToken($iLastClass, T_OPEN_CURLY) != $iLastOpen) {
				break;
			}
			
			return; // => in class
		}

		$constants = array();
		
		if (false === $iEOS = $tokenStream->findEOS($iConstKeyword)) {
			throw new PrephpException('Const: Could not find End Of Statement!');
		}
		
		$name = '';
		$expecting = C_EXP_NAME;
		
		$i = $iConstKeyword;
		while (++$i != $iEOS) {
			if ($tokenStream[$i]->is(T_WHITESPACE)) {
				continue;
			}
			
			if ($tokenStream[$i]->is(T_STRING)) {
				if ($expecting === C_EXP_NAME) {
					$name = $tokenStream[$i]->getContent();
					$expecting = C_EXP_EQU;
				}
				elseif ($tokenStream[$i]->getContent() == 'true' || $tokenStream[$i]->getContent() == 'false') {
					$constants[$name] = $tokenStream[$i];
					$expecting = C_EXP_COMMA;
				}
				else {
					throw new Prephp_Exception('Const: Found unexpected constant name');
				}
			}
			elseif ($tokenStream[$i]->is(T_EQUAL)) {
				if ($expecting === C_EXP_EQU) {
					$expecting = C_EXP_VAL;
				}
				else {
					throw new Prephp_Exception('Const: Found unexpected \'=\'');
				}
			}
			elseif ($tokenStream[$i]->is(T_COMMA)) {
				if ($expecting === C_EXP_COMMA) {
					$expecting = C_EXP_NAME;
				}
				else {
					throw new Prephp_Exception('Const: Found unexpected \',\'');
				}
			}
			elseif ($tokenStream[$i]->is(array(T_LNUMBER, T_DNUMBER, T_CONSTANT_ENCAPSED_STRING))) {
				if ($expecting === C_EXP_VAL) {
					$constants[$name] = $tokenStream[$i];
					$expecting = C_EXP_COMMA;
				}
				else {
					throw new Prephp_Exception('Const: Found unexpected constant value');
				}
			}
			else {
				throw new Prephp_Exception('Const: Found unexpected token');
			}
		}
		
		$tokenStream->extractStream($iConstKeyword, $iEOS);
		
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
				';'
			);
		}
		
		$tokenStream->insertStream($iConstKeyword, $aConstants);
	}
?>