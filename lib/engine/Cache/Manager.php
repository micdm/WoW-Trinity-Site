<?php

/**
 * Файловый кэш.
 * @author Mic, 2010
 */
class Cache_Manager
{
	/**
	 * Время жизни кэша по умолчанию.
	 * @var integer
	 */
	const DEFAULT_LIFETIME							= 30;
	
	/**
	 * Последний затребованный ключ.
	 * @var string
	 */
	protected static $_lastKey;
	
	/**
	 * Проверяет, испортился ли кэш.
	 * @param string $path
	 * @param integer $lifetime
	 * @return bool
	 */
	protected static function _IsExpired($path, $lifetime)
	{
		//Если кэш отключен, то и к файлам обращаться не нужно:
		if (Env::Get()->debug->IsCacheDisabled())
		{
			return true;
		}
		
		//Проверяем время последней модификации файла:
		$mtime = file_exists($path) ? filemtime($path) : false;
		return ($mtime === false || (time() - $mtime) > $lifetime);
	}
	
	/**
	 * Формирует путь к файлу по ключу кэша.
	 * @param string $key
	 * @return string
	 */
	protected static function _GetPath($key)
	{
		return CACHE_ROOT.$key;
	}
	
	/**
	 * Рекурсивно удаляет директорию.
	 * @param string $path
	 */
	protected static function _RemoveDirectory($path)
	{
		$dir = dir($path);
		while (($file = $dir->read()) !== false)
		{
			if ($file[0] === '.')
			{
				continue;
			}
			
			$file = $path.'/'.$file;
			if (is_dir($file))
			{
				self::_RemoveDirectory($file);
			}
			else
			{
				unlink($file);
			}
		}
		
		$dir->close();
		rmdir($path);
	}
	
	/**
	 * Загружает ресурс из кэша.
	 * @param string $key
	 * @param integer $lifetime
	 * @param bool $bodyOnly
	 * @return Cache_Result
	 */
	public function Load($key, $lifetime = null, $bodyOnly = true)
	{
		self::$_lastKey = $key;
		
		//Интервал по умолчанию:
		if ($lifetime === null)
		{
			$lifetime = self::DEFAULT_LIFETIME;
		}
		
		$path = self::_GetPath($key);
		
		//Если кэш не испортился, возвращаем значение:
		$result = null;
		if (self::_IsExpired($path, $lifetime) == false)
		{
			$result = Cache_Result::Factory(0, unserialize(file_get_contents($path)));
		}
		
		if ($bodyOnly && $result)
		{
			$result = $result->data;
		}

		return $result;
	}
	
	/**
	 * Помещает ресурс в кэш.
	 * @param string $key
	 * @param mixed $data
	 */
	public function Save($key, $data)
	{
		//Можно не указывать ключ для удобства:
		if ($key === null)
		{
			$key = self::$_lastKey;
		}
		
		//Ключ не должен быть пустой строкой (но при этом нулем он может быть):
		$key = strval($key);
		if ($key === '')
		{
			throw new Exception_Cache_EmptyKey();
		}

		$path = self::_GetPath($key);
		
		//Если директория не существует, создаем рекурсивно:
		if (file_exists(dirname($path)) == false)
		{
			mkdir(dirname($path), 0777, true);
		}
		
		//Сохраняем:
		file_put_contents($path, serialize($data));
		
		//Обнуляем последний ключ, чтоб не записать не в тот файл:
		self::$_lastKey = null;
	}
	
	/**
	 * Очищает указанный элемент кэша.
	 * @param string $key
	 */
	public function Clear($key)
	{
		$path = self::_GetPath($key);
		
		if (is_dir($path))
		{
			self::_RemoveDirectory($path);
		}
		else if (is_file($path))
		{
			unlink($path);
		}
	}
};
