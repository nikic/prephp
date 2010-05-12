<?php
	function prephp_varClassStatic($tokenStream, $iStart) {
		$i = $iStart;
		$numof = count($tokenStream);
		
		// count number of tokens before $class
		$dollarClass = 0;
		for (; $i<$numof && $tokenStream[$i]->is(T_DOLLAR); ++$i) {
			++$dollarClass;
		}
		
		if (!$tokenStream[$i]->is(T_VARIABLE)) {
			return; // not a variable (syntax error, actually)
		}
		
		$tClass = $tokenStream[$i];
		
		if (!$tokenStream[$i = $tokenStream->skipWhitespace($i)]->is(T_PAAMAYIM_NEKUDOTAYIM)) {
			return; // not scope resolution call
		}
		
		$i = $tokenStream->skipWhitespace($i);
		
		// count number of dollarMains before main
		$dollarMain = 0;
		for (; $i<$numof && $tokenStream[$i]->is(T_DOLLAR); ++$i) {
			++$dollarMain;
		}
		
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
				
				$tokenStream->extractStream($iStart, $iMain); // remove
				$tokenStream->insertStream($iStart, array(
					new Prephp_Token(
						T_STRING,
						'constant'
					),
					'(',
						$dollarClass?array_fill(0, $dollarClass, '$'):null,
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
				
				$tokenStream->extractStream($iStart, $iMain);
				$tokenStream->insertStream($iStart, array(
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
							$dollarClass?array_fill(0, $dollarClass, '$'):null,
							$tClass,
							',',
							$tMethod->is(T_VARIABLE)&&$dollarMain?array_fill(0, $dollarMain, '$'):null,
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
				
				$tokenStream->extractStream($iStart, $iMain);
				$tokenStream->insertStream($iStart, array(
					'(', // encapsulate everything in brackets
						new Prephp_Token(
							T_STRING,
							'property_exists'
						),
						'(',
							$dollarClass?array_fill(0, $dollarClass, '$'):null,
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
								$dollarClass?array_fill(0, $dollarClass, '$'):null,
								$tClass,
								'.',
								new Prephp_Token(
									T_CONSTANT_ENCAPSED_STRING,
									'\'::$\''
								),
								'.',
								// depending on whether there are $dollarMains
								$dollarMain
									// either insert the dollarMains and the original T_VARIABLE
									?
										array(
											array_fill(0, $dollarMain, '$'),
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