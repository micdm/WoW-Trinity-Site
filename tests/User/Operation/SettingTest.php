<?php

/**
 * @author Mic, 2010
 */
class User_Operation_SettingTest extends User_Operation_BaseTest
{
	/**
	 * Настраивает POST и запускает смену пароля.
	 * @param string $old
	 * @param string $new
	 * @param string $confirm
	 */
	protected function _SetPassword($old, $new, $confirm)
	{
		Env::Get()->request->post = array(
			'password' => true,
			'old' => $old,
			'new' => $new,
			'confirm' => $confirm,
		);
		
		User_Operation_Base::Factory('setting')->Run(false);
	}
	
	/**
	 * Настраивает POST и запускает смену адреса почты.
	 * @param string $old
	 * @param string $new
	 */
	protected function _SetEmail($old, $new)
	{
		Env::Get()->request->post = array(
			'email' => true,
			'old' => $old,
			'new' => $new,
		);
		
		User_Operation_Base::Factory('setting')->Run(false);
	}
	
	/**
	 * Настраивает POST, добавляет разрешение на команду и запускает блокировку.
	 * @param integer $commandLevel
	 * @param integer $value
	 */
	protected function _SetLock($commandLevel, $value)
	{
		Env::Get()->db->Get('game')->Query('
			REPLACE INTO #world.command (name, security)
			VALUES (\'account lock\', :security)
		', array(
			'security' => array('d', $commandLevel)
		));
		
		Env::Get()->request->post = array(
			'lock' => true,
			'value' => $value,
		);
		
		User_Operation_Base::Factory('setting')->Run(false);
	}
	
	/**
	 * Проверяем смену пароля, если старый указан неправильно.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testSetPasswordIfOldPasswordBad()
	{
		$this->_SetPassword('bar2', 'foo', 'foo');
	}
	
	/**
	 * Проверяем смену пароля, если подтверждение указано неправильно.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testSetPasswordIfConfirmBad()
	{
		$this->_SetPassword('bar', 'foo', 'foo2');
	}
	
	/**
	 * Проверяем смену пароля, если новый пароль не соответствует правилам.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testSetPasswordIfNewPasswordBad()
	{
		$this->_SetPassword('bar', 'f', 'f');
	}
	
	/**
	 * Проверяем нормальную смену пароля.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testSetPasswordNormal()
	{
		try
		{
			$this->_SetPassword('bar', 'foo', 'foo');
		}
		catch (Exception_Http_Redirected $e)
		{
			$this->assertTrue(Env::Get()->user->GetAccount()->HasPassword('foo'));
			throw $e;
		}
	}
	
	/**
	 * Проверяем смену адреса, если старый указан неправильно.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testSetEmailIfOldEmailBad()
	{
		$this->_SetEmail('foo2@example.com', 'bar@example.com');
	}
	
	/**
	 * Проверяем смену адреса, если новый указан некорректно.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testSetEmailIfNewEmailBad()
	{
		$this->_SetEmail('foo@example.com', 'bar');
	}
	
	/**
	 * Проверяем нормальную смену адреса.
	 * @expectedException Exception_Http_Redirected
	 * @dataProvider providerSetEmail
	 */
	public function testSetEmail($email, $old)
	{
		try
		{
			Env::Get()->user->GetAccount()->SetEmail($email);
			$this->_SetEmail($old, 'bar@example.com');
		}
		catch (Exception_Http_Redirected $e)
		{
			$this->assertEquals('bar@example.com', Env::Get()->user->GetAccount()->GetEmail());
			throw $e;
		}
	}
	
	public static function providerSetEmail()
	{
		return array(
			array('', null),
			array('foo', null),
			array('foo', 'bar'),
			array('foo@example.com', 'foo@example.com'),
		);
	}
	
	/**
	 * Проверяем включение блокировки, если на команду "account lock" не хватает прав.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testSetLockIfLevelTooSmall()
	{
		$this->_SetLock(Env::Get()->user->GetAccount()->GetLevel() + 1, 1);
	}
	
	/**
	 * Проверяем отключение блокировки, если на команду "account lock" не хватает прав.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testUnsetLockIfLevelTooSmall()
	{
		$this->_SetLock(Env::Get()->user->GetAccount()->GetLevel() + 1, 0);
	}
	
	/**
	 * Проверяем нормальную блокировку аккаунта.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testSetLock()
	{
		try
		{
			$this->_SetLock(Env::Get()->user->GetAccount()->GetLevel(), 1);
		}
		catch (Exception_Http_Redirected $e)
		{
			$this->assertTrue(Env::Get()->user->GetAccount()->IsLocked());
			
			throw $e;
		}
	}
};
