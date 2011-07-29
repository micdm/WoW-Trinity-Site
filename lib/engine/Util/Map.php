<?php

/**
 * Хранилище в формате ключ-значение.
 * @author MIc, 2010
 */
class Util_Map
{
	/**
	 * Хранилище.
	 * @var array
	 */
	protected static $_map = array();
	
	/**
	 * Возвращает значение по ключу.
	 * @param string $key
	 * @return mixed
	 */
	public static function Get($key)
	{
		return array_key_exists($key, self::$_map) ? self::$_map[$key] : null;
	}
	
	/**
	 * Сохраняет значение по ключу.
	 * @param string $key
	 * @param mixed $value
	 */
	public static function Set($key, $value)
	{
		self::$_map[$key] = $value;
	}
	
	/**
	 * Очищает ключи, начинающиеся с указанной строки.
	 * @param string $part
	 */
	public static function Clear($part)
	{
		foreach (self::$_map as $key => $value)
		{
			if (strpos($key, $part) === 0)
			{
				unset(self::$_map[$key]);
			}
		}
	}
};
