<?php
    // This class' job is to simulate PHP's inclusion behaviour
    // I tried to base it on PHP's implementation, but there are still differences,
    // e.g. this class does not resolve symlinks
    
    class Prephp_Path
    {
        private static $antiSlash = array('/' => '\\', '\\' => '/');
        private static $win;
        
        // initializes slashes and OS
        public static function init() {
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
                        $paths[] = self::normalize($path . DIRECTORY_SEPARATOR . $filename, $executer);
                    }
                    catch(InvalidArgumentException $e) {}
                }
            }
            
            $paths[] = self::normalize($caller . DIRECTORY_SEPARATOR . $filename);
            
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
                
                $filename = $cwd . DIRECTORY_SEPARATOR . $filename;
            }
            
            $parts = explode(DIRECTORY_SEPARATOR, $filename);
            
            $filename = array_shift($parts);
            while ($part = array_shift($parts)) {
                if ($part == '.') {
                    continue;
                }
                
                if ($part == '..') {
                    $filename = dirname($filename);
                }
                else {
                    $filename .= DIRECTORY_SEPARATOR . $part;
                }
            }
            
            // strip multiple slashes
            return self::stripDoubleSlashes($filename);
        }
        
        // normalizes slashes to self::$slash
        // filename: must be non-wrapper
        public static function normalizeSlashes($filename) {
            return strtr($filename, self::$antiSlash[DIRECTORY_SEPARATOR], DIRECTORY_SEPARATOR);
        }
        
        // removes double slashes
        // filename: must be slash-normalized non-wrapper
        public static function stripDoubleSlashes($filename) {
            return (self::isUNC($filename)?'\\':'') // do not strip \\ at beginning (UNC path)
                    . preg_replace('#'.preg_quote(DIRECTORY_SEPARATOR).'{2,}#', DIRECTORY_SEPARATOR, $filename);
        }
        
        // checks if is relative path: (./ and ../)
        // filename: must be slash-normalized
        public static function isRelative($filename) {
            return
                isset($filename[1]) // minimum two chars, to be relative
                && $filename[0] == '.' && (self::isSlash($filename[1]) || (isset($filename[2]) && $filename[1] == '.' && self::isSlash($filename[2])));
        }
        
        // checks if is absolute path (X: and UNC for win, / otherwise)
        // filename: must be slash-normalized
        public static function isAbsolute($filename) {
            // Windows
            if (self::$win) {
                return isset($filename[1]) // minimum two chars
                && (
                    (self::isSlash($filename[0]) && self::isSlash($filename[1])) // UNC path
                    || (ctype_alpha($filename[0]) && $filename[1] == ':') // X: path
                );
            }
            
            // Unix
            return isset($filename[0]) && self::isSlash($filename[0]);
        }
        
        // check if is UNC-path (win only)
        // filename: must be slash-normalized
        public static function isUNC($filename) {
            return self::$win && isset($filename[1]) && self::isSlash($filename[0]) && self::isSlash($filename[1]);
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
            return $char == DIRECTORY_SEPARATOR;
        }
    }
    
    Prephp_Path::init();
?>