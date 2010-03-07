<?php
	error_reporting(E_ALL | E_STRICT);
	
	/*
		How it works:
		Everything Core does as is, is to get passed the source of a file
		and give you the source back.
		
		But it is possible to manipulate the resulting TokenStream
		and the backcompilation of the TokenStream to PHP.
		
		Thisfor you have to register a Token-Listener.
		There are two types of listeners:
		The TokenListener and the TokenCompileListener
		The first gets called when walking trough the tree, so one can manipulate the TokenStream itself,
		the second gets called when the TokenStream is compiled back to PHP.
		
		TokenListeners get the TokenStream and the index of the Token as parameters
		
		TokenCompileListeners only get the Token Content, because they are intended to manipulate
		the output of only one Token. A TokenCompileListener has to return a string or an empty string (no match).
	*/
	
	require_once 'TokenStream.php';
	
	class Prephp_Core
	{
		protected $tokenStream;
		protected $tokenListeners;
		protected $tokenCompileListeners;
		
		public function __construct($source) {
			$this->tokenStream = new Prephp_Token_Stream($source);
		}
		
		public function addTokenListener($tokId, $callback) {
			if (!is_callable($callback)) {
				throw new InvalidArgumentException('Callback not callable!');
			}
			
			$this->tokenListeners[$tokId][] = $callback;
		}
		
		public function addTokenCompileListener($tokId, $callback) {
			if (!is_callable($callback)) {
				throw new InvalidArgumentException('Callback not callable!');
			}
			
			$this->tokenCompileListeners[$tokId][] = $callback;
		}
		
		// walks tree and calls tokenListeners
		protected function walkStream() {
			foreach ($this->tokenStream as $i=>$token) {
				if (isset($this->tokenListeners[$token->getTokenId()])) {
					foreach ($this->tokenListeners[$token->getTokenId()] as $listener) {
						call_user_func($listener, $this->tokenStream, $i);
					}
				}
			}
		}
		
		public function compile() {
			$this->walkStream();
			
			$source = '';
			
			$numof = sizeof($this->tokenStream);
			for ($i = 0; $i < $numof; ++$i) {
				if (isset($this->tokenCompileListeners[$this->tokenStream[$i]->getTokenId()])) {
					foreach ($this->tokenCompileListeners[$this->tokenStream[$i]->getTokenId()] as $listener) {
						$ret = call_user_func($listener, $this->tokenStream[$i]);
						if ($ret != '') {
							$this->tokenStream[$i] = new Prephp_Token(
								$this->tokenStream[$i]->getTokenId(),
								$ret,
								$this->tokenStream[$i]->getLine()
							);
						}
					}
				}
				
				$source .= $this->tokenStream[$i]->getContent();
			}
			
			return $source;
		}
	}
?>