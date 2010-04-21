<?php
	require_once 'TokenStream.php';
	
	class Prephp_Preprocessor
	{
		protected $sourcePreparators = array();
		protected $streamManipulators = array();
		protected $tokenCompilers = array();
		
		/* register event listeners, stream manipulators and token compilers */
		public function registerSourcePreparator($callback) {
			if (!is_callable($callback)) {
				throw new InvalidArgumentException('Source Preparator not callable!');
			}
			
			$this->sourcePreparators[] = $callback;
		}
		
		public function registerStreamManipulator($tokens, $callback) {
			if (!is_callable($callback)) {
				throw new InvalidArgumentException('Stream Manipulator not callable!');
			}
			
			if (!is_array($tokens)) {
				$tokens = array($tokens);
			}
			
			$this->streamManipulators[] = array($callback, $tokens);
		}
		
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
		
		public function preprocess($source) {
			// FIRST: prepare source (sourcePreparator)
			foreach ($this->sourcePreparators as $preparator) {
				$source = $preparator($source);
			}
			
			// get token stream
			$tokenStream = new Prephp_Token_Stream(token_get_all($source));
			
			// SECOND: manipulate tokens
			foreach ($this->streamManipulators as $manipulator) {
				list($callback, $tokens) = $manipulator;
				foreach ($tokenStream as $i=>$token) {
					if ($token->is($tokens)) {
						$callback($tokenStream, $i);
					}
				}
			}
			
			// THIRD: compile source
			$source = '';
			foreach ($tokenStream as $token) {
				if (isset($this->tokenCompilers[$token->getTokenId()])) {
					foreach ($this->tokenCompilers[$token->getTokenId()] as $compiler) {
						$ret = $compiler($token);
						if ($ret !== false) {
							$tokenStream[$i] = new Prephp_Token(
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