<?php
	error_reporting(E_ALL | E_STRICT);
	
	require_once 'Token.php';
	
	// define undefined tokens (php evolves...)
	if(!defined('T_ML_COMMENT'))
		define('T_ML_COMMENT', T_COMMENT);
	if(!defined('T_OLD_FUNCTION'))
		define('T_OLD_FUNCTION', 1000001); // TODO: Find out correct number
		
	// need to define these newer tokens, in case a newer PHP Version is used
	if(!defined('T_DIR'))
		define('T_DIR', 379);
	if(!defined('T_GOTO'))
		define('T_GOTO', 333);
	if(!defined('T_NS_C'))
		define('T_NS_C', 378);
	if(!defined('T_USE'))
		define('T_USE', 340);
	
	class Prephp_Token_Stream implements ArrayAccess, Countable, SeekableIterator
	{
		protected $tokens = array();
		
		protected static $customTokens = array(
			'(' => 'T_OPEN_ROUND',
			')' => 'T_CLOSE_ROUND',
			'[' => 'T_OPEN_SQUARE',
			']' => 'T_CLOSE_SQUARE',
			'{' => 'T_OPEN_CURLY',
			'}' => 'T_CLOSE_CURLY',
			';' => 'T_SEMICOLON',
			'.' => 'T_DOT',
			',' => 'T_COMMA',
			'=' => 'T_EQUAL',
			'<' => 'T_LT',
			'>' => 'T_GT',
			'+' => 'T_PLUS',
			'-' => 'T_MINUS',
			'*' => 'T_MULT',
			'/' => 'T_DIV',
			'?' => 'T_QUESTION',
			'!' => 'T_EXCLAMATION',
			':' => 'T_COLON',
			'"' => 'T_DOUBLE_QUOTES',
			'@' => 'T_AT',
			'&' => 'T_AMP',
			'%' => 'T_PERCENT',
			'|' => 'T_PIPE',
			'$' => 'T_DOLLAR',
			'^' => 'T_CARET',
			'~' => 'T_TILDE',
			'`' => 'T_BACKTICK',
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
						constant('Prephp_Token::'.self::$customTokens[$token]),
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
					
					// cant use $token[2], cause it need php version 5.?
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
		
		public function insertStreamAt($i, $tokenStream) {
			if ($i == $this->count() - 1) {
				$this->insertStreamAtEnd($tokenStream);
				return;
			}
			
			$after = $this->sliceSubStream($i + 1, $this->count() - 1);
			
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
		
		public function sliceSubStream($from, $to) {
			$tokenStream = new Prephp_Token_Stream();
			$tokenStream->insertStreamAtEnd(
				array_splice($this->tokens, $from, $to - $from + 1, array())
			);
			
			return $tokenStream;
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