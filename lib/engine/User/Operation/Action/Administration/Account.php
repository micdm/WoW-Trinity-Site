<?php

/**
 * Просмотр информации об аккаунте и смена почты/пароля.
 * @package User_Operation_Action_Administration
 * @author Mic, 2010
 */
class User_Operation_Action_Administration_Account extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this
			->_AddAccount('username', array(
				'isName' => true,
				'canBeBanned' => true,
			))
			->_AddPlainField('email', array('canBeNull' => true))
			->_AddPlainField('password', array('canBeNull' => true));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$account = $accounts['username'];
		$email = $plain['email'];
		//$password = $plain['password'];
		return sprintf('Поиск аккаунта %s для редактирования: email=%b', $account, $email);
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Проверяем адрес почты:
		$email = $this->GetPlainField('email');
		if ($email && Env::Get()->user->GetAccount()->CanSetEmail($email) == false)
		{
			throw new Exception_User_Operation_BadCondition('укажите корректный адрес почты');
		}
		
		//Проверяем пароль:
		$password = $this->GetPlainField('password');
		if ($password && Env::Get()->user->GetAccount()->CanSetPassword($password) == false)
		{
			throw new Exception_User_Operation_BadCondition('укажите корректный пароль');
		}
	}

	protected function _DoSomeActions()
	{
		//Обновляем почту:
		$email = $this->GetPlainField('email');
		if ($email)
		{
			$this->GetAccount()->SetEmail($email);
		}
		
		//Обновляем пароль:
		$password = $this->GetPlainField('password');
		if ($password)
		{
			$this->GetAccount()->SetPassword($password);
		}

		//Если что-то изменилось, обновим страницу:
		if (empty($email) && empty($password))
		{
			$this->_SetRedirectNotRequired();
		}
		
		$this->_SetSuccessMessages('информация для аккаунта изменена');
	}
};
