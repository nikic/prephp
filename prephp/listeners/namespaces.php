<?php
    class Prephp_Namespace
    {
        // replace T_NS_SEPARATOR by
        const SEPARATOR = '__N__';
        
        // current namespace
        private static $ns;
        
        // current aliases as
        // array('Hi' => 'Test\Hi')
        private static $use;
        
        // false if not a class or line where class ends
        private static $classTill;

        // reset on new file (registered as sourcePreparator)
        public static function reset($source) {
            self::$ns        = '';
            self::$use       = array();
            self::$classTill = false;
            
            return $source;
        }
        
        // T_NAMESPACE may be either a namespace declaration ( namespace Foo\Bar[;{] )
        // or a namespace lookup ( namespace\Foo\Bar )
        public static function NS($tokenStream, $iNS) {
            $numof = count($tokenStream);
            
            $ns = '';
            $last = 0;
            for ($i = $tokenStream->skipWhitespace($iNS); $i < $numof; ++$i) {
                if ($tokenStream[$i]->is(T_WHITESPACE)) {
                    continue;
                }
                
                if ($tokenStream[$i]->is(T_NS_SEPARATOR)) {
                    if ($last == T_NS_SEPARATOR) {
                        throw new Prephp_Exception('NS: A T_NS_SEPARATOR must not be followed by another T_NS_SEPARATOR');
                    }
                }
                elseif ($tokenStream[$i]->is(T_STRING)) {
                    if ($last == T_STRING) {
                        throw new Prephp_Exception('NS: Two T_STRING namespace parts must be separated by a T_NS_SEPARATOR');
                    }
                }
                else {
                    break;
                }
                
                $ns  .= $tokenStream[$i]->content;
                $last = $tokenStream[$i]->type;
            }
            
            // namespace declaration
            if ($ns == '' || $ns[0] != '\\') {
                self::$ns = $ns;
                self::$use = array();
                self::$classTill = false;
                
                // semicolon style
                if ($tokenStream[$i]->is(T_SEMICOLON)) {
                    // remove the ; too
                    $tokenStream->extract($iNS, $i);
                }
                // bracket style
                elseif ($tokenStream[$i]->is(T_OPEN_CURLY)) {
                    // leave the {
                    $tokenStream->extract($iNS, $i - 1);
                }
                else {
                    throw new Prephp_Exception("NS: A namespace declaration must be followed by ';' or '{'");
                }
                
                // At this point one may notice, that prephp does allow mixing
                // semicolon and bracket style
            }
            // namespace lookup:
            // replace T_NAMESPACE with current namespace
            // and fully qualify it
            else {
                $tokenStream->extract($iNS);
                
                if (self::$ns == '') {
                    return;
                }
                
                $aReplace = array();
                foreach (explode('\\', self::$ns) as $part) {
                    $aReplace[] = new Prephp_Token(
                        T_NS_SEPARATOR,
                        '\\'
                    );
                    $aReplace[] = new Prephp_Token(
                        T_STRING,
                        $part
                    );
                }
                
                $tokenStream->insert($iNS, $aReplace);
            }
        }
        
        // register use
        public static function alias($tokenStream, $iUse) {
            $iEOS = $tokenStream->find($iUse, T_SEMICOLON);
            
            if (false === $iEOS) {
                throw new Prephp_Exception("NS: Alias (use) definition is not terminated by ';'");
            }
            
            $last = T_USE;
            $current = '';
            $as = false;
            $i = $iUse + 1; // skip T_USE
            while ($i++ < $iEOS) { // till EOS (inclusive)
                if ($tokenStream[$i]->is(T_WHITESPACE)) {
                    continue;
                }
                
                if ($tokenStream[$i]->is(T_AS)) {
                    $as = '';
                }
                elseif ($tokenStream[$i]->is(T_STRING)) {
                    if ($last == T_STRING) {
                        throw new Prephp_Exception('NS: Two T_STRINGs in alias (use) declaration must be separated by a T_NS_SEPARATOR');
                    }
                    
                    if ($last == T_AS) {
                        $as = $tokenStream[$i]->content;
                    }
                    else {
                        $current .=	$tokenStream[$i]->content;
                    }
                }
                elseif ($tokenStream[$i]->is(T_NS_SEPARATOR)) {
                    if ($last == T_NS_SEPARATOR) {
                        throw new Prephp_Exception('NS: A T_NS_SEPARATOR in an alias (use) declaration must not be preceeded by another T_NS_SEPARATOR');
                    }
                    
                    if ($as !== false) {
                        throw new Prephp_Exception('NS: The as section of an alias (use) declaration must not contain a T_NS_SEPARATOR');
                    }
                    
                    $current .= $tokenStream[$i]->content;
                }
                elseif ($tokenStream[$i]->is(array(T_COMMA, T_SEMICOLON))) {
                    if ($last != T_STRING) {
                        throw new Prephp_Exception("NS: A ',' or ';' in an alias (use) declaration must be preceeded by a T_STRING");
                    }
                    
                    self::$use[$as !== false ? $as : substr($current, strrpos($current, '\\') + 1)] = ($current[0] == '\\' ? '' : '\\') . $current;
                    $as = false;
                    $current = '';
                }
                else {
                    throw new Prephp_Exception('NS: Found ' . $tokenStream[$i]->name . '. Only T_STRING, T_NS_SEPARATOR, T_AS and T_COMMA are allowed in an alias (use) declaration');
                }
                
                $last = $tokenStream[$i]->type;
            }
            
            $tokenStream->extract($iUse, $iEOS);
        }
    
        // register classes
        public static function registerClass($tokenStream, $iClass) {
            $iName = $tokenStream->skipWhitespace($iClass);

            $ns = str_replace('\\', self::SEPARATOR, self::$ns);
            $tokenStream[$iName] = new Prephp_Token(
                T_STRING,
                ($ns ? $ns . self::SEPARATOR : '') . $tokenStream[$iName]->content
            );
            
            $iStart = $tokenStream->find($iName, T_OPEN_CURLY);
            if ($iStart === false) {
                throw new Prephp_Exception("NS class registration: Unexpected END, expected '{'");
            }
            $iEnd   = $tokenStream->complementaryBracket($iStart);
            
            self::$classTill = $tokenStream[$iEnd]->line;
        }
        
        // register non-classes (functions and constants)
        public static function registerOther($tokenStream, $iKeyword) {
            // first check if we are in a class
            if (self::$classTill !== false && self::$classTill >= $tokenStream[$iKeyword]->line) {
                return;
            }
            
            $iName = $tokenStream->skipWhitespace($iKeyword);
            
            $ns = str_replace('\\', self::SEPARATOR, self::$ns);
            $tokenStream[$iName] = new Prephp_Token(
                T_STRING,
                ($ns ? $ns . self::SEPARATOR : '') . $tokenStream[$iName]->content
            );
        }
        
        // tokenCompiler on T_NS_C
        public static function NS_C($token) {
            return "'" . self::$ns . "'";
        }
        
        // resolves non-variable namespace calls
        public static function resolve($tokenStream, $iStart) {
            // ensure it's not a definition and not a scope resolution or object access
            if ($tokenStream[$tokenStream->skipWhitespace($iStart, true)]->is(T_CLASS, T_FUNCTION, T_CONST, T_PAAMAYIM_NEKUDOTAYIM, T_OBJECT_OPERATOR)) {
                return; // in defintion
            }
            
            // skip true, false and null constants, they may not be overwritten
            if ($tokenStream[$iStart]->is(T_STRING)
                && in_array($tokenStream[$iStart]->content, array('true', 'false', 'null'))) {
                return;
            }
            
            if ($tokenStream[$tokenStream->skipWhitespace($iStart)]->is(T_OPEN_ROUND)) {
                // these functions are under prephp's protection
                if (in_array($tokenStream[$iStart]->content, array(
                    'call_user_func',
                    'prephp_rt_prepareInclude',
                    'prephp_rt_preparePath',
                    'prephp_rt_arrayAccess',
                    'prephp_rt_checkFunction',
                    'prephp_rt_checkConstant',
                ))) {
                    return;
                }
            }
            
            $numof = count($tokenStream);
            
            $ns = '';
            $last = 0;
            for ($i = $iStart; $i < $numof && $tokenStream[$i]->is(T_STRING, T_NS_SEPARATOR, T_WHITESPACE); ++$i) {
                if ($tokenStream[$i]->is(T_WHITESPACE)) {
                    continue;
                }
                
                if ($last == T_NS_SEPARATOR && $tokenStream[$i]->is(T_NS_SEPARATOR)) {
                    throw new Prephp_Exception('NS Resolution: A T_NS_SEPARATOR may not be preceeded by another T_NS_SEPARATOR');
                }
                
                if ($last == T_STRING && $tokenStream[$i]->is(T_STRING)) {
                    throw new Prephp_Exception('NS Resolution: A T_STRING may not be preceeded by another T_STRING');
                }
                
                $ns .= $tokenStream[$i]->content;
            }
            
            $tokenStream->extract($iStart, $i - 1); // we went one too far
            
            $current = self::$ns;
            
            // aliases
            // qualified (namespace aliases)
            if ($firstNSSeparator = strpos($ns, '\\')) {
                $ns_before = substr($ns, 0, $firstNSSeparator);
                
                // if alias replace part before first ns separator
                if (isset(self::$use[$ns_before])) {
                    $ns = substr_replace($ns, self::$use[$ns_before], 0, $firstNSSeparator);
                }
                // if no alias, prepend current ns
                else {
                    $ns = '\\' . ($current == '' ? '' : $current . '\\') . $ns;
                }
            }
            // unqualified (class aliases)
            else {
                // check that is class and then apply class alias if exists
                if (isset(self::$use[$ns])
                    && ($tokenStream[$tokenStream->skipWhitespace($iStart, true)]->is(T_NEW)
                    || $tokenStream[$iStart]->is(T_PAAMAYIM_NEKUDOTAYIM, T_VARIABLE)
                    || $tokenStream[$iStart + 1]->is(T_PAAMAYIM_NEKUDOTAYIM, T_VARIABLE)
                    )
                ) {
                    $ns = self::$use[$ns];
                }
            }
            
            // replace spl autoload functions by prephp autoloader
            $splAutoloadFunctions = array(
                'spl_autoload_register'     => 'register',
                'spl_autoload_unregister'   => 'unregister',
                'spl_autoload_call'         => 'call',
                'spl_autoload_functions'    => 'functions',
                '\\spl_autoload_register'   => 'register',
                '\\spl_autoload_unregister' => 'unregister',
                '\\spl_autoload_call'       => 'call',
                '\\spl_autoload_functions'  => 'functions',
            );
            if (isset($splAutoloadFunctions[$ns])) {
                $tokenStream->insert($iStart, array(
                    new Prephp_Token(
                        T_STRING,
                        'Prephp_RT_Autoload'
                    ),
                    new Prephp_Token(
                        T_PAAMAYIM_NEKUDOTAYIM,
                        '::'
                    ),
                    new Prephp_Token(
                        T_STRING,
                        $splAutoloadFunctions[$ns]
                    ),
                ));
                return;
            }
            
            // for (now) fully qualified
            if ($ns[0] == '\\') {
                $tokenStream->insert($iStart,
                    new Prephp_Token(
                        T_STRING,
                        str_replace('\\', self::SEPARATOR, substr($ns, 1))
                    )
                );
                return;
            }
            // and now unqualified (there aren't qualified ones any more)
            else {
                // if global space and unnamespaced than so direct insert
                if ($current == '' && false === strpos('\\', $ns)) {
                    $tokenStream->insert($iStart,
                        new Prephp_Token(
                            T_STRING,
                            $ns
                        )
                    );
                    return;
                }
                
                // determine whether we are dealing with a class, function or const lookup
                if ($tokenStream[$iStart]->is(T_PAAMAYIM_NEKUDOTAYIM, T_VARIABLE)
                    || $tokenStream[$tokenStream->skipWhitespace($iStart)]->is(T_PAAMAYIM_NEKUDOTAYIM, T_VARIABLE)) {
                    $type = T_CLASS;
                }
                elseif ($tokenStream[$iStart]->is(T_OPEN_ROUND)
                    || $tokenStream[$tokenStream->skipWhitespace($iStart)]->is(T_OPEN_ROUND)) {
                    if ($tokenStream[$tokenStream->skipWhitespace($iStart, true)]->is(T_NEW)) {
                        $type = T_CLASS;
                    }
                    else {
                        $type = T_FUNCTION;
                    }
                }
                else {
                    $type = T_CONST;
                }
                
                if ($type == T_CLASS) {
                    $tokenStream->insert($iStart,
                        new Prephp_Token(
                            T_STRING,
                            str_replace('\\', self::SEPARATOR, ($current == '' ? '' : $current . '\\') . $ns)
                        )
                    );
                }
                else {
                    $tokenStream->insert($iStart,
                        array(
                            new Prephp_Token(
                                T_STRING,
                                'prephp_rt_check' . ($type == T_FUNCTION ? 'Function' : 'Constant')
                            ),
                            '(',
                                new Prephp_Token(
                                    T_CONSTANT_ENCAPSED_STRING,
                                    "'" . str_replace('\\', self::SEPARATOR, ($current == '' ? '' : $current . '\\') . $ns) . "'"
                                ),
                                ',',
                                new Prephp_Token(
                                    T_CONSTANT_ENCAPSED_STRING,
                                    "'" . str_replace('\\', self::SEPARATOR, $ns) . "'"
                                ),
                            ')',
                        )
                    );
                }
            }
        }
    }
?>