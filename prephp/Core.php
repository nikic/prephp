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
		
		The Listener has two accept two parameters
		&$tokenStream, $i
		$i is the offset of the Token in the stream
		
		A TokenCompileListener has to return a string
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
			if (is_array($callback)) {
				throw new InvalidArgumentException('Sorry, cant use array-callbacks');
			}
			
			$this->tokenListeners[$tokId][] = $callback;
		}
		
		public function addTokenCompileListener($tokId, $callback) {
			if (!is_callable($callback)) {
				throw new InvalidArgumentException('Callback not callable!');
			}
			if (is_array($callback)) {
				throw new InvalidArgumentException('Sorry, cant use array-callbacks');
			}
			
			$this->tokenCompileListeners[$tokId][] = $callback;
		}
		
		// walks tree and calls tokenListeners
		protected function walkStream() {
			foreach ($this->tokenStream as $i=>$token) {
				if (isset($this->tokenListeners[$token->getTokenId()])) {
					foreach ($this->tokenListeners[$token->getTokenId()] as $listener) {
						$listener($this->tokenStream, $i);
					}
				}
			}
		}
		
		public function compile() {
			$this->walkStream();
			
			$source = '';
			
			foreach ($this->tokenStream as $i=>$token) {
				if (isset($this->tokenCompileListeners[$token->getTokenId()])) {
					foreach ($this->tokenCompileListeners[$token->getTokenId()] as $listener) {
						$source .= $listener($this->tokenStream, $i);
					}
				}
				else {
					$source .= $token;
				}
			}
			
			return $source;
		}
	}
?>