<?php
	// gives filename in source from filename in cache
	function prephp_rt_getFileName($fileConstant) { // $fileConstant is __FILE__
		return 	$GLOBALS['prephp_source_location']
			.	str_replace($GLOBALS['prephp_cache_location'], '', $fileConstant);
	}
	
	// returns file to be included and prepares files
	function prephp_rt_prepareInclude($fileConstant, $fileName) {
		// now the absolute path would be, if the file were in source dir
		$inSourceDir = prephp_rt_getFileName(dirname($fileConstant) . DIRECTORY_SEPARATOR . $fileName);		
		
		if (!file_exists($inSourceDir)) {
			// Will throw an error in most cases, but maybe the file is found in include_path
			return $fileName;
		}
		
		$relativeToHtaccess = str_replace($GLOBALS['prephp_base_dir'], '', $inSourceDir);
		
		// check if it is a php file and precomile it, if it is one
		if (preg_match('#\.php[345]?$#', $fileName)) {
			return Prephp_Core::get()->buildFile($relativeToHtaccess);
		}
		
		// include html / txt / ... file
		return $relativeToHtaccess;
	}
	
	// func()[n] to prephp_functionArrayAccess(func(), n)
	function prephp_rt_arrayAccess($return, $access) {
		return $return[$access];
	}
?>