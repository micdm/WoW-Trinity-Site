<?php

/**
 * Деавторизация.
 * @package User_Operation_Action_Logout
 * @author Mic, 2010
 */
class User_Operation_Action_Logout_Main extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return 'Выход';
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Гости не могут выйти:
		if (Env::Get()->user->IsGuest())
		{
			throw new Exception_User_Operation_BadCondition('вы не авторизовались');
		}
	}

	protected function _DoSomeActions()
	{
		Env::Get()->user->Deauthorize();
	}
};
