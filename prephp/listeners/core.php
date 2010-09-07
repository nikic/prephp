<?php	
    function prephp_LINE($token) {
        return $token->line;
    }
    
    function prephp_FILE($token) {
        return "'" . Prephp_Core::getInstance()->currentFile() . "'";
    }
        
    function prephp_DIR($token) {		
        return "'" . dirname(Prephp_Core::getInstance()->currentFile()) . "'";
    }
    
    function prephp_real_FILE($token) {
        if ($token->content == 'PREPHP__FILE__') {
            return '__FILE__';
        }
        return false;
    }
    
    function prephp_include($tokenStream, $i) {
        $i = $tokenStream->skipWhitespace($i);
        $file = $tokenStream->extract($i, $tokenStream->findEOS($i) - 1);
        
        $tokenStream->insert($i,
            array(
                !$tokenStream[$i]->is(T_WHITESPACE) ? new Prephp_Token(T_WHITESPACE, ' ') : null,
                new Prephp_Token(
                    T_STRING,
                    'prephp_rt_prepareInclude'
                ),
                '(',
                    new Prephp_Token(
                        T_FILE,
                        '__FILE__'
                    ),
                    ',',
                    $file,
                ')',
            )
        );
    }