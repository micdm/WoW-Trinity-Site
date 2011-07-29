<?php

/**
 * Обработчик заголовка Content-Type.
 * @package Http_Header
 * @author Mic, 2010
 */
class Http_Header_ContentType
{
	/**
	 * Текущий тип.
	 * @var string
	 */
	private static $_type = 'text/html';

	/**
	 * Устанавливает новый тип документа.
	 * @param string $type
	 */
	public static function Set($type)
	{
		self::$_type = $type;
	}
	
	/**
	 * Возвращает текущий тип документа.
	 * @return string
	 */
	public static function Get()
	{
		return self::$_type;
	}
	
	/**
	 * Проверяет тип с текущим типом документа.
	 * @param string $type
	 * @return bool
	 */
	public static function Is($type)
	{
		return (self::$_type == $type);
	}
	
	/**
	 * Отправляет заголовок клиенту.
	 */
	public static function Send()
	{
		if (headers_sent() == false)
		{
			$header = 'Content-Type: '.self::$_type;
			
			//Для текстовых типов добавляем кодировку:
			if (strpos(self::$_type, 'text/') === 0)
			{
				$header .= '; charset=utf-8';
			}
	
			header($header, true);
		}
	}
};
