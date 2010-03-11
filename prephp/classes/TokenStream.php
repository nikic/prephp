<?php
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
			'*' => T_MULT,
			'/' => T_DIV,
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
		);
		
		protected $pos = 0;
		
		// expects token array in token_get_all notation
		// or nothing => empty TokenStream
		public function __construct($tokenArray = null) {
			if ($tokenArray === null) {
				return;
			}
			
			$line = 1;
			
			foreach ($tokenArray as $token) {
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
					
					// cant use $token[2], cause it needs php version 5.?
					$line += substr_count($token[1], "\n");
				}
			}
		}
		
		// returns the next non whitespace index
		public function skipWhiteSpace($i) {
			$numof = $this->count();
			do {
				++$i;
			}
			while ($i < $numof && $this->tokens[$i]->is(T_WHITESPACE));
			
			if ($i == $numof)
				return false;
			
			return $i;
		}
		
		// Finds the previous token of type
		// T_ or array(T_,T_,...)
		public function findPreviousToken($i, $tokens) {
			do {
				$i--;
			}
			while ($i > 0 && !$this->tokens[$i]->is($tokens));
			
			if ($i == 0 && !$this->token[$i]->is($tokens))
				return false;
			
			return $i;
		}
		
		// Finds the next token of type
		// T_ or array(T_,T_,...)
		public function findNextToken($i, $tokens) {
			$numof = $this->count();
			do {
				$i++;
			}
			while ($i < $numof && !$this->tokens[$i]->is($tokens));
			
			if($i == $numof)
				return false;
			
			return $i;
		}
		
		// Finds previous end of statement
		public function findPreviousEOS($i) {
			return $this->findPreviousToken($i,
				array(
					T_SEMICOLON,
					T_CLOSE_CURLY,
					T_OPEN_TAG,
				)
			);
		}
		
		// Finds next end of statement
		public function findNextEOS($i) {
			return $this->findNextToken($i,
				array(
					T_SEMICOLON,
					T_CLOSE_TAG,
				)
			);
		}
		
		// Finds the complementary bracket
		public function findComplementaryBracket($i) {
			if	(!$this->tokens[$i]->is(
					array(
						T_OPEN_ROUND,
						T_OPEN_SQUARE,
						T_OPEN_CURLY,
					))
				) {
				throw new InvalidArgumentException("TokenStream: Token at $i is not a opening bracket");
			}
			
			$compl = array(
				T_OPEN_ROUND => T_CLOSE_ROUND,
				T_OPEN_SQUARE => T_CLOSE_SQUARE,
				T_OPEN_CURLY => T_CLOSE_CURLY,
			);
			
			$type = $this->tokens[$i]->getTokenId();
			
			$depth = 0;
			do {
				$i = $this->findNextToken($i, array($type, $compl[$type]));
				
				if ($i === false) {
					throw new Exception('TokenStream: Open and Close Tokens not matching');
				}
				
				if ($this->tokens[$i]->is($type)) { // opening
					++$depth;
				}
				else { // closing
					--$depth;
				}
			}
			while($depth > 0);
			
			return $i;
		}
		
		// returns a Prephp_Token_Stream containing elements $from to $to
		// and *removes* it from the original stream
		public function extractStream($from, $to) {
			$tokenStream = new Prephp_Token_Stream();
			$tokenStream->appendStream(
				array_splice($this->tokens, $from, $to - $from + 1, array())
			);
			
			return $tokenStream;
		}
		
		// inserts stream at $i, moving all following tokens down
		public function insertStream($i, $tokenStream) {
			if ($i == $this->count() - 1) {
				$this->appendStream($tokenStream);
				return;
			}
			
			// remove following stream to append later
			$after = $this->extractStream($i, $this->count() - 1);
			
			$this->appendStream($tokenStream);
			
			if (isset($after)) {
				$this->appendStream($after);
			}
		}
		
		// appends stream
		public function appendStream($tokenStream) {			
			foreach ($tokenStream as $token) {
				$this->tokens[] = $token;
			}
		}
		
		// need extractToken?
		
		// inserts token at $i moving all other tokens down
		public function insertToken($i, Prephp_Token $token) {
			$this->insertStream($i, // maybe implement this more nice?
				array(
					$token
				)
			);
		}
		
		// appends token to stream
		public function appendToken(Prephp_Token $token) {
			$this->tokens[] = $token;
		}
		
		
		
		// interface Countable
		public function count() {
			return count($this->tokens);
		}
		
		// interface SeekableIterator
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
		
		// interface ArrayAccess
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
				throw new InvalidArgumentException('Expecting Prephp_Token');
			}
			
			$this->tokens[$offset] = $value;
		}
		
		public function offsetUnset($offset)
		{
			unset($this->tokens[$offset]);
		}
	}
?>