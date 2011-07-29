<?php

/**
 * Администрирование.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Administration extends User_Operation_Base
{
	protected function _Setup()
	{
		$this->_AddAction('webmoney', 'Administration_Webmoney');
		$this->_AddAction('account', 'Administration_Account');
	}
	
	/**
	 * Возвращает аккаунт, который искали.
	 * @return User_Account
	 */
	public function GetAccount()
	{
		return $this->_actions['account']->GetAccount();
	}
};
