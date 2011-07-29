<?php

/**
 * @author Mic, 2010
 */
class User_Operation_RegistrationTest extends User_Operation_BaseTest
{
	/**
	 * Настраивает POST и запускает регистрацию.
	 * @param string $username
	 * @param string $password
	 * @param string $confirm
	 * @param string $email
	 * @param string $captcha
	 */
	protected function _Run($username = 'test', $password = 'bar', $confirm = 'bar', $email = 'test@example.com', $captcha = null)
	{
		foreach (array('username', 'password', 'confirm', 'email', 'captcha') as $var)
		{
			Env::Get()->request->post[$var] = $$var;
		}
		
		User_Operation_Base::Factory('registration')->Run(false);
	}
	
	/**
	 * Проверяем регистрацию с некорректным логином.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfBadUsername()
	{
		$this->_Run('!@#$%');
	}
	
	/**
	 * Проверяем регистрацию с логином, который уже занят.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfAccountExists()
	{
		$this->_Run('foo');
	}
	
	/**
	 * Проверяем регистрацию с некорректным паролем.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfBadPassword()
	{
		$this->_Run('test', '!@#$%', '!@#$%');
	}
	
	/**
	 * Проверяем регистрацию с некорректным подтверждением.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfBadConfirm()
	{
		$this->_Run('test', 'bar', 'notbar');
	}
	
	/**
	 * Проверяем нормальную регистрацию.
	 * @expectedException Exception_Http_Redirected
	 * @dataProvider providerTestNormal
	 */
	public function testNormal($email)
	{
		try
		{
			$this->_Run('test', 'bar', 'bar', $email);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что аккаунт создан:
			$account = Env::Get()->user->FindByName('test');
			
			$this->assertTrue($account->HasPassword('bar'));
			$this->assertEquals($email, $account->GetEmail());
			
			//Проверяем премиум-аккаунт:
			if (Env::Get()->config->Get('premiumAccountPeriod'))
			{
				$this->assertTrue($account->IsPremium());
			}
			
			throw $e;
		}
	}
	
	public static function providerTestNormal()
	{
		return array(
			array(''),
			array('test@example.com'),
		);
	}
	
	/**
	 * Проверяем реферальную регистрацию.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testWithReferral()
	{
		TestHelper_Character::Add(1, 'foo', 1);
		Env::Get()->request->get['ref'] = 1;
		
		try
		{
			$this->_Run();
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что реферал прикрепился:
			$guid = Env::Get()->db->Get('game')->Query('
				SELECT to_character
				FROM #site.site_referrals
				ORDER BY account DESC
				LIMIT 1
			')->FetchOne();
			
			$this->assertEquals(1, $guid);
			
			throw $e;
		}
	}
};
