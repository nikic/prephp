<?php
	require_once 'TokenStream.php';
	
	class Prephp_Preprocessor
	{
		protected $sourcePreparators = array();
		protected $ensureTokens = array();
		protected $streamManipulators = array();
		protected $tokenCompilers = array();
		
		// ensure that a token exists
		// e.g. ensure that T_STRING(__NAMESPACE__) is converted to
		// T_NS_C(__NAMESPACE__) do ensureToken('__NAMESPACE__', T_NS_C);
		// works only for T_STRINGs
		public function ensureToken($content, $tokenId) {
			$ensureTokens[$content] = $tokenId;
		}
		
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
			$tokenStream = new Prephp_Token_Stream($source);
			
			// ensure tokens
			$count = count($tokenStream);
			for ($i = 0; $i < $count; ++$i) {
				if ($tokenStream[$i]->is(T_STRING) && isset($this->ensureTokens[$tokenStream[$i]->getContent()])) {
					$tokenStream[$i] = new Prephp_Token(
						$this->ensureTokens[$tokenStream[$i]->getContent()],
						$tokenStream[$i]->getContent(),
						$tokenStream[$i]->getLine()
					);
				}
			}
			
			// manipulate tokens
			foreach ($this->streamManipulators as $manipulator) {
				list($callback, $tokens) = $manipulator;
				foreach ($tokenStream as $i=>$token) {
					if ($token->is($tokens)) {
						call_user_func($callback, $tokenStream, $i);
					}
				}
			}
			
			// compile source
			$source = '';
			foreach ($tokenStream as $token) {
				if (isset($this->tokenCompilers[$token->getTokenId()])) {
					foreach ($this->tokenCompilers[$token->getTokenId()] as $compiler) {
						$ret = call_user_func($compiler, $token);
						if ($ret !== false) {
							$token = new Prephp_Token(
								$token->getTokenId(),
								$ret,
								$token->getLine()
							);
						}
					}
				}
				
				$source .= $token->getContent();
			}
			
			return $source;
		}
	}
?>