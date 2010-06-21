<?php
	require_once 'Exception.php';
	
	abstract class Prephp_Core_Abstract
	{
		//
		// Singleton stuff
		//
		
		protected static $instance; // Singleton instance
		final private function __clone() {} // block cloning
		
		public static function getInstance() {
			if (!class_exists('Prephp_Core')) {
				throw new Prephp_Exception('You have to define a Prephp_Core!');
			}
			
			if (!isset(Prephp_Core::$instance)) {
				Prephp_Core::$instance = new Prephp_Core();
			}
			
			return Prephp_Core::$instance;
		}
		
		//
		// Default configuration
		//
		
		const sourceDir = '..';
		const cacheDir = '../cache';
		
		protected function registerListeners() {
			$p = $this->preprocessor;
			
			require_once 'listeners/core.php';
			require_once 'listeners/arrayAccess.php';
			require_once 'listeners/funcRetCall.php';
			
			// PHP 5.3 simulators
			if (version_compare(PHP_VERSION, '5.3', '<')) {
				require_once 'listeners/lambda.php';
				require_once 'listeners/const.php';
				require_once 'listeners/varClassStatic.php';
				require_once 'listeners/namespaces.php';
				
				$p->registerStreamManipulator(T_STRING, 'prephp_DIR_simulator');
				$p->registerStreamManipulator(T_STRING, 'prephp_use_simulator');
				$p->registerStreamManipulator(T_STRING, 'prephp_namespace_simulator');
				$p->registerStreamManipulator(T_STRING, 'prephp_ns_c_simulator');
				
				$p->registerStreamManipulator(T_FUNCTION, 'prephp_lambda');
				$p->registerStreamManipulator(T_CONST, 'prephp_const');
				
				$p->registerStreamManipulator(array(T_VARIABLE, T_DOLLAR), 'prephp_varClassStatic');
				
				// namespaces
				$p->registerSourcePreparator(array('Prephp_Namespace', 'reset'));
				
				$p->registerStreamManipulator(T_NAMESPACE, array('Prephp_Namespace', 'NS'));
				$p->registerStreamManipulator(T_USE, array('Prephp_Namespace', 'alias'));
				
				$p->registerStreamManipulator(T_CLASS, array('Prephp_Namespace', 'registerClass'));
				$p->registerStreamManipulator(array(T_FUNCTION, T_CONST), array('Prephp_Namespace', 'registerOther'));
				
				$p->registerStreamManipulator(array(T_STRING, T_NS_SEPARATOR), array('Prephp_Namespace', 'resolve'));
				$p->registerTokenCompiler(T_NS_C, array('Prephp_Namespace', 'NS_C'));
			}
			
			// PHP Extenders
			$p->registerStreamManipulator(array(T_STRING, T_VARIABLE, T_DOLLAR), 'prephp_arrayAccess');
			$p->registerStreamManipulator(array(T_STRING, T_VARIABLE, T_DOLLAR), 'prephp_funcRetCall');
			
			// Core
			$p->registerTokenCompiler(T_LINE, 'prephp_LINE');
			
			$p->registerStreamManipulator(array(T_REQUIRE, T_INCLUDE, T_REQUIRE_ONCE, T_INCLUDE_ONCE), 'prephp_include');
			$p->registerStreamManipulator(T_DIR, 'prephp_DIR');
			$p->registerStreamManipulator(T_STRING, 'prephp_preparePath');
			
			$p->registerTokenCompiler(T_FILE, 'prephp_FILE');
			$p->registerTokenCompiler(T_STRING, 'prephp_real_FILE');
		}
		
		public function http_404($accessPath) {
			header('HTTP/1.1 404 Not Found');
			
			echo 'The file you accessed (', htmlspecialchars($accessPath),') does not exist in the specified source directory.';
		}
		
		//
		// Main functional code
		//
		
		protected $preprocessor;
		
		protected $executer;
		protected $current;
		
		protected function __construct() {
			if (!is_readable(Prephp_Core::sourceDir)) {
				throw new Prephp_Exception('sourceDir not readable');
			}
			
			if (!is_writeable(Prephp_Core::cacheDir) && !mkdir(Prephp_Core::cacheDir, 0777, true)) {
				throw new Prephp_Exception('cacheDir not writeable and not createable');
			}
		}
		
		// get preprocessor
		public function getPreprocessor() {
			if ($this->preprocessor === null) {
				require_once 'Preprocessor.php'; // load preprocessor only when necessary
				
				$this->preprocessor = new Prephp_Preprocessor();
				
				$this->registerListeners();
			}
			
			return $this->preprocessor;
		}
		
		// first file is executed, not processed
		public function execute($accessPath) {
			$sourcePath = $this->accessToSource($accessPath);
			
			if (false === $sourcePath || false === $cachePath = $this->process($sourcePath)) {
				$this->http_404($accessPath);
				die();
			}
			
			$this->executer = $sourcePath;
			
			return $cachePath;
		}
		
		// processes file, returns cachePath
		public function process($sourcePath) {			
			if (!is_readable($sourcePath)) {
				return false;
			}
			
			$cachePath = $this->sourceToCache($sourcePath);
			
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
			
			if (false === $source = file_get_contents($sourcePath)) {
				throw new Prephp_Exception('Could not file_get_contents '.$sourcePath);
			}
			
			$source = $this->getPreprocessor()->preprocess($source);
			
			if (!file_exists(dirname($cachePath)) && !mkdir(dirname($cachePath), 0777, true)) {
				throw new Prephp_Exception('Cache directory '.dirname($cachePath).' didn\'t exist and couldn\'t be created');
			}
			
			if (false === file_put_contents($cachePath, $source)) {
				throw new Prephp_Exception('Couldn\'t file_put_contents to '.$cachePath);
			}
		}
		
		public function accessToSource($accessPath) {
			return realpath(Prephp_Core::sourceDir . DIRECTORY_SEPARATOR . $accessPath);
		}
		public function sourceToCache($sourcePath) {
			return str_replace(realpath(Prephp_Core::sourceDir), realpath(Prephp_Core::cacheDir), $sourcePath);
		}
		
		public function getExecuter() {
			return $this->executer;
		}
		public function getCurrent() {
			return $this->current;
		}
	}
?>