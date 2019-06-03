<?php
if(!class_exists("Nose"))
{
	class Nose
	{
		private static $asserted = false;

		static function index($dir, $test_implicit = false)
		{
			if(in_array(substr($dir, -1), ["/", "\\"]))
			{
				$dir = substr($dir, 0, -1);
			}
			$index = [];
			foreach(scandir($dir) as $file)
			{
				if(substr($file, 0, 1) == ".")
				{
					continue;
				}
				if(!$test_implicit && strpos(strtolower($file), "test") === false)
				{
					continue;
				}
				$file = $dir."/".$file;
				if(is_dir($file))
				{
					$index = array_merge($index, self::index($file, true));
				}
				else if(strtolower(substr($file, -4)) == ".php")
				{
					array_push($index, $file);
				}
			}
			return $index;
		}

		static function test($files)
		{
			$errors = 0;
			$warnings = 0;
			foreach($files as $i => $file)
			{
				$funcs = get_defined_functions()["user"];
				$classes = get_declared_classes();
				/** @noinspection PhpIncludeInspection */
				require $file;
				$funcs = array_diff(get_defined_functions()["user"], $funcs);
				$classes = array_diff(get_declared_classes(), $classes);
				$last_file = count($files) - 1 == $i;
				echo ($last_file ? "└" : "├")." $file\n";
				if($funcs)
				{
					foreach($funcs as $j => $func)
					{
						if((new ReflectionFunction($func))->getNumberOfRequiredParameters() > 0)
						{
							unset($funcs[$j]);
						}
					}
					$j = 0;
					foreach($funcs as $func)
					{
						$last_func = !$classes && count($funcs) == ++$j;
						$succ = false;
						Nose::$asserted = false;
						ob_start(function($buffer) use (&$last_file, &$last_func, &$func, &$succ)
						{
							// Reflecting function to preserve casing
							/** @noinspection PhpUnhandledExceptionInspection */
							$out = ($last_file ? " " : "│")." ".($last_func ? "└" : "├")." ".(new ReflectionFunction($func))->getName().($succ ? " ✓" : "")."\n";
							$lines = explode("\n", trim($buffer));
							foreach($lines as $l => $line)
							{
								if(!$line)
								{
									unset($lines[$l]);
								}
							}
							$lines = array_values($lines);
							foreach($lines as $l => $line)
							{
								$out .= ($last_file ? " " : "│")." ".($last_func ? " " : "│")." ".(count($lines) - 1 == $l ? "└" : "├")." ".trim($line)."\n";
							}
							return $out;
						});
						try
						{
							$func();
							if(Nose::$asserted)
							{
								$succ = true;
							}
							else
							{
								echo "This test didn't assert anything.";
								$warnings++;
							}
						}
						/** @noinspection PhpRedundantCatchClauseInspection */
						catch(AssertionFailedException $e)
						{
							echo "Assertion failed: ".$e->getMessage()."\n";
							$errors++;
						}
						catch(Exception $e)
						{
							echo get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
							$errors++;
						}
						ob_end_flush();
					}
				}
				$j = 0;
				foreach($classes as $class)
				{
					$last_class = count($classes) == ++$j;
					echo ($last_file ? " " : "│")." ".($last_class ? "└" : "├")." $class\n";
					$funcs = get_class_methods($class);
					foreach($funcs as $k => $func)
					{
						if((new ReflectionMethod("{$class}::{$func}"))->getNumberOfRequiredParameters() > 0)
						{
							unset($funcs[$k]);
						}
					}
					$funcs = array_values($funcs);
					foreach($funcs as $k => $func)
					{
						$succ = false;
						Nose::$asserted = false;
						ob_start(function($buffer) use (&$last_file, &$last_class, &$funcs, &$k, &$func, &$succ)
						{
							$out = ($last_file ? " " : "│")." ".($last_class ? " " : "│")." ".(count($funcs) - 1 == $k ? "└" : "├")." $func".($succ ? " ✓" : "")."\n";
							$lines = explode("\n", trim($buffer));
							foreach($lines as $l => $line)
							{
								if(!$line)
								{
									unset($lines[$l]);
								}
							}
							$lines = array_values($lines);
							foreach($lines as $l => $line)
							{
								$out .= ($last_file ? " " : "│")." ".($last_class ? " " : "│")." ".(count($funcs) - 1 == $k ? " " : "│")." ".(count($lines) - 1 == $l ? "└" : "├")." ".trim($line)."\n";
							}
							return $out;
						});
						try
						{
							@eval("{$class}::{$func}();");
							if(Nose::$asserted)
							{
								$succ = true;
							}
							else
							{
								echo "This test didn't assert anything.";
								$warnings++;
							}
						}
						/** @noinspection PhpRedundantCatchClauseInspection */
						catch(AssertionFailedException $e)
						{
							echo "Assertion failed: ".$e->getMessage()."\n";
							$errors++;
						}
						catch(Exception $e)
						{
							echo get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
							$errors++;
						}
						ob_end_flush();
					}
				}
			}
			return [
				"errors" => $errors,
				"warnings" => $warnings
			];
		}

		/**
		 * @throws AssertionFailedException
		 */
		protected static function throwExceptionWithCodeSnippet()
		{
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT + DEBUG_BACKTRACE_IGNORE_ARGS);
			for($i = 0; $i < count($backtrace); $i++)
			{
				$caller = $backtrace[$i];
				if($caller["file"] != __FILE__)
				{
					throw new AssertionFailedException(trim(file($caller["file"])[$caller["line"] - 1]));
				}
			}
			throw new Exception("Couldn't generate AssertionFailedException");
		}

		protected static function isExceptionWithCodeSnippetRecommended($expectation)
		{
			return is_bool($expectation) || $expectation === NULL;
		}

		/**
		 * @param mixed $reality
		 * @throws AssertionFailedException
		 */
		static function assert($reality)
		{
			Nose::$asserted = true;
			if($reality == false)
			{
				self::codeFail();
			}
		}

		/**
		 * @param mixed $reality
		 * @throws AssertionFailedException
		 */
		static function assertNot($reality)
		{
			Nose::$asserted = true;
			if($reality == true)
			{
				self::codeFail();
			}
		}

		/**
		 * @param mixed $reality
		 * @param mixed $expectation
		 * @throws AssertionFailedException
		 */
		static function assertEquals($reality, $expectation)
		{
			Nose::$asserted = true;
			if($reality !== $expectation)
			{
				if(self::isExceptionWithCodeSnippetRecommended($expectation))
				{
					self::throwExceptionWithCodeSnippet();
				}
				throw new AssertionFailedException(var_export($reality, true)." is not equal to ".var_export($expectation, true));
			}
		}

		/**
		 * @param mixed $reality
		 * @param mixed $expectation
		 * @throws AssertionFailedException
		 */
		static function assertNotEquals($reality, $unexpected)
		{
			Nose::$asserted = true;
			if($reality === $unexpected)
			{
				if(self::isExceptionWithCodeSnippetRecommended($unexpected))
				{
					self::throwExceptionWithCodeSnippet();
				}
				throw new AssertionFailedException(var_export($reality, true)." is equal to ".var_export($unexpected, true));
			}
		}

		/**
		 * @param mixed $reality
		 * @throws AssertionFailedException
		 */
		static function assertTrue($reality)
		{
			self::assertEquals($reality, true);
		}

		/**
		 * @param mixed $reality
		 * @throws AssertionFailedException
		 */
		static function assertFalse($reality)
		{
			self::assertEquals($reality, false);
		}

		/**
		 * @param mixed $reality
		 * @throws AssertionFailedException
		 */
		static function assertNull($reality)
		{
			self::assertEquals($reality, null);
		}

		/**
		 * @param mixed $reality
		 * @throws AssertionFailedException
		 */
		static function assertNotNull($reality)
		{
			self::assertNotEquals($reality, null);
		}
	}
}
