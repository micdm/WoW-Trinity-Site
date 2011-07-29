<?php

/**
 * Обертка над setcookie :).
 * @package Util
 * @author Mic, 2010
 */
class Util_Cookie
{
	/**
	 * Выставляет куку.
	 * @param string $name
	 * @param string $value
	 * @param integer $expires
	 * @param string $path
	 * @return boolean
	 */
	public static function Set($name, $value, $expires = 0, $path = '/')
	{
		return Env::Get()->debug->IsTesting() ? true : setcookie($name, $value, $expires, $path);
	}
};
