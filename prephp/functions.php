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
	
	// func()[n] to prephp_functionArrayAccess(func(), n)
	function prephp_rt_arrayAccess($array, $access) {
		return $array[$access];
	}
?>