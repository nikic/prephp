<?php
	error_reporting(E_ALL | E_STRICT);
	
	require_once 'Token.php';
	
	class Prephp_Token_Stream implements ArrayAccess, Countable, SeekableIterator
	{
		protected $tokens = array();
		
		protected static $customTokens = array(
			'(' => Prephp_Token::T_OPEN_ROUND,
			')' => Prephp_Token::T_CLOSE_ROUND,
			'[' => Prephp_Token::T_OPEN_SQUARE,
			']' => Prephp_Token::T_CLOSE_SQUARE,
			'{' => Prephp_Token::T_OPEN_CURLY,
			'}' => Prephp_Token::T_CLOSE_CURLY,
			';' => Prephp_Token::T_SEMICOLON,
			'.' => Prephp_Token::T_DOT,
			',' => Prephp_Token::T_COMMA,
			'=' => Prephp_Token::T_EQUAL,
			'<' => Prephp_Token::T_LT,
			'>' => Prephp_Token::T_GT,
			'+' => Prephp_Token::T_PLUS,
			'-' => Prephp_Token::T_MINUS,
			'*' => Prephp_Token::T_MULT,
			'/' => Prephp_Token::T_DIV,
			'?' => Prephp_Token::T_QUESTION,
			'!' => Prephp_Token::T_EXCLAMATION,
			':' => Prephp_Token::T_COLON,
			'"' => Prephp_Token::T_DOUBLE_QUOTES,
			'@' => Prephp_Token::T_AT,
			'&' => Prephp_Token::T_AMP,
			'%' => Prephp_Token::T_PERCENT,
			'|' => Prephp_Token::T_PIPE,
			'$' => Prephp_Token::T_DOLLAR,
			'^' => Prephp_Token::T_CARET,
			'~' => Prephp_Token::T_TILDE,
			'`' => Prephp_Token::T_BACKTICK,
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
			while ($i < $numof && $this->tokens[$i]->is(Prephp_Token::T_WHITESPACE));
			
			if ($i == $numof)
				return false;
			
			return $i;
		}
		
		// Finds the previous token of type $tokId
		public function findPreviousToken($i, $tokId) {
			do {
				$i--;
			}
			while ($i > 0 && !$this->tokens[$i]->is($tokId));
			
			if ($i == 0 && !$this->token[$i]->is($tokId))
				return false;
			
			return $i;
		}
		
		// Finds the next token of type $tokId
		public function findNextToken($i, $tokId) {
			$numof = $this->count();
			do {
				$i++;
			}
			while ($i < $numof && !$this->tokens[$i]->is($tokId));
			
			if($i == $numof)
				return false;
			
			return $i;
		}
		
		public function splice($from, $to) {
			$tokenStream = new Prephp_Token_Stream();
			$tokenStream->insertStreamAtEnd(
				array_splice($this->tokens, $from, $to - $from + 1, array())
			);
			
			return $tokenStream;
		}
		
		public function insertStreamAt($i, $tokenStream) {
			if ($i == $this->count() - 1) {
				$this->insertStreamAtEnd($tokenStream);
				return;
			}
			
			$after = $this->splice($i + 1, $this->count() - 1);
			
			$this->insertStreamAtEnd($tokenStream);
			
			if (isset($after)) {
				$this->insertStreamAtEnd($after);
			}
		}
		
		public function insertStreamAtEnd($tokenStream) {			
			foreach ($tokenStream as $token) {
				$this->tokens[] = $token;
			}
		}
		
		public function insertAtEnd(Prephp_Token $token) {
			$this->tokens[] = $token;
		}
		
		public function count() {
			return count($this->tokens);
		}
		
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