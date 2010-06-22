<?php
	require_once 'Exception.php';
	require_once 'Token.php';
	
	class Prephp_Token_Stream implements ArrayAccess, Countable, SeekableIterator
	{
		protected $tokens = array();
		
		protected static $customTokens = array(
			'(' => T_OPEN_ROUND,
			')' => T_CLOSE_ROUND,
			'[' => T_OPEN_SQUARE,
			']' => T_CLOSE_SQUARE,
			'{' => T_OPEN_CURLY,
			'}' => T_CLOSE_CURLY,
			';' => T_SEMICOLON,
			'.' => T_DOT,
			',' => T_COMMA,
			'=' => T_EQUAL,
			'<' => T_LT,
			'>' => T_GT,
			'+' => T_PLUS,
			'-' => T_MINUS,
			'*' => T_STAR,
			'/' => T_SLASH,
			'?' => T_QUESTION,
			'!' => T_EXCLAMATION,
			':' => T_COLON,
			'"' => T_DOUBLE_QUOTES,
			'@' => T_AT,
			'&' => T_AMP,
			'%' => T_PERCENT,
			'|' => T_PIPE,
			'$' => T_DOLLAR,
			'^' => T_CARET,
			'~' => T_TILDE,
			'`' => T_BACKTICK,
			'\\' => T_NS_SEPARATOR,
		);
		
		// expects source code
		public function __construct($source = '') {
			ob_start();
			$tokens = token_get_all($source);
			$errors = ob_get_clean();
			
			$line = 1;
			foreach ($tokens as $token) {
				if (is_string($token)) {
					$this->tokens[] = new Prephp_Token(
						self::$customTokens[$token],
						$token,
						$line
					);
				}
				else {
					$this->tokens[]= new Prephp_Token(
						$token[0],
						$token[1],
						$line
					);
					
					$line += substr_count($token[1], "\n");
				}
			}
			
			// There were E_COMPILE_WARNINGs, e.g.
			// <b>Warning</b>:  Unexpected character in input:  '\' (ASCII=92) state=1 in [...]
			// therefore look for #'(.)'#
			if ($errors != '' && preg_match_all('#\'(.)\'#', $errors, $matches, PREG_PATTERN_ORDER)) {
				$chars = $matches[1];
				$c = count($chars);
				
				$i = 0; // string offset in source
				$count = count($this->tokens);
				for ($n = 0; $c && $n < $count; ++$n) {
					$s = $this->tokens[$n]->getContent();
					
					$l = strlen($s);
					if (substr($source, $i, $l) == $s) {
						$i += $l;
						continue; // source matches token
					}
					
					// not the missing char
					if ($source[$i] != $chars[0]) {
						throw new Prephp_Exception('Token Stream construction failed. \''.$source[$i].'\' found, \''.$chars[0].'\' expected');
					}
					
					$this->insertToken($n, array_shift($chars));
					++$i;
					++$count;
					--$c;
				}
			}
		}
		
		//
		// search methods
		//
		
		// returns the next (previous if $reverse) non whitespace index
		public function skipWhitespace($i, $reverse = false) {
			if ($reverse) { // find previous
				while ($i--) {
					if (!$this->tokens[$i]->is(T_WHITESPACE)) {
						return $i;
					}
				}
			}
			else { // find next
				$numof = $this->count();
				for (++$i; $i < $numof; ++$i) {
					if (!$this->tokens[$i]->is(T_WHITESPACE)) {
						return $i;
					}
				}
			}
			
			return false;
		}
		
		// finds next (previous if $reverse) index which is any of $tokens
		public function findToken($i, $tokens, $reverse = false) {
			if ($reverse) { // find previous
				while ($i--) {
					if ($this->tokens[$i]->is($tokens)) {
						return $i;
					}
				}
			}
			else { // find next
				$numof = $this->count();
				for (++$i; $i < $numof; ++$i) {
					if ($this->tokens[$i]->is($tokens)) {
						return $i;
					}
				}
			}
			
			return false;
		}
		
		// finds next (previous if $reverse) end of statement
		public function findEOS($i, $reverse = false) {
			if ($reverse) { // find previous
				return $this->findToken(
					$i,
					array(
						T_SEMICOLON,
						T_CLOSE_CURLY,
						T_OPEN_CURLY,
						T_OPEN_TAG,
					),
					true
				);
			}
			else { // find next
				return $this->findToken(
					$i,
					array(
						T_SEMICOLON,
						T_CLOSE_TAG, // is this one correct?
					)
				);
			}
		}
		
		// finds the complementary bracket
		// is infallible (never returns false [throws Exception instead])
		public function findComplementaryBracket($i, $reverse = false) {
			if ($reverse) {
				if (
					!$this->tokens[$i]->is(array(
							T_CLOSE_ROUND,
							T_CLOSE_SQUARE,
							T_CLOSE_CURLY,
					))
				) {
					throw new Prephp_Exception('TokenStream/complementaryBracket: Unexpected '.token_name($this->tokens[$i]->getTokenId()).' at '.$i.', expected opening bracket!');
				}
				
				$complements = array(
					T_CLOSE_ROUND => T_OPEN_ROUND,
					T_CLOSE_SQUARE => T_OPEN_SQUARE,
					T_CLOSE_CURLY => T_OPEN_CURLY,
				);
			}
			else {
				if (
					!$this->tokens[$i]->is(array(
							T_OPEN_ROUND,
							T_OPEN_SQUARE,
							T_OPEN_CURLY,
					))
				) {
					throw new Prephp_Exception('TokenStream/complementaryBracket: Unexpected '.token_name($this->tokens[$i]->getTokenId()).' at '.$i.', expected opening bracket!');
				}
				
				$complements = array(
					T_OPEN_ROUND => T_CLOSE_ROUND,
					T_OPEN_SQUARE => T_CLOSE_SQUARE,
					T_OPEN_CURLY => T_CLOSE_CURLY,
				);
			}
				
			$type = $this->tokens[$i]->getTokenId();
			$compl = $complements[$type];
			$depth = 1;
			
			while ($depth > 0) {
				$i = $this->findToken($i, array($type, $compl), $reverse);
				
				if ($i === false) {
					throw new Prephp_Exception('TokenStream/complementaryBracket: Opening and closing brackets not matching.');
				}
				
				if ($this->tokens[$i]->is($type)) { // opening
					++$depth;
				}
				else { // closing
					--$depth;
				}
			}

			return $i;
		}
		
		// define shortcut functions (e.g. for compatibility reasons)
		
		// finds the previous token (shortcut for findToken(,,) => findToken(,,true))
		public function findPreviousToken($i, $tokens) {
			return $this->findToken($i, $tokens, true);
		}
		
		// finds the next token of type (shortcut for findToken(,,false))
		public function findNextToken($i, $tokens) {
			return $this->findToken($i, $tokens, false);
		}
		
		// finds previous end of statement (shortcut for findEOS(,false))
		public function findPreviousEOS($i) {
			return $this->findEOS($i, true);
		}
		
		// finds next end of statement (shortcut for findEOS(,) => findEOS(,true))
		public function findNextEOS($i) {
			return $this->findEOS($i);
		}
		
		//
		// TokenStream operations
		//
		
		// returns a Prephp_Token_Stream containing elements $from to $to
		// and *removes* it from the original stream
		public function extractStream($from, $to) {
			$tokenStream = new Prephp_Token_Stream;
			$tokenStream->appendStream(
				array_splice($this->tokens, $from, $to - $from + 1, array())
			);
			
			return $tokenStream;
		}
		
		// inserts stream at $i, moving all following tokens down
		public function insertStream($i, $tokenStream) {
			if ($i == $this->count() - 1) { // end => append
				$this->appendStream($tokenStream);
				return;
			}
			
			// remove following stream to append later
			$after = $this->extractStream($i, $this->count() - 1);
			
			$this->appendStream($tokenStream);
			$this->appendStream($after);
		}
		
		// appends stream
		public function appendStream($tokenStream) {			
			foreach ($tokenStream as $token) {
				// Prephp_Token: append
				if ($token instanceof Prephp_Token) {
					$this->tokens[] = $token;
				}
				// One char Token: append Prephp_Token resulting from it
				elseif (is_string($token)) {
					$this->tokens[] = new Prephp_Token(
						self::$customTokens[$token],
						$token
					);
				}
				// array or Stream: recursively call appendStream
				elseif (is_array($token) || $token instanceof Prephp_Token_Stream) {
					$this->appendStream($token);
				}
				// drop anything else
			}
		}
		
		// extracts token as $i moving all other tokens down
		public function extractToken($i) {
			$stream = $this->extractStream($i, $i);
			return $stream[0];
		}
		
		// inserts token at $i moving all other tokens up
		public function insertToken($i, $token) {
			$this->insertStream($i, // maybe implement this more efficient?
				array(
					$token
				)
			);
		}
		
		// appends token to stream
		public function appendToken($token) {
			$this->tokens->appendStream(array($token));
		}
		
		//
		// interface Countable
		//
		
		public function count() {
			return count($this->tokens);
		}
		
		//
		// interface SeekableIterator
		//
		
		protected $pos = 0;
		
		public function rewind() {
			$this->pos = 0;
		}
		
		public function valid() {
			return isset($this->tokens[$this->pos]);
		}
		
		public function key() {
			return $this->pos;
		}
		
		public function current()
		{
			return $this->tokens[$this->pos];
		}

		public function next()
		{
			++$this->pos;
		}
		
		public function seek($pos)
		{
			$this->pos = $pos;
	 
			if (!$this->valid()) {
				throw new OutOfBoundsException('Invalid seek position');
			}
		}
		
		//
		// interface ArrayAccess
		//
		
		public function offsetExists($offset)
		{
			return isset($this->tokens[$offset]);
		}
		
		public function offsetGet($offset)
		{
			return $this->tokens[$offset];
		}
		
		public function offsetSet($offset, $value)
		{
			if(!($value instanceof Prephp_Token)) {
				throw new InvalidArgumentException('Cannot set offset '.$offset.': Expecting Prephp_Token');
			}
			
			$this->tokens[$offset] = $value;
		}
		
		public function offsetUnset($offset)
		{
			unset($this->tokens[$offset]);
		}
	}
?>