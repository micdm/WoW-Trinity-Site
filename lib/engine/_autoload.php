<?php

/**
 * Автоматическая загрузка классов. 
 * @author Mic, 2010
  */
class Autoload
{
	/**
	 * Список загруженных классов.
	 * @var array
	 */
	private static $_loaded = array();
	
	/**
	 * Список масок и путей для поиска классов.
	 * @var array
	 */
	private static $_patterns = array();
	
	/**
	 * Добавляет новое правило для поиска классов.
	 * @param string $mask
	 * @param string $path
	 * @param array $conversion
	 * @param callback apply
	 */
	private static function _AddPattern($mask, $path, $conversion = null, $apply = null)
	{
		$pattern = array(
			'mask' => $mask,
			'path' => $path,
			'conversion' => $conversion,
			'apply' => $apply,
		);

		self::$_patterns[] = $pattern;
	}
	
	/**
	 * Находит путь к скрипту с описанием класса.
	 * @param string $className
	 * @return string
	 */
	public static function FindPath($className)
	{
		$result = null;
		
		//По очереди проходим по всем паттернам:
		foreach (self::$_patterns as $pattern)
		{
			//Если маска пустая, или имя класса соответствует маске, подготавливаем путь:
			if (empty($pattern['mask']) || preg_match($pattern['mask'], $className))
			{
				$path = $pattern['path'];
				if ($pattern['conversion'])
				{
					//Выполняем необходимые преобразования над названием класса, чтобы получить имя файла:
					$path .= preg_replace($pattern['conversion'][0], $pattern['conversion'][1], $className);
				}
				
				if ($pattern['apply'])
				{
					//Применяем функцию к результату:
					$path = call_user_func($pattern['apply'], $path);
				}
				
				$result = $path;
				break;
			}
		}
		
		return $result;
	}
	
	/**
	 * Перечисление всех правил для поиска классов.
	 */
	public static function Init()
	{
		//KCAPTCHA:
		self::_AddPattern('#^KCAPTCHA$#', LIB_ROOT.'kcaptcha/kcaptcha.php');
		
		//PHPMailer:
		self::_AddPattern('#^PHPMailer$#', LIB_ROOT.'PHPMailer/class.phpmailer.php');
		
		//Smarty:
		self::_AddPattern('#^Smarty$#', LIB_ROOT.'smarty/libs/Smarty.class.php');
		
		//PHPUnit:
		self::_AddPattern('#^PHPUnit#', '/usr/share/php5/PHPUnit/Framework.php');
		
		//Конфиги:
		self::_AddPattern('#^Config_#', CONFIG_ROOT, array(array('#^Config_#', '#$#'), array('', '.php')));
		
		//Тесты:
		self::_AddPattern('#Test$#', UNIT_TESTS_ROOT, array(array('#_#', '#$#'), array('/', '.php')));
		
		//Вспомогательные классы для тестов:
		self::_AddPattern('#^TestHelper#', UNIT_TESTS_ROOT, array(array('#_#', '#$#'), array('/', '.php')));
		
		//Все остальные классы:
		self::_AddPattern('', ENGINE_ROOT, array(array('#_#', '#$#'), array('/', '.php')));
	}
	
	/**
	 * Подключает файл с описанием нужного класса.
	 * @param string $className имя класса для автозагрузки
	 */
	public static function Run($className)
	{
		//Ищем путь к описанию класса:
		$path = self::FindPath($className);
		
		include_once($path);
		self::$_loaded[$className] = $path;
	}
	
	/**
	 * Возвращает список загруженных в данный момент классов.
	 * @return array
	 */
	public static function GetLoadedClasses()
	{
		return self::$_loaded;
	} 
};

if (function_exists('spl_autoload_register'))
{
	spl_autoload_register(array('Autoload', 'Run'));
}
else
{
	function __autoload($className)
	{
		Autoload::Run($className);
	}
}

Autoload::Init();
