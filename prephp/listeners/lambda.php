<?php
    /*
        Basic idea of our implementation of lamda functions and closures:
        
        First of all, we avoided to use create_function(). Even though create_function()
        actually is maybe the most exact implementation of lambda functions and closures
        in php it is said to have really bad performance, memory leaks and other problems.
        Therefore we decided to define the functions as real php functions and replace the
        function call with the name of the function. This name is something like
        prephp_lambda_{md5(mt_rand()}. In PHP a function can be called by calling a variable
        with the function name. Exactly this behaviour is used. The virtual functions created
        this way are inserted at the beginning of the file the lambda function was defined in.
        
        To handle closured we use another trick:
        All use()-variables are assigned to $GLOBALS[{functionName}][{variableName}].
        So, if i want to use($a), it is declared like this:
        $GLOBALS['prephp_lamda_SOMELONGSTRING']['a'] = $a;
        Then, at the beginning of the lambda functions code block the variables are assigned back:
        $a = $GLOBALS['prephp_lamda_SOMELONGSTRING']['a'];
    */
    function prephp_lambda($tokenStream, $iFunction) {
        if (!$tokenStream[$tokenStream->skipWhitespace($iFunction)]->is(T_OPEN_ROUND)) {
            return; // normal function
        }
        
        // start of the function definition (including '{')
        if (false === $iDefinition = $tokenStream->find($iFunction, T_OPEN_CURLY)) {
            throw new Prephp_Exception("Lambda-Listener: No function definition (couldn't find '{')");
        }
        
        // function definition stream
        $sDefinition = $tokenStream->extract($iDefinition, $tokenStream->complementaryBracket($iDefinition));
        $sDefinition->extract(0); // remove {
        $sDefinition->extract(count($sDefinition) - 1); // remove }
        
        // function top (arguments and use() definition)
        $sTop = $tokenStream->extract($iFunction, $iDefinition - 1);
        
        $funcName = uniqid('prephp_lambda_');
        
        // insert lambda function name as string as replacement for the function
        $tokenStream->insert($iFunction,
            new Prephp_Token(
                T_CONSTANT_ENCAPSED_STRING,
                '\''.$funcName.'\''
            )
        );
        
        // now we exctract all the variables to use() and the function arguments
        // $use will contain an array of this form:
        // array(
        //   array(
        //     0 => 'varname',
        //     1 => false // is ref?
        //   )
        // )
        $use = array();

        // to extract the function args later
        $iArgumentsStart = $sTop->find(0, T_OPEN_ROUND);
        $iArgumentsEnd = $sTop->complementaryBracket($iArgumentsStart);
        
        // is use() exists
        if (false !== $i = $sTop->find($iArgumentsEnd, T_USE)) {
            $numof = count($sTop);
            $isRef = false;
            
            while (++$i < $numof && !$sTop[$i]->is(T_CLOSE_ROUND)) {
                if ($sTop[$i]->is(T_AMP)) {
                    $isRef = true;
                }
                elseif ($sTop[$i]->is(T_VARIABLE)) {
                    $use[] = array(
                        substr($sTop[$i]->content, 1), // remove $
                        $isRef
                    );
                    
                    $isRef = false;
                }
            }
        }
        
        // the function arguments (including brackets)
        $sArguments = $sTop->extract($iArgumentsStart, $iArgumentsEnd);
        
        if (!empty($use)) {
            // now we register the used vars as $GLOBALs
            $aRegisterGlobals = array(
                new Prephp_Token(
                    T_WHITESPACE,
                    "\n"
                ),
                new Prephp_Token(
                    T_VARIABLE,
                    '$GLOBALS'
                ),
                '[',
                    new Prephp_Token(
                        T_CONSTANT_ENCAPSED_STRING,
                        "'" . $funcName . "'"
                    ),
                ']',
                '=',
                new Prephp_Token(
                    T_ARRAY,
                    'array'
                ),
                '(',
            );
            
            foreach ($use as $u) {
                array_push($aRegisterGlobals,
                    new Prephp_Token(
                        T_CONSTANT_ENCAPSED_STRING,
                        "'" . $u[0] . "'"
                    ),
                    new Prephp_Token(
                        T_DOUBLE_ARROW,
                        '=>'
                    ),
                    $u[1] ? '&' : null, // insert & if is ref
                    new Prephp_Token(
                        T_VARIABLE,
                        '$' . $u[0]
                    ),
                    ','
                );
            }
            
            $aRegisterGlobals[] = ')'; // close array(
            $aRegisterGlobals[] = ';'; // EOS
            
            // insert after last statement before lambda
            $tokenStream->insert($tokenStream->findEOS($iFunction, true)+1,
                $aRegisterGlobals
            );
        }
        
        // now insert lambda function code
        
        // prepare redefinition of use() vars
        $aRedeclareVars = array();
        if (!empty($use)) {
            // redeclare use()d vars
            $aRedeclareVars = array(
                new Prephp_Token(
                    T_WHITESPACE,
                    "\n"
                ),
            );
            
            foreach ($use as $u) {
                array_push($aRedeclareVars,
                    new Prephp_Token(
                        T_VARIABLE,
                        '$'.$u[0]
                    ),
                    '=',
                    $u[1] ? '&' : null, // insert & if isRef
                    new Prephp_Token(
                        T_VARIABLE,
                        '$GLOBALS'
                    ),
                    '[',
                        new Prephp_Token(
                            T_CONSTANT_ENCAPSED_STRING,
                            "'" . $funcName . "'"
                        ),
                    ']',
                    '[',
                        new Prephp_Token(
                            T_CONSTANT_ENCAPSED_STRING,
                            "'" . $u[0] . "'"
                        ),
                    ']',
                    ';',
                    new Prephp_Token(
                        T_WHITESPACE,
                        "\n"
                    )
                );
            }		
        }
        
        // insert lambda function code after first T_OPEN_TAG
        $tokenStream->insert($tokenStream->find(0, T_OPEN_TAG)+1,
            array(
                new Prephp_Token(
                    T_FUNCTION,
                    'function'
                ),
                new Prephp_Token(
                    T_WHITESPACE,
                    ' '
                ),
                new Prephp_Token(
                    T_STRING,
                    $funcName
                ),
                $sArguments,
                '{',
                    $aRedeclareVars,
                    $sDefinition,
                '}',
                new Prephp_Token(
                    T_WHITESPACE,
                    "\n"
                ),
            )
        );
    }
?>