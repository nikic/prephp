<?php
	function prephp_varClassStatic($tokenStream, $iClass) {
		$tClass = $tokenStream[$iClass];
		
		if (!$tokenStream[$i = $tokenStream->skipWhitespace($iClass)]->is(T_PAAMAYIM_NEKUDOTAYIM)) {
			return; // not scope resolution call
		}
		
		$i = $tokenStream->skipWhitespace($i);
		
		// count number of dollars before main
		$dollar = 0;
		while ($tokenStream[$i++]->is(T_DOLLAR)) {
			$dollar++;
		}
		$i--; // we walked one too far
		
		if (!$tokenStream[$i]->is(array(T_STRING, T_VARIABLE))) {
			return;
		}
		
		$iMain = $i;
		
		if ($tokenStream[$i = $tokenStream->skipWhitespace($i)]->is(T_OPEN_ROUND)) {
			$type = 'm'; // method
		}
		else {
			$type = $tokenStream[$iMain]->is(T_STRING)?'c':'p'; // constant or property
		}
		
		switch ($type) {
			case 'c': // constant
				$const = $tokenStream[$iMain]->getContent();
				
				$tokenStream->extractStream($iClass, $iMain); // remove
				$tokenStream->insertStream($iClass, array(
					new Prephp_Token(
						T_STRING,
						'constant'
					),
					'(',
						$tClass,
						'.',
						new Prephp_Token(
							T_CONSTANT_ENCAPSED_STRING,
							'\'::'.$const.'\''
						),
					')',
				));
				break;
			case 'm':
				$tMethod = $tokenStream[$iMain];
				if ($tMethod->is(T_STRING)) {
					$tMethod = new Prephp_Token(
						T_CONSTANT_ENCAPSED_STRING,
						'\''.$tMethod->getContent().'\''
					);
				}
				
				$sArgumentList = $tokenStream->extractStream($i, $tokenStream->findComplementaryBracket($i));
				$sArgumentList->extractStream(0, 0); // remove (
				$sArgumentList->extractStream(count($sArgumentList)-1, count($sArgumentList)-1); // remove )
				
				$tokenStream->extractStream($iClass, $iMain);
				$tokenStream->insertStream($iClass, array(
					new Prephp_Token(
						T_STRING,
						'call_user_func'
					),
					'(',
						new Prephp_Token(
							T_STRING,
							'array'
						),
						'(',
							$tClass,
							',',
							$tMethod->is(T_VARIABLE)&&$dollar?array_fill(0, $dollar, '$'):null,
							$tMethod,
						')',
						count($sArgumentList)?',':null,
						$sArgumentList,
					')'
				));
				break;
			case 'p':
				$tProperty = $tokenStream[$iMain];
				$property = substr($tProperty->getContent(), 1);
				
				$tokenStream->extractStream($iClass, $iMain);
				$tokenStream->insertStream($iClass, array(
					'(', // encapsulate everything in brackets
						new Prephp_Token(
							T_STRING,
							'property_exists'
						),
						'(',
							$tClass,
							',',
							new Prephp_Token(
								T_CONSTANT_ENCAPSED_STRING,
								'\''.$property.'\''
							),
						')',
						'?',
							new Prephp_Token(
								T_STRING,
								'eval'
							),
							'(',
								new Prephp_Token(
									T_CONSTANT_ENCAPSED_STRING,
									'\'return \''
								),
								'.',
								$tClass,
								'.',
								new Prephp_Token(
									T_CONSTANT_ENCAPSED_STRING,
									'\'::$\''
								),
								'.',
								// depending on whether there are $dollars
								$dollar
									// either insert the dollars and the original T_VARIABLE
									?
										array(
											array_fill(0, $dollar, '$'),
											$tProperty
										)
									// or a string containing the property name
									:
										new Prephp_Token(
											T_CONSTANT_ENCAPSED_STRING,
											'\''.$property.'\''
										),
								'.',
								new Prephp_Token(
									T_CONSTANT_ENCAPSED_STRING,
									'\';\''
								),
							')',
						':',
						new Prephp_Token(
							T_STRING,
							'null'
						),
					')',
				));
				break;
		}
	}
?>