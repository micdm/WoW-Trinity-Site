<?php

class Util_MailTest extends PHPUnit_Framework_TestCase
{
	const RECEIVER_MAIL								= 'user@example.com';
	
	/**
	 * Тестируем плохой тип письма.
	 * @expectedException Exception_Runtime
	 */
	public function testWrongType()
	{
		Util_Mail::Send(self::RECEIVER_MAIL, 'some wrong type', 'test1');
	}
	
	/**
	 * Тестируем отправку письма без переменных.
	 */
	public function testNormal()
	{
		$body = Util_Mail::Send(self::RECEIVER_MAIL, 'test', 'test');
		$this->assertEquals('', $body);
	}
	
	/**
	 * Тестируем отправку письма без переменных.
	 */
	public function testNormalWithVars()
	{
		$body = Util_Mail::Send(self::RECEIVER_MAIL, 'test', 'test', array(
			'foo' => 'foo',
			'bar' => 'bar'
		));
		
		$this->assertEquals('foobar', $body);
	}
};
