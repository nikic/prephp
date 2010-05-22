<?php
	// define undefined tokens (php evolves...)
	if (!defined('T_ML_COMMENT'))
		define('T_ML_COMMENT',   T_COMMENT);
		
	// need to define these newer tokens for PHP 5.2
	if (!defined('T_DIR'))
		define('T_DIR',          379);
	if (!defined('T_GOTO'))
		define('T_GOTO',         333);
	if (!defined('T_NAMESPACE'))
		define('T_NAMESPACE',    377);
	if (!defined('T_NS_C'))
		define('T_NS_C',         378);
	if (!defined('T_NS_SEPARATOR'))
		define('T_NS_SEPARATOR', 380);
	if (!defined('T_USE'))
		define('T_USE',          340);
	
	// define custom one char tokens
	define('T_OPEN_ROUND',   1001);
	define('T_CLOSE_ROUND',  1002);
	define('T_OPEN_SQUARE',  1003);
	define('T_CLOSE_SQUARE', 1004);
	define('T_OPEN_CURLY',   1005);
	define('T_CLOSE_CURLY',  1006);
	define('T_SEMICOLON',    1007);
	define('T_DOT',          1008);
	define('T_COMMA',        1009);
	define('T_EQUAL',        1010);
	define('T_LT',           1011);
	define('T_GT',           1012);
	define('T_PLUS',         1013);
	define('T_MINUS',        1014);
	define('T_STAR',         1015);
	define('T_SLASH',        1016);
	define('T_QUESTION',     1017);
	define('T_EXCLAMATION',  1018);
	define('T_COLON',        1019);
	define('T_DOUBLE_QUOTES',1020);
	define('T_AT',           1021);
	define('T_AMP',          1022);
	define('T_PERCENT',      1023);
	define('T_PIPE',         1024);
	define('T_DOLLAR',       1025);
	define('T_CARET',        1026);
	define('T_TILDE',        1027);
	define('T_BACKTICK',     1028);
	
	class Prephp_Token
	{
		protected $tokId;   // Token Identifier. Something like T_VARIABLE
		protected $content; // Token Content. Something like "$var"
		protected $line;    // Line of Token (e.g. for Exceptions). Something like 7
		
		public function __construct($tokId, $content, $line = -1) {
			$this->tokId = $tokId;
			$this->content = $content;
			$this->line = $line;
		}
		
		public function getTokenId() {
			return $this->tokId;
		}
		
		public function getContent() {
			return $this->content;
		}
		public function __toString() {
			return $this->content;
		}
		
		public function getLine() {
			return $this->line;
		}
		
		// $tokId may be a T_ or an array(T_,T_,...)
		public function is($tokId) {
			return $tokId==$this->tokId || (is_array($tokId) && in_array($this->tokId, $tokId));
		}
	}
?>