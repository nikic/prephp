<?php
	// This class' job is to simulate PHP's inclusion behaviour
	// I tried to base it on PHP's implementation, but I think there still are differences
	
	class Path
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
		
		// expects: no restrictions
		// returns all possible paths, where PHP will look whilst including
		public static function possiblePaths($filename, $caller, $executer, $include_path = null) {
			$executer = dirname($executer);
			$caller = dirname($caller);
			if ($include_path === null) {
				$include_path = get_include_path();
			}
			
			// stream wrapper
			if (self::isStreamWrapper($filename)) {
				return array($filename);
			}
			
			if (self::isRelative($filename) || self::isAbsolute($filename)) {
				return array(self::normalize($filename, $executer));
			}
						
			$paths = array();
			
			if ($include_path != '') {
				$include_paths = explode(PATH_SEPARATOR, $include_path);
				
				foreach ($include_paths as $path) {
					try {
						if (self::isRelative($path)) {
							$path = $executer . self::$slash . $path;
						}
						
						$paths[] = self::normalize($filename, $path);
					}
					catch(InvalidArgumentException $e) {}
				}
			}
			
			$paths[] = self::normalize($caller . self::$slash . $filename);
			
			return $paths;
		}
		
		// expects: no restrictions
		public static function normalize($filename, $cwd = '') {
			// first check for stream wrapper
			if (self::isStreamWrapper($filename)) {
				return $filename; // no further processing (should one do something with file://?)
			}
			
			// all further functions require normalized slashes;
			$filename = self::normalizeSlashes($filename);
			$cwd = self::normalizeSlashes($cwd);
			
			if (!self::isAbsolute($filename)) {
				if (!self::isAbsolute($cwd) && !self::isStreamWrapper($cwd)) {
					throw new InvalidArgumentException('cwd is not absolute, not a stream wrapper or not specified!');
				}
				
				$filename = $cwd . self::$slash . $filename;
			}
			
			// now resolve ./ and ../
			$filename = array_reduce(explode(self::$slash, $filename), array(__CLASS__, 'normalizor'), 0);
			
			// strip multiple slashes
			$filename = self::stripDoubleSlashes($filename);
			
			return $filename;
		}
		
		// internal!
		// used for array_reduce in self::normalize()
		protected static function normalizor($current, $next) {
			if ($next == '.') {
				return $current;
			}
			
			if ($next == '..') {
				return dirname($current);
			}
			
			if ($current === 0) { // initial
				return $next;
			}
			return $current . self::$slash . $next;
		}
		
		// normalizes slashes to self::$slash
		// expects: non-wrapper filename
		public static function normalizeSlashes($filename) {
			return str_replace(self::$antiSlash, self::$slash, $filename);
		}
		
		// removes double slashes (///)
		// expects: slash-normalized non-wrapper filename
		public static function stripDoubleSlashes($filename) {
			return (self::isUNC($filename)?'\\':'') // do not strip \\ at beginning (UNC path)
					. preg_replace('#'.preg_quote(self::$slash).'{2,}#', self::$slash, $filename);
		}
		
		// chechs if is relative path
		// ./ and ../
		// (X:./ would be relative too, actually, but this is PHP's implementation)
		// expects: slash-normalized filename
		public static function isRelative($filename) {
			$l = strlen($filename);
			
			return
				$l > 1 // minimum two chars, to be relative
				&& $filename[0] == '.' && (self::isSlash($filename[1]) || ($l > 2 && $filename[1] == '.' && self::isSlash($filename[2])));
		}
		
		// checks if is absolute path (X: and UNC for win, / otherwise)
		// expects: slash-normalized filename
		public static function isAbsolute($filename) {
			$l = strlen($filename);
			
			// Windows
			if (self::$win) {
				return $l > 1 // minimum of two chars
				&& (
					(self::isSlash($filename[0]) && self::isSlash($filename[1])) // UNC path
					|| (ctype_alpha($filename[0]) && $filename[1]==':') // X: path (actually, this may be relative, but PHP implements it this way)
				);
			}
			
			// Unix
			return $l && self::isSlash($filename[0]);
		}
		
		// check if is UNC-path (win only)
		// expects: slash-normalized filename
		public static function isUNC($filename) {
			return self::$win && strlen($filename) > 1 && self::isSlash($filename[0]) && self::isSlash($filename[1]);
		}
		
		// checks if is stream wrapper
		// important: does not check, whether the stream wrapper actually exists
		// expects: UNNORMALIZED slashes (file:\\ is not recognized, for sure)
		public static function isStreamWrapper($filename, &$addinf = 'no_addinf') {
			if (preg_match('#^([-+.a-zA-Z0-9]+)://(.*)$#', $filename, $matches)) {
				if ($addinf != 'no_addinf') {
					$addinf = array($matches[1], $matches[2]);
				}
				return true;
			}
			return false;
		}
		
		// internal!
		// checks if $char is self::$slash
		// expects: normalized slash
		protected static function isSlash($char) {
			return $char == self::$slash;
		}
	}
	
	Path::init();
?>