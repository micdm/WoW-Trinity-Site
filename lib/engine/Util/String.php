<?php

/**
 * Различные утилиты для работы со строками.
 * @author Mic, 2010
 */
class Util_String
{
	/**
	 * Проверяет, является ли строка корректным email-адресом.
	 * @param string $string
	 * @return boolean
	 */
	public static function IsEmail($string)
	{
		if (function_exists('filter_var'))
		{
			$result = (filter_var($string, FILTER_VALIDATE_EMAIL) !== false);
		}
		else
		{
			$result = (preg_match('#^.+@.+$#', $string) == 1);
		}
		
		return $result;
	}
	
	/**
	 * Используется в Util_String::GetNumber
	 * @param integer $value
	 * @param integer $number
	 * @return bool
	 */
	private static function _CheckNumber($value, $number)
	{
		return ($value % 10 == $number) && (($value - (10 + $number)) % 100 != 0);
	}	
	
	/**
	 * Возвращает подходящее число (единственное, двойственное, множественное).
	 * @param integer $value
	 * @param array $numbers
	 * @return string
	 */
	public static function GetNumber($value, $numbers, $format = '#1 #2')
	{
		if (self::_CheckNumber($value, 1))
		{
			$result = $numbers[0];
		}
		else if (self::_CheckNumber($value, 2) || self::_CheckNumber($value, 3) || self::_CheckNumber($value, 4))
		{
			$result = $numbers[1];
		}
		else
		{
			$result = $numbers[2];
		}
	
		return str_replace(array('#1', '#2'), array($value, $result), $format);
	}
	
	/**
	 * Объединяет несколько слешей в один.
	 * @params string $string
	 * @return string
	 */
	public static function CombineSlashes($string)
	{
		return preg_replace('#/+#', '/', $string);
	}
	
	/**
	 * Переводит строку в нижний регистр.
	 * @param string $string
	 * @return string
	 */
	public static function ToLower($string)
	{
		return mb_strtolower($string, 'utf-8');
	}
	
	/**
	 * Переводит строку в верхний регистр.
	 * @param string $string
	 * @return string
	 */
	public static function ToUpper($string)
	{
		return mb_strtoupper($string, 'utf-8');
	}
	
	/**
	 * Возвращает длину строки.
	 * @param string $string
	 * @return string
	 */
	public static function GetLength($string)
	{
		return mb_strlen($string, 'utf-8');
	}
	
	/**
	 * Возвращает подстроку.
	 * @param string $string
	 * @param integer $start
	 * @param integer $length
	 * @return string
	 */
	public static function GetSubstring($string, $start, $length = null)
	{
		if ($length == null)
		{
			$length = self::GetLength($string);
		}
		
		return mb_substr($string, $start, $length, 'utf-8');
	}
	
	/**
	 * Форматирует строку как предложение: делает первую букву заглавной, а в конце ставит точку.
	 * @param string $string
	 * @return string
	 */
	public static function FormatSentence($string)
	{
		return Util_String::ToUpper(Util_String::GetSubstring($string, 0, 1)).Util_String::GetSubstring($string, 1).'.';
	}
};
