<?php

/**
 * Восстановление пароля по почте.
 * @package User_Operation_Action_Recovery
 * @author Mic, 2010
 */
class User_Operation_Action_Recovery_Main extends User_Operation_Action_Base
{
	protected function _GetSubjectForMailConfirm()
	{
		return 'Восстановление пароля';
	}
	
	protected function _GetMailConfirmReciever()
	{
		return $this->GetAccount();
	}
	
	protected function _Setup()
	{
		$this
			->_SetMailConfirmRequired()
			->_AddAccount('username', array('isName' => true));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return 'Заявка на восстановление пароля';
	}

	protected function _DoSomeActions()
	{
		//Генерируем пароль:
		$password = substr(md5(microtime(true).$this->GetAccount()->GetLogin()), 0, 10);
		
		//Устанавливаем пароль:
		$this->GetAccount()->SetPassword($password);
		
		$this->_SetSuccessMessages('Ваш новый пароль '.$password.', не забудьте сразу же поменять его');
	}
};
