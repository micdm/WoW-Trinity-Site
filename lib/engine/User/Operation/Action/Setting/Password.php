<?php

/**
 * Смена пароля.
 * @package User_Operation_Action_Setting
 * @author Mic, 2010
 */
class User_Operation_Action_Setting_Password extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this
			->_AddPlainField('old', array('noHistory' => true))
			->_AddPlainField('new', array('noHistory' => true))
			->_AddPlainField('confirm', array('noHistory' => true));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return sprintf('Смена пароля с %s', $plain['old']);
	}
	
	protected function _CheckAdditionalConditions()
	{
		$account = Env::Get()->user->GetAccount();
		
		//Проверяем старый пароль:
		if ($account->HasPassword($this->GetPlainField('old')) == false)
		{
			throw new Exception_User_Operation_BadCondition('введите старый пароль правильно');
		}
		
		//Проверяем новый пароль:
		if ($this->GetPlainField('new') !== $this->GetPlainField('confirm'))
		{
			throw new Exception_User_Operation_BadCondition('введите одинаковые новый пароль и подтверждение');
		}
		
		//Еще проверяем:
		if ($account->CanSetPassword($this->GetPlainField('new')) == false)
		{
			throw new Exception_User_Operation_BadCondition('введите новый пароль, который будет соответствовать правилам');
		}
	}

	protected function _DoSomeActions()
	{
		//Сохраняем пароль:
		Env::Get()->user->GetAccount()->SetPassword($this->GetPlainField('new'));

		$this->_SetSuccessMessages('пароль изменен');
	}
};
