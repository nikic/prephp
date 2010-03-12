<?php
	require_once 'Exception.php';
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
			if (!$sourceCont) {
				throw new Exception('Could not read source file');
			}
			
			if (!file_exists(dirname($cache)) && !mkdir(dirname($cache), 0777, true)) {
				throw new Exception('Cache Dir didnt exist and couldnt be created');
			}
			
			if (!file_put_contents($cache, $this->compile($sourceCont))) {
				throw new Exception('Could not write cache file');
			}
		}
		
		public function buildFile($filename) {
			return $this->cache->get($filename);
		}
		
		public function registerTokenListener($tokId, $callback) {
			if (!is_callable($callback)) {
				throw new InvalidArgumentException('Callback not callable!');
			}
			
			$this->tokenListeners[$tokId][] = $callback;
		}
		
		public function registerTokenCompileListener($tokId, $callback) {
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