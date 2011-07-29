<?php

/**
 * @author Mic, 2010
 */
class User_Operation_LogoutTest extends User_Operation_BaseTest
{
	/**
	 * Проверяем попытку гостя выйти.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfUserIsGuest()
	{
		Env::Get()->user->Deauthorize();
		
		User_Operation_Base::Factory('logout')->Run(false);
	}
	
	/**
	 * Проверяем нормальный выход.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		Env::Get()->user->Authorize(1);

		try
		{
			User_Operation_Base::Factory('logout')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что вышли:
			$this->assertEquals(true, Env::Get()->user->IsGuest());
			
			throw $e;
		}
	}
};
