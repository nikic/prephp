<?php
	define('C_EXP_NAME', 1);
	define('C_EXP_EQU', 2);
	define('C_EXP_VAL', 3);
	define('C_EXP_COMMA', 4);
	
	function prephp_const($tokenStream, $i) {
		$constKeyword = $i;
		
		// check that we're not *directly* inside a class
		
		// find previous { on same level
		$lastOpen = $i;
		while ($lastOpen = $tokenStream->findToken($lastOpen, T_OPEN_CURLY, true)) {
			if ($tokenStream->findComplementaryBracket($lastOpen) < $i) {
				continue;
			}

			// no class before {
			if (!$lastClass = $tokenStream->findToken($lastOpen, T_CLASS, true)) {
				break;
			}
			
			// there is another { between class and our {
			if ($tokenStream->findToken($lastClass, T_OPEN_CURLY) != $lastOpen) {
				break;
			}
			
			return; // is a class
		}

		$constants = array();
		
		$till = $tokenStream->findNextEOS($i);
		if ($till === false) {
			throw new PrephpException('Const: Could not find next EOS!');
		}
		
		$name = '';
		$expecting = C_EXP_NAME;
		while (++$i != $till) {
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
					throw new Prephp_Exception("Const: Found unexpected constant name");
				}
			}
			elseif ($tokenStream[$i]->is(T_EQUAL)) {
				if ($expecting === C_EXP_EQU) {
					$expecting = C_EXP_VAL;
				}
				else {
					throw new Prephp_Exception("Const: Found unexpected '='");
				}
			}
			elseif ($tokenStream[$i]->is(T_COMMA)) {
				if ($expecting === C_EXP_COMMA) {
					$expecting = C_EXP_NAME;
				}
				else {
					throw new Prephp_Exception("Const: Found unexpected ','");
				}
			}
			elseif ($tokenStream[$i]->is(array(T_LNUMBER, T_DNUMBER, T_CONSTANT_ENCAPSED_STRING))) {
				if	($expecting === C_EXP_VAL) {
					$constants[$name] = $tokenStream[$i];
					$expecting = C_EXP_COMMA;
				}
				else {
					throw new Prephp_Exception("Const: Found unexpected constant value");
				}
			}
			else {
				throw new Prephp_Exception("Const: Found unexpected token");
			}
		}
		
		$tokenStream->extractStream($constKeyword, $till);
		
		$i = $constKeyword;
		
		foreach($constants as $name => $value) {
			$tokenStream->insertStream($i,
				array(
					new Prephp_Token(
						T_STRING,
						'define',
						-1
					),
					new Prephp_Token(
						T_OPEN_ROUND,
						'(',
						-1
					),
					new Prephp_Token(
						T_CONSTANT_ENCAPSED_STRING,
						'\''.$name.'\'',
						-1
					),
					new Prephp_Token(
						T_COMMA,
						',',
						-1
					),
					$value,
					new Prephp_Token(
						T_CLOSE_ROUND,
						')',
						-1
					),
					new Prephp_Token(
						T_SEMICOLON,
						';',
						-1
					),
				)
			);
		}
	}
?>