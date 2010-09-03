<?php
    error_reporting(E_ALL | E_STRICT);
    
    if (!isset($_GET['prephp_path'])) {
        die();
    }

    require_once './classes/Core.php';
    
    class Prephp_Core extends Prephp_Core_Abstract
    {
    }
    
    require_once './functions.php';
    
    if (version_compare(PHP_VERSION, '5.3', '<')) {
        spl_autoload_register(array('Prephp_RT_Autoload', 'call'));
    }
    
    require Prephp_Core::getInstance()->execute($_GET['prephp_path']);
?>