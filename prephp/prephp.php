<?php
    // directory of this file for accessing prephp files while in another directory
    define('PREPHP_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
    
    // stop script execution if no path given or path doesn't exist
    if (!isset($_GET['prephp_path']) || !file_exists($_GET['prephp_path'])) {
        die();
    }

    // load core
    require_once PREPHP_DIR . 'classes/CoreAbstract.php';
    
    // configure core
    class Prephp_Core extends Prephp_CoreAbstract
    {
    }
    
    // load runtime functions and classes
    require_once PREPHP_DIR . 'runtime.php';
    
    // register autoload simulator for namespaced code
    if (version_compare(PHP_VERSION, '5.3', '<')) {
        spl_autoload_register(array('Prephp_RT_Autoload', 'call'));
    }
    
    // chdir to executing file (as php does)
    chdir(dirname($_GET['prephp_path']));
    
    // compile and run file
    require Prephp_Core::getInstance()->process($_GET['prephp_path']);