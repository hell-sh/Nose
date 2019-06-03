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
		foreach($files as $file)
		{
			$funcs = get_defined_functions()["user"];
			$classes = get_declared_classes();
			/** @noinspection PhpIncludeInspection */
			require $file;
			$funcs = array_diff(get_defined_functions()["user"], $funcs);
			$classes = array_diff(get_declared_classes(), $classes);
			echo $file."\n";
			if($funcs)
			{
				echo "\t<no class>\n";
				foreach($funcs as $func)
				{
					// Reflecting function to preserve casing
					/** @noinspection PhpUnhandledExceptionInspection */
					echo "\t\t".(new ReflectionFunction($func))->getName()."\n";
					ob_start(function($buffer)
					{
						return preg_replace('/^(.*)$/m', "\t\t\t$1", $buffer);
					});
					try
					{
						$func();
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
			foreach($classes as $class)
			{
				echo "\t$class\n";
				foreach(get_class_methods($class) as $func)
				{
					echo "\t\t$func\n";
					ob_start(function($buffer)
					{
						return preg_replace('/^(.*)$/m', "\t\t\t$1", $buffer);
					});
					try
					{
						@eval("{$class}::{$func}();");
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
	 * @param mixed $bool
	 * @throws AssertionFailedException
	 */
	static function assert($bool)
	{
		if($bool == false)
		{
			$caller = debug_backtrace()[0];
			throw new AssertionFailedException("Assertion failed: ".trim(file($caller["file"])[$caller["line"] - 1]));
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
