<?php
    function prephp_ternary($tokenStream, $iQuestion) {
        // not a ternary without middle part
        if (!($iColon = $tokenStream->skipWhitespace($iQuestion))
            || !$tokenStream[$iColon]->is(T_COLON)) {
            return;
        }
        
        $i = $iQuestion;
        while ($i--) {
            if ($tokenStream[$i]->is(T_CLOSE_ROUND, T_CLOSE_SQUARE, T_CLOSE_CURLY)) {
                $i = $tokenStream->complementaryBracket($i);
                continue;
            }
            
            if ($tokenStream[$i]->is(array(
                // operators with lower precedence
                T_EQUAL,
                T_PLUS_EQUAL,
                T_PLUS_EQUAL,
                T_MUL_EQUAL,
                T_DIV_EQUAL,
                T_CONCAT_EQUAL,
                T_MOD_EQUAL,
                T_AND_EQUAL,
                T_OR_EQUAL,
                T_XOR_EQUAL,
                T_SL_EQUAL,
                T_SR_EQUAL,
                T_DOUBLE_ARROW,
                T_LOGICAL_AND,
                T_LOGICAL_XOR,
                T_LOGICAL_OR,
                T_COMMA,
                
                // other ending tokens
                T_OPEN_ROUND,
                T_OPEN_SQUARE,
                T_OPEN_CURLY,
                T_SEMICOLON,
            ))) {
                break;
            }
        }
        
        if ($i == 0) {
            throw new Prephp_Exception('ternary not terminated on left side');
        }
        
        // we went one too far
        $iBegin = $tokenStream->skipWhitespace($i);
        
        // put result of ternary condition in a temporary variable
        // and insert it as middle part
        $variable = new Prephp_Token(
            T_VARIABLE,
            uniqid('$prephp_var_')
        );
        
        // insert middle part
        $tokenStream->insert($iColon, $variable);
        
        // insert closing bracket (opening inserted later)
        $tokenStream->insert($iQuestion - 1, ')');
        
        // insert ($variable = ...
        $tokenStream->insert($iBegin, array(
            '(',
            $variable,
            '='
        ));
    }
?>