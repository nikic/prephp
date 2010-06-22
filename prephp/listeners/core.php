<?php	
	function prephp_LINE($token) {
		return (string)$token->getLine();
	}
	
	function prephp_FILE($token) {
		return '\'' . Prephp_Core::getInstance()->getCurrent() . '\'';
	}
	
	function prephp_real_FILE($token) {
		if ($token->getContent() == 'PREPHP__FILE__') {
			return '__FILE__';
		}
		return false;
	}
	
	function prephp_DIR($tokenStream, $i) {		
		$tokenStream->extractStream($i, $i); // remove __DIR__
		
		$tokenStream->insertStream($i,
			array(
				new Prephp_Token(
					T_STRING,
					'dirname'
				),
				'(',
					new Prephp_Token(
						T_FILE,
						'__FILE__'
					),
				')',
			)
		);
	}
	
	function prephp_include($tokenStream, $i) {
		$i = $tokenStream->skipWhitespace($i);
		if ($tokenStream[$i]->is(T_OPEN_ROUND)) {
			$i = $tokenStream[$i]->skipWhitespace($i);
		}
		
		$tokenStream->insertStream($i,
			array(
				new Prephp_Token(
					T_STRING,
					'prephp_rt_prepareInclude'
				),
				'(',
					new Prephp_Token(
						T_FILE,
						'__FILE__',
						$tokenStream[$i]->getLine()
					),
					',',
			)
		);
		
		$tokenStream->insertToken($tokenStream->findEOS($i),
				')'
		);
	}
	
	function prephp_preparePath($tokenStream, $iFunction) {
		$functions = array(
			//'basename'            => array(),
			'chgrp'               => array(1),
			'chmod'               => array(1),
			'chown'               => array(1),
			'clearstatcache'      => array(2),
			'copy'                => array(1,2),
			//'dirname'             => array(),
			//'disk_free_space'     => array(),
			//'disk_total_space'    => array(),
			//'diskfreespace'       => array(),
			'file_exists'         => array(1),
			'file_get_contents'   => array(1), // include_path 2
			'file_put_contents'   => array(1),
			'file'                => array(1),
			'fileatime'           => array(1),
			'filectime'           => array(1),
			'filegroup'           => array(1),
			'fileinode'           => array(1),
			'filemtime'           => array(1),
			'fileowner'           => array(1),
			'fileperms'           => array(1),
			'filesize'            => array(1),
			'filetype'            => array(1),
			'fopen'               => array(1), // include_path 3
			//'glob'                => array(),
			'is_dir'              => array(1),
			'is_executable'       => array(1),
			'is_file'             => array(1),
			'is_link'             => array(1),
			'is_readable'         => array(1),
			'is_uploaded_file'    => array(1),
			'is_writable'         => array(1),
			'is_writeable'        => array(1),
			'lchgrp'              => array(1),
			'lchown'              => array(1),
			'link'                => array(1,2),
			'linkinfo'            => array(1),
			'lstat'               => array(1),
			'mkdir'               => array(1),
			'move_uploaded_file'  => array(1,2),
			'parse_ini_file'      => array(1),
			'pathinfo'            => array(1),
			'readfile'            => array(1), // include_path 2
			'readlink'            => array(1),
			'realpath'            => array(1),
			'rename'              => array(1,2),
			'rmdir'               => array(1),
			'stat'                => array(1),
			'symlink'             => array(1),
			'tempnam'             => array(1),
			'touch'               => array(1),
			'unlink'              => array(1),
		);
		
		$name = $tokenStream[$iFunction]->getContent();
		
		// not a filesystem related function
		if (!isset($functions[$name])) {
			return;
		}
		
		$iBracketOpen = $tokenStream->skipWhitespace($iFunction);
		if ($iBracketOpen === false || !$tokenStream[$iBracketOpen]->is(T_OPEN_ROUND)) {
			throw new Prephp_Exception('Core/preparePath: Function name isn\'t followed by \'(\'');
		}
		
		$iBracketClose = $tokenStream->findComplementaryBracket($iBracketOpen);
		
		$arg = 1;
		$mode = T_COMMA;
		for ($i = $iBracketOpen+1; $i <= $iBracketClose; ++$i) {
			if ($mode == T_COMMA && in_array($arg, $functions[$name])) {
				$tokenStream->insertStream($i, array(
					new Prephp_Token(
						T_STRING,
						'prephp_rt_preparePath'
					),
					'(',
				));
				$i += 2;
				$iBracketClose += 2;
				$mode = T_STRING;
			}
			
			if ($tokenStream[$i]->is(array(T_OPEN_ROUND, T_OPEN_SQUARE, T_OPEN_CURLY))) {
				$i = $tokenStream->findComplementaryBracket($i);
			}
			elseif ($tokenStream[$i]->is(array(T_COMMA, T_CLOSE_ROUND))) {
				if ($mode == T_STRING) {
					$tokenStream->insertToken($i, ')');
					$i += 1;
					$iBracketClose += 1;
					$mode = T_COMMA;
				}
				
				++$arg;
			}
		}
	}
?>