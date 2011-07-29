<?php

/**
 * Перехват и обработка ошибок.
 * @author Mic, 2010
 */
class Error
{
	public static function Init()
	{
		error_reporting(E_ALL | E_STRICT);
		set_error_handler(array(__CLASS__, 'ErrorHandler'));
	}

	/**
	 * Собственный обработчик ошибок.
	 * @param integer $code
	 * @param string $message
	 * @param string $file
	 * @param integer $line
	 */
	public static function ErrorHandler($code, $message, $file, $line)
	{
		switch ($code)
		{
			case E_WARNING:
				$class = 'Exception_Php_Warning';
				break;
				
			case E_NOTICE:
				$class = 'Exception_Php_Notice';
				break;
				
			case E_USER_ERROR:
				$class = 'Exception_Php_User_Error';
				break;
				
			case E_USER_WARNING:
				$class = 'Exception_Php_User_Warning';
				break;
				
			case E_USER_NOTICE:
				$class = 'Exception_Php_User_Notice';
				break;
				
			case E_STRICT:
				$class = 'Exception_Php_Strict';
				break;
				
			case E_RECOVERABLE_ERROR:
				$class = 'Exception_Php_Recoverable';
				break;
				
			default:
				$class = 'Exception_Php_Base';
				break;
		}

		throw new $class($message);
	}
};
