<?php

/**
 * Регистрация.
 * @package User_Operation_Action_Registration
 * @author Mic, 2010
 */
class User_Operation_Action_Registration_Main extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this
			->_AddPlainField('username')
			->_AddPlainField('password', array('noHistory' => true))
			->_AddPlainField('confirm', array('noHistory' => true))
			->_AddPlainField('email');
		
		//Добавляем поле для капчи при необходимости:
		if ($this->_operation->NeedCaptcha())
		{
			$this->_AddPlainField('captcha');
		}
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return sprintf('Регистрация аккаунта %s', $plain['username']);
	}
	
	protected function _CheckAdditionalConditions()
	{
		$this->_CheckLogin();
		$this->_CheckPassword();
		$this->_CheckEmail();
		$this->_CheckCaptcha();
	}

	protected function _DoSomeActions()
	{
		//Собственно, создаем новый аккаунт:
		$account = Env::Get()->user->GetAccount()->Create($this->GetPlainField('username'), $this->GetPlainField('password'), $this->GetPlainField('email'));
		
		//Если реферал:
		$ref = $this->_operation->GetReferrer();
		if ($ref)
		{
			User_ReferralSystem::ApplyReferral($ref, $account);
		}
		
		//Если включены премиум-аккаунты:
		$premium = Env::Get()->config->Get('premiumAccountPeriod');
		if ($premium)
		{
			$account->SetPremium();
		}
		
		//Отправляем письмо с логином-паролем:
		try
		{
			$account->SendMail('Регистрация', 'registration', array(
				'username' => $this->GetPlainField('username'),
				'password' => $this->GetPlainField('password'),
			));
		}
		catch (Exception $e)
		{
			//Отправка письма опциональна, поэтому не встаем здесь.
		}
		
		$this->_SetSuccessMessages('регистрация завершена, желаем Вам приятной игры :)');
	}
	
	/**
	 * Проверяет логин.
	 */
	protected function _CheckLogin()
	{
		$user = Env::Get()->user;
		$username = $this->GetPlainField('username');
		
		if ($user->GetAccount()->CanSetLogin($username) == false)
		{
			throw new Exception_User_Operation_BadCondition('введите логин, который будет соответствовать правилам');
		}
		
		//Ищем:
		if ($user->FindByName($username))
		{
			throw new Exception_User_Operation_BadCondition('выбранный Вами логин уже занят, попробуйте другой');
		}
	}
	
	/**
	 * Проверяет пароль.
	 */
	protected function _CheckPassword()
	{
		//Проверяем пароль:
		if ($this->GetPlainField('password') !== $this->GetPlainField('confirm'))
		{
			throw new Exception_User_Operation_BadCondition('введите одинаковые новый пароль и подтверждение');
		}
		
		//Еще проверяем:
		if (Env::Get()->user->GetAccount()->CanSetPassword($this->GetPlainField('password')) == false)
		{
			throw new Exception_User_Operation_BadCondition('введите пароль, который будет соответствовать правилам');
		}
	}
	
	/**
	 * Проверяем адрес почты.
	 */
	protected function _CheckEmail()
	{
		$email = $this->GetPlainField('email');
		if ($email !== '' && Env::Get()->user->GetAccount()->CanSetEmail($email) == false)
		{
			throw new Exception_User_Operation_BadCondition('введите корректный адрес электронной почты');
		}
	}
	
	/**
	 * Проверяет капчу.
	 */
	protected function _CheckCaptcha()
	{
		if ($this->_operation->NeedCaptcha())
		{
			$isValid = Site_Captcha::IsValidCode($this->GetPlainField('captcha'));
			
			//Удаляем старый код:
			Site_Captcha::SetCode();
			
			if ($isValid == false)
			{
				throw new Exception_User_Operation_BadCondition('введите текст с картинки правильно');
			}
		}
	}
};
