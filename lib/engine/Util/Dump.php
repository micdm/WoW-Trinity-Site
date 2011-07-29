<?php

/**
 * Выдает пользователю читаемую переменную.
 * @author Mic, 2010
 */
class Util_Dump
{
	/**
	 * Красиво распечатывает переменную.
	 * @param mixed $data
	 * @param bool $needReturn
	 * @return string
	 */
	public static function Dump($data, $needReturn = false)
	{
		if (is_array($data) || is_object($data))
		{
			//Массивы и объекты выводим по-особенному:
			$body = print_r($data, true);
		}
		else
		{
			ob_start();
			var_dump($data);
			$body = ob_get_clean();
		}
		
		if (Http_Header_ContentType::Is('text/plain') == false)
		{
			$body = '<pre>'.$body.'</pre>';			
		}
		
		if ($needReturn)
		{
			return $body;
		}
		else
		{
			print($body);
		}
	}
};
