<?php	
    // returns file to be included
    function prephp_rt_prepareInclude($caller, $fileName) {
        require_once PREPHP_DIR . 'classes/Path.php';
        
        foreach (Prephp_Path::possiblePaths($fileName, $caller) as $path) {
            if (!file_exists($path)) {
                continue;
            }
            
            if (substr($path, -4) == '.php' && $inCache = Prephp_Core::getInstance()->process($path)) {
                return $inCache;
            }
            
            return $path;
        }
        
        // let php throw some nice error message
        return $fileName;
    }
    
    // get offset of an array
    function prephp_rt_arrayAccess($array, $index) {
        return $array[$index];
    }
    
    // class to simulate SPL autoload behavior
    class Prephp_RT_Autoload {
        public static $functions = false;
        
        public static function call($class) {
            if (self::$functions === false) {
                return;
            }
        
            $class = str_replace('__N__', '\\', $class);
            
            foreach (self::$functions as $function) {
                if (class_exists($class, false)) {
                    return;
                }
                
                call_user_func($function, $class);
            }
        }
        
        public static function functions() {
            return self::$functions;
        }
        
        public static function register($function = null, $throw = true, $prepend = false) {
            if ($function === null) {
                if (self::$functions === false) {
                    self::$functions[] = 'spl_autoload';
                    return true;
                }
                return false;
            }
            
            if (!is_callable($function)) {
                if ($throw) {
                    throw new LogicException('Callback not callable');
                }
                return false;
            }
            
            if ($prepend) {
                array_unshift(self::$functions, $function);
            }
            else {
                self::$functions[] = $function;
            }
        }
        
        public static function unregister($function) {
            foreach (self::$functions as $i => $f) {
                if ($f === $function) {
                    unset(self::$functions[$i]);
                    return true;
                }
            }
            
            return false;
        }
    }