<?php

/**
 * Работа с данными в сессии.
 * @author Mic, 2010
 */
class User_Session
{
	/**
	 * Стартует сессию.
	 */
	private static function _Start()
	{
		//При тестировании сессии не используем:
		if (Env::Get()->debug->IsTesting() == false) 
		{
			//Второй раз сессию не стартуем:
			if (session_id() == '')
			{
				session_start();
			}
		}
	}
	
	/**
	 * Возвращает переменную из сессии.
	 * @param string $name
	 * @return mixed
	 */
	public function Get($name)
	{
		self::_Start();
		return isset($_SESSION) && array_key_exists($name, $_SESSION) ? $_SESSION[$name] : null;
	}
	
	/**
	 * Помещает переменную в сессию.
	 * @param string $name
	 * @param mixed $value
	 * @return User_Session
	 */
	public function Set($name, $value)
	{
		self::_Start();
		$_SESSION[$name] = $value;
		
		return $this;
	}
};
