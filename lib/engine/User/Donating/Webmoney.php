<?php

/**
 * Пополнение баланса через Webmoney.
 * @package User_Donating
 * @author Mic, 2010
 */
class User_Donating_Webmoney
{
	/**
	 * Возвращает номер кошелька.
	 * @return string
	 */
	public static function GetPurse()
	{
		return Env::Get()->config->Get('webmoneyPurse');
	}
	
	/**
	 * Возвращает текущий курс обмена WMR на чеки.
	 */
	public static function GetRate()
	{
		return User_Money_Converting::ToWmr(1);
	}
};
