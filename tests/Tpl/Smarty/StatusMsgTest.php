<?php

class Tpl_Smarty_StatusMsgTest extends PHPUnit_Framework_TestCase
{
	const MSG										= 'test message';
	const ANOTHER_MSG								= 'another test message';
	const NAME										= 'test';
	
	private $_session;
	
	public function setUp()
	{
		$this->_session = Env::Get()->session;
		Env::Get()->session = new SessionStub();
	}
	
	public function tearDown()
	{
		Env::Get()->session = $this->_session;
	}
	
	/**
	 * Проверяем добавление и загрузку сообщения.
	 */
	public function testAddMessage()
	{
		Tpl_Smarty_StatusMsg::Add(self::NAME, self::MSG);
		$result = Tpl_Smarty_StatusMsg::Get(self::NAME);
		
		$this->assertEquals(array(self::MSG), $result);
	}
	
	/**
	 * Проверяем добавление двух одинаковых сообщений.
	 */
	public function testAddTwoMessages()
	{
		Tpl_Smarty_StatusMsg::Add(self::NAME, self::MSG);
		Tpl_Smarty_StatusMsg::Add(self::NAME, self::MSG);
		$result = Tpl_Smarty_StatusMsg::Get(self::NAME);
		
		$this->assertEquals(array(self::MSG), $result);
	}
	
	/**
	 * Проверяем добавление двух разных сообщений.
	 */
	public function testAddTwoDifferentMessages()
	{
		Tpl_Smarty_StatusMsg::Add(self::NAME, self::MSG);
		Tpl_Smarty_StatusMsg::Add(self::NAME, self::ANOTHER_MSG);
		$result = Tpl_Smarty_StatusMsg::Get(self::NAME);
		
		$this->assertEquals(array(self::MSG, self::ANOTHER_MSG), $result);
	}
	
	/**
	 * Проверяем, что сообщения удаляются после выдачи.
	 */
	public function testIfMessageRemovedAfterGet()
	{
		Tpl_Smarty_StatusMsg::Add(self::NAME, self::MSG);
		Tpl_Smarty_StatusMsg::Get(self::NAME);
		
		$result = Tpl_Smarty_StatusMsg::Get(self::NAME);
		$this->assertTrue(empty($result));
	}
};


/**
 * Эмуляция менеджера сессии.
 * @author Mic, 2010
 */
class SessionStub
{
	private $_values;
	
	public function Get($name)
	{
		return empty($this->_values[$name]) ? null : $this->_values[$name];
	}
	
	public function Set($name, $value)
	{
		$this->_values[$name] = $value;
	}
};
