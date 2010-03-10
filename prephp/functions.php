<?php
	function prephp_getFileName($fileConstant) { // $fileConstant is __FILE__
		return 	$GLOBALS['prephp_source_location']
			.	str_replace($GLOBALS['prephp_cache_location'], '', $fileConstant);
	}
	
	function prephp_prepareInclude($fileConstant, $fileName) {
		// there exists a cached file with this name
		if (file_exists(dirname($fileConstant) . DIRECTORY_SEPARATOR . $fileName)) {
			return $fileName;
		}
		
		// now the absolute path would be, if the file were in source dir
		$inSourceDir = prephp_getFileName(dirname($fileConstant) . DIRECTORY_SEPARATOR . $fileName);
		
		// and relative to $fileConstant
		
		
		if (!file_exists($inSourceDir)) {
			// Will throw an error in most cases, but maybe the file is found in include_path
			return $fileName;
		}
		
		$relativeToHtaccess = str_replace($GLOBALS['prephp_base_dir'], '', $inSourceDir);
		
		// check if it is a php file and precomile it, if it is one
		if(preg_match('#\.php[345]?$#', $fileName)) {
			return Prephp_Core::get()->buildFile($relativeToHtaccess);
		}
		
		// include html / txt / ... file
		return $relativeToHtaccess;
	}
	
	/*function prephp_require($fileConstant, $fileName) { // $fileConstant is __FILE__
		if(file_exists(dirname($fileConstant) . DIRECTORY_SEPARATOR . $fileName)) {
			require dirname($fileConstant) . DIRECTORY_SEPARATOR . $fileName;
		}
		else { // not yet cached
			// create path as it would be in source
			$inSource = prephp_getFileName(dirname($fileConstant) . DIRECTORY_SEPARATOR . $fileName);
			
			if(file_exists($inSource)) {
				// is a php file, so precompile
				if(preg_match('#\.php[345]?$#', $inSource)) {
					$relativeToHtaccess = str_replace($GLOBALS['prephp_base_dir'], '', $inSource);
					echo $relativeToHtaccess;
					require Prephp_Core::get()->buildFile($relativeToHtaccess);
				}
				// otherwise easily require
				else {
					require $inSource;
				}
			}
			else {
				// will normally throw an error or maybe search include path aso
				require $fileName;
			}
		}
	}*/
?>