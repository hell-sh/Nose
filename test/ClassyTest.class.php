<?php
class MyTestClass
{
	/**
	 * @throws AssertionFailedException
	 */
	function myTestFunctionInMyTestClass()
	{
		Nose::assert(2 + 2 == 4);
		Nose::assertEquals(2 + 2, 5);
	}

	function myExceptionTest()
	{
		Nose::expectException(Exception::class, function()
		{
			throw new Exception();
		});
		Nose::expectException(Exception::class, function(){});
	}

	function myOtherExceptionTest()
	{
		Nose::expectException(RuntimeException::class, function()
		{
			throw new Exception();
		});
	}

	function iWillBeIgnored($because_of_my_required_parameter) {}
}

/**
 * @throws AssertionFailedException
 */
function myDeclassifiedFunction()
{
	Nose::assertTrue("true" == true);
	Nose::assertFalse("true" != false);
}
