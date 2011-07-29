<?php

/**
 * Обработка результатов работы аддона CharacterProfiler.
 * @package Third
 * @author Mic, 2010
 */
class Third_CharacterProfiler
{
	/**
	 * Сконвертированные данные.
	 * @var array
	 */
	protected static $_data;
	
	/**
	 * Конвертирует ключ массива.
	 * @param string $key
	 * @return string
	 */
	protected static function _GetArrayKey($key)
	{
		return trim($key, '[]"');
	}
	
	/**
	 * Конвертирует значение переменной.
	 * @param string $value
	 */
	protected static function _PrepareValue($value)
	{
		if ($value == 'true')
		{
			$value = true;
		}
		else if ($value == 'false')
		{
			$value = false;
		}
		else
		{
			$value = str_replace('<br>', ' ', $value);
			$value = trim($value, '",');
		}
		
		return $value;
	}
	
	/**
	 * Обрабатывает набор строк как массив.
	 * @param array $input
	 * @param integer $start
	 * @return array
	 */
	protected static function _Parse($input, &$start)
	{
		$result = array();
		for ($i = $start; $i < count($input); $i += 1)
		{
			//Разбиваем очередную строку на две части:
			$line = array_map('trim', explode('=', $input[$i], 2));
			if (isset($line[1]))
			{
				//Если есть правая часть, то это либо новый массив, либо присвоение значения:
				$key = self::_GetArrayKey($line[0]);
				if ($line[1] == '{')
				{
					//Новый массив:
					$i += 1;
					$result[$key] = self::_Parse($input, $i);
				}
				else
				{
					//Поле массива:
					$result[$key] = self::_PrepareValue($line[1]);
				}
			}
			else if ($line[0] == '{')
			{
				//Новый массив, но ключа нет:
				$i += 1;
				$result[] = self::_Parse($input, $i);
			}
			else if ($line[0] == '},' || $line[0] == '}')
			{
				//Пришли к концу массива:
				break;
			}
		}
		
		$start = $i;
		return $result;
	}
	
	/**
	 * Загружает данные из внешнего источника и конвертирует.
	 * @param string $string
	 */
	protected static function _Convert()
	{
		if (self::$_data !== null)
		{
			return;
		}
		
		$data = file_get_contents('/home/www/php/CharacterProfiler.lua');
		preg_match('#myProfile = {(.+)}#s', $data, $matches);

		//Убираем комментарии:
		$string = preg_replace('#-- .*#', '', $matches[1]);
		
		$input = array_map('trim', explode("\n", $string));
		$start = 0;
		self::$_data = self::_Parse($input, $start);
	}
	
	/**
	 * Находит в сконвертированных данных информацию о персонаже.
	 * @param string $name
	 * @return array;
	 */
	protected static function _GetCharacter($name)
	{
		self::_Convert();
		foreach (self::$_data as $realms)
		{
			foreach ($realms as $characters)
			{
				foreach ($characters as $key => $character)
				{
					if ($key == $name)
					{
						return $character;
					}
				}
			}
		}
	}
	
	/**
	 * Декодирует информацию про предмет.
	 * @param array $item
	 * @return array
	 */
	protected static function _GetItemInfo($item)
	{
		$info = explode(':', $item['Item']);
		return array(
			'entry' => $info[0],
			'count' => isset($item['Quantity']) ? $item['Quantity'] : 0,
		);
	}
	
	/**
	 * Возвращает уровень персонажа.
	 * @param string $name
	 * @return integer
	 */
	public static function GetLevel($name)
	{
		$character = self::_GetCharacter($name);
		return $character['Level'];
	}
	
	/**
	 * Возвращает идентификаторы всех предметов, которые есть у персонажа.
	 * @param string $name
	 * @return array
	 */
	public static function GetItems($name)
	{
		$character = self::_GetCharacter($name);

		//Проверяем, что вся информация собрана:
		foreach (array('Equipment', 'Inventory', 'Bank', 'MailBox') as $key)
		{
			if (empty($character[$key]))
			{
				throw new Exception_Third_CharacterProfiler_NotEnoughData();
			}
		}

		$result = array();
		
		//Надето:
		foreach ($character['Equipment'] as $item)
		{
			$result[] = self::_GetItemInfo($item);
		}
		
		//Лежит в сумках, и сами сумки:
		foreach (array('Inventory', 'Bank') as $store)
		{
			foreach ($character[$store] as $key => $bag)
			{
				//Нулевую сумку-рюкзак не добавляем:
				if ($key != 'Bag0')
				{
					$result[] = self::_GetItemInfo($bag);
				}
				
				foreach ($bag['Contents'] as $item)
				{
					$result[] = self::_GetItemInfo($item);
				}
			}
		}

		//Лежит в почте:
		foreach ($character['MailBox'] as $letter)
		{
			if (isset($letter['Contents']))
			{
				foreach ($letter['Contents'] as $item)
				{
					$result[] = self::_GetItemInfo($item);
				}
			}
		}

		return $result;
	}
	
	/**
	 * Возвращает количество меди у персонажа.
	 * @param string $name
	 * @return integer
	 */
	public static function GetMoney($name)
	{
		$character = self::_GetCharacter($name);
		return $character['Money']['Copper'] + $character['Money']['Silver'] * 100 + $character['Money']['Gold'] * 10000;
	}
};
