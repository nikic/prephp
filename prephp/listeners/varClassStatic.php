<?php
    function prephp_varClassStatic($tokenStream, $iStart) {
        $i = $iStart;
        $numof = count($tokenStream);
        
        // count number of tokens before $class
        $dollarClass = 0;
        for (; $i<$numof && $tokenStream[$i]->is(T_DOLLAR); ++$i) {
            ++$dollarClass;
        }
        
        // not a dynmaic scope resolution
        if (!$tokenStream[$i]->is(T_VARIABLE)) {
            return;
        }
        
        $tClass = $tokenStream[$i];
        
        // not a scope resolution
        if (!$tokenStream[$i = $tokenStream->skipWhitespace($i)]->is(T_PAAMAYIM_NEKUDOTAYIM)) {
            return;
        }
        
        $i = $tokenStream->skipWhitespace($i);
        
        // count number of dollarMains before main
        $dollarMain = 0;
        for (; $i<$numof && $tokenStream[$i]->is(T_DOLLAR); ++$i) {
            ++$dollarMain;
        }
        
        // unsupported syntax (something like ${})
        if (!$tokenStream[$i]->is(T_STRING, T_VARIABLE)) {
            return;
        }
        
        $iMain = $i;
        
        if ($tokenStream[$i = $tokenStream->skipWhitespace($i)]->is(T_OPEN_ROUND)) {
            $type = 'm'; // method
        }
        else {
            $type = $tokenStream[$iMain]->is(T_STRING) ? 'c' : 'p'; // constant or property
        }
        
        switch ($type) {
            case 'c': // constant
                $constName = $tokenStream[$iMain]->content;
                
                $tokenStream->extract($iStart, $iMain); // remove
                $tokenStream->insert($iStart, array(
                    new Prephp_Token(
                        T_STRING,
                        'constant'
                    ),
                    '(',
                        $dollarClass ? array_fill(0, $dollarClass, '$') : null,
                        $tClass,
                        '.',
                        new Prephp_Token(
                            T_CONSTANT_ENCAPSED_STRING,
                            "'::" . $constName . "'"
                        ),
                    ')',
                ));
                break;
            case 'm':
                $tMethod = $tokenStream[$iMain];
                if ($tMethod->is(T_STRING)) {
                    $tMethod = new Prephp_Token(
                        T_CONSTANT_ENCAPSED_STRING,
                        "'" . $tMethod->content . "'"
                    );
                }
                
                $sArgumentList = $tokenStream->extract($i, $tokenStream->complementaryBracket($i));
                $sArgumentList->extract(0); // remove (
                $sArgumentList->extract(count($sArgumentList)-1); // remove )
                
                $tokenStream->extract($iStart, $iMain);
                $tokenStream->insert($iStart, array(
                    new Prephp_Token(
                        T_STRING,
                        'call_user_func'
                    ),
                    '(',
                        new Prephp_Token(
                            T_ARRAY,
                            'array'
                        ),
                        '(',
                            $dollarClass ? array_fill(0, $dollarClass, '$') : null,
                            $tClass,
                            ',',
                            $tMethod->is(T_VARIABLE) && $dollarMain ? array_fill(0, $dollarMain, '$') : null,
                            $tMethod,
                        ')',
                        count($sArgumentList) ? ',' : null,
                        $sArgumentList,
                    ')'
                ));
                break;
            case 'p':
                if ($dollarMain) {
                    $aProperty = array(
                        $dollarMain - 1 > 0 ? array_fill(0, $dollarMain - 1, '$') : null,
                        $tokenStream[$iMain]
                    );
                }
                else {
                    $aProperty = new Prephp_Token( // should be $tProperty, but use a here for simplicity
                        T_CONSTANT_ENCAPSED_STRING,
                        "'" . substr($tokenStream[$iMain]->content, 1) . "'"
                    );
                }
                
                $tokenStream->extract($iStart, $iMain);
                $tokenStream->insert($iStart, array(
                    '(', // encapsulate everything in brackets
                        new Prephp_Token(
                            T_STRING,
                            'property_exists' // checks visibility too in PHP < 5.3, so okay here
                        ),
                        '(',
                            $dollarClass ? array_fill(0, $dollarClass, '$') : null,
                            $tClass,
                            ',',
                            $aProperty,
                        ')',
                        '?',
                            new Prephp_Token(
                                T_EVAL,
                                'eval'
                            ),
                            '(',
                                new Prephp_Token(
                                    T_CONSTANT_ENCAPSED_STRING,
                                    "'return '"
                                ),
                                '.',
                                $dollarClass ? array_fill(0, $dollarClass, '$') : null,
                                $tClass,
                                '.',
                                new Prephp_Token(
                                    T_CONSTANT_ENCAPSED_STRING,
                                    "'::$'"
                                ),
                                '.',
                                $aProperty,
                                '.',
                                new Prephp_Token(
                                    T_CONSTANT_ENCAPSED_STRING,
                                    "';'"
                                ),
                            ')',
                        ':',
                        new Prephp_Token(
                            T_STRING,
                            'null'
                        ),
                    ')',
                ));
                break;
        }
    }
?>