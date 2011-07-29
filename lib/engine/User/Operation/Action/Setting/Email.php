<?php

/**
 * Смена адреса почты.
 * @package User_Operation_Action_Setting
 * @author Mic, 2010
 */
class User_Operation_Action_Setting_Email extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this
			->_AddPlainField('old', array('canBeNull' => true))
			->_AddPlainField('new');
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return sprintf('Смена адреса почты с %s', $plain['old']);
	}
	
	protected function _CheckAdditionalConditions()
	{
		$account = Env::Get()->user->GetAccount();
		
		//Проверяем старый адрес:
		if ($account->IsEmailCorrect() && $account->GetEmail() !== Util_String::ToLower($this->GetPlainField('old')))
		{
			throw new Exception_User_Operation_BadCondition('введите старый адрес правильно');
		}
		
		//Проверяем новый адрес:
		if ($account->CanSetEmail($this->GetPlainField('new')) == false)
		{
			throw new Exception_User_Operation_BadCondition('введите новый корректный адрес электронной почты');
		}
	}

	protected function _DoSomeActions()
	{
		//Сохраняем адрес:
		Env::Get()->user->GetAccount()->SetEmail($this->GetPlainField('new'));

		$this->_SetSuccessMessages('адрес почты изменен');
	}
};
