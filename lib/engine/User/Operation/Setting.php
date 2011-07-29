<?php

/**
 * Изменение настроек аккаунта.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Setting extends User_Operation_Base
{
	protected function _Setup()
	{
		$this->_AddAction('password', 'Setting_Password');
		$this->_AddAction('email', 'Setting_Email');
		$this->_AddAction('lock', 'Setting_Lock');
	}
};
