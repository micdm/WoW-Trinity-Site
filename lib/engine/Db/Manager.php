<?php

/**
 * Инициализация подключений к БД.
 * @author Mic, 2010
 */
class Db_Manager
{
	/**
	 * Массив всех подключений к БД.
	 * @var array
	 */
	private static $_list = array();

	/**
	 * Создает подключение к БД.
	 * @param string $name
	 */
	private static function _CreateConnection($name)
	{
		$info = Env::Get()->config->Get('db/'.$name);
		
		//Информация для подключения:
		$dsn = 'mysql:host='.$info['host'].';port='.$info['port'].';dbname='.$info['name'];

		//Экстра-параметры:
		$extra = isset($info['extra']) ? $info['extra'] : null;

		//Сохраняем:
		self::$_list[$name] = Db_Pdo_Connection::Factory($dsn, $info['user'], $info['pass'], $extra);
	}
	
	/**
	 * Возвращает объект подключения к базе данных.
	 * @param string $name
	 * @return Db_Pdo_Connection
	 */
	public function Get($name)
	{
		if (empty(self::$_list[$name]))
		{
			self::_CreateConnection($name);
		}
		
		return self::$_list[$name];
	}
	
	/**
	 * Возвращает информацию об использовании БД.
	 * @return array
	 */
	public static function GetDebugInfo()
	{
		$result = array();
		foreach (self::$_list as $name => $db)
		{
			$info = $db->GetDebugInfo();
			if ($info)
			{
				$result[$name] = $info;
			}
		}
		
		return $result;
	}
};
