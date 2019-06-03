<?php
class Nose
{
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
				$j = 0;
				foreach($funcs as $func)
				{
					$last_func = !$classes && count($funcs) == ++$j;
					$succ = false;
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
						$succ = true;
					}
					/** @noinspection PhpRedundantCatchClauseInspection */
					catch(AssertionFailedException $e)
					{
						echo $e->getMessage()."\n";
					}
					catch(Exception $e)
					{
						echo get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
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
					$succ = false;
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
						$succ = true;
					}
					/** @noinspection PhpRedundantCatchClauseInspection */
					catch(AssertionFailedException $e)
					{
						echo $e->getMessage()."\n";
					}
					catch(Exception $e)
					{
						echo get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
					}
					ob_end_flush();
				}
			}
		}
	}

	/**
	 * @throws AssertionFailedException
	 */
	private static function codeFail()
	{
		$caller = debug_backtrace()[1];
		throw new AssertionFailedException("Assertion failed: ".trim(file($caller["file"])[$caller["line"] - 1]));
	}

	/**
	 * @param mixed $bool
	 * @throws AssertionFailedException
	 */
	static function assert($bool)
	{
		if($bool == false)
		{
			self::codeFail();
		}
	}

	/**
	 * @param mixed $bool
	 * @throws AssertionFailedException
	 */
	static function assertTrue($bool)
	{
		if($bool == false)
		{
			self::codeFail();
		}
	}

	/**
	 * @param mixed $bool
	 * @throws AssertionFailedException
	 */
	static function assertFalse($bool)
	{
		if($bool == true)
		{
			self::codeFail();
		}
	}

	/**
	 * @param mixed $value
	 * @param mixed $expectation
	 * @throws AssertionFailedException
	 */
	static function assertEquals($value, $expectation)
	{
		if($value !== $expectation)
		{
			throw new AssertionFailedException("Failed asserting that ".var_export($value, true)." is equal to ".var_export($expectation, true).".");
		}
	}
}
