<?php
    require_once 'TokenStream.php';
    
    class Prephp_Preprocessor
    {
        protected $sourcePreparators = array();
        protected $streamManipulators = array();
        protected $tokenCompilers = array();
        
        // sourcePreparators get the source passed as only argument
        // and must return some source
        public function registerSourcePreparator($callback) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('Source Preparator not callable!');
            }
            
            $this->sourcePreparators[] = $callback;
        }
        
        // streamManipulators get the tokenStream passed as first argument and the integral
        // position of the found token as the second argument. streamManipulators manipulate
        // the passed tokenStream. They do not return
        public function registerStreamManipulator($tokens, $callback) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('Stream Manipulator not callable!');
            }
            
            if (!is_array($tokens)) {
                $tokens = array($tokens);
            }
            
            $this->streamManipulators[] = array($callback, $tokens);
        }
        
        // tokenCompilers get the token passed as the only argument and have to return either
        // false or some content to be inserted into the source
        public function registerTokenCompiler($tokens, $callback) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('Token Compiler not callable!');
            }
            
            if (!is_array($tokens)) {
                $tokens = array($tokens);
            }
            
            foreach ($tokens as $token) {
                $this->tokenCompilers[$token][] = $callback;
            }
        }
        
        // this does the magic, preprocess my source!
        public function preprocess($source) {
            // prepare source (sourcePreparator)
            foreach ($this->sourcePreparators as $preparator) {
                $source = call_user_func($preparator, $source);
            }
            
            // get token stream
            $tokenStream = new Prephp_TokenStream($source);
            
            // manipulate tokens
            foreach ($tokenStream as $i => $token) {
                set_time_limit(3); // timeout (debug)
                
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
                        $ret = $compiler($token);
                        if ($ret !== false) {
                            $token->content = $ret;
                        }
                    }
                }
                
                $source .= $token->content;
            }
            
            return $source;
        }
    }
?>