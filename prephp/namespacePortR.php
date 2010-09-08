<?php
    error_reporting(E_ALL | E_STRICT);
    set_time_limit(0); // this script can take very long time
    $executionStart = microtime(true);
    
    /// Configuration
    
    // directory to take the files from (including any subdirectories)
    const sourceDir = '../../sandbox';
    
    // directory to put the preprocessed files in
    const targetDir = '../../symfony2';
    
    // If the file already exists in the target directory, do:
    // 'override':    override the existing file in any case
    // 'keep':        keep the existing file in any case
    // 'intelligent': override the existing file if the new file has a newer
    //                file modification timestamp
    const override = 'intelligent';
    
    // array of files/directories not to process but to
    // copy directly. (Do this for .php files, which aren't real PHP files)
    $noProcess = array(
        '..\..\sandbox\src\vendor\symfony\src\Symfony\Framework\FoundationBundle\Resources\skeleton'
    );
    
    /// Configuration END
    
    // required for prephp internals
    define('PREPHP_DIR', __DIR__ . DIRECTORY_SEPARATOR);
    
    // check that source directory is readable
    if (!is_dir(sourceDir) ||!is_readable(sourceDir)) {
        throw new RuntimeException(sourceDir . ' not a directory or not readable');
    }
    
    // check that target directory exists
    if (!is_dir(targetDir) && !mkdir(targetDir, 0777, true)) {
        throw new RuntimeException(targetDir . ' does not exist');
    }
    
    // and is writeable
    if (!is_writeable(targetDir) && !chmod(targetDir, 0777)) {
        throw new RuntimeException(targetDir . ' not writeable');
    }
    
    if (!in_array(override, array('override', 'keep', 'intelligent'))) {
        throw new LogicException('Invalid override mode');
    }
    
    // prepare configuration for use
    $sourceDir = realpath(sourceDir);
    $targetDir = realpath(targetDir);
    $skip      = isset($_GET['skip']) ? $_GET['skip'] : 0;
    array_walk($noProcess, function(&$value) { $value = realpath($value); });
    
    // create Preprocessor instance
    require_once PREPHP_DIR . 'classes/Preprocessor.php';
    $p = new Prephp_Preprocessor();
    
    // register namespace listeners
    require_once PREPHP_DIR . 'listeners/namespaces.php';
    $p->registerSourcePreparator(array('Prephp_Namespace', 'reset'));
    $p->registerStreamManipulator(T_NAMESPACE, array('Prephp_Namespace', 'NS'));
    $p->registerStreamManipulator(T_USE, array('Prephp_Namespace', 'alias'));
    $p->registerStreamManipulator(array(T_CLASS, T_INTERFACE), array('Prephp_Namespace', 'registerClass'));
    $p->registerStreamManipulator(array(T_FUNCTION, T_CONST), array('Prephp_Namespace', 'registerOther'));
    $p->registerStreamManipulator(array(T_STRING, T_NS_SEPARATOR), array('Prephp_Namespace', 'resolve'));
    //$p->registerStreamManipulator(T_NEW, array('Prephp_Namespace', 'resolveNew'));
    $p->registerTokenCompiler(T_NS_C, array('Prephp_Namespace', 'NS_C'));
    
    $counter = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir)) as $sourceFile) {
        // skip files <= $skip and non-files
        if (!$sourceFile->isFile() || ++$counter <= $skip) {
            continue;
        }
        
        if (!$sourceFile->isReadable()) {
            throw new RuntimeException($sourceFile . ' not readable');
        }
        
        $dir = str_replace($sourceDir, $targetDir, $sourceFile->getPath());
        $targetFile = $dir . DIRECTORY_SEPARATOR . $sourceFile->getFilename();
        
        if (!is_dir($dir)) {
            echo 'mkdir ', $dir, '<br />'; flush();
            if (!mkdir($dir, 0777, true)) {
                throw new RuntimeException('Could not create ' . $dir);
            }
        }
        
        if (!is_writeable($dir)) {
            echo 'chmod 0777 ', $dir; flush();
            if (!chmod($dir, 0777)) {
                throw new RuntimeException('Could not chmod ' . $dir);
            }
        }
        
        // check override mode
        if (file_exists($targetFile)
            && (override == 'keep'
                || (override == 'intelligent'
                    && filemtime($targetFile) > $sourceFile->getMTime()))
        ) {
            continue; // no override
        }
        
        switch (true) {
            // process php file
            case substr($sourceFile->getFilename(), -4) == '.php':
                $process = true;
                foreach ($noProcess as $path) {
                    if (strpos($sourceFile, $path) === 0) {
                        $process = false;
                        break;
                    }
                }
                
                if ($process === true) {
                    echo 'processing #', $counter, ': ', $sourceFile, '<br />'; flush();
                    file_put_contents(
                        $targetFile,
                        $p->preprocess(file_get_contents($sourceFile))
                    );
                    break;
                }
                // fall through if $process === false
            // copy non-php file
            default:
                echo 'copying #', $counter, ': ', $sourceFile, '<br />'; flush();
                copy($sourceFile, $targetFile); 
        }
    }
    
    echo '<br /><strong>task completed</strong> in ', microtime(true) - $executionStart, ' seconds';