<?php
    require_once PREPHP_DIR . 'classes/TokenStream.php';
    
    class Prephp_Preprocessor
    {
        protected $streamPreparators  = array();
        protected $streamManipulators = array();
        protected $tokenCompilers     = array();
        
        // StreamPreparators are passed the tokenStream as only argument.
        // StreamPreparators manipulate the passed tokenStream.
        // They shall not return.
        public function registerStreamPreparator($callback) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('Stream Preparator not callable!');
            }
            
            $this->streamPreparators[] = $callback;
        }
        
        // StreamManipulators are passed the tokenStream as first argument and the position
        // of the found token as second argument.
        // StreamManipulators manipulate the passed tokenStream.
        // They may return nothing or true. If true is returned all streamManipulators are called again on the current token
        public function registerStreamManipulator($tokens, $callback) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('Stream Manipulator not callable!');
            }
            
            $this->streamManipulators[] = array($callback, $tokens);
        }
        
        // TokenCompilers are passed the token as only argument
        // They may return nothing or a string to be inserted into the source
        public function registerTokenCompiler($tokens, $callback) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('Token Compiler not callable!');
            }
            
            if (is_array($tokens)) {
                foreach ($tokens as $token) {
                    $this->tokenCompilers[$token][] = $callback;
                }
            }
            else {
                $this->tokenCompilers[$tokens][] = $callback;
            }
        }
        
        // process a given source
        public function process($source) {
            $tokenStream = new Prephp_TokenStream($source);
            
            // prepare stream
            foreach ($this->streamPreparators as $preparator) {
                call_user_func($preparator, $tokenStream);
            }
            
            // manipulate stream
            foreach ($tokenStream as $i => $token) {
                do {
                    $loop = false;
                    foreach ($this->streamManipulators as $manipulator) {
                        list ($callback, $tokens) = $manipulator;
                        if ($tokenStream[$i]->is($tokens)) {
                            if (true === call_user_func($callback, $tokenStream, $i)) {
                                $loop = true;
                            }
                        }
                    }
                }
                while ($loop);
            }
            
            // compile source
            $source = '';
            foreach ($tokenStream as $token) {
                if (isset($this->tokenCompilers[$token->type])) {
                    foreach ($this->tokenCompilers[$token->type] as $compiler) {
                        if (is_string($ret = call_user_func($compiler, $token))) {
                            $token->content = $ret;
                        }
                    }
                }
                
                $source .= $token->content;
            }
            
            return $source;
        }
    }