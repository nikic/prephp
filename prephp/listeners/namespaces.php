<?php
if (!function_exists('prephp_use_simulator')) {
	function prephp_use_simulator($tokenStream, $i) {
		if ($tokenStream[$i]->getContent() == 'use') {
			$tokenStream[$i] = new Prephp_Token(
				T_USE,
				'use',
				$tokenStream[$i]->getLine()
			);
		}
	}
}
	
	function prephp_namespace_simulator($tokenStream, $i) {
		if ($tokenStream[$i]->getContent() == 'namespace') {
			$tokenStream[$i] = new Prephp_Token(
				T_NAMESPACE,
				'namespace',
				$tokenStream[$i]->getLine()
			);
		}
	}
	
	function prephp_ns_c_simulator($tokenStream, $i) {
		if ($tokenStream[$i]->getContent() == '__NAMESPACE__') {
			$tokenStream[$i] = new Prephp_Token(
				T_NS_C,
				'__NAMESPACE__',
				$tokenStream[$i]->getLine()
			);
		}
	}
	
	class Prephp_Namespace
	{
		// replaces \
		const SEPARATOR = '__N__';
		
		// contains an array of type
		// line => array('ns' => 'Foo\Bar\Test', 'use' => array('Hi' => 'Test\Hi))
		private static $ns;
		
		// contains an array with lines defined in self::$ns
		private static $lines;
		
		// contains lines as array( from => to ) for class definitions
		private static $classes;
		
		// gets the nearest NS definition line
		private static function getLine($line) {
			if (empty(self::$lines)) {
				return false;
			}
			
			$nearest = 0;
			foreach (self::$lines as $l) {
				if ($l > $nearest && $l <= $line) {
					$nearest = $l;
				}
			}
			
			return $nearest;
		}
		
		// get the ns-array for this line
		// or specify which index of ns-array to get (either 'ns' or 'use')
		private static function &get($line, $which = null) {
			$line = self::getLine($line);
			if (false === $line) {
				// no namespaces defined
				$array = array(
					'ns' => '',
					'use' => array(),
				);
			}
			else {
				$array =& self::$ns[$line];
			}
			
			if ($which === null) {
				return $array;
			}
			else {
				return $array[$which];
			}
		}

		// reset on new file (registered as sourcePreparator)
		public static function reset($source) {
			self::$ns = array();
			self::$lines = array();
			self::$classes = array();
			
			return $source;
		}
		
		// T_NAMESPACE may be either a namespace declaration ( namespace Foo\Bar[;{] )
		// or a namespace lookup ( namespace\Foo\Bar )
		public static function NS($tokenStream, $iNS) {
			$numof = count($tokenStream);
			
			$ns = '';
			$last = 0;
			for ($i = $tokenStream->skipWhitespace($iNS); $i < $numof; ++$i) {
				if ($tokenStream[$i]->is(T_WHITESPACE)) {
					continue;
				}
				
				if ($tokenStream[$i]->is(T_NS_SEPARATOR)) {
					if ($last == T_NS_SEPARATOR) {
						throw new Prephp_Exception('NS: A T_NS_SEPARATOR must not be followed by another T_NS_SEPARATOR');
					}
				}
				elseif ($tokenStream[$i]->is(T_STRING)) {
					if ($last == T_STRING) {
						throw new Prephp_Exception('NS: Two T_STRING namespace parts must be separated by a T_NS_SEPARATOR');
					}
				}
				else {
					break;
				}
				
				$ns  .= $tokenStream[$i]->getContent();
				$last = $tokenStream[$i]->getTokenId();
			}
			
			// namespace declaration
			if ($ns == '' || $ns[0] != '\\') {
				self::$ns[$tokenStream[$iNS]->getLine()] = array(
					'ns' => $ns,
					'use' => array(),
				);
				self::$lines[] = $tokenStream[$iNS]->getLine();
				
				// semicolon style
				if ($tokenStream[$i]->is(T_SEMICOLON)) {
					// remove the ; too
					$tokenStream->extractStream($iNS, $i);
				}
				// bracket style
				elseif ($tokenStream[$i]->is(T_OPEN_CURLY)) {
					// leave the {
					// (So a block { ... } is created
					$tokenStream->extractStream($iNS, $i-1);
				}
				else {
					throw new Prephp_Exception('NS: A namespace declaration must be followed by \';\' or \'{\'');
				}
				
				// At this point one may notice, that prephp does allow mixing
				// semicolon and bracket style
			}
			// namespace lookup
			else {
				// replace T_NAMESPACE with current namespace
				// and fully qualify it
				$tokenStream->extractToken($iNS);
				
				$parts = explode('\\', self::get($tokenStream[$iNS]->getLine(), 'ns'));
				$count = count($parts);
				
				if ($count == 1) {
					return;
				}
				
				$aReplace = array();
				for ($i = 0; $i < $count; ++$i) {
					$aReplace[] = new Prephp_Token(
						T_NS_SEPARATOR,
						'\\'
					);
					$aReplace[] = new Prephp_Token(
						T_STRING,
						$parts[$i]
					);
				}
				
				$tokenStream->insertStream($iNS, $aReplace);
			}
		}
		
		// streamManipulator for use clauses
		public static function alias($tokenStream, $iUse) {
			$iEOS = $tokenStream->findEOS($iUse);
			
			if (false === $iEOS) {
				throw new Prephp_Exception('NS: Alias (use) definition is not terminated by EOS');
			}
			
			$use =& self::get($tokenStream[$iUse]->getLine(), 'use');
			$last = T_USE;
			$current = '';
			$as = false;
			$i = $iUse + 1; // skip T_USE
			while ($i++ < $iEOS) { // till EOS (inclusive)
				if ($tokenStream[$i]->is(T_WHITESPACE)) {
					continue;
				}
				
				if ($tokenStream[$i]->is(T_AS)) {
					$as = '';
				}
				if ($tokenStream[$i]->is(T_STRING)) {
					if ($last == T_STRING) {
						throw new Prephp_Exception('NS: Two T_STRINGs in alias (use) declaration must be separated by a T_NS_SEPARATOR');
					}
					
					if ($last == T_AS) {
						$as = $tokenStream[$i]->getContent();
					}
					else {
						$current .=	$tokenStream[$i]->getContent();
					}
				}
				elseif ($tokenStream[$i]->is(T_NS_SEPARATOR)) {
					if ($last == T_NS_SEPARATOR) {
						throw new Prephp_Exception('NS: A T_NS_SEPARATOR in an alias (use) declaration must not be preceeded by another T_NS_SEPARATOR');
					}
					
					if ($as !== false) {
						throw new Prephp_Exception('NS: The as section of an alias (use) declaration must not contain a T_NS_SEPARATOR');
					}
					
					$current .= $tokenStream[$i]->getContent();
				}
				elseif ($tokenStream[$i]->is(array(T_COMMA, T_SEMICOLON))) {
					if ($last != T_STRING) {
						throw new Prephp_Exception('NS: A \',\' or \';\' in an alias (use) declaration must be preceeded by a T_STRING');
					}
					
					$use[$as===false?substr($current, strrpos($current, '\\')+1):$as] = $current;
					$as = false;
				}
				else {
					throw new Prephp_Exception('NS: Found '.token_name($tokenStream[$i]->getTokenId()).'. Only T_STRING, T_NS_SEPARATOR, T_AS and T_COMMA are allowed in an alias (use) declaration');
				}
				
				$last = $tokenStream[$i]->getTokenId();
			}
			
			$tokenStream->extractStream($iUse, $iEOS);
		}
	
		// register classes
		public static function registerClass($tokenStream, $iClass) {
			$iName = $tokenStream->skipWhitespace($iClass);

			$ns = str_replace('\\', self::SEPARATOR, self::get($tokenStream[$iName]->getLine(), 'ns'));
			$tokenStream[$iName] = new Prephp_Token(
				T_STRING,
				($ns?$ns.self::SEPARATOR:'').$tokenStream[$iName]->getContent()
			);
			
			$iStart = $tokenStream->skipWhitespace($iName);
			$iEnd   = $tokenStream->findComplementaryBracket($iStart);
			
			self::$classes[$tokenStream[$iStart]->getLine()] = $tokenStream[$iEnd]->getLine();
		}
		
		// register non-classes (functions and constants)
		public static function registerOther($tokenStream, $iKeyword) {
			// first check if we are in a class
			$line = $tokenStream[$iKeyword]->getLine();
			foreach (self::$classes as $start => $end) {
				if ($line > $start && $line < $end) {
					// we are in class, abort!
					return;
				}
			}
			
			$iName = $tokenStream->skipWhitespace($iKeyword);
			
			$ns = str_replace('\\', self::SEPARATOR, self::get($tokenStream[$iName]->getLine(), 'ns'));
			$tokenStream[$iName] = new Prephp_Token(
				T_STRING,
				($ns?$ns.self::SEPARATOR:'').$tokenStream[$iName]->getContent()
			);
		}
		
		// tokenCompiler on T_NS_C
		public static function NS_C($token) {
			return '\''.self::get($token->getLine(), 'ns').'\'';
		}
		
		// resolves non-variable namespace calls
		public static function resolve($tokenStream, $iStart) {
			// ensure it's not a true, false or null
			if ($tokenStream[$iStart]->is(T_STRING)
			    && in_array($tokenStream[$iStart]->getContent(), array('true', 'false', 'null'))) {
				return;
			}
			
			// ensure it's not a definition
			$iPrevious = $tokenStream->skipWhitespace($iStart, true);
			if ($iPrevious === false || $tokenStream[$iPrevious]->is(array(T_CLASS, T_FUNCTION, T_CONST))) {
				return; // in defintion
			}
			
			$numof = count($tokenStream);
			
			$ns = '';
			$last = 0;
			for ($i = $iStart; $i < $numof && $tokenStream[$i]->is(array(T_STRING, T_NS_SEPARATOR, T_WHITESPACE)); ++$i) {
				if ($tokenStream[$i]->is(T_WHITESPACE)) {
					continue;
				}
				
				if ($last == T_NS_SEPARATOR && $tokenStream[$i]->is(T_NS_SEPARATOR)) {
					throw new Prephp_Exception('NS Resolution: A T_NS_SEPARATOR may not be preceeded by another T_NS_SEPARATOR');
				}
				
				if ($last == T_STRING && $tokenStream[$i]->is(T_STRING)) {
					throw new Prephp_Exception('NS Resolution: A T_STRING may not be preceeded by another T_STRING');
				}
				
				$ns .= $tokenStream[$i]->getContent();
			}
			
			$tokenStream->extractStream($iStart, $i-1); // we went one too far
			
			$ns_pos = strpos($ns, '\\');
			$ns_before = substr($ns, 0, $ns_pos);
			$use = self::get($tokenStream[$iStart]->getLine(), 'use');
			$current = self::get($tokenStream[$iStart]->getLine(), 'ns');
			
			// aliases
			// qualified (namespace aliases)
			if ($ns_pos) {
				if (isset($use[$ns_before])) {
					$ns = substr_replace($ns, $use[$ns_before], 0, $ns_pos);
				}
				// if no alias, prepend current ns
				else {
					$ns = '\\'.($current==''?'':$current.'\\').$ns;
				}
			}
			// unqualified (class aliases)
			else {
				if (isset($use[$ns])) {
					$ns = $use[$ns];
				}
			}
			
			// for (now) fully qualified
			if ($ns[0] == '\\') {
				$tokenStream->insertToken($iStart,
					new Prephp_Token(
						T_STRING,
						str_replace('\\', self::SEPARATOR, substr($ns, 1))
					)
				);
				return;
			}
			// and now unqualified (there aren't qualified ones any more)
			else {
				// as in global namespace the global call is equivalent to the function
				// call further processing is omitted here to increase code execution
				// performance for applications not using namespaces and for better readability
				// of code
				if ($current == '') {
					$tokenStream->insertToken($iStart,
						new Prephp_Token(
							T_STRING,
							$ns
						)
					);
					return;
				}
				
				if ($tokenStream[$iStart]->is(T_OPEN_ROUND)
				    || $tokenStream[$tokenStream->skipWhitespace($iStart)]->is(T_OPEN_ROUND)) {
					$type = 'Function';
				}
				else {
					$type = 'Constant';
				}
				
				$tokenStream->insertStream($iStart,
					array(
						new Prephp_Token(
							T_STRING,
							'prephp_rt_check'.$type
						),
						'(',
							new Prephp_Token(
								T_CONSTANT_ENCAPSED_STRING,
								'\''.str_replace('\\', self::SEPARATOR, ($current==''?'':$current.'\\').$ns).'\''
							),
							',',
							new Prephp_Token(
								T_CONSTANT_ENCAPSED_STRING,
								'\''.$ns.'\''
							),
						')',
					)
				);
			}
		}
	}
?>