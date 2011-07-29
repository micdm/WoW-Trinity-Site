<?php

class Third_TrinityConsoleTest extends PHPUnit_Framework_TestCase
{
	private $_config;
	
	public function setUp()
	{
		$this->_config = Config_Base::$console;
	}
	
	public function tearDown()
	{
		Config_Base::$console = $this->_config;
	}
	
	/**
	 * Тестируем ситуацию, когда невозможно подключиться к серверу.
	 * @expectedException Exception_Third_TrinityConsole_NoConnect
	 */
	public function testIfNoConnect()
	{
		Config_Base::$console['host'] = 'localhost';
		Third_TrinityConsole::Send('hello');
	}
	
	/**
	 * Неправильно указан логин.
	 * @expectedException Exception_Third_TrinityConsole_AuthError
	 */
	public function testIfBadUser()
	{
		Config_Base::$console['user'] = 'foo';
		Third_TrinityConsole::Send('hello');
	}

	/**
	 * Неправильно указан пароль.
	 * @expectedException Exception_Third_TrinityConsole_AuthError
	 */
	public function testIfBadPassword()
	{
		Config_Base::$console['password'] = 'foo';
		Third_TrinityConsole::Send('hello');
	}

	/**
	 * Все правильно, кроме команды.
	 */
	public function testWrongCommand()
	{
		$result = Third_TrinityConsole::Send('hello');
		$this->assertEquals('There is no such command', $result);
	}
};
