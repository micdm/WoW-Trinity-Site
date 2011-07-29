<?php

class Util_VariablesTest extends PHPUnit_Framework_TestCase
{
	public function testWriteRead()
	{
		Util_Variables::Set('test', 'foo');
		$this->assertEquals('foo', Util_Variables::Get('test'));
	}
};
