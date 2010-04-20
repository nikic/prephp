<?php
	/*
		IMPORTANT NOTE:
		This class still doesn't perfectly simulate PHP's behaviour
		and does not perform as well as I would hope.
		Should be optimized and rewritten, maybe.
	*/
	
	class Path
	{
		private static $slash;
		private static $antiSlash;
		private static $win;
		
		// initializes slashes and os
		public static function init() {
			self::$slash = DIRECTORY_SEPARATOR;
			self::$antiSlash = self::$slash=='/'?'\\':'/';
			self::$win = PHP_OS == 'WINNT' || PHP_OS == 'WIN32';
		}
		
		public static function possiblePaths($filename, $caller, $executer, $include_path = null) {
			$executer = dirname($executer);
			$caller = dirname($caller);
			if ($include_path === null) {
				$include_path = get_include_path();
			}
			
			// stream wrapper
			if (self::isStreamWrapper($filename)) {
				return array($filename); // one possibility
			}
			
			$paths = array();
			
			if (self::isExplicitlyRelative($filename) || self::isAbsolute($filename)) {
				return array(self::normalize($filename, $executer));
			}
			
			if ($include_path != '') {
				$include_paths = explode(PATH_SEPARATOR, $include_path);
				
				foreach ($include_paths as $path) {
					try {
						if (self::isExplicitlyRelative($path)) {
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
					throw new InvalidArgumentException('cwd is not absolute or a stream wrapper or not specified!');
				}
				
				if ($filename_drive = self::getDriveLetter($filename)) {
					if ($filename_drive != self::getDriveLetter($cwd)) {
						throw new InvalidArgumentException('cwd\'s drive does not equal filename\'s drive');
					}
					
					$filename = $cwd . self::$slash . substr($filename, 2);
				}
				else { // no drive letter: append to cwd
					$filename = $cwd . self::$slash . $filename;
				}
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
		
		// expects: slash-normalized non-wrapper filename
		public static function stripDoubleSlashes($filename) {
			return (self::isUNC($filename)?'\\':'') // Do not strip \\at beginning
					. preg_replace('#'.preg_quote(self::$slash).'{2,}#', self::$slash, $filename);
		}
		
		
		// check if drive letter exists
		// return false if not, drive letter (capitalized) otherwise
		// expects: slash-normalized filename
		public static function getDriveLetter($filename) {
			if (
				self::$win && // windows
				strlen($filename) > 1 && // minimum 2
				ctype_alpha($filename[0]) && $filename[1] == ':' // drive letter
			) {
				return strtoupper($filename[0]); // return drive letter
			}
			
			return false; // no drive letter
		}
		
		// chechs if is relative path
		// ./ and ../
		// not X:/ or \\ on windows
		// expects: slash-normalized filename
		public static function isExplicitlyRelative($filename) {
			$l = strlen($filename);
			
			return
				$l > 1 // minimum two chars, to be relative
				&& ($filename[0] == '.' && (self::isSlash($filename[1]) || ($l > 2 && $filename[1] == '.' && self::isSlash($filename[2])))); // ./ and ../;
		}
		
		// checks if is absolute path (X:/ and UNC for win, / otherwise)
		// expects: slash-normaized filename
		public static function isAbsolute($filename) {
			$l = strlen($filename);
			
			// Windows
			if (self::$win) {
				return $l > 1 // minimum of two chars
				&& (
					(self::isSlash($filename[0]) && self::isSlash($filename[1])) // UNC path
					|| (ctype_alpha($filename[0]) && $filename[1]==':' && $l > 2 && self::isSlash($filename[2])) // X:/ path
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
		// checks id $char is self::$slash
		// expects: normalized slash
		protected static function isSlash($char) {
			return $char == self::$slash;
		}
	}
	
	Path::init();
?>