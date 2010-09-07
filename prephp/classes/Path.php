<?php
    // This class' job is to simulate PHP's inclusion behaviour
    // I tried to base it on PHP's implementation, but there are still differences,
    // e.g. this class does not resolve symlinks
    
    class Prephp_Path
    {
        private static $win;
        
        // initializes slashes and OS
        public static function init() {
            self::$win = PHP_OS == 'WINNT' || PHP_OS == 'WIN32';
        }
        
        // returns all possible paths, where PHP will look whilst including
        // filename: filename to resolve
        // caller: the file calling the function
        // cwd: current working directory or if not specified getcwd()
        // include_path: include_path or if not specified get_include_path()
        public static function possiblePaths($filename, $caller, $cwd = null, $include_path = null) {
            $caller = dirname($caller);
            if ($cwd === null) {
                $cwd = getcwd();
            }
            if ($include_path === null) {
                $include_path = get_include_path();
            }
            
            if (self::isStreamWrapper($filename)) {
                return array($filename);
            }
            
            $filename = self::normalizeSlashes($filename);
            
            if (self::isRelative($filename) || self::isAbsolute($filename)) {
                return array(self::normalize($filename, $cwd));
            }
                        
            $paths = array();
            
            if ($include_path != '') {
                foreach (explode(PATH_SEPARATOR, $include_path) as $path) {
                    try {
                        $paths[] = self::normalize($path . DIRECTORY_SEPARATOR . $filename, $cwd);
                    }
                    catch(Exception $e) {}
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
            
            // all further functions require normalized slashes
            $filename = self::normalizeSlashes($filename);
            
            // if relative prepend cwd
            if (!self::isAbsolute($filename)) {
                // if it is a stream wrapper append and don't do any further processing
                if (self::isStreamWrapper($cwd)) {
                    return $cwd . DIRECTORY_SEPARATOR . $filename;
                }
                
                $cwd = self::normalizeSlashes($cwd);
                
                if (!self::isAbsolute($cwd))  {
                    throw new InvalidArgumentException('cwd is not a stream wrapper, not absolute or not specified!');
                }
                
                $filename = $cwd . DIRECTORY_SEPARATOR . $filename;
            }
            
            $parts = array_reverse(explode(DIRECTORY_SEPARATOR, $filename));
            
            $filename = array_pop($parts);
            while ($part = array_pop($parts)) {
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
            return strtr($filename, DIRECTORY_SEPARATOR == '/' ? '\\' : '/', DIRECTORY_SEPARATOR);
        }
        
        // removes double slashes
        // filename: must be slash-normalized non-wrapper
        public static function stripDoubleSlashes($filename) {
            return (self::isUNC($filename) ? '\\' : '') // do not strip \\ at beginning (UNC path)
                    . preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR) . '{2,}#', DIRECTORY_SEPARATOR, $filename);
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