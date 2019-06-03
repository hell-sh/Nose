<?php
class MyOtherTestClass
{
	function someTest()
	{
		Nose::assertNull(null);
		Nose::assertNotNull(null);
	}

	function someEmptyTest() {}

	function someOtherTest()
	{
		Nose::assertTrue(true);
	}
}

function iWillBeIgnored($because_of_my_required_parameter) {}
