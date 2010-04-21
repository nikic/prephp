<?php
	require_once 'Exception.php';
	
	// todo: rethink the whole thing.
	// Everything feels like a dirty hack...
	
	abstract class Prephp_Core_Abstract
	{
		protected static $instance; // Singleton instance
		final private function __clone() {} // block cloning
		
		public static function getInstance() {
			if (!class_exists('Prephp_Core')) {
				throw new Prephp_Exception('You have to define an Prephp_Core!');
			}
			
			if (!isset(Prephp_Core::$instance)) {
				Prephp_Core::$instance = new Prephp_Core();
			}
			
			return Prephp_Core::$instance;
		}
		
		// default configuration
		const sourceDir = '..';
		const cacheDir = '../cache';
		
		const listenersFile = 'listeners.php';
		
		public function http_404() {}
		
		// and here our code really starts ...
		
		protected $preprocessor;
		
		protected $executer;
		protected $current;
		
		protected function __construct() {
			if (!is_readable(Prephp_Core::sourceDir)) {
				throw new Prephp_Exception('sourceDir not readable');
			}
			
			if (!is_writeable(Prephp_Core::cacheDir) && !mkdir(Prephp_Core::cacheDir, 0777, true)) {
				throw new Prephp_Exception('cacheDir not writeable');
			}
		}
		
		// get preprocessor
		public function getPreprocessor() {
			if ($this->preprocessor === null) {
				require_once 'Preprocessor.php'; // load preprocessor only when necessary
				
				$this->preprocessor = new Prephp_Preprocessor();
				
				require_once Prephp_Core::listenersFile;
			}
			
			return $this->preprocessor;
		}
		
		// process file, return filepath (in cache)
		public function process($accessPath) {			
			if (!$sourcePath = $this->getAsSource($accessPath, true)) {
				return false;
			}
			
			// first processed file becomes executer
			if (empty($this->executer)) {
				$this->executer = $sourcePath;
			}
			
			$cachePath = $this->getAsCache($accessPath);
			
			if ($this->needsRebuild($sourcePath, $cachePath)) {
				$this->compile($sourcePath, $cachePath);
			}
			
			return $cachePath;
		}
		
		// check file modification time
		public function needsRebuild($sourcePath, $cachePath) {
			return !file_exists($cachePath) || filemtime($sourcePath) >= filemtime($cachePath);
		}
		
		// compilation
		public function compile($sourcePath, $cachePath) {
			$this->current = $sourcePath;
			
			if (($source = @file_get_contents($sourcePath)) === false) {
				throw new Prephp_Exception('Could not file_get_contents '.$sourcePath);
			}
			
			$p = $this->getPreprocessor();
			
			$source = $p->preprocess($source);
			
			if (!file_exists(dirname($cachePath)) && !mkdir(dirname($cachePath), 0777, true)) {
				throw new Prephp_Exception('Cache directory '.dirname($cachePath).' didn\'t exist and couldn\'t be created');
			}
			
			if (@file_put_contents($cachePath, $source) === false) {
				throw new Prephp_Exception('Couldn\'t file_put_contents to '.$cachePath);
			}
		}
		
		public function getAsSource($accessPath, $checkExists = false) {
			if (!$checkExists) {
				return realpath(Prephp_Core::sourceDir) . DIRECTORY_SEPARATOR . $accessPath;
			}
			
			return realpath(Prephp_Core::sourceDir . DIRECTORY_SEPARATOR . $accessPath);
		}
		public function getAsCache($accessPath) {
			return realpath(Prephp_Core::cacheDir) . DIRECTORY_SEPARATOR . $accessPath;
		}
		
		public function getExecuter() {
			return $this->executer;
		}
		public function getCurrent() {
			return $this->current;
		}
	}
?>