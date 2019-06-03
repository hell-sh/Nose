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
}

/**
 * @throws AssertionFailedException
 */
function myDeclassifiedFunction()
{
	Nose::assertTrue("true" == true);
	Nose::assertFalse("true" != false);
}
