<?php

/**
 * @author Mic, 2010
 */
class User_Operation_LoginTest extends User_Operation_BaseTest
{
	/**
	 * Проверяем попытку входа с неправильным паролем.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfBadPassword()
	{
		TestHelper_Account::Add(2, 'test');
		
		Env::Get()->request->post['username'] = 'test';
		Env::Get()->request->post['password'] = 'notbar';
		
		User_Operation_Base::Factory('login')->Run(false);
	}
	
	/**
	 * Проверяем попытку входа, если в сессии уже хранится такой же аккаунт.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testIfAccountCurrent()
	{
		Env::Get()->user->Deauthorize();
		
		Env::Get()->request->post['username'] = 'foo';
		Env::Get()->request->post['password'] = 'bar';
		
		try
		{
			User_Operation_Base::Factory('login')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что авторизовались:
			$this->assertEquals(1, Env::Get()->user->GetAccount()->GetId());
			
			throw $e;
		}
	}
	
	/**
	 * Проверяем нормальный логин.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		TestHelper_Account::Add(2, 'test');
		
		Env::Get()->request->post['username'] = 'test';
		Env::Get()->request->post['password'] = 'bar';
		
		try
		{
			User_Operation_Base::Factory('login')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что авторизовались:
			$this->assertEquals(2, Env::Get()->user->GetAccount()->GetId());
			
			throw $e;
		}
	}
};
