<?php

/**
 * Установка заголовков для кэширования страниц.
 * @package Http
 * @author Mic, 2010
 */
class Http_Cache
{
	/**
	 * Время жизни кэша в секундах.
	 * @var integer
	 */
	private static $_period;
	
	/**
	 * Устанавливает заголовок "Cache-Control".
	 */
	private static function _SetCacheControlHeader()
	{
		if (self::$_period)
		{
			header('Cache-Control: public');
			header('Pragma: public');
		}
		else
		{
			header('Cache-Control: no-cache');
			header('Pragma: no-cache');
		}
	}
	
	/**
	 * Устанавливает заголовок "Expires".
	 */
	private static function _SetExpiresHeader()
	{
		$date = new DateTime(self::$_period ? ('+ '.self::$_period.' second') : '@0');
		header('Expires: '.$date->format(DateTime::RFC1123));
	}
	
	/**
	 * Устанавливает время жизни кэша.
	 * @param integer $value
	 */
	public static function SetPeriod($value)
	{
		self::$_period = $value;
	}
	
	/**
	 * Возвращает время жизни кэша.
	 * @return integer
	 */
	public static function GetPeriod()
	{
		return Env::Get()->debug->IsCacheDisabled() ? 0 : self::$_period;
	}
	
	/**
	 * Устанавливает все необходимые заголовки.
	 * @param integer $period
	 */
	public static function Run()
	{
		self::_SetCacheControlHeader();
		self::_SetExpiresHeader();
	}
}