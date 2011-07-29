<?php

/**
 * Авторизация.
 * @package User_Operation_Action_Login
 * @author Mic, 2010
 */
class User_Operation_Action_Login_Main extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this
			->_AddAccount('username', array('isName' => true))
			->_AddPlainField('password', array('noHistory' => true));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return 'Вход';
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Проверяем пароль:
		if ($this->GetAccount()->HasPassword($this->GetPlainField()) == false)
		{
			throw new Exception_User_Operation_BadCondition('укажите правильный пароль');
		}
	}

	protected function _DoSomeActions()
	{
		Env::Get()->user->Authorize($this->GetAccount()->GetId());
	}
};
