<?php
	/**
	 * Prephp_Cache
	 * 
	 * @author	fchriis
	 * @license	public domain
	 */
	class Prephp_Cache {
		protected $sourceDirectory = '';
		protected $cacheDirectory = '';
		protected $buildCallback;
		
		/**
		 * Creates new cache
		 * 
		 * @param	string		$sourceDirectory
		 * @param	string		$cacheDirectory
		 * @param	callback	$buildCallback
		 */
		public function __construct($sourceDirectory, $cacheDirectory, $buildCallback) {
			if (!is_callable($buildCallback)) {
				throw new Exception('Invalid Callback');
			}
				
			if (!is_readable($sourceDirectory)) {
				throw new Exception('Source Directory not readable');
			}
			
			if (!is_writeable($cacheDirectory)) {
				throw new Exception('Cache Directory not writable');
			}
			
			$this->sourceDirectory = $sourceDirectory;
			$this->cacheDirectory = $cacheDirectory;
			$this->buildCallback = $buildCallback;
		}
		
		/**
		 * Builds cached version of an file if not exists and returns filename of the cached version
		 *
		 * @param	string		$filename
		 * @return	string|false	filename of cached version or false if $filename doesn't exist
		 */
		public function get($filename) {
			// check if sourcefile exists
			if (!file_exists($this->getSourceFilename($filename)))
				return false;
			
			// check if cache needs to be rebuild
			if ($this->needRebuild($filename)) {
				call_user_func_array($this->buildCallback, array(
					$this->getSourceFilename($filename),
					$this->getCacheFilename($filename)
				));
			}
			
			
			return $this->getCacheFilename($filename);
		}
		
		/**
		 * Checks if cache needs to be rebuild
		 * 
		 * @param	string		$filename
		 * @return	boolean
		 */
		protected function needRebuild($filename) {
			// check if cached version doesn't exist
			if (!file_exists($this->getCacheFilename($filename)))
				return true;
			
			// check if cached version is up to date
			$sourceTime = @filemtime($this->getSourceFilename($filename));
			$cacheTime = @filemtime($this->getCacheFilename($filename));
			if ($sourceTime >= $cacheTime) {
				return true;
			}
		}
		
		/**
		 * Returns the filename of the source
		 * 
		 * @param	string		$filename
		 * @return	string
		 */
		public function getSourceFilename($filename) {
			return $this->sourceDirectory.$filename;
		}
		
		/**
		 * Returns the filename of the cached version
		 * 
		 * @param	string		$filename
		 * @return	string
		 */
		public function getCacheFilename($filename) {
			return $this->cacheDirectory.$filename;
		}
	}
?>