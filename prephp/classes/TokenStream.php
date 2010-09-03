<?php
    require_once 'TokenException.php';
    require_once 'Token.php';
    
    class Prephp_TokenStream implements Countable, ArrayAccess, Iterator
    {
        protected $tokens = array();
        
        /**
        * create TokenStream from source
        * @param string $source code (including <?php)
        */
        public function __construct($source = '') {
            // fast abort on empty source
            if ($source == '') {
                return;
            }
            
            // capture errors
            ob_start();
            
            $tokens = token_get_all($source);
            
            $line = 1;
            foreach ($tokens as $token) {
                if (is_string($token)) {
                    $this->tokens[] = Prephp_Token::newCharToken($token, $line);
                }
                else {
                    $this->tokens[] = new Prephp_Token(
                        $token[0],
                        $token[1],
                        $line
                    );
                    
                    $line += substr_count($token[1], "\n");
                }
            }
            
            // if there are errors, e.g.
            // <b>Warning</b>:  Unexpected character in input:  '\' (ASCII=92) state=1 in [...]
            // iterate through all tokens and compare to source
            if (ob_get_clean() != '') {
                $i = 0; // string offset in source
                $count = count($this->tokens);
                for ($n = 0; $n < $count; ++$n) {
                    $length = strlen($this->tokens[$n]->content);
                    if (substr($source, $i, $length) == $this->tokens[$n]->content) {
                        $i += $length;
                    } else { // token was missing
                        $this->insert($n, $source[$i]);
                        ++$i;
                        ++$count;
                    }
                }
            }
            
            // replace T_STRINGs with new PHP 5.3 tokens
            $replace = array(
                'goto'          => T_GOTO,
                'use'           => T_USE,
                'namespace'     => T_NAMESPACE,
                '__NAMESPACE__' => T_NS_C,
                '__DIR__'       => T_DIR,
            );
            
            for ($numof = count($this->tokens), $i = 0; $i < $numof; ++$i) {
                if ($this->tokens[$i]->type == T_STRING && isset($replace[$this->tokens[$i]->content])) {
                    $this->tokens[$i]->type = $this->tokens[$i]->content;
                }
            }
        }
        
        /*
            Search methods
        */
        
        /**
        * finds next token of given type
        * @param int $i
        * @param int|array $tokens token or array of tokens to search for
        * @param bool $reverse if true finds previous instead of next token
        * @return int|false returns false if no token found
        */
        public function find($i, $tokens, $reverse = false) {
            if ($reverse) { // find previous
                while ($i--) {
                    if ($this->tokens[$i]->is($tokens)) {
                        return $i;
                    }
                }
            } else { // find next
                $numof = count($this->tokens);
                while (++$i < $numof) {
                    if ($this->tokens[$i]->is($tokens)) {
                        return $i;
                    }
                }
            }
            
            return false;
        }
        
        /**
        * finds next token which is not of given type
        * @param int $i
        * @param int|array $tokens token or array of tokens to skip
        * @param bool $reverse if true skips backwards
        * @return int|false returns false if no token found
        */
        public function skip($i, $tokens, $reverse = false) {
            if ($reverse) { // find previous
                while ($i--) {
                    if (!$this->tokens[$i]->is($tokens)) {
                        return $i;
                    }
                }
            } else { // find next
                $numof = count($this->tokens);
                while (++$i < $numof) {
                    if (!$this->tokens[$i]->is($tokens)) {
                        return $i;
                    }
                }
            }
            
            return false;
        }
        
        /**
        * skips whitespace (shortcut for skip($i, T_WHITESPACE)
        * @param int $i
        * @param bool $reverse if true skips backwards
        * @return int|false returns false if no token found
        */
        public function skipWhitespace($i, $reverse = false) {
            return $this->skip($i, T_WHITESPACE, $reverse);
        }
        
        /**
        * finds next end of statement (that is, a position after which new code may be inserted)
        * @param int $i
        * @param bool $reverse if true finds backwords
        * @return int|false returns false if no token found
        */
        public function findEOS($i, $reverse = false) {
            if ($reverse) { // find previous
                while ($i--) {
                    if ($this->tokens[$i]->is(T_SEMICOLON, T_OPEN_TAG)
                        || ($this->tokens[$i]->is(T_CLOSE_CURLY)
                            && (!($next = $this->skipWhitespace($i)) // check that it's no lambda
                                || !$this->tokens[$next]->is(T_COMMA, T_CLOSE_ROUND, T_SEMICOLON)
                                )
                        )
                        || ($this->tokens[$i]->is(T_OPEN_CURLY) // check that it's no lambda
                            && !$this->tokens[$this->complementaryBracket($i)]->is(T_COMMA, T_CLOSE_ROUND, T_SEMICOLON)
                        )
                    ) {
                        return $i;
                    } elseif ($this->tokens[$i]->is(T_CLOSE_ROUND, T_CLOSE_SQUARE, T_CLOSE_CURLY)) {
                        $i = $this->complementaryBracket($i);
                    }
                }
            } else { // find next
                $numof = count($this->tokens);
                
                while (++$i < $numof) {
                    if ($this->tokens[$i]->is(T_SEMICOLON, T_CLOSE_TAG)) {
                        return $i;
                    } elseif ($this->tokens[$i]->is(T_OPEN_ROUND, T_OPEN_SQUARE, T_OPEN_CURLY)) {
                        $i = $this->complementaryBracket($i);
                    }
                }
            }
            
            return false;
        }
        
        /**
        * finds comlpementary bracket (direction determined using token type)
        * @param int $i
        * @return int
        * @throws TokenException on incorrect nesting
        */
        public function complementaryBracket($i) {
            $complements = array(
                T_OPEN_ROUND   => T_CLOSE_ROUND,
                T_OPEN_SQUARE  => T_CLOSE_SQUARE,
                T_OPEN_CURLY   => T_CLOSE_CURLY,
                T_CLOSE_ROUND  => T_OPEN_ROUND,
                T_CLOSE_SQUARE => T_OPEN_SQUARE,
                T_CLOSE_CURLY  => T_OPEN_CURLY,
            );
            
            if ($this->tokens[$i]->is(T_CLOSE_ROUND, T_CLOSE_SQUARE, T_CLOSE_CURLY)) {
                $reverse = true; // backwards search
            } elseif ($this->tokens[$i]->is(T_OPEN_ROUND, T_OPEN_SQUARE, T_OPEN_CURLY)) {
                $reverse = false; // forwards search
            } else {
                throw new Prephp_TokenException('Not a bracket');
            }
                
            $type = $this->tokens[$i]->type;
            
            $depth = 1;
            while ($depth > 0) {
                if (false === $i = $this->find($i, array($type, $complements[$type]), $reverse)) {
                    throw new Prephp_TokenException('Opening and closing brackets not matching');
                }
                
                if ($this->tokens[$i]->is($type)) { // opening
                    ++$depth;
                } else { // closing
                    --$depth;
                }
            }

            return $i;
        }
        
        /*
            Stream manipulations
        */
        
        /**
        * append token or stream to stream
        *
        * This function may either be passed a TokenStream, an array of token-like
        * elements or a single token-like element.
        * The array will be appended recursively (thus it can have sub-arrays.)
        * A token-like element is either a Token or a single character mapable to
        * a token. All other elements are dropped, *without* error message.
        *
        * @param mixed $tokenStream
        * @return int number of appended tokens
        */
        public function append($tokenStream) {			
            if (!is_array($tokenStream)) {
                $tokenStream = array($tokenStream);
            }
            
            $count = 0; // number of appended tokens
            foreach ($tokenStream as $token) {
                // instanceof Token: append
                if ($token instanceof Prephp_Token) {
                    $this->tokens[] = $token;
                    ++$count;
                }
                // one char token: append Token resulting from it
                elseif (is_string($token)) {
                    $this->tokens[] = Prephp_Token::newCharToken($token);
                    ++$count;
                }
                // token stream: append each
                elseif ($token instanceof Prephp_TokenStream) {
                    foreach ($token as $t) {
                        $this->tokens[] = $t;
                        ++$count;
                    }
                }
                // token array: recursively append
                elseif (is_array($token)) {
                    $count += $this->append($token);
                }
                // else: drop *without* error message
            }
            
            return $count;
        }
        
        /**
        * inserts a stream at $i
        *
        * This function is implemented on top of appendStream, therefore the notes
        * there apply to the tokenStream being inserted, too.
        *
        * @param int $i offset in token array
        * @param mixed $tokenStream
        */
        public function insert($i, $tokenStream) {
            if ($i == $this->count() - 1) { // end => append
                $this->append($tokenStream);
                return;
            }
            
            // remove following stream to append later
            $after = array_splice($this->tokens, $i);
            
            // "magic" append
            $count = $this->append($tokenStream);
            
            // fix iterator position
            if ($i < $this->position) {
                $this->position += $count;
            }
            
            // append $after
            foreach ($after as $token) {
                $this->tokens[] = $token;
            }
        }
        
        /**
        * get and remove substream or token
        * @param int $i
        * @param int $to
        */
        public function extract($i, $to = null) {
            // fix iterator position
            if ($i < $this->position) {
                $this->position -= $to === null ? 1 : ($to < $this->position ? $to - $i : $this->position - $i);
            }
            
            if ($to === null) {
                // fix iterator position
                if ($i < $this->position && 0 > --$this->position) {
                    $this->position = 0;
                }
                
                $tokens = array_splice($this->tokens, $i, 1, array());
                return $tokens[0];
            } else {
                if ($i < $this->position) {
                    if ($to <= $this->position) {
                        $this->position = $i;
                    } else {
                        $this->position -= $to - $i;
                    }
                    
                    if (--$this->position < 0) {
                        $this->position = 0;
                    }
                }
                
                $tokenStream = new Prephp_TokenStream;
                $tokenStream->append(
                    array_splice($this->tokens, $i, $to - $i + 1, array())
                );
                return $tokenStream;
            }
        }
        
        /**
        * get substream
        * @param int $from
        * @param int $to
        */
        public function get($from, $to) {
            $tokenStream = new Prephp_TokenStream;
            $tokenStream->append(
                array_slice($this->tokens, $from, $to - $from + 1)
            );
            
            return $tokenStream;
        }
        
        /*
            Converters
        */
        
        /**
        * convert token stream to source code
        * @return string
        */
        public function __toString() {
            $string = '';
            foreach ($this->tokens as $token) {
                $string .= $token;
            }
            return $string;
        }
        
        /**
        * dumps a formatted version of the token stream
        * @param bool $indentBrackets whether to indent on brackets
        * @param bool $convertWhitespace whether to convert whitespace characters to
        *                                \r, \n and \t string literals and display grey
        * @param bool $hideWhitespaceTokens whether to hide all T_WHITESPACE tokens
        */
        public function debugDump($indentBrackets = false, $convertWhitespace = false, $hideWhitespaceTokens = false) {
            $indent = 0;
            echo '<pre style="color:grey">';
            foreach ($this->tokens as $token) {
                if ($hideWhitespaceTokens && $token->is(T_WHITESPACE)) {
                    continue;
                }
                
                if ($token->is(T_CLOSE_ROUND, T_CLOSE_SQUARE, T_CLOSE_CURLY)) {
                    --$indent;
                }
                if ($indentBrackets) {
                    echo str_pad('', $indent, "\t");
                }
                if ($token->is(T_OPEN_ROUND, T_OPEN_SQUARE, T_OPEN_CURLY)) {
                    ++$indent;
                }
                
                echo '"<span style="color:black">';
                if (!$convertWhitespace) {
                    echo htmlspecialchars($token->content);
                } else {
                    echo str_replace(array("\n", "\r", "\t"), array(
                        '<span style="color:grey">\n</span>',
                        '<span style="color:grey">\r</span>',
                        '<span style="color:grey">\t</span>',
                    ), htmlspecialchars($token->content));
                }
                echo '</span>"';
                if (token_name($token->type) != 'UNKNOWN') {
                    echo ' ', token_name($token->type);
                }
                if ($token->line != 0) {
                    echo ' line: ', $token->line;
                }
                echo PHP_EOL;
            }
            echo '</pre>';
        }
        
        /*
            Interfaces
        */
            
        // interface: Countable
        public function count() {
            return count($this->tokens);
        }
        
        // interface: Iterator
        protected $position = 0;
        
        function rewind() {
            $this->position = 0;
        }

        function current() {
            return $this->tokens[$this->position];
        }

        function key() {
            return $this->position;
        }

        function next() {
            ++$this->position;
        }

        function valid() {
            return isset($this->tokens[$this->position]);
        }
        
        public function seek($offset) {
            if (!isset($this->tokens[$offset])) {
                throw new OutOfBoundsException('seeking to out of bounds offset: ' . $offset);
            }
            
            $this->position = $offset;
        }
        
        // interface: ArrayAccess
        public function offsetExists($offset)
        {
            return isset($this->tokens[$offset]);
        }
        
        public function offsetGet($offset)
        {
            if (!isset($this->tokens[$offset])) {
                throw new OutOfBoundsException('offset does not exist');
            }
            
            return $this->tokens[$offset];
        }
        
        public function offsetSet($offset, $value)
        {
            if (!$value instanceof Prephp_Token) {
                throw new InvalidArgumentException('Cannot set offset '.$offset.': Expecting Token');
            }
            
            if ($offset === null) {
                $this->tokens[] = $value;
            }
            else {
                $this->tokens[$offset] = $value;
            }
        }
        
        public function offsetUnset($offset)
        {
            if (!isset($this->tokens[$offset])) {
                throw new OutOfBoundsException('offset does not exist');
            }
            
            // need splice here to move other tokens down
            array_splice($this->tokens, $offset, 1);
            
            // fix iterator position
            if ($offset < $this->position) {
                --$this->position;
            }
        }
    }