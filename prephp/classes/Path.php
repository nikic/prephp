<?php
	// This class' job is to simulate PHP's inclusion behaviour
	// I tried to base it on PHP's implementation, but there are still differences,
	// e.g. this class does not resolve symlinks
	
	class Prephp_Path
	{
		private static $slash;
		private static $antiSlash;
		private static $win;
		
		// initializes slashes and OS
		public static function init() {
			self::$slash = DIRECTORY_SEPARATOR;
			self::$antiSlash = self::$slash=='/'?'\\':'/';
			self::$win = PHP_OS == 'WINNT' || PHP_OS == 'WIN32';
		}
		
		// returns all possible paths, where PHP will look whilst including
		// filename: filename to resolve
		// caller: the file calling the function
		// executer: the entry file (the file called originally)
		// include_path: include_path or if not specified get_include_path()
		public static function possiblePaths($filename, $caller, $executer, $include_path = null) {
			$executer = dirname($executer);
			$caller = dirname($caller);
			if ($include_path === null) {
				$include_path = get_include_path();
			}
			
			if (self::isStreamWrapper($filename)) {
				return array($filename);
			}
			
			$filename = self::normalizeSlashes($filename);
			
			if (self::isRelative($filename) || self::isAbsolute($filename)) {
				return array(self::normalize($filename, $executer));
			}
						
			$paths = array();
			
			if ($include_path != '') {
				$include_paths = explode(PATH_SEPARATOR, $include_path);
				
				foreach ($include_paths as $path) {
					try {
						$paths[] = self::normalize($path . self::$slash . $filename, $executer);
					}
					catch(InvalidArgumentException $e) {}
				}
			}
			
			$paths[] = self::normalize($caller . self::$slash . $filename);
			
			return $paths;
		}
		
		// normalizes a path to an absolute one (no link resolution!)
		// filename: the path/filename
		// cwd: current working directory used to resolve relative paths
		public static function normalize($filename, $cwd = '') {
			// check for stream wrapper
			if (self::isStreamWrapper($filename)) {
				return $filename;
			}
			
			// all further functions require normalized slashes;
			$filename = self::normalizeSlashes($filename);
			$cwd = self::normalizeSlashes($cwd);
			
			// if relative prepend cwd
			if (!self::isAbsolute($filename)) {
				if (!self::isAbsolute($cwd) && !self::isStreamWrapper($cwd)) {
					throw new InvalidArgumentException('cwd is not absolute, not a stream wrapper or not specified!');
				}
				
				$filename = $cwd . self::$slash . $filename;
			}
			
			$parts = explode(self::$slash, $filename);
			$count = count($parts);
			
			$filename = array_shift($parts);
			while ($part = array_shift($parts)) {
				if ($part == '.') {
					continue;
				}
				
				if ($part == '..') {
					$filename = dirname($filename);
				}
				else {
					$filename .= self::$slash . $part;
				}
			}
			
			// strip multiple slashes
			return self::stripDoubleSlashes($filename);
		}
		
		// normalizes slashes to self::$slash
		// filename: must be non-wrapper
		public static function normalizeSlashes($filename) {
			return str_replace(self::$antiSlash, self::$slash, $filename);
		}
		
		// removes double slashes
		// filename: must be slash-normalized non-wrapper
		public static function stripDoubleSlashes($filename) {
			return (self::isUNC($filename)?'\\':'') // do not strip \\ at beginning (UNC path)
					. preg_replace('#'.preg_quote(self::$slash).'{2,}#', self::$slash, $filename);
		}
		
		// checks if is relative path: (./ and ../)
		// filename: must be slash-normalized
		public static function isRelative($filename) {
			$l = strlen($filename);
			
			return
				$l > 1 // minimum two chars, to be relative
				&& $filename[0] == '.' && (self::isSlash($filename[1]) || ($l > 2 && $filename[1] == '.' && self::isSlash($filename[2])));
		}
		
		// checks if is absolute path (X: and UNC for win, / otherwise)
		// filename: must be slash-normalized
		public static function isAbsolute($filename) {
			$l = strlen($filename);
			
			// Windows
			if (self::$win) {
				return $l > 1 // minimum of two chars
				&& (
					(self::isSlash($filename[0]) && self::isSlash($filename[1])) // UNC path
					|| (ctype_alpha($filename[0]) && $filename[1] == ':') // X: path
				);
			}
			
			// Unix
			return $l && self::isSlash($filename[0]);
		}
		
		// check if is UNC-path (win only)
		// filename: must be slash-normalized
		public static function isUNC($filename) {
			return self::$win && strlen($filename) > 1 && self::isSlash($filename[0]) && self::isSlash($filename[1]);
		}
		
		// checks if is stream wrapper
		// (does not check, whether the stream wrapper actually exists)
		// filename: must NOT be slash-normalized
		public static function isStreamWrapper($filename) {
			return preg_match('#^([-+.a-zA-Z0-9]+)://#', $filename);
		}
		
		// checks if $char is self::$slash
		// char: must be slash-normalized
		private static function isSlash($char) {
			return $char == self::$slash;
		}
	}
	
	Prephp_Path::init();
?>