<?php
	function prephp_nowdoc($source) {
		function stringify($matches) {
			$cont = str_replace('\'', '\\\'', $matches[2]);
			return '\'' . $cont . ($cont[strlen($cont)-1] == '\\'?'\\':'') . '\'';
		}
		
		// empty NOWDOCs
		$source = preg_replace("#<<<'([^']+)'\r?\n\\1(?=;|\r?\n)#", "''", $source);
		
		// normal ones
		$source = preg_replace_callback("#<<<'([^']+)'\r?\n(.*)\r?\n\\1(?=;|\r?\n)#sU", 'stringify', $source);

		return $source;
	}
?>