<?php

/**
 * Взаимодействие с aowow.
 * @package Third
 * @author Mic, 2010
 */

class Third_Aowow
{
	/**
	 * Нормальный обработчик ошибок.
	 * @var string
	 */
	protected static $_handler;
	
	/**
	 * Нормальная рабочая директория.
	 * @var string
	 */
	protected static $_directory;
	
	/**
	 * Нормальная сессия.
	 * @var array
	 */
	protected static $_session;
	
	/**
	 * Обработчик для внутренних aowow-ошибок (коих немеряно).
	 */
	public static function ErrorHandler($code, $message)
	{

	}
	
	/**
	 * Возвращает ключ для кэша.
	 * @param string $function
	 * @param string $args
	 */
	protected static function _GetCacheKey($function, $args)
	{
		$argsHash = md5(serialize($args));
		return 'aowow/'.md5($function).'/'.substr($argsHash, -2).'/'.$argsHash;
	}
	
	/**
	 * Подготавливает окружение для aowow.
	 */
	protected static function _PrepareEnvironment()
	{
		//Сохраняем предыдущее состояние:
		self::$_handler = set_error_handler(array(__CLASS__, 'ErrorHandler'));
		self::$_directory = getcwd();
		self::$_session = $_SESSION;
		$_SESSION['locale'] = 8;

		$vars = get_defined_vars();
		
		//Подключаем скрипты:
		chdir(AOWOW_ROOT);
		include_once('includes/kernel.php');
		include_once('includes/allitems.php');
		
		//Смотрим, какие переменные добавились, и помещаем их в глобальную область видимости:
		foreach (get_defined_vars() as $name => $value)
		{
			if (array_key_exists($name, $vars))
			{
				continue;
			}

			global $$name;
			$$name = $value;
		}
	}
	
	/**
	 * Восстанавливает нормальное окружение.
	 */
	protected static function _RestoreEnvironment()
	{
		set_error_handler(self::$_handler);
		chdir(self::$_directory);
		$_SESSION = self::$_session;
	}
	
	/**
	 * Подключает необходимые скрипты, выполняет aowow-функцию и возвращает результат ее работы.
	 * Может принимать произвольное количество аргументов, которые будут перенаправлены в aowow-функцию.
	 * @param string $function
	 */
	protected static function _CallFunction($function)
	{
		//Выбираем все аргументы, кроме первого (в котором название функции):
		$args = func_get_args();
		$args = array_slice($args, 1);
		
		//Пытаемся забрать из кэша:
		$result = Env::Get()->cache->Load(self::_GetCacheKey($function, $args), 3600);
		if ($result === null)
		{
			self::_PrepareEnvironment();
			$result = call_user_func_array($function, $args);
			self::_RestoreEnvironment();
			
			Env::Get()->cache->Save(null, $result);
		}
		
		return $result;
	}
	
	/**
	 * Возвращает путь к картинке.
	 * @param string $image
	 * @param string $type
	 */
	public static function GetImagePath($image, $type = 'medium')
	{
		return AOWOW_RELATIVE_ROOT.'images/icons/'.$type.'/'.Util_String::ToLower($image).'.jpg';
	}
	
	/**
	 * Возвращает информацию о предмете.
	 * @param integer $entry
	 */
	public static function GetItem($entry)
	{
		return self::_CallFunction('iteminfo', $entry, 1);
	}
};
