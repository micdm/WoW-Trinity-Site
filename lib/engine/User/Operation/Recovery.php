<?php

/**
 * Восстановление пароля к аккаунту по почте.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Recovery extends User_Operation_Base
{
	public function Run($needWrapExceptions = true)
	{
		try
		{
			parent::Run($needWrapExceptions);
		}
		catch (Exception_UserInput $e)
		{
			//Переписываем сообщение про плохой адрес почты, чтоб оно было понятнее:
			if ($e->getCode() == self::ERROR_BAD_EMAIL)
			{
				$msg = 'для данного аккаунта не был указан адрес почты, обратитесь к администратору';
			}
			else
			{
				$msg = $e->getMessage();
			}
			
			throw new Exception_UserInput($msg, $e->getCode());
		}
	}
};
