<?php

/**
 * Блокировка аккаунта по IP.
 * @package User_Operation_Action_Setting
 * @author Mic, 2010
 */
class User_Operation_Action_Setting_Lock extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this->_AddPlainField('value');
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$action = $plain['value'] ? 'Включение' : 'Выключение';
		return sprintf('%s блокировки аккаунта', $action);
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Если пользователь хочет включить блокировку, проверяем, доступно ли оно ему:
		if ($this->GetPlainField())
		{
			$access = Env::Get()->db->Get('game')->Query('
				SELECT security
				FROM #world.command
				WHERE name = \'account lock\'
			')->FetchOne();
			
			if ($access > Env::Get()->user->GetAccount()->GetLevel())
			{
				throw new Exception_User_Operation_BadCondition('команда блокировки Вам недоступна');
			}
		}
	}

	protected function _DoSomeActions()
	{
		Env::Get()->user->GetAccount()->SetLocked($this->GetPlainField());

		$this->_SetSuccessMessages($this->GetPlainField() ? 'блокировка включена' : 'блокировка отключена');
	}
};
