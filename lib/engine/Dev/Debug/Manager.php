<?php

/**
 * Вывод отладочной информации.
 * @author Mic, 2010
 */
class Dev_Debug_Manager
{
	/**
	 * Возвращает содержимое куки, по которой будет определяться состояние режима отладки.
	 * @return string
	 */
	public function GetCookieValue()
	{
		return md5($_SERVER['REMOTE_ADDR']);
	}
	
	/**
	 * Определяет, включен ли отладочный режим.
	 * @return boolean
	 */
	public function IsActive()
	{
		return isset($_COOKIE['debug']) && $_COOKIE['debug'] == $this->GetCookieValue();
	}
	
	/**
	 * Выключено ли кэширование?
	 * @return boolean
	 */
	public function IsCacheDisabled()
	{
		return isset($_COOKIE['no_cache']) && $_COOKIE['no_cache'] == $this->GetCookieValue();
	}
	
	/**
	 * Определяет, выполняется ли в данный момент юнит-тестирование.
	 * @return boolean
	 */
	public function IsTesting()
	{
		return defined('IS_DEV');
	}
	
	/**
	 * Работаем ли локально?
	 * @return boolean
	 */
	public function IsLocal()
	{
		return $_SERVER['REMOTE_ADDR'] == '127.0.0.1';
	}
	
	/**
	 * Выводит в поток всю доступную отладочную информацию.
	 */
	public function ShowInfo()
	{
		$chunks = array();
		$chunks[] = self::_GetAutoloadInfo();
		//$chunks[] = self::_GetDbInfo();
		$chunks[] = Dev_Debug_Section::GetDebugInfo();
		$chunks[] = self::_GetMemoryInfo();
		
		$output = implode(PHP_EOL.PHP_EOL, $chunks);
		if (Http_Header_ContentType::Is('text/html'))
		{
			$output = '<div id="debug"><pre>'.$output.'</pre></div>';
		}
		
		print($output);
	}
	
	/**
	 * Возвращает статистику по автозагрузке.
	 * @return string
	 */
	private static function _GetAutoloadInfo()
	{
		//Список классов:
		$classes = array_keys(Autoload::GetLoadedClasses());
		return 'Автозагрузка ('.sizeof($classes).'):'.PHP_EOL.implode(PHP_EOL, $classes);
	}

	/**
	 * Возвращает статистику по использованию БД.
	 * @return string
	 */
	private static function _GetDbInfo()
	{
		$info = Env::Get()->db->GetDebugInfo();
		if ($info)
		{
			$chunks = array();
			foreach ($info as $name => $db)
			{
				//Название БД:
				$chunks[] = $name.':';
				
				//Извлекаем текст запросов:
				foreach ($db as $query)
				{
					$chunks[] = $query['sql'];
				}
				
				$chunks[] = PHP_EOL;
			}
	
			$result = implode(PHP_EOL.PHP_EOL, $chunks);
		}
		else
		{
			$result = '*пусто*';
		}
		
		return 'Использование БД:'.PHP_EOL.$result;
	}

	private static function _GetMemoryInfo()
	{
		return 'Использование памяти в пике:'.PHP_EOL.number_format(memory_get_peak_usage()/1024/1024, 3).'M';
	}
};
