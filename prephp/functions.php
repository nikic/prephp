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
	
	// NS runtime function resolver
	function prephp_rt_checkFunction($inNS, $inGlobal) {
		if (function_exists($inNS)) {
			return $inNS;
		}
		elseif (function_exists($inGlobal)) {
			return $inGlobal;
		}
		else {
			throw new Prephp_Exception('NS runtime function resolver: The function '.$inGlobal.' does neither exist in current nor in global namespace.');
		}
	}
	
	// NS runtime constant resolver
	function prephp_rt_checkConstant($inNS, $inGlobal) {
		if (defined($inNS)) {
			return constant($inNS);
		}
		elseif (defined($inGlobal)) {
			return constant($inGlobal);
		}
		else {
			throw new Prephp_Exception('NS runtime constant resolver: The constant '.$inGlobal.' does neither exist in current nor in global namespace.');
		}
	}
?>