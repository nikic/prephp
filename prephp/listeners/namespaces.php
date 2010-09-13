<?php
    class Prephp_Namespace
    {
        // replace T_NS_SEPARATOR by
        const SEPARATOR = '__N__';
        
        // if true it is assumed that all unqualified
        // functions and constants which are defined globally
        // are global
        const assumeGlobal = true;
        
        // current namespace
        private static $ns;
        
        // current aliases as
        // array('Hi' => 'Test\Hi')
        private static $use;
        
        // false if not a class or line where class ends
        private static $classTill;

        // reset on new file
        public static function reset() {
            self::$ns        = '';
            self::$use       = array();
            self::$classTill = 0;
        }
        
        // T_NAMESPACE may be either
        // namespace declaration ( namespace Foo\Bar[;{] )
        // namespace lookup ( namespace\Foo\Bar )
        public static function NS($tokenStream, $iNS) {
            // get label
            $numof = count($tokenStream);
            $ns = '';
            $last = 0;
            for ($i = $iNS + 1; $i < $numof && $tokenStream[$i]->is(T_STRING, T_NS_SEPARATOR, T_WHITESPACE); ++$i) {
                if ($tokenStream[$i]->is(T_WHITESPACE)) {
                    continue;
                }
                
                if ($last == $tokenStream[$i]->type) {
                    throw new Prephp_TokenException('NS Registration: There mustn\'t be two consecutive ' . $tokenStream[$i]->name);
                }
                
                $ns  .= $tokenStream[$i]->content;
                $last = $tokenStream[$i]->type;
            }
            
            // namespace declaration
            if ($ns == '' || $ns[0] != '\\') {
                self::$ns = $ns;
                self::$use = array();
                
                // semicolon style
                if ($tokenStream[$i]->is(T_SEMICOLON)) {
                    // remove the ; and the whitespace after it (if there is any)
                    $tokenStream->extract($iNS, $i + (int) $tokenStream[$i + 1]->is(T_WHITESPACE));
                }
                // bracket style
                elseif ($tokenStream[$i]->is(T_OPEN_CURLY)) {
                    // leave the {
                    $tokenStream->extract($iNS, $i - 1);
                }
                else {
                    throw new Prephp_TokenException("NS: A namespace declaration must be followed by ';' or '{'. Found " . $tokenStream[$i]->name);
                }
                
                // At this point one may notice, that prephp does allow mixing
                // semicolon and bracket style and allows global ns declarations
                // using semicolon style
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
        
        // alias (use) declaration
        public static function alias($tokenStream, $iUse) {
            // lambda function, not alias
            if ($tokenStream[$tokenStream->skipWhitespace($iUse)]->is(T_OPEN_ROUND)) {
                return;
            }
            
            $iEOS = $tokenStream->find($iUse, T_SEMICOLON);
            if (false === $iEOS) {
                throw new Prephp_TokenException("NS: Alias (use) definition is not terminated by ';'");
            }
            
            $last    = T_USE;
            $current = ''; // long name
            $as      = ''; // alias for long name
            $i       = $iUse + 1; // skip T_USE
            while ($i++ < $iEOS) { // till EOS (inclusive)
                if ($tokenStream[$i]->is(T_WHITESPACE)) {
                    continue;
                }
                
                if (!$tokenStream[$i]->is(T_AS, T_STRING, T_NS_SEPARATOR, T_COMMA, T_SEMICOLON)) {
                    throw new Prephp_TokenException('NS alias (use) declaration: Unexcpected ' . $tokenStream[$i]->name . '. Expected T_STRING, T_NS_SEPARATOR, T_AS or T_COMMA');
                }
                
                if ($last == $tokenStream[$i]->type) {
                    throw new Prephp_TokenException('NS alias (use) declaration: There mustn\'t be two consecutive ' . $tokenStream[$i]->name);
                }
                
                
                if ($tokenStream[$i]->is(T_STRING)) {
                    if ($last == T_AS) {
                        $as = $tokenStream[$i]->content;
                    }
                    else {
                        $current .=	$tokenStream[$i]->content;
                    }
                }
                elseif ($tokenStream[$i]->is(T_NS_SEPARATOR)) {
                    if ($as != '') {
                        throw new Prephp_TokenException('NS: The as section of an alias (use) declaration must not contain a T_NS_SEPARATOR');
                    }
                    
                    $current .= $tokenStream[$i]->content;
                }
                elseif ($tokenStream[$i]->is(T_COMMA, T_SEMICOLON)) {
                    if ($last != T_STRING) {
                        throw new Prephp_TokenException("NS: A ',' or ';' in an alias (use) declaration must be preceeded by a T_STRING");
                    }
                    
                    self::$use[$as !== '' ? $as : substr($current, strrpos($current, '\\') + 1)] = ($current[0] == '\\' ? '' : '\\') . $current;
                    $current = $as = '';
                }
                
                $last = $tokenStream[$i]->type;
            }
            
            // get rid of whitespace, too
            if ($tokenStream[$iEOS + 1]->is(T_WHITESPACE)) {
                ++$iEOS;
            }
            
            $tokenStream->extract($iUse, $iEOS);
            
            // process multiple use statements next to each other
            if ($tokenStream[$iUse]->is(T_USE)) {
                self::alias($tokenStream, $iUse);
            }
        }
    
        // register classes
        public static function registerClass($tokenStream, $iClass) {
            $iName = $tokenStream->skipWhitespace($iClass);

            $ns = str_replace('\\', self::SEPARATOR, self::$ns);
            $tokenStream[$iName]->content = ($ns ? $ns . self::SEPARATOR : '') . $tokenStream[$iName]->content;
            
            $iStart = $tokenStream->find($iName, T_OPEN_CURLY);
            if ($iStart === false) {
                throw new Prephp_TokenException("NS class registration: Unexpected END, expected '{'");
            }
            
            self::$classTill = $tokenStream[$tokenStream->complementaryBracket($iStart)]->line;
        }
        
        // register non-classes (functions and constants)
        public static function registerOther($tokenStream, $iKeyword) {
            // first check if we are in a class
            if (self::$classTill > $tokenStream[$iKeyword]->line) {
                return;
            }
            
            $iName = $tokenStream->skipWhitespace($iKeyword);
            
            $ns = str_replace('\\', self::SEPARATOR, self::$ns);
            $tokenStream[$iName]->content = ($ns ? $ns . self::SEPARATOR : '') . $tokenStream[$iName]->content;
        }
        
        // tokenCompiler on T_NS_C
        public static function NS_C($token) {
            return "'" . self::$ns . "'";
        }
        
        // resolves non-variable namespace calls
        public static function resolve($tokenStream, $iStart) {
            // ensure it's not a definition and not a scope resolution or object access
            if ($tokenStream[$tokenStream->skipWhitespace($iStart, true)]->is(T_CLASS, T_INTERFACE, T_FUNCTION, T_CONST, T_PAAMAYIM_NEKUDOTAYIM, T_OBJECT_OPERATOR)) {
                return; // in defintion
            }
            
            // get label
            $numof = count($tokenStream);
            $ns = '';
            $last = 0;
            for ($i = $iStart; $i < $numof && $tokenStream[$i]->is(T_STRING, T_NS_SEPARATOR, T_WHITESPACE); ++$i) {
                if ($tokenStream[$i]->is(T_WHITESPACE)) {
                    continue;
                }
                
                if ($last == $tokenStream[$i]->type) {
                    throw new Prephp_TokenException('NS Resolution: There mustn\'t be two consecutive ' . $tokenStream[$i]->name);
                }
                
                $ns  .= $tokenStream[$i]->content;
                $last = $tokenStream[$i]->type;
            }
            
            // determinte type (class, function or const)
            $type = T_CONST;
            if ($tokenStream[$i]->is(T_PAAMAYIM_NEKUDOTAYIM, T_VARIABLE)
                || $tokenStream[$tokenStream->skipWhitespace($iStart, true)]->is(T_NEW, T_EXTENDS, T_IMPLEMENTS, T_INSTANCEOF)
            ) {
                $type = T_CLASS;
            }
            elseif ($tokenStream[$i]->is(T_OPEN_ROUND)) {
                $type = T_FUNCTION;
            }
            
            // skip self and parent classes, prephp_ functions and true, false and null constants
            if (   ($type == T_CLASS    && ($ns == 'self' || $ns == 'parent'))
                || ($type == T_FUNCTION && substr($ns, 0, 7) == 'prephp_')
                || ($type == T_CONST    && in_array($ns, array('true', 'false', 'null')))
            ) {
                return;
            }
            
            // remove unresolved label (but leave whitespace after it)
            $tokenStream->extract($iStart, $tokenStream->skipWhitespace($i, true));
            
            // replace spl_autoload functions with Prephp_RT_Autoload methods
            if ($type == T_FUNCTION
                && (false !== $pos = strpos($ns, 'spl_autoload_'))
                && ($pos == 0
                    || ($pos == 1 && $ns[0] == '\\'))
            ) {
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
                        substr($ns, strrpos($ns, '_'))
                    ),
                ));
                return;
            }
            
            // resolve aliases
            // qualified (namespace aliases)
            if ($firstNSSeparator = strpos($ns, '\\')) {
                $nsBefore = substr($ns, 0, $firstNSSeparator);
                
                // if alias replace part before first ns separator
                if (isset(self::$use[$nsBefore])) {
                    $ns = substr_replace($ns, self::$use[$nsBefore], 0, $firstNSSeparator);
                }
                // if no alias, prepend current ns
                else {
                    $ns = '\\' . (self::$ns == '' ? '' : self::$ns . '\\') . $ns;
                }
            }
            // unqualified (class aliases)
            else {
                // check that is class and then apply class alias if exists
                if ($type == T_CLASS && isset(self::$use[$ns])) {
                    $ns = self::$use[$ns];
                }
            }
            
            // for (now) fully qualified
            if ($ns[0] == '\\') {
                $tokenStream->insert($iStart,
                    new Prephp_Token(
                        T_STRING,
                        str_replace('\\', self::SEPARATOR, substr($ns, 1))
                    )
                );
            }
            // and now unqualified (there aren't qualified ones any more)
            else {
                // if in global namespace direct insert
                if (self::$ns == '') {
                    $tokenStream->insert($iStart,
                        new Prephp_Token(
                            T_STRING,
                            $ns
                        )
                    );
                    return;
                }
                
                // if class prepend current namespace
                if ($type == T_CLASS) {
                    $tokenStream->insert($iStart,
                        new Prephp_Token(
                            T_STRING,
                            str_replace('\\', self::SEPARATOR, self::$ns . '\\' . $ns)
                        )
                    );
                }
                // function or constant
                else {
                    // check whether defined as global function/constant
                    if (self::assumeGlobal && ($type == T_FUNCTION ? function_exists($ns) : defined($ns))) {
                        $tokenStream->insert($iStart,
                            new Prephp_Token(
                                T_STRING,
                                $ns
                            )
                        );
                        return;
                    }
                    
                    // temporary variable to hold resolved function name / constant name
                    $tVariable = new Prephp_Token(
                        T_VARIABLE,
                        '$prephp_' . ($type == T_FUNCTION ? 'f' : 'c') . '_' . $ns
                    );
                    
                    // insert function call / constant lookup
                    if ($type == T_FUNCTION) {
                        $tokenStream->insert($iStart, $tVariable);
                    }
                    else {
                        $tokenStream->insert($iStart, array(
                            new Prephp_Token(
                                T_STRING,
                                'constant'
                            ),
                            '(',
                                $tVariable,
                            ')',
                        ));
                    }
                    
                    // insert function / constant existance check
                    $tokenStream->insert($tokenStream->findEOS($iStart, true) + 1, array(
                        new Prephp_Token(
                            T_WHITESPACE,
                            "\n"
                        ),
                        new Prephp_Token(
                            T_IF,
                            'if'
                        ),
                        '(',
                            '!',
                            new Prephp_Token(
                                T_STRING,
                                $type == T_FUNCTION ? 'function_exists' : 'defined'
                            ),
                            '(',
                                $tVariable,
                                '=',
                                new Prephp_Token(
                                    T_CONSTANT_ENCAPSED_STRING,
                                    "'" . str_replace('\\', self::SEPARATOR, self::$ns . '\\' . $ns) . "'"
                                ),
                            ')',
                        ')',
                        '{',
                            $tVariable,
                            '=',
                            new Prephp_Token(
                                T_CONSTANT_ENCAPSED_STRING,
                                "'" . $ns . "'"
                            ),
                            ';',
                        '}',
                    ));
                }
            }
        }
        
        // resolve a variable class instantiation
        // (will implement other stuff later)
        public static function resolveNew($tokenStream, $i) {
            $iName = $tokenStream->skipWhitespace($i);
            
            // not a variable class instantiation
            if ($iName === false || !$tokenStream[$iName]->is(T_VARIABLE)) {
                return;
            }
            
            // remove variable and fetch varname
            $tOldVar = $tokenStream->extract($iName);
            $varName = substr($tOldVar->content, 1);
            
            $tVariable = new Prephp_Token(
                T_VARIABLE,
                '$prephp_C_' . $varName
            );
            
            // insert temporary variable instead
            $tokenStream->insert($iName, $tVariable);
            
            // insert classname preparation code
            $tokenStream->insert($tokenStream->findEOS($i, true) + 1, array(
                new Prephp_Token(
                    T_WHITESPACE,
                    "\n"
                ),
                $tVariable,
                '=',
                new Prephp_Token(
                    T_STRING,
                    'str_replace'
                ),
                '(',
                    new Prephp_Token(
                        T_CONSTANT_ENCAPSED_STRING,
                        "'\\\\'"
                    ),
                    ',',
                    new Prephp_Token(
                        T_CONSTANT_ENCAPSED_STRING,
                        "'" . self::SEPARATOR . "'"
                    ),
                    ',',
                    $tOldVar,
                ')',
                ';'
            ));
        }
    }