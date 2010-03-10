<?php
	$prephp_config = array();
	
	// Path of .htaccess file, relative to the prephp.php file
	$prephp_config['htaccess_location'] = '../';
	
	// All paths relative to prephp .htaccess file
	$prephp_config['source_location'] = ''; // location of php-files, to be prephpized
	$prephp_config['cache_location'] = 'cache/'; // location for prephp compilation cache. Ensure prephp has write access
	
	$prephp_config['exclude'] = array( // excludes everything that *starts* with these strings
		'playground',
		'prephp',
	);
?>