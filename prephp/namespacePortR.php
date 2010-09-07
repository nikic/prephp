<?php
    error_reporting(E_ALL | E_STRICT);
    
    const inputDir  = '../../sandbox';
    const outputDir = '../../symfony2';
    
    define('PREPHP_DIR', __DIR__ . DIRECTORY_SEPARATOR);
    
    if (!file_exists(outputDir) && !mkdir(outputDir, 0777, true)) {
        throw new RuntimeException('outputDir does not exist');
    }
    
    if (!is_writeable(outputDir) && !chmod(outputDir, 0777)) {
        throw new RuntimeException('outputDir not writeable');
    }
    
    require_once PREPHP_DIR . 'classes/Preprocessor.php';
    
    $p = new Prephp_Preprocessor();
    
    require_once PREPHP_DIR . 'listeners/namespaces.php';
    $p->registerSourcePreparator(array('Prephp_Namespace', 'reset'));
    $p->registerStreamManipulator(T_NAMESPACE, array('Prephp_Namespace', 'NS'));
    $p->registerStreamManipulator(T_USE, array('Prephp_Namespace', 'alias'));
    $p->registerStreamManipulator(array(T_CLASS, T_INTERFACE), array('Prephp_Namespace', 'registerClass'));
    $p->registerStreamManipulator(array(T_FUNCTION, T_CONST), array('Prephp_Namespace', 'registerOther'));
    $p->registerStreamManipulator(array(T_STRING, T_NS_SEPARATOR), array('Prephp_Namespace', 'resolve'));
    $p->registerStreamManipulator(T_NEW, array('Prephp_Namespace', 'resolveNew'));
    $p->registerTokenCompiler(T_NS_C, array('Prephp_Namespace', 'NS_C'));
    
    $executionStart = microtime(true);
    
    $skip = isset($_GET['skip']) ? $_GET['skip'] : 0;
    $counter = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(inputDir)) as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -4) != '.php') {
            continue;
        }
        
        if (++$counter <= $skip) {
            continue;
        }
        
        if (!$file->isReadable()) {
            throw new RuntimeException($file->getPathname() . ' not readable');
        }
        
        $dir = str_replace(inputDir, outputDir, $file->getPath());
        
        if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
            throw new RuntimeException($dir . ' does not exist');
        }
        
        if (!is_writeable($dir) && !chmod($dir, 0777)) {
            throw new RuntimeException($dir . ' not writeable');
        }
        
        echo 'processing #', $counter, ': ', $file->getPathname(), '<br />';
        flush();
        
        file_put_contents($dir . DIRECTORY_SEPARATOR . $file->getFilename(), $p->preprocess(file_get_contents($file->getPathname())));
    }
    
    echo '<br /><strong>task completed</strong> in ', microtime(true) - $executionStart, 'seconds';