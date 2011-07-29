<?php

/**
 * Конвертер данных в различные кодировки.
 * @author Mic, 2010
 */
class Util_Charset
{
	const CHARSET_CP1251							= 'CP1251';
	const CHARSET_UTF8								= 'UTF8';
	
	/**
	 * Конвертирует строку.
	 * @param string $string
	 * @param string $from
	 * @param string $to
	 * @return string
	 */
	public static function Convert($string, $from, $to)
	{
		return iconv($from, $to, $string);
	}
};
