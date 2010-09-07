<?php
    class Prephp_FileException extends RuntimeException {}
    
    abstract class Prephp_CoreAbstract
    {
        //
        // Singleton stuff
        //
        
        protected static $instance; // Singleton instance
        final private function __clone() {} // block cloning
        
        public static function getInstance() {
            if (!class_exists('Prephp_Core')) {
                throw new LogicException('You have to define a Prephp_Core class!');
            }
            
            if (!isset(Prephp_Core::$instance)) {
                Prephp_Core::$instance = new Prephp_Core();
            }
            
            return Prephp_Core::$instance;
        }
        
        //
        // Default configuration
        //
        
        const cacheDir = '../cache';
        
        protected function registerListeners() {
            $p = $this->preprocessor;
            
            // PHP 5.3 simulators
            if (version_compare(PHP_VERSION, '5.3', '<')) {
                require_once PREPHP_DIR . 'listeners/lambda.php';
                require_once PREPHP_DIR . 'listeners/const.php';
                require_once PREPHP_DIR . 'listeners/varClassStatic.php';
                require_once PREPHP_DIR . 'listeners/ternary.php';
                require_once PREPHP_DIR . 'listeners/namespaces.php';
                
                $p->registerStreamManipulator(T_FUNCTION, 'prephp_lambda');
                $p->registerStreamManipulator(T_CONST, 'prephp_const');
                
                $p->registerStreamManipulator(array(T_VARIABLE, T_DOLLAR), 'prephp_varClassStatic');
                
                $p->registerStreamManipulator(T_QUESTION, 'prephp_ternary');
                
                // namespaces
                $p->registerSourcePreparator(array('Prephp_Namespace', 'reset'));
                
                $p->registerStreamManipulator(T_NAMESPACE, array('Prephp_Namespace', 'NS'));
                $p->registerStreamManipulator(T_USE, array('Prephp_Namespace', 'alias'));
                
                $p->registerStreamManipulator(array(T_CLASS, T_INTERFACE), array('Prephp_Namespace', 'registerClass'));
                $p->registerStreamManipulator(array(T_FUNCTION, T_CONST), array('Prephp_Namespace', 'registerOther'));
                
                $p->registerStreamManipulator(array(T_STRING, T_NS_SEPARATOR), array('Prephp_Namespace', 'resolve'));
                $p->registerStreamManipulator(T_NEW, array('Prephp_Namespace', 'resolveNew'));
                
                $p->registerTokenCompiler(T_NS_C, array('Prephp_Namespace', 'NS_C'));
            }
            
            // PHP 5.4 simulators
            if (version_compare(PHP_VERSION, '5.4', '<')) {
                require_once PREPHP_DIR . 'listeners/arrayAccess.php';
                
                $p->registerStreamManipulator(array(T_STRING, T_VARIABLE, T_DOLLAR), 'prephp_arrayAccess');
            }
            
            require_once PREPHP_DIR . 'listeners/funcRetCall.php';
            require_once PREPHP_DIR . 'listeners/core.php';
            
            // PHP Extenders
            $p->registerStreamManipulator(array(T_STRING, T_VARIABLE, T_DOLLAR), 'prephp_funcRetCall');
            
            // Core
            $p->registerStreamManipulator(array(T_REQUIRE, T_INCLUDE, T_REQUIRE_ONCE, T_INCLUDE_ONCE), 'prephp_include');
            
            $p->registerTokenCompiler(T_LINE, 'prephp_LINE');
            $p->registerTokenCompiler(T_FILE, 'prephp_FILE');
            $p->registerTokenCompiler(T_DIR,  'prephp_DIR');
            $p->registerTokenCompiler(T_STRING, 'prephp_real_FILE');
        }
        
        //
        // Main functional code
        //
        
        // Prephp_Preprocessor instance
        protected $preprocessor;
        // currently processed file
        protected $current;
        
        protected function __construct() {
            // ensure cacheDir exists
            if (!file_exists(Prephp_Core::cacheDir) && !mkdir(Prephp_Core::cacheDir, 0777, true)) {
                throw new Prephp_FileException('cacheDir could not be created');
            }
            
            // and is writeable
            if (!is_writeable(Prephp_Core::cacheDir) && !chmod(Prephp_Core::cacheDir, 0777)) {
                throw new Prephp_FileException('could not obtain chmod 777 on cacheDir');
            }
        }
        
        // get preprocessor instance
        public function getPreprocessor() {
            if ($this->preprocessor === null) {
                require_once PREPHP_DIR . 'classes/Preprocessor.php';
                $this->preprocessor = new Prephp_Preprocessor();
                
                $this->registerListeners();
            }
            
            return $this->preprocessor;
        }
        
        // process file, return cachePath or false on error
        public function process($sourcePath) {			
            if (!is_readable($sourcePath)) {
                throw new Prephp_FileException($sourcePath . 'is not readable');
            }
            
            $cachePath = $this->toCachePath($sourcePath);
            
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
                throw new Prephp_FileException('Could not file_get_contents ' . $sourcePath);
            }
            
            $source = $this->getPreprocessor()->preprocess($source);
            
            if (!file_exists(dirname($cachePath)) && !mkdir(dirname($cachePath), 0777, true)) {
                throw new Prephp_FileException('Cache directory ' . dirname($cachePath) . ' didn\'t exist and couldn\'t be created');
            }
            
            if (false === file_put_contents($cachePath, $source)) {
                throw new Prephp_FileException('Could not file_put_contents to ' . $cachePath);
            }
        }
        
        public function toCachePath($sourcePath) {
            $hash = md5(dirname($sourcePath));
            return realpath(PREPHP_DIR . Prephp_Core::cacheDir) . DIRECTORY_SEPARATOR . $hash . DIRECTORY_SEPARATOR . basename($sourcePath);
        }
        
        public function currentFile() {
            return $this->current;
        }
    }