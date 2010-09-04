<?php	
    // returns file to be included and prepares files
    function prephp_rt_prepareInclude($caller, $fileName) {
        require_once './classes/Path.php';
        
        $core = Prephp_Core::getInstance();
        
        $paths = Prephp_Path::possiblePaths($fileName, $caller, $core->getExecuter());
        
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            
            if (preg_match('#\.php5?$#', $path) && $inCache = $core->process($path)) {
                return $inCache;
            }
            
            return $path;
        }
        
        // let php throw some nice error message
        return $fileName;
    }
    
    function prephp_rt_preparePath($path) {
        require_once './classes/Path.php';
        return Prephp_Path::normalize($path, dirname(Prephp_Core::getInstance()->getExecuter()));
    }
    
    // func()[n] to prephp_functionArrayAccess(func(), n)
    function prephp_rt_arrayAccess($array, $index) {
        return $array[$index];
    }
    
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
?>