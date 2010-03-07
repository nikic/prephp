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
	require_once 'Cache.php';
	
	class Prephp_Core
	{
		private static $instance; // Singleton
		public static function get() {
			if (!isset(self::$instance)) {
				$c = __CLASS__;
				self::$instance = new $c;
			}
			
			return self::$instance;
		}
		final private function __construct() {}
		final private function __clone() {}
		
		protected $cache;
		
		protected $tokenListeners;
		protected $tokenCompileListeners;
		
		public function createCache($sourceDir, $cacheDir) {
			if(!is_writable($cacheDir)) {
				throw new InvalidArgumentException("Cache Directory isn't wirteable");
			}
			
			if(!is_readable($sourceDir)) {
				throw new InvalidArgumentException("Source Directory isn't readable");
			}
			
			$this->cache = new Prephp_Cache(
				$sourceDir,
				$cacheDir,
				array(
					$this,
					'buildCallback'
				)
			);
		}
		
		public function buildCallback($source, $cache) {
			$sourceCont = file_get_contents($source);
			if(!$sourceCont) {
				throw new Exception('Could not read source file');
			}
			if(!file_put_contents($cache, $this->compile($sourceCont))) {
				throw new Exception('Could not write cache file');
			}
		}
		
		public function buildFile($filename) {
			return $this->cache->get($filename);
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
		
		public function compile($source) {
			$tokenStream = new Prephp_Token_Stream(token_get_all($source));
			
			// first we walk the stream and call TokenListeners
			foreach ($tokenStream as $i=>$token) {
				if (isset($this->tokenListeners[$token->getTokenId()])) {
					foreach ($this->tokenListeners[$token->getTokenId()] as $listener) {
						call_user_func($listener, $tokenStream, $i);
					}
				}
			}
			
			// now we compile the source
			$source = '';
			$numof = count($tokenStream);
			for ($i = 0; $i < $numof; ++$i) {
				// in compiling we call TokenCompileListeners
				if (isset($this->tokenCompileListeners[$tokenStream[$i]->getTokenId()])) {
					foreach ($this->tokenCompileListeners[$tokenStream[$i]->getTokenId()] as $listener) {
						$ret = call_user_func($listener, $tokenStream[$i]);
						if ($ret != '') {
							$tokenStream[$i] = new Prephp_Token(
								$tokenStream[$i]->getTokenId(),
								$ret,
								$tokenStream[$i]->getLine()
							);
						}
					}
				}
				
				$source .= $tokenStream[$i]->getContent();
			}
			
			return $source;
		}
	}
?>